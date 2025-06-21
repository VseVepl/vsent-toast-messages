<?php

declare(strict_types=1);

namespace Vsent\LaravelToastify; // Updated Namespace

use DateTimeImmutable;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Vsent\LaravelToastify\Contracts\ToastManagerContract; // Updated Namespace
use Vsent\LaravelToastify\DTOs\ToastMessageDTO;      // Updated Namespace
use Vsent\LaravelToastify\Events\ToastCreated;        // Updated Namespace

class ToastManager implements ToastManagerContract
{
    protected string $sessionKey;
    protected array $configData = [];
    protected array $validPriorities = [];
    protected string $soundBasePath = ''; // This will be relative to public_path('vendor/vsent/laravel-toastify')

    public function __construct(
        protected Session $session,
        protected ConfigRepository $config,
        protected EventDispatcher $events,
        protected LogManager $log
    ) {
        $this->loadConfiguration();
    }

    protected function loadConfiguration(): void
    {
        $this->configData = $this->config->get('toasts', []);

        $requiredSections = ['animations', 'behavior', 'close_button', 'display', 'progress_bar', 'queue', 'sounds', 'types'];
        foreach ($requiredSections as $section) {
            if (!isset($this->configData[$section]) || !is_array($this->configData[$section])) {
                $this->log->error(sprintf('Laravel Toastify: Configuration section "%s" is missing or invalid.', $section));
                $this->configData[$section] = []; // Provide minimal fallback
            }
        }
        if (empty($this->configData['types']) || empty($this->configData['display'])) {
            throw new \RuntimeException('Laravel Toastify: Essential configuration (types or display) is missing. Please publish and review config/toasts.php.');
        }

        $this->sessionKey = $this->configData['session_key'] ?? 'laravel_toastify_messages'; // Package specific key

        if (!empty($this->configData['queue']['priority']['enabled']) && !empty($this->configData['queue']['priority']['levels'])) {
            $this->validPriorities = array_keys($this->configData['queue']['priority']['levels']);
        } else {
            $this->validPriorities = ['high', 'normal', 'low'];
            $this->log->warning('Laravel Toastify: Priority levels not configured or priority disabled. Defaulting to high, normal, low.');
        }
        // Base path for sounds, relative to the public vendor directory for the package
        // e.g., if sounds are in public/vendor/vsent/laravel-toastify/sounds/
        // then base_path in config might be 'sounds/' or empty if src includes full path from vendor dir.
        // Let's make it `vendor/vsent/laravel-toastify/sounds` which is then prefixed with `/` for web.
        $this->soundBasePath = 'vendor/vsent/laravel-toastify/' . trim($this->getConfig('sounds.global.base_path', 'sounds'), '/');
    }

    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->configData, $key, $default);
    }

    public function add(
        string $type,
        string $message,
        ?string $title = null,
        ?int $duration = null,
        ?string $priority = null,
        ?bool $autoDismiss = null,
        ?bool $pauseOnHover = null,
        ?bool $showProgressBar = null,
        ?string $animationPreset = null,
        ?string $layoutPreset = null,
        ?string $soundAsset = null,
        array $actions = [],
        array $customData = []
    ): ToastMessageDTO {
        $type = strtolower($type);
        $typeDefaults = $this->getConfig('types.defaults', []);
        $typeConfig = $this->getConfig('types.' . $type, []);
        if (!is_array($typeConfig)) {
            $this->log->warning(sprintf('Laravel Toastify: Toast type "%s" not found. Applying defaults.', $type));
            $typeConfig = [];
        }

        $effectivePriority = $priority ?? $typeConfig['priority'] ?? $typeDefaults['priority'] ?? 'low';
        if (!in_array($effectivePriority, $this->validPriorities)) {
            $this->log->warning(sprintf('Laravel Toastify: Invalid priority "%s" for type "%s". Defaulting to "low".', $effectivePriority, $type));
            $effectivePriority = 'low';
        }

        $effectiveDuration = $duration ?? $typeConfig['duration'] ?? $typeDefaults['duration'] ?? $this->getConfig('display.default_duration', 5000);
        $effectiveDuration = max(0, (int) $effectiveDuration);
        $effectiveAutoDismiss = $autoDismiss ?? $typeConfig['dismissible'] ?? $typeDefaults['dismissible'] ?? $this->getConfig('behavior.auto_dismiss', true);
        $effectivePauseOnHover = $pauseOnHover ?? $this->getConfig('behavior.pause_on_hover', true);
        $effectiveShowProgressBar = $showProgressBar ?? $typeConfig['show_progress'] ?? $typeDefaults['show_progress'] ?? $this->getConfig('progress_bar.enabled', true);
        $effectiveBgColor = $typeConfig['bg'] ?? $typeDefaults['bg'] ?? 'bg-gray-800';
        $effectiveTextColor = $typeConfig['text_color'] ?? $typeDefaults['text_color'] ?? 'text-white';
        $effectiveIcon = $typeConfig['icon'] ?? $typeDefaults['icon'] ?? '';
        $effectiveAriaRole = $typeConfig['aria_role'] ?? $typeDefaults['aria_role'] ?? $this->getConfig('behavior.aria_live_region', 'status');
        $effectivePosition = $typeConfig['position'] ?? $typeDefaults['position'] ?? $this->getConfig('display.position', 'top-right');
        $effAnimationPresetName = $animationPreset ?? $typeConfig['animation_preset'] ?? $typeDefaults['animation_preset'] ?? $this->getConfig('animations.preset', 'fade');
        $resolvedAnimation = $this->resolveAnimationPreset($effAnimationPresetName);
        $effLayoutPresetName = $layoutPreset ?? $typeConfig['layout_preset'] ?? $typeDefaults['layout_preset'] ?? 'default';
        $resolvedLayoutClasses = $this->resolveLayoutPreset($effLayoutPresetName);
        $effectiveCloseButtonEnabled = $typeConfig['close_button'] ?? $typeDefaults['close_button'] ?? $this->getConfig('close_button.enabled', true);

        $webSoundPath = null;
        $effectiveSoundVolume = null;
        $effectiveSoundLoop = null;
        if ($this->getConfig('sounds.global.enabled', false)) {
            $soundTypeConfig = $typeConfig['sound'] ?? $typeDefaults['sound'] ?? [];
            $soundMapAssetKey = $this->getConfig('sounds.types.' . $type);
            $finalSoundAssetKey = $soundAsset ?? $soundMapAssetKey ?? $this->getConfig('sounds.global.default_sound');
            if ($finalSoundAssetKey) {
                $soundAssetConfig = $this->getConfig('sounds.assets.' . $finalSoundAssetKey);
                if ($soundAssetConfig && isset($soundAssetConfig['src'])) {
                    $webSoundPath = '/' . trim($this->soundBasePath . '/' . $soundAssetConfig['src'], '/');
                    $effectiveSoundVolume = $soundTypeConfig['volume'] ?? $soundAssetConfig['volume'] ?? $this->getConfig('sounds.global.default_volume', 1.0);
                    $effectiveSoundLoop = $soundTypeConfig['loop'] ?? $soundAssetConfig['loop'] ?? $this->getConfig('sounds.global.default_loop', false);
                } else {
                    $this->log->warning(sprintf('Laravel Toastify: Sound asset key "%s" for type "%s" not found.', $finalSoundAssetKey, $type));
                }
            }
        }
        $effectiveActions = $actions ?: ($typeConfig['actions'] ?? $typeDefaults['actions'] ?? []);

        $toasts = $this->getAllToastsFromSession();
        if ($this->getConfig('behavior.duplicate_detection.enabled', false)) {
            $threshold = $this->getConfig('behavior.duplicate_detection.duration_threshold', 1000);
            $now = new DateTimeImmutable();
            foreach ($toasts as $existingToast) {
                if ($existingToast->type === $type && $existingToast->message === $message) {
                    $diffMillis = ($now->getTimestamp() * 1000 + (int)($now->format('u') / 1000)) -
                                  ($existingToast->createdAt->getTimestamp() * 1000 + (int)($existingToast->createdAt->format('u') / 1000));
                    if ($diffMillis <= $threshold) {
                        $this->log->info(sprintf('Laravel Toastify: Duplicate toast skipped (type: %s).', $type));
                        return $existingToast;
                    }
                }
            }
        }

        $toast = new ToastMessageDTO(
            id: (string) Str::uuid(), type: $type, message: $message, title: $title, priority: $effectivePriority,
            duration: $effectiveDuration, position: $effectivePosition, bgColor: $effectiveBgColor, textColor: $effectiveTextColor,
            icon: $effectiveIcon, sound: $webSoundPath, autoDismiss: $effectiveAutoDismiss, pauseOnHover: $effectivePauseOnHover,
            showProgressBar: $effectiveShowProgressBar, createdAt: new DateTimeImmutable(), dismissed: false,
            animation: $resolvedAnimation, layout: $resolvedLayoutClasses, closeButtonEnabled: $effectiveCloseButtonEnabled,
            soundVolume: $effectiveSoundVolume, soundLoop: $effectiveSoundLoop, ariaRole: $effectiveAriaRole,
            actions: $effectiveActions, customData: $customData
        );

        $toasts->prepend($toast);
        $this->updateToastsInSession($toasts);
        $this->events->dispatch(new ToastCreated($toast));
        return $toast;
    }

    protected function resolveAnimationPreset(string $presetName): array { /* ... content from previous ToastManager ... */
        $preset = $this->getConfig('animations.presets.' . $presetName);
        if (!$preset || !is_array($preset)) {
            $this->log->warning(sprintf('Laravel Toastify: Animation preset "%s" not found. Falling back to "fade".', $presetName));
            $preset = $this->getConfig('animations.presets.fade', []);
        }
        $globalAnimation = $this->getConfig('animations.global', []);
        return array_merge([
            'enter_duration' => $globalAnimation['default_enter_duration'] ?? 300,
            'enter_easing' => $globalAnimation['default_enter_easing'] ?? 'ease-out',
            'enter_transition_classes' => $globalAnimation['default_transition_classes'] ?? 'transition',
            'leave_duration' => $globalAnimation['default_leave_duration'] ?? 200,
            'leave_easing' => $globalAnimation['default_leave_easing'] ?? 'ease-in',
            'leave_transition_classes' => $globalAnimation['default_transition_classes'] ?? 'transition',
            'delay' => 0,
            'hooks' => [],
        ], $preset);
    }
    protected function resolveLayoutPreset(string $presetName): array { /* ... content from previous ToastManager ... */
        $layout = $this->getConfig('types.layouts.' . $presetName);
        if (!$layout || !is_array($layout)) {
            $this->log->warning(sprintf('Laravel Toastify: Layout preset "%s" not found. Falling back to "default".', $presetName));
            $layout = $this->getConfig('types.layouts.default', []);
        }
        return $layout;
    }
    protected function getAllToastsFromSession(): Collection { /* ... content from previous ToastManager, ensure DTO namespace is Vsent\LaravelToastify\DTOs ... */
        $toastsData = $this->session->get($this->sessionKey, []);
        $toasts = new Collection();
        foreach ($toastsData as $toastDatum) {
            if (is_array($toastDatum)) {
                try {
                    $toasts->push(ToastMessageDTO::fromArray($toastDatum));
                } catch (InvalidArgumentException $e) {
                    $this->log->error(sprintf('Laravel Toastify: Error reconstructing DTO: %s. Data: %s', $e->getMessage(), json_encode($toastDatum)));
                }
            }
        }
        return $toasts;
    }
    protected function updateToastsInSession(Collection $toasts): void { /* ... content from previous ToastManager ... */
        $currentTime = new DateTimeImmutable();
        $queueLifetime = $this->getConfig('queue.lifetime');

        $cleanedToasts = $toasts->filter(function (ToastMessageDTO $toast) use ($currentTime, $queueLifetime) {
            if ($toast->dismissed) return false;
            if ($toast->isExpired($currentTime)) return false;
            if ($queueLifetime > 0) {
                $toastAgeMillis = ($currentTime->getTimestamp() * 1000 + (int)($currentTime->format('u')/1000)) -
                                  ($toast->createdAt->getTimestamp() * 1000 + (int)($toast->createdAt->format('u')/1000));
                if ($toastAgeMillis > $queueLifetime) return false;
            }
            return true;
        });
        $this->session->put($this->sessionKey, $cleanedToasts->map->jsonSerialize()->values()->toArray());
    }
    public function success(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO { return $this->add('success', $message, $title, $duration); }
    public function error(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO { return $this->add('error', $message, $title, $duration); }
    public function warning(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO { return $this->add('warning', $message, $title, $duration); }
    public function info(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO { return $this->add('info', $message, $title, $duration); }
    public function custom(string $message, ?string $title = null, array $options = []): ToastMessageDTO { /* ... content from previous ToastManager ... */
        $type = $options['type'] ?? 'default';
        unset($options['type']);
        return $this->add(
            type: $type, message: $message, title: $title,
            duration: $options['duration'] ?? null, priority: $options['priority'] ?? null,
            autoDismiss: $options['autoDismiss'] ?? null, pauseOnHover: $options['pauseOnHover'] ?? null,
            showProgressBar: $options['showProgressBar'] ?? null, animationPreset: $options['animationPreset'] ?? null,
            layoutPreset: $options['layoutPreset'] ?? null, soundAsset: $options['soundAsset'] ?? null,
            actions: $options['actions'] ?? [], customData: $options['customData'] ?? $options
        );
    }
    public function get(): Collection { /* ... content from previous ToastManager, ensure DTO namespace is Vsent\LaravelToastify\DTOs ... */
        $activeToasts = $this->getAllToastsFromSession()->filter(
            fn(ToastMessageDTO $toast) => !$toast->dismissed && !$toast->isExpired(new DateTimeImmutable())
        );

        $priorityEnabled = $this->getConfig('queue.priority.enabled', false);
        $globalMaxToasts = $this->getConfig('queue.max_toasts', 5);
        $perTypeLimitConfig = $this->getConfig('queue.per_type_limit');
        $perTypeLimit = is_numeric($perTypeLimitConfig) && $perTypeLimitConfig > 0 ? (int) $perTypeLimitConfig : 0;


        $displayedToasts = new Collection();
        $currentGlobalCount = 0;
        $typeCounts = [];

        $sortedToasts = $activeToasts->sortBy(function (ToastMessageDTO $toast) {
            $priorityOrder = array_search($toast->priority, $this->validPriorities);
            return ($priorityOrder === false ? count($this->validPriorities) : $priorityOrder) . '_' . $toast->createdAt->getTimestamp();
        })->values();

        foreach ($sortedToasts as $toast) {
            if ($currentGlobalCount >= $globalMaxToasts) break;
            if ($priorityEnabled) {
                $priorityLevelConfig = $this->getConfig('queue.priority.levels.' . $toast->priority);
                if ($priorityLevelConfig && isset($priorityLevelConfig['limit'])) {
                    if ($displayedToasts->where('priority', $toast->priority)->count() >= $priorityLevelConfig['limit']) continue;
                }
            }
            if ($perTypeLimit > 0) {
                $typeCounts[$toast->type] = $typeCounts[$toast->type] ?? 0;
                if ($typeCounts[$toast->type] >= $perTypeLimit) continue;
                $typeCounts[$toast->type]++;
            }
            $displayedToasts->push($toast);
            $currentGlobalCount++;
        }

        if ($this->getConfig('behavior.reverse_order_on_stack', false)) {
            return $displayedToasts->sortByDesc(fn(ToastMessageDTO $t) => $t->createdAt->getTimestamp())->values();
        }
        return $displayedToasts->sortBy(fn(ToastMessageDTO $t) => $t->createdAt->getTimestamp())->values();
    }
    public function clear(): void { $this->session->forget($this->sessionKey); }
    public function dismiss(string $id): void { /* ... content from previous ToastManager ... */
        $toasts = $this->getAllToastsFromSession();
        $updatedToasts = $toasts->map(function (ToastMessageDTO $toast) use ($id) {
            if ($toast->id === $id) return $toast->markAsDismissed();
            return $toast;
        });
        $this->updateToastsInSession($updatedToasts);
    }
    public function hasToasts(): bool { return $this->get()->isNotEmpty(); }
}
