<?php

declare(strict_types=1);

namespace Vsent\ToastMessages\Http\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\On; // For Livewire 3 event listeners
use Livewire\Attributes\Locked; // Added for Livewire 3
use Illuminate\Contracts\Config\Repository as Config;
use Vsent\ToastMessages\Contracts\ToastManagerContract;
use Vsent\ToastMessages\DTOs\ToastMessageDTO;

/**
 * Class ToastContainer
 *
 * @package VsE\ToastMessages\Http\Livewire
 *
 * This Livewire component acts as the primary container for displaying toast notifications.
 * It manages the state of active toasts, fetches them from the ToastManager,
 * and handles user interactions like dismissing individual toasts.
 *
 * It listens for internal Livewire events (like `toast-created`) to ensure real-time
 * updates without requiring a full page refresh.
 */
class ToastContainer extends Component
{
    /**
     * @var Collection<int, ToastMessageDTO> The collection of toast messages currently active.
     * IMPORTANT: This property MUST be 'protected' (or 'private') to prevent Livewire
     * from attempting to hydrate complex DTO objects directly, which causes the "Property type not supported" error.
     * The data for the frontend is explicitly serialized in the 'render' method.
     */
    protected Collection $toasts; // <<<<<<<<<<< THIS LINE MUST BE 'protected'

    /**
     * @var ToastManagerContract The injected instance of our ToastManager.
     */
    protected ToastManagerContract $toastManager;

    /**
     * @var array<string, mixed> The loaded 'toasts' configuration.
     */
    protected array $configData;

    /**
     * @var string The default screen position for toasts (e.g., 'bottom-right').
     * This will be derived from configuration.
     */
    #[Locked] // Added Livewire 3 Locked attribute
    public string $positionClasses = 'bottom-right';

    /**
     * @var string The maximum width class for toasts (e.g., 'max-w-md').
     */
    #[Locked] // Added Livewire 3 Locked attribute
    public string $maxWidthClasses = 'max-w-md';


    /**
     * Constructor for the Livewire ToastContainer component.
     *
     * Livewire 3 automatically injects dependencies. We primarily use this for
     * initial setup and assigning the ToastManager.
     *
     * @param ToastManagerContract $toastManager The contract implementation for toast management.
     * @param Config $config The Laravel config repository.
     * @return void
     */
    public function boot(ToastManagerContract $toastManager, Config $config): void
    {
        $this->toastManager = $toastManager;
        $this->configData = $config->get('toasts', []);

        // Resolve position and max-width from config
        // These are static layout properties that apply to the overall container.
        $this->positionClasses = $this->resolvePositionClasses();
        $this->maxWidthClasses = $this->resolveMaxWidthClasses();
    }

    /**
     * Called once when the component is first mounted.
     * Initializes the toasts collection.
     *
     * @return void
     */
    public function mount(): void
    {
        // Initial load of toasts from the session via the ToastManager.
        $this->toasts = $this->toastManager->get();
    }

    /**
     * Resolve the appropriate CSS classes for toast container positioning
     * based on the 'display.position' and 'display.mobile_position' config.
     *
     * @return string
     */
    protected function resolvePositionClasses(): string
    {
        $position = $this->configData['display']['position'] ?? 'bottom-right';
        $mobilePosition = $this->configData['display']['mobile_position'] ?? $position; // Fallback to desktop position

        $classes = match ($position) {
            'top-right' => 'top-0 right-0',
            'top-left' => 'top-0 left-0',
            'bottom-right' => 'bottom-0 right-0',
            'bottom-left' => 'bottom-0 left-0',
            'top-center' => 'top-0 left-1/2 -translate-x-1/2',
            'bottom-center' => 'bottom-0 left-1/2 -translate-x-1/2',
            default => 'bottom-0 right-0', // Fallback
        };

        $mobileClasses = match ($mobilePosition) {
            'top-right' => 'sm:top-0 sm:right-0',
            'top-left' => 'sm:top-0 sm:left-0',
            'bottom-right' => 'sm:bottom-0 sm:right-0',
            'bottom-left' => 'sm:bottom-0 sm:left-0',
            'top-center' => 'sm:top-0 sm:left-1/2 sm:-translate-x-1/2',
            'bottom-center' => 'sm:bottom-0 sm:left-1/2 sm:-translate-x-1/2',
            default => 'sm:bottom-0 sm:right-0', // Fallback
        };

        // Ensure responsive classes are applied for mobile position overriding desktop if different.
        if ($position !== $mobilePosition) {
            $classes .= ' ' . $mobileClasses;
        }

        // Add padding/margin for spacing from screen edges
        $classes .= ' p-4'; // Add padding to the container itself

        // Make the container fixed so it floats over content
        // The flex-col and items-end/start/center logic for stacking and alignment
        // is now handled purely by Alpine.js in the toast-list.blade.php partial
        // to avoid conflicts and simplify responsive alignment.
        $classes .= ' fixed z-[9999] w-full';

        return $classes;
    }

    /**
     * Resolve the appropriate CSS classes for toast maximum width
     * based on the 'display.max_width' and 'display.mobile_max_width' config.
     *
     * @return string
     */
    protected function resolveMaxWidthClasses(): string
    {
        $maxWidth = $this->configData['display']['max_width'] ?? 'max-w-md';
        $mobileMaxWidth = $this->configData['display']['mobile_max_width'] ?? $maxWidth; // Fallback to desktop width

        $classes = $maxWidth;

        // Apply mobile-specific max-width if different
        if ($maxWidth !== $mobileMaxWidth) {
            $classes .= ' sm:' . $mobileMaxWidth;
        }

        return $classes;
    }


    /**
     * Listens for the 'toast-created' event (dispatched from ToastManager)
     * and refreshes the list of toasts. This ensures new toasts appear immediately.
     *
     * @param string $toastId The ID of the newly created toast (though we refetch all).
     * @return void
     */
    #[On('toast-created')]
    public function refreshToasts(string $toastId = null): void
    {
        // Re-fetch all toasts to ensure all limits, priorities, and expiries are re-evaluated.
        // This is robust against multiple changes in the session.
        $this->toasts = $this->toastManager->get();
    }

    /**
     * Dismisses a specific toast message.
     * This method is called from the frontend via Livewire (e.g., wire:click="dismiss('{{ $toast->id }}')").
     *
     * @param string $id The unique ID of the toast to dismiss.
     * @return void
     */
    public function dismiss(string $id): void
    {
        $this->toastManager->dismiss($id);
        // After dismissing, refresh the list to remove the toast from display.
        $this->refreshToasts();
    }

    /**
     * Renders the Livewire component view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        // IMPORTANT: Convert the Collection of DTOs to a plain array of JSON-serializable data.
        // This ensures Livewire doesn't attempt to hydrate/dehydrate complex DTO objects
        // and passes a simple array that Js::from() can directly handle for Alpine.js.
        $toastsForJs = $this->toasts->map(fn(ToastMessageDTO $toast) => $toast->jsonSerialize())->toArray();

        return view('toast-messages::container', [
            'toasts' => $toastsForJs, // Pass the prepared array to the view
            'positionClasses' => $this->positionClasses,
            'maxWidthClasses' => $this->maxWidthClasses,
            // Pass the entire configuration for frontend logic (e.g., animation details, sound paths)
            'config' => $this->configData,
        ]);
    }
}
