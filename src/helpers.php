<?php

declare(strict_types=1);

use Vsent\ToastMessages\Facades\Toast;
use Vsent\ToastMessages\DTOs\ToastMessageDTO; // Ensure DTO is imported for type hints in docblocks

// Prevents redeclaration errors if the helpers file is somehow included multiple times.
// This is typically handled by the Service Provider's `loadHelpers` method, but this adds an extra layer of safety.
if (!function_exists('toast')) {
    /**
     * Adds a new toast message to the queue.
     *
     * This global helper function provides a convenient way to add toast notifications
     * without needing to use the `Toast` facade explicitly or resolve the manager from the container.
     * It's a direct wrapper around `Toast::add()`.
     *
     * @param string      $type            The type of the toast (e.g., 'success', 'error', 'warning', 'info', 'custom').
     * @param string      $message         The main content of the toast message.
     * @param string|null $title           Optional title for the toast.
     * @param int|null    $duration        Optional custom duration for this toast in milliseconds.
     * @param string|null $priority        Optional priority for this toast ('high', 'normal', 'low').
     * @param bool|null   $autoDismiss     Optional override for auto-dismiss behavior.
     * @param bool|null   $pauseOnHover    Optional override for pause-on-hover behavior.
     * @param bool|null   $showProgressBar Optional override for progress bar visibility.
     * @param string|null $animationPreset Optional animation preset to use.
     * @param string|null $layoutPreset    Optional layout preset to use.
     * @param string|null $soundAsset      Optional sound asset name to use (e.g., 'success', 'error').
     * @param array       $actions         Optional array of actions for the toast.
     * @param array       $customData      Optional custom data to attach to the toast.
     *
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    function toast(
        string $type,
        string $message,
        ?string $title = null,
        ?int $duration = null,
        ?string $priority = null,
        ?bool $autoDismiss = null,
        ?bool $pauseOnHover = null,
        ?bool $showProgressBar = null,
        ?string $animationPreset = null,
        ?string $layoutPreset = null,
        ?string $soundAsset = null,
        array $actions = [],
        array $customData = []
    ): ToastMessageDTO {
        return Toast::add(
            $type,
            $message,
            $title,
            $duration,
            $priority,
            $autoDismiss,
            $pauseOnHover,
            $showProgressBar,
            $animationPreset,
            $layoutPreset,
            $soundAsset,
            $actions,
            $customData
        );
    }
}

if (!function_exists('toast_success')) {
    /**
     * Adds a 'success' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    function toast_success(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toast::success($message, $title, $duration);
    }
}

if (!function_exists('toast_error')) {
    /**
     * Adds an 'error' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    function toast_error(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toast::error($message, $title, $duration);
    }
}

if (!function_exists('toast_warning')) {
    /**
     * Adds a 'warning' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    function toast_warning(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toast::warning($message, $title, $duration);
    }
}

if (!function_exists('toast_info')) {
    /**
     * Adds an 'info' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    function toast_info(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toast::info($message, $title, $duration);
    }
}

if (!function_exists('toast_custom')) {
    /**
     * Adds a 'custom' type toast message with more flexible options.
     *
     * This helper function is a wrapper around `Toast::custom()`, allowing for
     * more specific customization of the toast's appearance and behavior.
     *
     * @param string      $message The main content of the toast message.
     * @param string|null $title   Optional title for the toast.
     * @param array       $options An associative array of custom options to apply to the toast.
     * Refer to the `ToastManager::custom()` method for supported keys.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    function toast_custom(string $message, ?string $title = null, array $options = []): ToastMessageDTO
    {
        return Toast::custom($message, $title, $options);
    }
}
