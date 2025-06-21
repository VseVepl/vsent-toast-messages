{{--
    Main container for all toast messages.
    Located at: your-package-root/resources/views/container.blade.php
    Accessed via: view('laravel-toastify::container')

    Receives $toastsForJs (public array from Livewire component passed by Livewire),
    $positionClasses, $maxWidthClasses (public strings from Livewire component),
    and $config (full config array passed by Livewire component).
--}}
<div
    class="{{ $positionClasses }} {{ $maxWidthClasses }} pointer-events-none"
    aria-live="{{ $config['behavior']['aria_live_region'] ?? 'polite' }}"
    aria-atomic="false" {{-- Individual toasts are atomic, this container is a live region --}}
    role="region"
    aria-label="Toast Notifications Region"
>
    @include('laravel-toastify::toast-list', [
        // $toastsForJs is automatically available to @entangle in the included view
        // if it's a public property on the Livewire component.
        // Explicitly passing $config ensures it's available.
        'config' => $config,
    ])
</div>
