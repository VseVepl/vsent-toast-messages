{{--
    Renders the list of toast messages within the main container.
    Located at: your-package-root/resources/views/toast-list.blade.php
    Included by: 'laravel-toastify::container'

    Uses Alpine.js for transitions and managing the list.
    Receives $config from the parent view.
    Entangles `toasts` with Livewire component's public `toastsForJs` property.
--}}
<div
    x-data="{
        toasts: @entangle('toastsForJs').live,
        config: {{ json_encode($config) }}, // Passed from container.blade.php

        playSound(soundPath, volume = 1.0, loop = false) {
            if (!this.config.sounds?.global?.enabled || !soundPath) {
                return;
            }
            try {
                let audio = new Audio(soundPath);
                audio.volume = Math.min(1.0, Math.max(0.0, parseFloat(volume) || this.config.sounds.global.default_volume || 1.0));
                audio.loop = loop || this.config.sounds.global.default_loop || false;

                if (this.constructor.lastSoundPlayedAt && (Date.now() - this.constructor.lastSoundPlayedAt < (this.config.sounds.global.throttle_ms || 50))) {
                    return;
                }
                this.constructor.lastSoundPlayedAt = Date.now();

                audio.play().catch(e => console.error('Laravel Toastify: Error playing sound:', e, 'Path:', soundPath));
            } catch (e) {
                console.error('Laravel Toastify: Failed to create audio object:', e);
            }
        },

        getStackingClasses() {
            const position = this.config.display?.position || 'bottom-right';
            const reverseOrder = this.config.behavior?.reverse_order_on_stack || false;
            let classes = ['flex', 'flex-col', 'w-full', 'space-y-3'];

            if (position.includes('left')) classes.push('items-start');
            else if (position.includes('right')) classes.push('items-end');
            else if (position.includes('center')) classes.push('items-center');
            else classes.push('items-end');

            if (reverseOrder) {
                if (position.startsWith('bottom-')) classes.push('flex-col-reverse');
            } else {
                if (position.startsWith('top-')) classes.push('flex-col-reverse');
            }
            return classes.join(' ');
        },

        init() {
            let knownToastIds = new Set(this.toasts.map(t => t.id));
            this.$watch('toasts', (newToastsArray) => {
                const currentToastIds = new Set();
                newToastsArray.forEach(toast => {
                    currentToastIds.add(toast.id);
                    if (!knownToastIds.has(toast.id)) {
                        if (toast.sound) {
                            this.playSound(toast.sound, toast.soundVolume, toast.soundLoop);
                        }
                        knownToastIds.add(toast.id);
                    }
                });
                knownToastIds.forEach(id => {
                    if (!currentToastIds.has(id)) knownToastIds.delete(id);
                });
            });
            this.toasts.forEach(toast => {
                if (toast.sound) {
                    setTimeout(() => this.playSound(toast.sound, toast.soundVolume, toast.soundLoop), 50);
                }
            });
        }
    }"
    @play-sound-event.window="playSound($event.detail.path, $event.detail.volume, $event.detail.loop)"
    :class="getStackingClasses()"
    class="pointer-events-auto"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter-start="toast.animation?.enter_from || config.animations?.presets?.[config.animations?.preset]?.enter_from || 'opacity-0'"
            x-transition:enter-end="toast.animation?.enter_to || config.animations?.presets?.[config.animations?.preset]?.enter_to || 'opacity-100'"
            x-transition:leave-start="toast.animation?.leave_from || config.animations?.presets?.[config.animations?.preset]?.leave_from || 'opacity-100'"
            x-transition:leave-end="toast.animation?.leave_to || config.animations?.presets?.[config.animations?.preset]?.leave_to || 'opacity-0'"
            class="transition"
            :style="`
                transition-duration: ${toast.animation?.enter_duration || config.animations?.global?.default_enter_duration || 300}ms;
                transition-timing-function: ${toast.animation?.enter_easing || config.animations?.global?.default_enter_easing || 'ease-out'};
                ${toast.animation?.transform_origin ? 'transform-origin: ' + toast.animation.transform_origin + ';' : ''}
            `"
            x-on:mouseleave="
                if (toast.animation?.leave_duration) $el.style.transitionDuration = `${toast.animation?.leave_duration || config.animations?.global?.default_leave_duration || 200}ms`;
                if (toast.animation?.leave_easing) $el.style.transitionTimingFunction = toast.animation?.leave_easing || config.animations?.global?.default_leave_easing || 'ease-in';
            "
        >
            {{-- Use the package's view namespace for the component --}}
            @include('laravel-toastify::components.toast', [
                'config' => $config // Pass the main config object
                                     // Alpine's `toast` from x-for will be in scope in the included partial
            ])
        </div>
    </template>
</div>
