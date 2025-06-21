<?php

declare(strict_types=1);

namespace Vsent\ToastMessages;

use DateTimeImmutable;
use Illuminate\Config\Repository as Config;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Log\LoggerInterface; // To use a logger instead of error_log directly
use Vsent\ToastMessages\Contracts\ToastManagerContract;
use Vsent\ToastMessages\DTOs\ToastMessageDTO;
use Vsent\ToastMessages\Events\ToastCreated;
use Illuminate\Support\Facades\Log;

/**
 * Class ToastManager
 *
 * @package VsE\ToastMessages
 *
 * This class implements the ToastManagerContract and provides the core logic
 * for managing toast notifications within a Laravel application. It handles
 * adding, retrieving, dismissing, and clearing toasts, applying global and
 * priority-based display limits, and persisting toasts in the session.
 *
 * It is designed to be highly configurable via the 'toasts.php' configuration file,
 * supporting various animation presets, sound effects, and complex priority-based
 * queuing and display rules.
 */
class ToastManager implements ToastManagerContract
{
    /**
     * The key used to store toasts in the session.
     *
     * @var string
     */
    protected string $sessionKey;

    /**
     * Stores the entire loaded configuration for toasts.
     *
     * @var array<string, mixed>
     */
    protected array $configData = [];

    /**
     * Valid priority levels derived from config.
     *
     * @var array<string>
     */
    protected array $validPriorities = [];

    /**
     * Constructor for the ToastManager.
     *
     * @param SessionStore    $session The Laravel session store instance.
     * @param Config          $config The Laravel config repository instance.
     * @param EventDispatcher $events The Laravel event dispatcher instance.
     * @param LoggerInterface $logger The PSR-3 logger instance for error logging.
     */
    public function __construct(
        protected SessionStore $session,
        protected Config $config,
        protected EventDispatcher $events,
        protected LoggerInterface $logger
    ) {
        $this->loadConfiguration();
    }

    /**
     * Loads and validates the toast configuration from 'config/toasts.php'.
     *
     * This method centralizes configuration loading and provides defensive checks
     * against missing or invalid configuration values, preventing potential runtime errors.
     * It parses the new, more complex nested structure.
     *
     * @throws \RuntimeException If essential configuration keys are missing or invalid.
     */
    protected function loadConfiguration(): void
    {
        $this->configData = $this->config->get('toasts', []);

        // Validate essential top-level configuration keys
        $requiredSections = ['animations', 'behavior', 'close_button', 'display', 'progress_bar', 'queue', 'sounds', 'types'];
        foreach ($requiredSections as $section) {
            if (!isset($this->configData[$section]) || !is_array($this->configData[$section])) {
                throw new \RuntimeException(sprintf('Toast configuration section "%s" is missing or invalid in config/toasts.php.', $section));
            }
        }

        $this->sessionKey = $this->configData['session_key'] ?? 'toasts';

        // Initialize valid priorities based on the 'queue.priority.levels'
        if (isset($this->configData['queue']['priority']['enabled']) && $this->configData['queue']['priority']['enabled']) {
            foreach (($this->configData['queue']['priority']['levels'] ?? []) as $levelName => $levelConfig) {
                if (is_array($levelConfig)) {
                    $this->validPriorities[] = $levelName;
                }
            }
        }
        // Fallback if priority is disabled or levels are not configured
        if (empty($this->validPriorities)) {
            $this->validPriorities = ['high', 'normal', 'low'];
            $this->logger->warning('Toast priority levels are not configured or priority is disabled. Defaulting to high, normal, low.');
        }
    }

    /**
     * Retrieves a configuration value safely, with support for dot notation.
     *
     * @param string $key The configuration key (e.g., 'display.default_duration').
     * @param mixed|null $default The default value to return if the key is not found.
     * @return mixed
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->configData, $key, $default);
    }

    /**
     * Adds a new toast message to the queue.
     *
     * This method is highly flexible, allowing for extensive customization based on
     * the new 'toasts.php' configuration. It prioritizes explicit parameters,
     * then type-specific configuration, then global defaults.
     *
     * @param string      $type            The type of the toast (e.g., 'success', 'error', 'warning', 'info', 'custom').
     * @param string      $message         The main content of the toast message.
     * @param string|null $title           Optional title for the toast.
     * @param int|null    $duration        Optional custom duration for this toast in milliseconds.
     * @param string|null $priority        Optional priority for this toast ('high', 'normal', 'low').
     * @param bool|null   $autoDismiss     Optional override for auto-dismiss behavior.
     * @param bool|null   $pauseOnHover    Optional override for pause-on-hover behavior.
     * @param bool|null   $showProgressBar Optional override for progress bar visibility.
     * @param string|null $animationPreset Optional animation preset to use.
     * @param string|null $layoutPreset    Optional layout preset to use.
     * @param string|null $soundAsset      Optional sound asset name to use (e.g., 'success', 'error').
     * @param array       $actions         Optional array of actions for the toast.
     * @param array       $customData      Optional custom data to attach to the toast.
     *
     * @return ToastMessageDTO The DTO of the newly created toast message.
     * @throws InvalidArgumentException If essential configuration for a type is missing.
     */
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

