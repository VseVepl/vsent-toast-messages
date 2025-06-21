<?php

declare(strict_types=1);

namespace Vsent\LaravelToastify\Traits; // Updated Namespace

use Vsent\LaravelToastify\Facades\Toastify; // Updated Namespace for Facade
use Vsent\LaravelToastify\DTOs\ToastMessageDTO; // Updated Namespace for DTO

/**
 * Trait WithToastMessages
 *
 * Provides convenient methods for Livewire components or any other
 * class to easily add toast notifications using the Toastify system.
 */
trait WithToastMessages
{
    /**
     * Adds a new toast message to the queue.
     *
     * @param string      $type            The type of the toast.
     * @param string      $message         The main content of the toast message.
     * @param string|null $title           Optional title for the toast.
     * @param int|null    $duration        Optional custom duration in milliseconds.
     * @param string|null $priority        Optional priority.
     * @param bool|null   $autoDismiss     Optional override for auto-dismiss.
     * @param bool|null   $pauseOnHover    Optional override for pause-on-hover.
     * @param bool|null   $showProgressBar Optional override for progress bar.
     * @param string|null $animationPreset Optional animation preset.
     * @param string|null $layoutPreset    Optional layout preset.
     * @param string|null $soundAsset      Optional sound asset.
     * @param array       $actions         Optional array of actions.
     * @param array       $customData      Optional custom data.
     *
     * @return ToastMessageDTO
     */
    public function addToast(
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
        return Toastify::add( // Using the new Facade name
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

    /**
     * Adds a 'success' type toast message.
     */
    public function addSuccessToast(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toastify::success($message, $title, $duration);
    }

    /**
     * Adds an 'error' type toast message.
     */
    public function addErrorToast(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toastify::error($message, $title, $duration);
    }

    /**
     * Adds a 'warning' type toast message.
     */
    public function addWarningToast(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toastify::warning($message, $title, $duration);
    }

    /**
     * Adds an 'info' type toast message.
     */
    public function addInfoToast(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toastify::info($message, $title, $duration);
    }

    /**
     * Adds a custom toast message with flexible options.
     */
    public function addCustomToast(string $message, ?string $title = null, array $options = []): ToastMessageDTO
    {
        return Toastify::custom($message, $title, $options);
    }
}
