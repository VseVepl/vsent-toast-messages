{{--
    Renders a single toast message.
    This component is included by `toast-list.blade.php` inside an Alpine x-for loop.
    The `toast` variable here is from the Alpine.js scope of that loop.
    The `config` variable is passed down from `toast-list`.
--}}
<div
    x-data="{
        toastData: toast, // Current toast object from x-for
        mainConfig: config, // Full configuration object

        // Individual toast state
        id: toast.id,
        duration: parseInt(toast.duration),
        autoDismiss: toast.autoDismiss,
        showProgressBar: toast.showProgressBar && (config.progress_bar?.enabled ?? true) && parseInt(toast.duration) > 0,
        pauseOnHover: toast.pauseOnHover && (config.behavior?.pause_on_hover ?? true),
        pauseOnWindowBlur: config.behavior?.pause_on_window_blur ?? true,

        timerId: null,
        remainingTime: parseInt(toast.duration),
        isPaused: false,
        timerStartedAt: null,
        progressBarWidth: 100, // Initial width for progress bar

        initToast() {
            if (this.autoDismiss && this.duration > 0) {
                this.startTimer();

                if (this.pauseOnHover) {
                    this.$el.addEventListener('mouseenter', () => this.pauseTimer());
                    this.$el.addEventListener('mouseleave', () => this.resumeTimer());
                }
                if (this.pauseOnWindowBlur) {
                    window.addEventListener('blur', () => this.pauseTimerOnBlur());
                    window.addEventListener('focus', () => this.resumeTimerOnFocus());
                }
            }
            // Set CSS variable for actual progress bar animation if using CSS animations
            this.$el.style.setProperty('--toast-duration-ms', this.duration + 'ms');
        },

        startTimer() {
            if (this.timerId) clearTimeout(this.timerId);
            if (this.remainingTime <= 0) { // Already expired or no duration
                if(this.autoDismiss) this.dismiss(); // Dismiss if it should have auto-dismissed
                return;
            }

            this.isPaused = false;
            this.timerStartedAt = Date.now();

            this.timerId = setTimeout(() => {
                this.dismiss();
            }, this.remainingTime);

            this.animateProgressBar();
        },

        pauseTimer() {
            if (!this.autoDismiss || this.isPaused || this.remainingTime <= 0) return;

            this.isPaused = true;
            if (this.timerId) clearTimeout(this.timerId);
            const elapsed = Date.now() - this.timerStartedAt;
            this.remainingTime -= elapsed;

            // Update progress bar to reflect paused state (visually stop or show current)
            this.updateProgressBarWidth();
            if (this.$refs.progressBarInner) {
                this.$refs.progressBarInner.style.transitionDuration = '0ms'; // Stop animation abruptly
                this.$refs.progressBarInner.style.width = this.progressBarWidth + '%';
            }
        },

        resumeTimer() {
            if (!this.autoDismiss || !this.isPaused || this.remainingTime <= 0) return;

            this.isPaused = false;
            // timerStartedAt will be reset in startTimer
            this.startTimer(); // This will also call animateProgressBar
        },

        // Specific handlers for window blur/focus to avoid double-pausing if mouse is also over toast
        pauseTimerOnBlur() {
            if (this.pauseOnWindowBlur && !this.isPaused) { // Only pause if not already paused by hover
                this.pauseTimer();
            }
        },
        resumeTimerOnFocus() {
            if (this.pauseOnWindowBlur && this.isPaused) { // Only resume if it was paused (potentially by blur)
                 // Check if mouse is still over element, if so, hover-pause should take precedence
                if (this.pauseOnHover && this.$el.matches(':hover')) {
                    // Do not resume, hover is keeping it paused
                } else {
                    this.resumeTimer();
                }
            }
        },

        updateProgressBarWidth() {
            if (!this.showProgressBar) return;
            if (this.duration <= 0) {
                this.progressBarWidth = 100; // Full if no duration / not auto-dismissing
                return;
            }
            this.progressBarWidth = Math.max(0, (this.remainingTime / this.duration) * 100);
        },

        animateProgressBar() {
            if (!this.showProgressBar || !this.$refs.progressBarInner) return;

            // Ensure current width is set before starting transition
            this.$refs.progressBarInner.style.transitionDuration = '0ms';
            this.$refs.progressBarInner.style.width = this.progressBarWidth + '%';

            // Force reflow to apply the width change before starting the transition
            void this.$refs.progressBarInner.offsetWidth;

            // Start transition to 0% width over the remainingTime
            this.$refs.progressBarInner.style.transitionProperty = 'width';
            this.$refs.progressBarInner.style.transitionTimingFunction = this.mainConfig.progress_bar?.transition?.timing || 'linear';
            this.$refs.progressBarInner.style.transitionDuration = this.remainingTime + 'ms';
            this.$refs.progressBarInner.style.width = '0%';
        },

        dismiss() {
            if (this.timerId) clearTimeout(this.timerId);
            // Tell Livewire component to dismiss this toast from the backend
            // $wire is available if this component is a root of a Livewire render.
            // Here, we are in an Alpine component included in a Livewire view.
            // We need to emit an event that the parent Livewire component can catch,
            // or call a method on $wire if accessible.
            // Assuming $wire is accessible from the parent Livewire context:
            this.$wire.dismiss(this.id);

            // Optional: play dismiss sound
            const dismissSoundAssetKey = this.mainConfig.sounds?.assets?.dismiss?.src ? 'dismiss' : null; // Check if a 'dismiss' sound is defined
            if (dismissSoundAssetKey && this.mainConfig.sounds?.global?.enabled) {
                 const dismissSoundConfig = this.mainConfig.sounds.assets[dismissSoundAssetKey];
                 const dismissSoundPath = '/' + (this.mainConfig.sounds.global.base_path || 'sounds/toastify').replace(/^\/+|\/+$/g, '') + '/' + dismissSoundConfig.src.replace(/^\/+|\/+$/g, '');
                 // Call the playSound method from the parent toast-list Alpine component
                 this.$dispatch('play-sound-event', { path: dismissSoundPath, volume: dismissSoundConfig.volume, loop: dismissSoundConfig.loop });
            }
        },

        handleAction(handler) {
            if (!handler) return;
            // Basic examples, can be expanded
            if (handler.startsWith('Livewire.dispatch')) {
                const eventName = handler.match(/Livewire.dispatch\(['"]([^'"]+)['"]/)?.[1];
                if (eventName) Livewire.dispatch(eventName);
            } else if (handler.startsWith('$wire.')) {
                const methodName = handler.replace('$wire.', '');
                // This requires $wire to be correctly scoped or passed.
                // For now, assume direct call. Could also use events.
                try {
                    eval(handler); // Be cautious with eval
                } catch (e) { console.error('Toastify: Error evaluating action handler:', handler, e); }
            } else if (typeof window[handler] === 'function') {
                window[handler](this.toastData); // Call global JS function
            } else {
                // Default to Livewire component method call
                this.$wire.call(handler, this.id, this.toastData.customData);
            }
            // Optionally dismiss toast after action
            // if (action.dismiss_on_click ?? true) this.dismiss();
        }
    }"
    x-init="initToast()"
    class="w-full pointer-events-auto"
    :class="[toastData.layout?.wrapper_classes || mainConfig.types?.layouts?.default?.wrapper_classes || 'p-4 rounded-lg shadow-lg flex items-center space-x-3', toastData.bgColor, toastData.textColor]"
    role="status"
    :aria-live="toastData.ariaRole === 'alert' ? 'assertive' : 'polite'"
    tabindex="0"