        // Normalize type to lowercase for consistent config lookup
        $type = strtolower($type);

        // Get default and type-specific configurations
        $typeDefaults = $this->getConfig('types.defaults', []);
        $typeConfig = $this->getConfig('types.' . $type, []);

        // --- Determine effective values for DTO properties ---

        // Basic Properties
        $effectiveDuration = $duration ?? $typeConfig['duration'] ?? $typeDefaults['duration'] ?? $this->getConfig('display.default_duration', 5000);
        $effectiveDuration = max(0, $effectiveDuration); // Ensure non-negative

        $effectivePriority = $priority ?? $typeConfig['priority'] ?? $typeDefaults['priority'] ?? 'low';
        // Validate effective priority against allowed levels
        if (!in_array($effectivePriority, $this->validPriorities)) {
            $this->logger->warning(sprintf('Invalid toast priority "%s" for type "%s". Defaulting to "low".', $effectivePriority, $type));
            $effectivePriority = 'low';
        }

        $effectiveAutoDismiss = $autoDismiss ?? $typeConfig['dismissible'] ?? $typeDefaults['dismissible'] ?? $this->getConfig('behavior.auto_dismiss', true);
        $effectivePauseOnHover = $pauseOnHover ?? $this->getConfig('behavior.pause_on_hover', true); // No type-specific override in config for this
        $effectiveShowProgressBar = $showProgressBar ?? $typeConfig['show_progress'] ?? $typeDefaults['show_progress'] ?? $this->getConfig('progress_bar.enabled', true);

        // Appearance Properties
        $effectiveBgColor = $typeConfig['bg'] ?? $typeDefaults['bg'] ?? 'bg-gray-800';
        $effectiveTextColor = $typeConfig['text_color'] ?? $typeDefaults['text_color'] ?? 'text-white';
        $effectiveIcon = $typeConfig['icon'] ?? $typeDefaults['icon'] ?? '';
        $effectiveAriaRole = $typeConfig['aria_role'] ?? $typeDefaults['aria_role'] ?? $this->getConfig('behavior.aria_live_region', 'status');

        // Animation
        $effectiveAnimationPreset = $animationPreset ?? $typeConfig['animation_preset'] ?? $typeDefaults['animation_preset'] ?? $this->getConfig('animations.preset', 'fade');
        $resolvedAnimation = $this->resolveAnimationPreset($effectiveAnimationPreset);

        // Layout
        $effectiveLayoutPreset = $layoutPreset ?? $typeConfig['layout_preset'] ?? $typeDefaults['layout_preset'] ?? 'default';
        $resolvedLayoutClasses = $this->resolveLayoutPreset($effectiveLayoutPreset);

        // Position
        $effectivePosition = $typeConfig['position'] ?? $typeDefaults['position'] ?? $this->getConfig('display.position', 'top-right');
        // Mobile position logic should be handled by the frontend Livewire component based on responsive classes.

        // Close button
        $effectiveCloseButton = $typeConfig['close_button'] ?? $typeDefaults['close_button'] ?? $this->getConfig('close_button.enabled', true);

        // Sound
        $effectiveSoundSrc = null;
        $effectiveSoundVolume = null;
        $effectiveSoundLoop = null;

