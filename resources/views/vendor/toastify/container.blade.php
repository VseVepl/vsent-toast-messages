{{--
    Main container for all toast messages.
    Manages overall screen positioning and max-width.
    Receives $toastsForJs (public array from Livewire component),
    $positionClasses, $maxWidthClasses (public strings from Livewire component),
    and $config (full config array).
--}}
<div
    class="{{ $positionClasses }} {{ $maxWidthClasses }} pointer-events-none"
    aria-live="{{ $config['behavior']['aria_live_region'] ?? 'polite' }}"
    aria-atomic="false"
    role="region" {{-- Using region as it's a collection of status updates --}}
>
    @include('toastify::toast-list', [
        'toastsForJs' => $toastsForJs, {{-- Already passed by Livewire's render method --}}
        'config' => $config,
    ])
</div>