>
    <!-- Icon -->
    <template x-if="toastData.icon">
        <div :class="toastData.layout?.icon_wrapper_classes || mainConfig.types?.layouts?.default?.icon_wrapper_classes || 'flex-shrink-0'">
            <div x-html="toastData.icon"></div>
        </div>
    </template>

    <!-- Content -->
    <div :class="toastData.layout?.content_wrapper_classes || mainConfig.types?.layouts?.default?.content_wrapper_classes || 'flex-grow'">
        <template x-if="toastData.title">
            <h3 class="text-sm font-medium" x-text="toastData.title"></h3>
        </template>
        <p class="text-sm" x-html="toastData.message"></p> {{-- Use x-html if message can contain HTML --}}

        <!-- Actions -->
        <template x-if="toastData.actions && toastData.actions.length > 0">
            <div :class="toastData.layout?.action_container_classes || mainConfig.types?.layouts?.[toastData.layout_preset || 'default']?.action_container_classes || 'flex justify-end space-x-2 mt-2'">
                <template x-for="action in toastData.actions" :key="action.label">
                    <button
                        type="button"
                        @click="handleAction(action.handler)"
                        :class="action.classes || 'text-sm underline hover:opacity-75'"
                        x-text="action.label"
                    ></button>
                </template>
            </div>
        </template>
    </div>

    <!-- Close Button -->
    <template x-if="toastData.closeButtonEnabled && (mainConfig.close_button?.enabled ?? true)">
        <div :class="mainConfig.close_button?.position_classes || 'ml-auto pl-3'"> {{-- Simplified default positioning --}}
            <div class="-mx-1.5 -my-1.5">
                 <button
                    type="button"
                    @click="dismiss()"
                    :class="mainConfig.close_button?.base_classes + ' ' + mainConfig.close_button?.size_classes + ' ' + mainConfig.close_button?.color_classes + ' ' + mainConfig.close_button?.hover_classes + ' ' + mainConfig.close_button?.transition_classes || 'p-1.5 rounded-md focus:outline-none'"
                    :aria-label="mainConfig.close_button?.aria_label || 'Close'"
                >
                    <span class="sr-only" x-text="mainConfig.close_button?.aria_label || 'Close'"></span>
                    <div x-html="mainConfig.close_button?.icon || '<svg class=\"h-5 w-5\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 20 20\" fill=\"currentColor\" aria-hidden=\"true\"><path fill-rule=\"evenodd\" d=\"M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z\" clip-rule=\"evenodd\" /></svg>'"></div>
                </button>
            </div>
        </div>
    </template>

    <!-- Progress Bar -->
    <template x-if="showProgressBar">
        <div
            :class="mainConfig.progress_bar?.base_class + ' ' + (mainConfig.progress_bar?.type_overrides?.[toastData.type]?.height || mainConfig.progress_bar?.height || 'h-1') + ' ' + (mainConfig.progress_bar?.background?.light || 'bg-black/10') + ' absolute bottom-0 left-0 right-0 rounded-b-lg'"
            role="progressbar"
            :aria-valuenow="progressBarWidth"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div x-ref="progressBarInner"
                 class="h-full rounded-b-lg"
                 :class="mainConfig.progress_bar?.type_overrides?.[toastData.type]?.foreground || mainConfig.types?.[toastData.type]?.progress_bar?.bg || mainConfig.types?.defaults?.progress_bar?.bg || 'bg-blue-500'"
                 :style="`width: ${progressBarWidth}%;`"> {{-- Initial width, transition handled by animateProgressBar --}}
            </div>
        </div>
    </template>
</div>
