{{-- resources/views/container.blade.php --}}

<div
    {{-- This div acts as the main container for all toast messages. --}}
    {{-- It's responsible for the overall screen positioning (e.g., top-right, bottom-center). --}}
    {{-- $positionClasses and $maxWidthClasses are dynamically set by the Livewire component. --}}
    class="{{ $positionClasses }} {{ $maxWidthClasses }} space-y-4"
    aria-live="{{ $config['behavior']['aria_live_region'] ?? 'polite' }}"
    aria-atomic="false" {{-- Toasts are typically atomic, announced as a whole. --}}
    role="status" {{-- This region serves as a live region for status updates. --}}>
    {{-- The `toast-list.blade.php` partial is included here. --}}
    {{-- It will loop through the `toasts` collection and render each individual toast. --}}
    @include('toast-messages::toast-list', [
    'toasts' => $toasts,
    'config' => $config, {{-- Pass the full config for detailed options in child views --}}
    ])
</div>