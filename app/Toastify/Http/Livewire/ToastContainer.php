<?php

declare(strict_types=1);

namespace App\Toastify\Http\Livewire;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Toastify\Contracts\ToastManagerContract;
use App\Toastify\DTOs\ToastMessageDTO;

class ToastContainer extends Component
{
    protected ToastManagerContract $toastManager;
    protected ConfigRepository $configRepo;

    // This will hold the DTOs from the backend, for internal logic.
    // It's protected because Livewire 3 handles public properties differently,
    // and we pass a serialized version `toastsForJs` to the view for Alpine.
    protected Collection $backendToasts;

    // Public property to be entangled with Alpine.js in the view.
    // This will hold the array of serialized toast data.
    public array $toastsForJs = [];

    public string $positionClasses = '';
    public string $maxWidthClasses = '';
    public array $livewireConfig = []; // To pass relevant parts of config to JS if needed

    /**
     * Boot method for dependency injection.
     */
    public function boot(ToastManagerContract $toastManager, ConfigRepository $configRepo): void
    {
        $this->toastManager = $toastManager;
        $this->configRepo = $configRepo;
        $this->livewireConfig = $this->configRepo->get('toasts', []); // Load entire toasts config
    }

    public function mount(): void
    {
        $this->resolveContainerClasses();
        $this->loadToasts();
    }

    protected function resolveContainerClasses(): void
    {
        $position = $this->livewireConfig['display']['position'] ?? 'bottom-right';
        $mobilePosition = $this->livewireConfig['display']['mobile_position'] ?? $position;

        // Base classes for desktop
        $desktopClasses = match ($position) {
            'top-right' => 'top-0 right-0',
            'top-left' => 'top-0 left-0',
            'bottom-right' => 'bottom-0 right-0',
            'bottom-left' => 'bottom-0 left-0',
            'top-center' => 'top-0 left-1/2 -translate-x-1/2',
            'bottom-center' => 'bottom-0 left-1/2 -translate-x-1/2',
            default => 'bottom-0 right-0',
        };

        // Base classes for mobile (prefixed with sm:)
        $mobileSpecificClasses = match ($mobilePosition) {
            'top-right' => 'sm:top-0 sm:right-0',
            'top-left' => 'sm:top-0 sm:left-0',
            'bottom-right' => 'sm:bottom-0 sm:right-0',
            'bottom-left' => 'sm:bottom-0 sm:left-0',
            'top-center' => 'sm:top-0 sm:left-1/2 sm:-translate-x-1/2',
            'bottom-center' => 'sm:bottom-0 sm:left-1/2 sm:-translate-x-1/2',
            default => 'sm:bottom-0 sm:right-0',
        };

        // Combine, ensuring mobile overrides if different, or applies if same
        // This logic assumes mobile is `sm:` breakpoint. If config implies mobile-first, it's different.
        // For now, desktop first, then `sm:` overrides.
        if ($position === $mobilePosition) {
             // If positions are same, mobileSpecificClasses will just reinforce with sm: prefix
             // which is fine if Tailwind handles it gracefully or they are distinct sets of utilities.
             // A simpler approach if they are the same: just use desktop.
             // However, the original config implies they can be different.
             $this->positionClasses = $desktopClasses . ' ' . $mobileSpecificClasses;
        } else {
            // If different, we want desktop for base, and mobile to override AT `sm`
            // This needs careful thought. A common pattern is to define mobile first, then larger screens.
            // Or, define base (desktop), then specific overrides for mobile.
            // Let's assume the config implies: $desktopClasses for screens < sm, $mobileSpecificClasses for sm and up.
            // This would typically be: mobile classes first, then sm:desktop classes.
            // Given the config's naming, it's more like: desktop default, mobile override.
            // So, classes for mobile would apply, then `sm:` prefixed desktop classes.
            // This seems reversed. Let's try:
            $posClasses = match ($mobilePosition) { // Mobile first approach
                'top-right' => 'top-0 right-0',
                'top-left' => 'top-0 left-0',
                'bottom-right' => 'bottom-0 right-0',
                'bottom-left' => 'bottom-0 left-0',
                'top-center' => 'top-0 left-1/2 -translate-x-1/2',
                'bottom-center' => 'bottom-0 left-1/2 -translate-x-1/2',
                default => 'bottom-0 right-0',
            };
            $desktopOverrideClasses = match ($position) {
                'top-right' => 'sm:top-0 sm:right-0',
                'top-left' => 'sm:top-0 sm:left-0',
                'bottom-right' => 'sm:bottom-0 sm:right-0',
                'bottom-left' => 'sm:bottom-0 sm:left-0',
                'top-center' => 'sm:top-0 sm:left-1/2 sm:-translate-x-1/2',
                'bottom-center' => 'sm:bottom-0 sm:left-1/2 sm:-translate-x-1/2',
                default => 'sm:bottom-0 sm:right-0',
            };
             $this->positionClasses = $posClasses . ($position !== $mobilePosition ? ' ' . $desktopOverrideClasses : '');
        }


        $this->positionClasses .= ' fixed z-[9999] p-4'; // Common container styles

        // Max width
        $maxWidth = $this->livewireConfig['display']['max_width'] ?? 'max-w-md';
        $mobileMaxWidth = $this->livewireConfig['display']['mobile_max_width'] ?? $maxWidth;

        // Assuming mobile-first for max-width as well for consistency with common Tailwind patterns
        $this->maxWidthClasses = $mobileMaxWidth . ($maxWidth !== $mobileMaxWidth ? ' sm:' . $maxWidth : '');
    }


    protected function loadToasts(): void
    {
        $this->backendToasts = $this->toastManager->get();
        $this->toastsForJs = $this->backendToasts
            ->map(fn(ToastMessageDTO $toast) => $toast->jsonSerialize())
            ->values() // Ensure it's a flat array for JS
            ->toArray();
    }

    #[On('toast-created')]
    #[On('refresh-toasts')] // Generic event to allow refreshing from anywhere
    public function refreshToastsList(): void
    {
        $this->loadToasts();
    }

    /**
     * Handles Livewire's navigate event for SPA-like page changes.
     * Clears toasts if configured to do so.
     */
    #[On('navigate')]
    public function handleNavigation(): void
    {
        if ($this->livewireConfig['behavior']['clear_all_on_navigate'] ?? true) {
            $this->toastManager->clear();
            $this->loadToasts(); // Refresh the list, which will now be empty
        }
    }

    public function dismiss(string $toastId): void
    {
        $this->toastManager->dismiss($toastId);
        $this->loadToasts(); // Refresh the displayed toasts
    }

    public function render(): View
    {
        // $this->loadToasts(); // Re-load toasts on every render to catch session changes (can be too frequent)
        // It's better to rely on events or specific actions to call loadToasts().
        // Mount and event listeners handle initial load and updates.

        return view('toastify::container', [
            // toastsForJs is already a public property, Livewire will pass it.
            // 'positionClasses' and 'maxWidthClasses' are public.
            // Pass the full config for AlpineJS to use in child components.
            'config' => $this->livewireConfig,
        ]);
    }
}
