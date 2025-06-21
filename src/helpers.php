<?php

declare(strict_types=1);

// Ensure this file is not included multiple times if not using Composer's autoloader strictly
// (though Composer's `files` autoload should handle this).

use Vsent\LaravelToastify\Facades\Toastify; // Updated Namespace for Facade
use Vsent\LaravelToastify\DTOs\ToastMessageDTO; // Updated Namespace for DTO (for type hinting)

if (!function_exists('toastify')) {
    /**
     * Adds a new toast message to the queue via the Toastify facade.
     *
     * @param string      $type            The type of the toast (e.g., 'success', 'error').
     * @param string      $message         The main content of the toast message.
     * @param string|null $title           Optional title for the toast.
     * @param int|null    $duration        Optional custom duration in ms.
     * @param string|null $priority        Optional priority ('high', 'normal', 'low').
     * @param bool|null   $autoDismiss     Override auto-dismiss behavior.
     * @param bool|null   $pauseOnHover    Override pause-on-hover behavior.
     * @param bool|null   $showProgressBar Override progress bar visibility.
     * @param string|null $animationPreset Optional animation preset name.
     * @param string|null $layoutPreset    Optional layout preset name.
     * @param string|null $soundAsset      Optional sound asset name.
     * @param array       $actions         Optional array of actions for the toast.
     * @param array       $customData      Optional custom data.
     *
     * @return ToastMessageDTO
     */
    function toastify(
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
        return Toastify::add( // Using the Facade with the new namespace
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

if (!function_exists('toastify_success')) {
    /**
     * Adds a 'success' type toast message.
     */
    function toastify_success(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toastify::success($message, $title, $duration);
    }
}

if (!function_exists('toastify_error')) {
    /**
     * Adds an 'error' type toast message.
     */
    function toastify_error(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toastify::error($message, $title, $duration);
    }
}

if (!function_exists('toastify_warning')) {
    /**
     * Adds a 'warning' type toast message.
     */
    function toastify_warning(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toastify::warning($message, $title, $duration);
    }
}

if (!function_exists('toastify_info')) {
    /**
     * Adds an 'info' type toast message.
     */
    function toastify_info(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toastify::info($message, $title, $duration);
    }
}

if (!function_exists('toastify_custom')) {
    /**
     * Adds a 'custom' type toast message with flexible options.
     *
     * @param string      $message The main content of the toast message.
     * @param string|null $title   Optional title for the toast.
     * @param array       $options An associative array of custom options.
     */
    function toastify_custom(string $message, ?string $title = null, array $options = []): ToastMessageDTO
    {
        return Toastify::custom($message, $title, $options);
    }
}
