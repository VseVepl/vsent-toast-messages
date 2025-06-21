{{-- resources/views/toast-list.blade.php --}}
{{--
    This partial renders the list of toast messages within the main container.
    It utilizes Alpine.js for transition animations as toasts are added or removed
    from the Livewire component's `toasts` collection.
--}}

<div
    x-data="{
        {{--
            Initialize Alpine.js state for the toast list.
            `toasts` is initialized with the Livewire component's `$toasts` data.
            This `x-init` block ensures Alpine.js is aware of the initial toast state.
        --}}
        toasts: @entangle('toasts').live,

        {{--
            Sound playback function.
            It checks if sounds are globally enabled and attempts to play the sound asset.
            This is an in-browser audio playback, so paths must be relative to the public directory.
        --}}
        playSound(soundPath, volume = 1.0, loop = false) {
            if (!{{ json_encode($config['sounds']['global']['enabled'] ?? false) }}) {
                return;
            }

            if (soundPath) {
                try {
                    let audio = new Audio(soundPath);
                    audio.volume = Math.min(1.0, Math.max(0.0, volume)); // Clamp volume between 0 and 1
                    audio.loop = loop;
                    audio.play().catch(e => console.error('Error playing sound:', e));
                } catch (e) {
                    console.error('Failed to create audio object:', e);
                }
            }
        },

        {{--
            Determine the correct stacking direction based on `reverse_order_on_stack` behavior.
            This impacts where new toasts appear visually in a stack.
        --}}
        getStackingOrder() {
            return {{ json_encode($config['behavior']['reverse_order_on_stack'] ?? false) }} ? 'top' : 'bottom';
        },

        {{--
            Initial setup on Alpine.js component initialization.
            Play sounds for any toasts that are already present (e.g., on first load
            or after a full page refresh if they were session-flashed).
        --}}
        init() {
            // Livewire will hydrate `toasts` automatically.
            // When Livewire updates `toasts`, `x-for` handles reactivity.
            // This `init` is primarily for playing sounds on initial load.
            this.$watch('toasts', (newToasts, oldToasts) => {
                const newToastIds = new Set(newToasts.map(toast => toast.id));
                const oldToastIds = new Set(oldToasts.map(toast => toast.id));

                newToasts.forEach(newToast => {
                    if (!oldToastIds.has(newToast.id)) {
                        // This is a new toast. Play its sound if configured.
                        if (newToast.sound) {
                            this.playSound(newToast.sound, newToast.soundVolume, newToast.soundLoop);
                        }
                    }
                });
            });

            // Initial sound playback for toasts already present on mount
            this.toasts.forEach(toast => {
                if (toast.sound) {
                    this.playSound(toast.sound, toast.soundVolume, toast.soundLoop);
                }
            });
        }
    }"
    class="pointer-events-auto flex flex-col w-full"
    :class="{
        'items-end': ['top-right', 'bottom-right'].includes('{{ $config['display']['position'] ?? 'bottom-right' }}'),
        'items-start': ['top-left', 'bottom-left'].includes('{{ $config['display']['position'] ?? 'bottom-right' }}'),
        'items-center': ['top-center', 'bottom-center'].includes('{{ $config['display']['position'] ?? 'bottom-right' }}'),
        'space-y-reverse': getStackingOrder() === 'top' && ({{ json_encode($config['display']['position'] ?? 'bottom-right') }}.includes('bottom') || {{ json_encode($config['display']['mobile_position'] ?? 'bottom-right') }}.includes('bottom')),
        'space-y-4': true {{-- Add spacing between toasts --}}
    }">
    {{--
        x-for is used to iterate over the `toasts` array.
        It uses x-transition directives to animate toasts when they enter or leave the DOM.
        Each toast is rendered using the `toast.blade.php` component.
    --}}
    <template x-for="toast in toasts" :key="toast.id">
        <div
            {{-- Dynamically apply enter/leave transition classes based on the toast's animation preset --}}
            x-transition:enter="{{ $config['animations']['global']['default_transition_classes'] ?? 'transition' }}"
            :x-transition:enter-start="toast.animation.enter_from"
            :x-transition:enter-end="toast.animation.enter_to"
            :x-transition:enter-duration="toast.animation.enter_duration"
            :x-transition:enter-ease="toast.animation.enter_easing"

            x-transition:leave="{{ $config['animations']['global']['default_transition_classes'] ?? 'transition' }}"
            :x-transition:leave-start="toast.animation.leave_from"
            :x-transition:leave-end="toast.animation.leave_to"
            :x-transition:leave-duration="toast.animation.leave_duration"
            :x-transition:leave-ease="toast.animation.leave_easing"

            {{-- Optional transform origin for scale animations --}}
            :style="toast.animation.transform_origin ? `transform-origin: ${toast.animation.transform_origin}` : ''">
            @include('toast-messages::components.toast', ['toast' => $toast, 'config' => $config])
        </div>
    </template>
</div>