        // Check if sounds are globally enabled first
        if ($this->getConfig('sounds.global.enabled', false)) {
            $soundTypeConfig = $typeConfig['sound'] ?? $typeDefaults['sound'] ?? [];
            $soundMapAsset = $this->getConfig('sounds.types.' . $type); // Get asset name from type mapping

            // Determine which sound asset to use: explicit override > type mapped asset > global default
            $finalSoundAsset = $soundAsset ?? $soundMapAsset ?? $this->getConfig('sounds.global.default_sound');

            if ($finalSoundAsset) {
                // If a sound asset is defined, try to resolve its full path and properties
                $soundAssetConfig = $this->getConfig('sounds.assets.' . $finalSoundAsset);
                if ($soundAssetConfig && isset($soundAssetConfig['src'])) {
                    $effectiveSoundSrc = $this->getConfig('sounds.global.base_path') . $soundAssetConfig['src'];
                    Log::debug('Toast sound debug', [
                        'base_path' => $this->getConfig('sounds.global.base_path'),
                        'src' => $soundAssetConfig['src'] ?? null,
                        'effectiveSoundSrc' => ($this->getConfig('sounds.global.base_path') ?? '') . ($soundAssetConfig['src'] ?? '')
                    ]);

                    $effectiveSoundVolume = $soundTypeConfig['volume'] ?? $soundAssetConfig['volume'] ?? $this->getConfig('sounds.global.default_volume', 1.0);
                    $effectiveSoundLoop = $soundTypeConfig['loop'] ?? $soundAssetConfig['loop'] ?? $this->getConfig('sounds.global.default_loop', false);
                } else {
                    $this->logger->warning(sprintf('Toast sound asset "%s" is configured for type "%s" but not found in sounds.assets.', $finalSoundAsset, $type));
                }
            }
        }

        // Actions
        $effectiveActions = $actions ?? $typeConfig['actions'] ?? [];

        // Duplicate Detection
        $toasts = $this->getAllToastsFromSession();
        $duplicateDetectionEnabled = $this->getConfig('behavior.duplicate_detection.enabled', false);
        $duplicateDurationThreshold = $this->getConfig('behavior.duplicate_detection.duration_threshold', 1000); // ms

        if ($duplicateDetectionEnabled) {
            $currentTime = new DateTimeImmutable();
            foreach ($toasts as $existingToast) {
                // Consider a toast a duplicate if message and type match within the threshold
                if ($existingToast->type === $type && $existingToast->message === $message) {
                    $interval = $existingToast->createdAt->diff($currentTime);
                    $milliseconds = ($interval->days * 24 * 60 * 60 * 1000) +
                        ($interval->h * 60 * 60 * 1000) +
                        ($interval->i * 60 * 1000) +
                        ($interval->s * 1000) +
                        round($interval->f * 1000); // Microseconds to milliseconds

                    if ($milliseconds <= $duplicateDurationThreshold) {
                        $this->logger->info(sprintf('Skipping duplicate toast creation for type "%s" with message "%s".', $type, $message));
                        return $existingToast; // Return existing toast or null, depending on desired behavior
                    }
                }
            }
        }


        // Construct the DTO
        $toast = new ToastMessageDTO(
            id: (string) Str::uuid(),
            type: $type,
            message: $message,
            title: $title,
            priority: $effectivePriority,
            duration: $effectiveDuration,
            position: $effectivePosition,
            bgColor: $effectiveBgColor,
            textColor: $effectiveTextColor,
            icon: $effectiveIcon,
            sound: $effectiveSoundSrc, // Full path for sound
            autoDismiss: $effectiveAutoDismiss,
            pauseOnHover: $effectivePauseOnHover,
            showProgressBar: $effectiveShowProgressBar,
            createdAt: new DateTimeImmutable(),
            dismissed: false,
            // New fields from the expanded config
            animation: $resolvedAnimation,
            layout: $resolvedLayoutClasses,
            closeButtonEnabled: $effectiveCloseButton,
            soundVolume: $effectiveSoundVolume,
            soundLoop: $effectiveSoundLoop,
            ariaRole: $effectiveAriaRole,
            actions: $effectiveActions,
            customData: $customData
        );

        // Add the new toast to the collection and store in session
        $toasts->prepend($toast); // Prepend to maintain "newest first" in session initially
        $this->updateToastsInSession($toasts);

        // Dispatch event
        $this->events->dispatch(new ToastCreated($toast));

