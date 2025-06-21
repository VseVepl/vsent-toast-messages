{{--
    Renders the list of toast messages within the main container.
    Uses Alpine.js for transitions and managing the list.
    Receives $toastsForJs (entangled from Livewire component) and $config.
--}}
<div
    x-data="{
        toasts: @entangle('toastsForJs').live, // Entangle with the Livewire public property
        config: {{ json_encode($config) }}, // Make full config available to Alpine

        playSound(soundPath, volume = 1.0, loop = false) {
            if (!this.config.sounds?.global?.enabled || !soundPath) {
                return;
            }
            try {
                let audio = new Audio(soundPath); // soundPath should be web-accessible URL
                audio.volume = Math.min(1.0, Math.max(0.0, parseFloat(volume) || this.config.sounds.global.default_volume || 1.0));
                audio.loop = loop || this.config.sounds.global.default_loop || false;

                // Sound throttling logic (basic example)
                if (this.constructor.lastSoundPlayedAt && (Date.now() - this.constructor.lastSoundPlayedAt < (this.config.sounds.global.throttle_ms || 50))) {
                    // console.log('Toastify: Sound throttled');
                    return;
                }
                this.constructor.lastSoundPlayedAt = Date.now();

                audio.play().catch(e => console.error('Toastify: Error playing sound:', e, 'Path:', soundPath));
            } catch (e) {
                console.error('Toastify: Failed to create audio object:', e);
            }
        },

        getStackingClasses() {
            const position = this.config.display?.position || 'bottom-right';
            const reverseOrder = this.config.behavior?.reverse_order_on_stack || false;
            let classes = ['flex', 'flex-col', 'w-full', 'space-y-3']; // Common base

            // Horizontal alignment based on X part of position (left, center, right)
            if (position.includes('left')) classes.push('items-start');
            else if (position.includes('right')) classes.push('items-end');
            else if (position.includes('center')) classes.push('items-center');
            else classes.push('items-end'); // Default alignment

            // Vertical stacking order
            // If reverseOrder is true, newest toasts are at the "top" of the visual stack.
            // If container is at bottom (e.g., bottom-right), flex-col-reverse makes new items appear at bottom.
            // If container is at top (e.g., top-right), flex-col makes new items appear at top.
            if (reverseOrder) {
                if (position.startsWith('bottom-')) {
                    classes.push('flex-col-reverse'); // Newest (prepended in JS/PHP) appears at bottom
                } else {
                    // For top positions, flex-col is fine, newest will be at the top of list
                }
            } else { // Oldest at top of visual stack
                if (position.startsWith('top-')) {
                    classes.push('flex-col-reverse'); // Oldest (appended in JS/PHP or natural order) appears at top
                } else {
                     // For bottom positions, flex-col is fine, oldest will be at top of list
                }
            }
            return classes.join(' ');
        },

        init() {
            // Watch for new toasts being added to play their sound
            // This relies on Livewire updating the `toasts` array.
            // Alpine's $watch is good for reacting to changes in the entangled array.
            let knownToastIds = new Set(this.toasts.map(t => t.id));

            this.$watch('toasts', (newToastsArray) => {
                const currentToastIds = new Set();
                newToastsArray.forEach(toast => {
                    currentToastIds.add(toast.id);
                    if (!knownToastIds.has(toast.id)) {
                        // This is a new toast
                        if (toast.sound) {
                            this.playSound(toast.sound, toast.soundVolume, toast.soundLoop);
                        }
                        knownToastIds.add(toast.id);
                    }
                });

                // Clean up knownToastIds if toasts are removed
                knownToastIds.forEach(id => {
                    if (!currentToastIds.has(id)) {
                        knownToastIds.delete(id);
                    }
                });
            });

            // Initial sound playback for any toasts already present on mount
            // (e.g. if loaded from session on first page load before Livewire updates fully)
            this.toasts.forEach(toast => {
                if (toast.sound) {
                     // Small delay to ensure audio context might be ready after user interaction
                    setTimeout(() => this.playSound(toast.sound, toast.soundVolume, toast.soundLoop), 50);
                }
            });
        }
    }"
    @play-sound-event.window="playSound($event.detail.path, $event.detail.volume, $event.detail.loop)" {{-- Listen for dismiss sound event --}}
    :class="getStackingClasses()"
    class="pointer-events-auto" {{-- Container for toasts should allow pointer events --}}
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter-start="toast.animation?.enter_from || config.animations?.presets?.[config.animations?.preset]?.enter_from || 'opacity-0'"
            x-transition:enter-end="toast.animation?.enter_to || config.animations?.presets?.[config.animations?.preset]?.enter_to || 'opacity-100'"
            x-transition:leave-start="toast.animation?.leave_from || config.animations?.presets?.[config.animations?.preset]?.leave_from || 'opacity-100'"
            x-transition:leave-end="toast.animation?.leave_to || config.animations?.presets?.[config.animations?.preset]?.leave_to || 'opacity-0'"
            class="transition" {{-- Base transition class, specific timings/easing from config --}}
            :style="`
                transition-duration: ${toast.animation?.enter_duration || config.animations?.global?.default_enter_duration || 300}ms;
                transition-timing-function: ${toast.animation?.enter_easing || config.animations?.global?.default_enter_easing || 'ease-out'};
                ${toast.animation?.transform_origin ? 'transform-origin: ' + toast.animation.transform_origin + ';' : ''}
            `"
            x-on:mouseleave="
                if (toast.animation?.leave_duration) {
                    $el.style.transitionDuration = `${toast.animation?.leave_duration || config.animations?.global?.default_leave_duration || 200}ms`;
                }
                if (toast.animation?.leave_easing) {
                    $el.style.transitionTimingFunction = toast.animation?.leave_easing || config.animations?.global?.default_leave_easing || 'ease-in';
                }
            "
        >
            @include('toastify::components.toast', [
                // 'toast' variable is from x-for, implicitly available to @include if not passed.
                // However, explicitly passing ensures clarity and Livewire context if sub-component was LW.
                // For plain Blade include, Alpine's `toast` variable from x-for scope is used by the partial.
                // We pass full 'config' down.
                'config' => $config // Pass the main config object
            ]) {{-- Alpine's `toast` from x-for will be in scope here --}}
        </div>
    </template>
</div>
