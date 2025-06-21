<?php

declare(strict_types=1);

namespace Vsent\ToastMessages\Traits;

use Vsent\ToastMessages\DTOs\ToastMessageDTO;
use Vsent\ToastMessages\Facades\Toast;

/**
 * Trait WithToastMessages
 *
 * @package VsE\ToastMessages\Traits
 *
 * This trait provides convenient methods for Livewire components or any other
 * class to easily add toast notifications. It leverages the `Toast` facade
 * internally, offering a cleaner API for interacting with the toast system.
 */
trait WithToastMessages
{
    /**
     * Adds a new toast message to the queue.
     *
     * This method directly wraps the `Toast::add()` facade call, allowing for
     * granular control over toast properties.
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

    /**
     * Adds a 'success' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function addSuccessToast(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toast::success($message, $title, $duration);
    }

    /**
     * Adds an 'error' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function addErrorToast(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toast::error($message, $title, $duration);
    }

    /**
     * Adds a 'warning' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function addWarningToast(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toast::warning($message, $title, $duration);
    }

    /**
     * Adds an 'info' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function addInfoToast(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO
    {
        return Toast::info($message, $title, $duration);
    }

    /**
     * Adds a custom toast message with flexible options.
     *
     * This method allows for defining a toast with specific styling and behavior
     * that might not directly map to predefined types in the configuration.
     *
     * @param string      $message The main content of the toast message.
     * @param string|null $title   Optional title for the toast.
     * @param array       $options An associative array of custom options.
     * Refer to `ToastManager::custom()` for supported keys.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function addCustomToast(string $message, ?string $title = null, array $options = []): ToastMessageDTO
    {
        return Toast::custom($message, $title, $options);
    }
}