        return $toast;
    }

    /**
     * Resolves an animation preset from the configuration.
     *
     * @param string $presetName The name of the animation preset (e.g., 'slide_from_bottom').
     * @return array<string, mixed> The resolved animation configuration.
     */
    protected function resolveAnimationPreset(string $presetName): array
    {
        $preset = $this->getConfig('animations.presets.' . $presetName);
        if (!$preset || !is_array($preset)) {
            $this->logger->warning(sprintf('Animation preset "%s" not found. Falling back to "fade".', $presetName));
            $preset = $this->getConfig('animations.presets.fade', []); // Fallback to 'fade'
        }

        // Merge with global animation defaults
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

    /**
     * Resolves a layout preset from the configuration.
     *
     * @param string $presetName The name of the layout preset (e.g., 'default').
     * @return array<string, string> The resolved layout classes.
     */
    protected function resolveLayoutPreset(string $presetName): array
    {
        $layout = $this->getConfig('types.layouts.' . $presetName);
        if (!$layout || !is_array($layout)) {
            $this->logger->warning(sprintf('Layout preset "%s" not found. Falling back to "default".', $presetName));
            $layout = $this->getConfig('types.layouts.default', []); // Fallback to 'default'
        }
        return $layout;
    }


    /**
     * Retrieves all toast DTOs currently stored in the session.
     *
     * This method safely retrieves and deserializes toast data, handling cases
     * where session data might be missing or malformed.
     *
     * @return Collection<int, ToastMessageDTO> A collection of all raw toast DTOs.
     */
    protected function getAllToastsFromSession(): Collection
    {
        $toastsData = $this->session->get($this->sessionKey, []);
        $toasts = new Collection();

        // Ensure each item is an array before attempting to reconstruct DTO
        foreach ($toastsData as $toastDatum) {
            if (is_array($toastDatum)) {
                try {
                    $toasts->push(ToastMessageDTO::fromArray($toastDatum));
                } catch (InvalidArgumentException $e) {
                    $this->logger->error(sprintf('Error reconstructing ToastMessageDTO from session: %s', $e->getMessage()));
                }
            }
        }

        return $toasts;
    }

    /**
     * Stores the given collection of ToastMessageDTOs back into the session.
     *
     * This method serializes the DTOs for session storage.
     *
     * @param Collection<int, ToastMessageDTO> $toasts The collection of toast DTOs to store.
     * @return void
     */
    protected function updateToastsInSession(Collection $toasts): void
    {
        // Filter out toasts that are truly expired or dismissed for session cleanup
        $currentTime = new DateTimeImmutable();
        $cleanedToasts = $toasts->filter(function (ToastMessageDTO $toast) use ($currentTime) {
            // Keep toasts that are not dismissed OR are not yet expired
            // This ensures dismissed toasts are removed from session, and truly expired ones are too.
            return !$toast->dismissed && !$toast->isExpired($currentTime);
        });

        // Apply queue lifetime if enabled and a lifetime is set
        $queueLifetime = $this->getConfig('queue.lifetime');
        if ($queueLifetime > 0) {
            $cleanedToasts = $cleanedToasts->filter(function (ToastMessageDTO $toast) use ($currentTime, $queueLifetime) {
                $toastAge = $currentTime->getTimestamp() - $toast->createdAt->getTimestamp(); // Age in seconds
                return ($toastAge * 1000) <= $queueLifetime; // Convert age to milliseconds for comparison
            });
        }

        // Serialize DTOs to array for session storage
        $serializedToasts = $cleanedToasts->map(fn(ToastMessageDTO $toast) => $toast->jsonSerialize())->toArray();
        $this->session->put($this->sessionKey, $serializedToasts);
    }

    /**
     * Convenience method for adding a success toast.
     *
     * @inheritdoc
     */
    public function success(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return $this->add('success', $message, $title, $duration);
    }

    /**
     * Convenience method for adding an error toast.
     *
     * @inheritdoc
     */
    public function error(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return $this->add('error', $message, $title, $duration);
    }

    /**
     * Convenience method for adding a warning toast.
     *
     * @inheritdoc
     */
    public function warning(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return $this->add('warning', $message, $title, $duration);
    }

    /**
     * Convenience method for adding an info toast.
     *
     * @inheritdoc
     */
    public function info(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return $this->add('info', $message, $title, $duration);
    }

    /**
     * Convenience method for adding a custom toast with flexible options.
     *
     * This method now leverages the 'options' array to pass through specific DTO properties
     * that align with the `add` method's new parameters, ensuring the configuration's flexibility.
     *
     * @param string      $message The main content of the toast message.
     * @param string|null $title   Optional title for the toast.
     * @param array       $options An associative array of custom options to apply to the toast.
     * Keys can include 'duration', 'priority', 'auto_dismiss', 'pause_on_hover', 'show_progress_bar',
     * 'animation_preset', 'layout_preset', 'sound_asset', 'actions', 'custom_data',
     * and any 'types.{type}' specific keys like 'bg', 'text_color', 'icon'.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function custom(string $message, ?string $title = null, array $options = []): ToastMessageDTO
    {
        // Extract known parameters for the add method directly from options
        $duration = $options['duration'] ?? null;
        $priority = $options['priority'] ?? null;
        $autoDismiss = $options['auto_dismiss'] ?? null;
        $pauseOnHover = $options['pause_on_hover'] ?? null;
        $showProgressBar = $options['show_progress_bar'] ?? null;
        $animationPreset = $options['animation_preset'] ?? null;
        $layoutPreset = $options['layout_preset'] ?? null;
        $soundAsset = $options['sound_asset'] ?? null;
        $actions = $options['actions'] ?? [];
        $customData = $options['custom_data'] ?? []; // For generic custom data

        // Filter out these handled keys to pass any remaining as part of 'custom_data' or specific DTO attributes.
        // For simplicity, for `custom` type, we pass any remaining directly to the `add` method's $options
        // which now maps to additional DTO properties directly.
        unset(
            $options['duration'],
            $options['priority'],
            $options['auto_dismiss'],
            $options['pause_on_hover'],
            $options['show_progress_bar'],
            $options['animation_preset'],
            $options['layout_preset'],
            $options['sound_asset'],
            $options['actions'],
            $options['custom_data']
        );

        // Pass remaining options to 'customData' for generic storage,
        // or directly map to existing DTO properties if their names match.
        // For this specific custom function, the 'type' is set to 'custom_notification'
        // or a dynamic type if provided in $options, otherwise it defaults to 'default'.
        $customType = $options['type'] ?? 'default'; // Allow specifying a custom type name

        return $this->add(
            $customType,
            $message,
            $title,
            $duration,
            $priority,
            $autoDismiss,
            $pauseOnHover,
            $showProgressBar,
            $animationPreset,
            $layoutPreset,
            $soundAsset,
            $actions,
            $customData // Remaining options can be passed as custom data
        );
    }


    /**
     * Retrieves the list of toast messages that are currently active and should be displayed.
     *
     * This method applies the global, priority, and per-type display limits,
     * sorts toasts by priority, and filters out any dismissed or expired toasts.
     * It respects the 'queue.priority.overflow_behavior' and 'behavior.reverse_order_on_stack'.
     *
     * @return Collection<int, ToastMessageDTO> A collection of ToastMessageDTO objects ready for display.
     */
    public function get(): Collection
    {
        $allToasts = $this->getAllToastsFromSession();
        $currentTime = new DateTimeImmutable();

        // 1. Filter out dismissed and truly expired toasts
        $activeToasts = $allToasts->filter(function (ToastMessageDTO $toast) use ($currentTime) {
            return !$toast->dismissed && !$toast->isExpired($currentTime);
        });

        // 2. Separate toasts by their assigned priority
        $toastsByPriority = new Collection();
        foreach ($this->validPriorities as $priorityLevel) {
            $toastsByPriority[$priorityLevel] = $activeToasts->where('priority', $priorityLevel)->values();
        }

        $displayedToasts = new Collection();
        $currentGlobalDisplayCount = 0;
        $globalMaxToasts = $this->getConfig('queue.max_toasts', 5);
        $priorityEnabled = $this->getConfig('queue.priority.enabled', false);
        $overflowBehavior = $this->getConfig('queue.priority.overflow_behavior', 'distribute');
        $perTypeLimit = $this->getConfig('queue.per_type_limit');

        // Order priorities from high to low for processing display
        $prioritiesToProcess = $this->validPriorities; // Assuming these are already sorted high to low
        if ($this->getConfig('behavior.reverse_order_on_stack', false)) {
            // If reverse order, we display oldest first, meaning we process low priority first
            // and then high priority, adding them to the *end* of the displayedToasts collection.
            // This doesn't change the filtering logic, only the final ordering of the stack visually.
        }

        // Keep track of counts per type for 'per_type_limit'
        $typeCounts = [];
        foreach ($this->getConfig('types') as $typeName => $typeConfig) {
            $typeCounts[$typeName] = 0;
        }


        // Priority-based display logic
        foreach ($prioritiesToProcess as $priorityLevel) {
            $priorityToasts = $toastsByPriority[$priorityLevel] ?? new Collection();
            $priorityLimit = $this->getConfig('queue.priority.levels.' . $priorityLevel . '.limit', PHP_INT_MAX);

            // Sort toasts within each priority by creation time (oldest first for FIFO/natural order)
            $priorityToasts = $priorityToasts->sortBy(fn($toast) => $toast->createdAt->getTimestamp());

            foreach ($priorityToasts as $toast) {
                // Check global display limit
                if ($currentGlobalDisplayCount >= $globalMaxToasts) {
                    if ($overflowBehavior === 'distribute' && $priorityEnabled) {
                        // In 'distribute' mode, a higher priority toast might displace a lower priority one.
                        // This complex displacement logic is typically handled by the UI or by ensuring
                        // the initial `get()` result contains the 'best' set of toasts.
                        // For the backend, we primarily limit by the global and priority caps.
                        // The UI will handle the rendering and removal of visible toasts.
                        // For now, if global limit is hit, we stop considering new toasts.
                        break; // Stop adding for this priority level if global limit is reached
                    } else {
                        // Strict or global limit hit, no more toasts can be displayed
                        break;
                    }
                }

                // Check priority-specific limit
                if ($displayedToasts->where('priority', $priorityLevel)->count() >= $priorityLimit) {
                    if ($overflowBehavior === 'strict' && $priorityEnabled) {
                        continue; // Skip this toast if its priority queue is full and strict mode
                    }
                    // For 'distribute' or if priority is disabled, we might still try to add it
                    // if overall limits allow, but this is handled by the global limit check above
                    // and the per-type limit check below.
                }

                // Check per-type limit if enabled and configured
                if ($perTypeLimit && $typeCounts[$toast->type] >= $perTypeLimit) {
                    continue; // Skip if per-type limit is met
                }

                $displayedToasts->push($toast);
                $currentGlobalDisplayCount++;
                $typeCounts[$toast->type]++;
            }
        }

        // Sort the final displayed toasts for consistent rendering order in the UI.
        // Apply 'reverse_order_on_stack' for visual consistency.
        // If reverse_order_on_stack is true, then newest toasts are at the 'top' (end of array).
        // Otherwise, oldest toasts are at the 'top' (end of array).
        if ($this->getConfig('behavior.reverse_order_on_stack', false)) {
            // Sort by priority (high to low), then by creation time (newest first within priority)
            $displayedToasts = $displayedToasts->sortByDesc(function (ToastMessageDTO $toast) {
                $priorityOrder = array_flip($this->validPriorities); // high=0, normal=1, low=2 etc.
                return ($priorityOrder[$toast->priority] ?? count($this->validPriorities)) . '-' . $toast->createdAt->getTimestamp();
            })->values();
        } else {
            // Default order: by priority (high to low), then by creation time (oldest first within priority)
            $displayedToasts = $displayedToasts->sortBy(function (ToastMessageDTO $toast) {
                $priorityOrder = array_flip($this->validPriorities);
                return ($priorityOrder[$toast->priority] ?? count($this->validPriorities)) . '-' . $toast->createdAt->getTimestamp();
            })->values();
        }


        // Update the session to ensure only currently valid toasts are stored (cleanup)
        // This implicitly removes dismissed/expired toasts from the session over time.
        $this->updateToastsInSession($activeToasts);

        return $displayedToasts;
    }

    /**
     * Clears all toast messages from the session.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->session->forget($this->sessionKey);
    }

    /**
     * Marks a specific toast message as dismissed.
     *
     * This method retrieves all toasts, finds the specific one by ID, marks it
     * as dismissed, and then stores the updated collection back in the session.
     *
     * @param string $id The unique ID of the toast message to dismiss.
     * @return void
     */
    public function dismiss(string $id): void
    {
        $toasts = $this->getAllToastsFromSession();

        $updatedToasts = $toasts->map(function (ToastMessageDTO $toast) use ($id) {
            if ($toast->id === $id) {
                return $toast->markAsDismissed();
            }
            return $toast;
        });

        $this->updateToastsInSession($updatedToasts); // Update session with dismissed toast
    }

    /**
     * Checks if there are any active (non-dismissed, non-expired, and within limits) toasts.
     *
     * This method is an alias for `get()->isNotEmpty()`.
     *
     * @return bool True if there are toasts to display, false otherwise.
     */
    public function hasToasts(): bool
    {
        return $this->get()->isNotEmpty();
    }
}
