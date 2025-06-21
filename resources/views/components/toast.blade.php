{{-- resources/views/components/toast.blade.php --}}
{{--
    This Blade partial renders a single toast message.
    It takes a `ToastMessageDTO` object and the global configuration as props.
    It dynamically applies Tailwind CSS classes and Alpine.js directives
    based on the toast's properties and the configuration.
--}}

<div
    x-data="{
        {{-- Initialize Alpine.js state for this individual toast --}}
        toastId: '{{ $toast->id }}',
        duration: {{ $toast->duration }},
        autoDismiss: {{ json_encode($toast->autoDismiss) }},
        showProgressBar: {{ json_encode($toast->showProgressBar) }},
        progressBarEnabled: {{ json_encode($config['progress_bar']['enabled'] ?? false) }},
        pauseOnHover: {{ json_encode($toast->pauseOnHover) }},
        pauseOnWindowBlur: {{ json_encode($config['behavior']['pause_on_window_blur'] ?? true) }},
        dismissed: false,
        timeoutId: null,
        progressBarWidth: 100,
        progressBarAnimationDuration: '{{ $toast->duration }}ms',
        createdAt: new Date('{{ $toast->createdAt->format(\DateTimeInterface::ATOM) }}'),
        remainingTime: {{ $toast->duration }}, {{-- In milliseconds --}}
        timerStartedAt: Date.now(),

        {{--
            Initialize toast timer and event listeners.
            This is called when the toast element is added to the DOM.
        --}}
        init() {
            // Set CSS variable for progress bar animation duration
            this.$el.style.setProperty('--toast-duration', this.duration + 'ms');

            if (this.autoDismiss && this.duration > 0) {
                this.startTimer();

                if (this.pauseOnHover) {
                    this.$el.addEventListener('mouseenter', () => this.pauseTimer());
                    this.$el.addEventListener('mouseleave', () => this.resumeTimer());
                }

                if (this.pauseOnWindowBlur) {
                    window.addEventListener('blur', () => this.pauseTimer());
                    window.addEventListener('focus', () => this.resumeTimer());
                }
            }

            // Listen for Livewire navigation to clear toasts if configured
            @if ($config['behavior']['clear_all_on_navigate'] ?? true)
                document.addEventListener('livewire:navigate', () => {
                    // This event is typically handled by the ToastContainer component,
                    // but we can ensure individual toasts also react.
                    // For now, relies on ToastContainer to manage the collection.
                });
            @endif
        },

        {{-- Start or restart the auto-dismiss timer --}}
        startTimer() {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
            }
            this.timerStartedAt = Date.now();
            this.timeoutId = setTimeout(() => {
                this.dismissToast();
            }, this.remainingTime);

            this.updateProgressBar();
        },

        {{-- Pause the auto-dismiss timer --}}
        pauseTimer() {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
                const elapsed = Date.now() - this.timerStartedAt;
                this.remainingTime -= elapsed;
                // Stop progress bar animation if it's running via CSS
                this.$el.style.setProperty('--toast-paused', 'running'); // Using 'running' to effectively pause CSS animation
            }
        },

        {{-- Resume the auto-dismiss timer --}}
        resumeTimer() {
            if (this.autoDismiss && this.duration > 0 && this.remainingTime > 0) {
                this.$el.style.setProperty('--toast-paused', 'paused'); // Using 'paused' to resume CSS animation
                this.startTimer();
            }
        },

        {{-- Update the progress bar width based on remaining time --}}
        updateProgressBar() {
            if (this.showProgressBar && this.progressBarEnabled) {
                // The CSS animation for the progress bar will handle the actual width.
                // We just need to ensure the duration variable is set.
                // This function is mostly a placeholder for starting the CSS animation.
            }
        },

        {{-- Dismiss the toast programmatically or via user interaction --}}
        dismissToast() {
            this.dismissed = true;
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
            }
            // Emit a Livewire event to inform the parent component to dismiss the toast
            this.$wire.dismiss(this.toastId);
        }
    }"
    {{-- Apply overall wrapper classes --}}
    class="{{ $toast->layout['wrapper_classes'] ?? 'p-4 rounded-lg shadow-lg flex items-center space-x-4' }} {{ $toast->bgColor }} {{ $toast->textColor }} relative"
    :class="{ 'hidden': dismissed }" {{-- Hide element when dismissed --}}
    role="{{ $toast->ariaRole }}"
    aria-live="{{ $toast->ariaRole === 'alert' ? 'assertive' : 'polite' }}"
    tabindex="0" {{-- Make toast focusable for accessibility --}}>
    {{-- Icon Wrapper --}}
    @if ($toast->icon)
    <div class="{{ $toast->layout['icon_wrapper_classes'] ?? 'flex-shrink-0' }}">
        {!! $toast->icon !!}
    </div>
    @endif

    {{-- Content Wrapper --}}
    <div class="{{ $toast->layout['content_wrapper_classes'] ?? 'flex-grow' }}">
        @if ($toast->title)
        <h3 class="text-sm {{ $config['types']['defaults']['title_classes'] ?? 'font-semibold' }}">{{ $toast->title }}</h3>
        @endif
        <p class="text-sm {{ $config['types']['defaults']['message_classes'] ?? '' }}">{{ $toast->message }}</p>

        {{-- Actions Section --}}
        @if (!empty($toast->actions))
        <div class="{{ $toast->layout['action_container_classes'] ?? 'flex justify-end space-x-2 mt-2' }}">
            @foreach ($toast->actions as $action)
            @php
            $actionHandler = $action['handler'] ?? null;
            $actionClasses = $action['classes'] ?? '';
            @endphp
            <button
                type="button"
                @if ($actionHandler)
                @if (str_starts_with($actionHandler, 'Livewire.' )) {{-- Example: Livewire.dispatch('someEvent') --}}
                x-on:click="{!! $actionHandler !!}"
                @elseif (str_starts_with($actionHandler, 'this.' )) {{-- Example: this.dismissToast() --}}
                x-on:click="{!! $actionHandler !!}"
                @else {{-- Assume it's a Livewire component method --}}
                wire:click="{{ $actionHandler }}"
                @endif
                @endif
                class="{{ $actionClasses }}">
                {{ $action['label'] }}
            </button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Close Button --}}
    @if ($toast->closeButtonEnabled && ($config['close_button']['enabled'] ?? true))
    <div class="{{ $config['close_button']['position_classes'] ?? 'absolute top-2 right-2' }}">
        <{{ $config['close_button']['html_element'] ?? 'button' }}
            type="button"
            x-on:click="dismissToast()"
            class="{{ $config['close_button']['base_classes'] ?? '' }} {{ $config['close_button']['size_classes'] ?? '' }} {{ $config['close_button']['color_classes'] ?? '' }} {{ $config['close_button']['hover_classes'] ?? '' }} {{ $config['close_button']['transition_classes'] ?? '' }}"
            aria-label="{{ $config['close_button']['aria_label'] ?? 'Close notification' }}">
            {!! $config['close_button']['icon'] ?? '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
            </svg>' !!}
        </{{ $config['close_button']['html_element'] ?? 'button' }}>
    </div>
    @endif

    {{-- Progress Bar --}}
    @if ($toast->showProgressBar && ($config['progress_bar']['enabled'] ?? true) && $toast->duration > 0)
    @php
    // Resolve progress bar foreground color (type-specific override > global)
    $progressBarTypeOverride = $config['progress_bar']['type_overrides'][$toast->type]['foreground'] ?? null;
    $progressBarBg = $progressBarTypeOverride ?? $toast->progress_bar['bg'] ?? ($config['progress_bar']['background']['light'] ?? 'bg-black/10');

    // Resolve progress bar height (type-specific override > global)
    $progressBarHeightTypeOverride = $config['progress_bar']['type_overrides'][$toast->type]['height'] ?? null;
    $progressBarHeight = $progressBarHeightTypeOverride ?? $config['progress_bar']['height'] ?? 'h-1';

    // Resolve progress bar width (desktop vs. mobile)
    $progressBarWidthDefault = $config['progress_bar']['width']['default'] ?? 'w-full';
    $progressBarWidthMobile = $config['progress_bar']['width']['mobile'] ?? $progressBarWidthDefault;

    $progressBarWidthClass = $progressBarWidthDefault;
    if ($progressBarWidthDefault !== $progressBarWidthMobile) {
    $progressBarWidthClass .= ' sm:' . $progressBarWidthMobile;
    }

    // Resolve progress bar transition properties
    $progressBarTransitionProperty = $config['progress_bar']['transition']['property'] ?? 'width';
    $progressBarTransitionTiming = $config['progress_bar']['transition']['timing'] ?? 'ease-linear';
    $progressBarTransitionDuration = $config['progress_bar']['transition']['duration'] ?? 'duration-[--toast-duration]';
    @endphp
    <div
        class="{{ $config['progress_bar']['base_class'] ?? 'overflow-hidden' }} {{ $progressBarHeight }} {{ $progressBarWidthClass }} {{ $progressBarBg }}">
        <div
            class="h-full {{ $toast->progress_bar['bg'] ?? $progressBarBg }}" {{-- Use toast's bg for foreground if specific isn't given --}}
            :style="`width: ${progressBarWidth}%; transition: ${progressBarTransitionProperty} ${progressBarAnimationDuration} ${progressBarTransitionTiming}; animation-play-state: var(--toast-paused, running);`"
            x-init="$watch('dismissed', val => { if (val) $el.style.width = '0%'; });" {{-- Animate to 0% on dismissal --}}></div>
    </div>
    @endif
</div>