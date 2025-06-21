{{--
    Renders a single toast message.
    Located at: your-package-root/resources/views/components/toast.blade.php
    Included by: 'laravel-toastify::toast-list'

    The `toast` variable here is from the Alpine.js x-for scope in toast-list.blade.php.
    The `config` variable is passed down from toast-list.blade.php.
--}}
<div
    x-data="{
        toastData: toast,
        mainConfig: config,

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
        progressBarWidth: 100,

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
            this.$el.style.setProperty('--toast-duration-ms', this.duration + 'ms');
        },

        startTimer() {
            if (this.timerId) clearTimeout(this.timerId);
            if (this.remainingTime <= 0) {
                if(this.autoDismiss) this.dismiss();
                return;
            }
            this.isPaused = false;
            this.timerStartedAt = Date.now();
            this.timerId = setTimeout(() => this.dismiss(), this.remainingTime);
            this.animateProgressBar();
        },

        pauseTimer() {
            if (!this.autoDismiss || this.isPaused || this.remainingTime <= 0) return;
            this.isPaused = true;
            if (this.timerId) clearTimeout(this.timerId);
            const elapsed = Date.now() - this.timerStartedAt;
            this.remainingTime -= elapsed;
            this.updateProgressBarWidth();
            if (this.$refs.progressBarInner) {
                this.$refs.progressBarInner.style.transitionDuration = '0ms';
                this.$refs.progressBarInner.style.width = this.progressBarWidth + '%';
            }
        },

        resumeTimer() {
            if (!this.autoDismiss || !this.isPaused || this.remainingTime <= 0) return;
            this.isPaused = false;
            this.startTimer();
        },

        pauseTimerOnBlur() {
            if (this.pauseOnWindowBlur && !this.isPaused) this.pauseTimer();
        },
        resumeTimerOnFocus() {
            if (this.pauseOnWindowBlur && this.isPaused) {
                if (this.pauseOnHover && this.$el.matches(':hover')) return;
                this.resumeTimer();
            }
        },

        updateProgressBarWidth() {
            if (!this.showProgressBar) return;
            this.progressBarWidth = (this.duration <= 0) ? 100 : Math.max(0, (this.remainingTime / this.duration) * 100);
        },

        animateProgressBar() {
            if (!this.showProgressBar || !this.$refs.progressBarInner) return;
            this.$refs.progressBarInner.style.transitionDuration = '0ms';
            this.$refs.progressBarInner.style.width = this.progressBarWidth + '%';
            void this.$refs.progressBarInner.offsetWidth;
            this.$refs.progressBarInner.style.transitionProperty = 'width';
            this.$refs.progressBarInner.style.transitionTimingFunction = this.mainConfig.progress_bar?.transition?.timing || 'linear';
            this.$refs.progressBarInner.style.transitionDuration = this.remainingTime + 'ms';
            this.$refs.progressBarInner.style.width = '0%';
        },

        dismiss() {
            if (this.timerId) clearTimeout(this.timerId);
            this.$wire.dismiss(this.id); // Call Livewire component method

            const dismissSoundAssetKey = this.mainConfig.sounds?.assets?.dismiss?.src ? 'dismiss' : null;
            if (dismissSoundAssetKey && this.mainConfig.sounds?.global?.enabled) {
                 const dismissSoundConfig = this.mainConfig.sounds.assets[dismissSoundAssetKey];
                 const soundBase = (this.mainConfig.sounds.global.base_path || 'sounds/toastify').replace(/^\/+|\/+$/g, '');
                 const soundSrc = dismissSoundConfig.src.replace(/^\/+|\/+$/g, '');
                 const dismissSoundPath = `/${soundBase}/${soundSrc}`;
                 this.$dispatch('play-sound-event', { path: dismissSoundPath, volume: dismissSoundConfig.volume, loop: dismissSoundConfig.loop });
            }
        },

        handleAction(handler, actionData = {}) { // Added actionData for potential future use
            if (!handler) return;
            if (handler.startsWith('Livewire.dispatch')) {
                const eventNameWithParams = handler.substring('Livewire.dispatch'.length).trim().slice(1, -1); // Get content like 'event-name', {id:1}
                const [eventName, paramsString] = eventNameWithParams.split(/,(.+)/s);
                const finalEventName = eventName.replace(/['"]/g, '');
                let finalParams = {};
                if (paramsString) {
                    try { finalParams = JSON.parse(paramsString.trim()); } catch (e) { console.error('Laravel Toastify: Invalid JSON in action params', paramsString, e); }
                }
                Livewire.dispatch(finalEventName, finalParams);
            } else if (handler.startsWith('$wire.')) {
                // For $wire.call('method', param1, param2) or $wire.method(param1)
                // This needs careful parsing if not simple.
                // A safer way is to always use Livewire.dispatch or specific $wire.call from config.
                // For simplicity, assuming simple $wire.methodName or $wire.call('methodName', ...argsFromToastData)
                const callMatch = handler.match(/\$wire\.call\(['"]([^'"]+)['"](?:,\s*([^)]+))?\)/) || handler.match(/\$wire\.([^'(]+)(?:\(([^)]*)\))?/);
                if (callMatch) {
                    const method = callMatch[1];
                    let args = [this.id, this.toastData.customData]; // Default args
                    // Very basic arg parsing for $wire.call('method', 'stringArg', 123) - not robust for complex objects
                    if (callMatch[2]) {
                        try { args = JSON.parse(`[${callMatch[2]}]`);}
                        catch(e) { args = callMatch[2].split(',').map(s => s.trim().replace(/^['"]|['"]$/g, ''));}
                    } else if (callMatch[3]) { // For $wire.method(args)
                         try { args = JSON.parse(`[${callMatch[3]}]`);}
                         catch(e) { args = callMatch[3].split(',').map(s => s.trim().replace(/^['"]|['"]$/g, ''));}
                    }
                    this.$wire.call(method, ...args);
                } else {
                     console.warn('Laravel Toastify: Could not parse $wire action handler:', handler);
                }
            } else if (typeof window[handler] === 'function') {
                window[handler](this.toastData);
            } else {
                this.$wire.call(handler, this.id, this.toastData.customData); // Default to Livewire component method
            }
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
        <p class="text-sm" x-html="toastData.message"></p>

        <!-- Actions -->
        <template x-if="toastData.actions && toastData.actions.length > 0">
            <div :class="toastData.layout?.action_container_classes || mainConfig.types?.layouts?.[toastData.layout_preset || 'default']?.action_container_classes || 'flex justify-end space-x-2 mt-2'">
                <template x-for="action in toastData.actions" :key="action.label">
                    <button
                        type="button"
                        @click="handleAction(action.handler, action)"
                        :class="action.classes || 'text-sm underline hover:opacity-75'"
                        x-text="action.label"
                    ></button>
                </template>
            </div>
        </template>
    </div>

    <!-- Close Button -->
    <template x-if="toastData.closeButtonEnabled && (mainConfig.close_button?.enabled ?? true)">
        <div :class="mainConfig.close_button?.position_classes || 'ml-auto pl-3'">
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
                 :style="`width: ${progressBarWidth}%;`">
            </div>
        </div>
    </template>
</div>
