<?php

declare(strict_types=1);

namespace Vsent\ToastMessages\Contracts;

use Illuminate\Support\Collection;
use Vsent\ToastMessages\DTOs\ToastMessageDTO;

/**
 * Interface ToastManagerContract
 *
 * @package VsE\ToastMessages\Contracts
 *
 * This interface defines the contract for the ToastManager.
 * It specifies the public API for managing toast notifications,
 * promoting loose coupling and allowing for alternative implementations
 * if required in the future.
 *
 * This version of the contract is updated to match the expanded 'add' method
 * signature in the concrete ToastManager implementation.
 */
interface ToastManagerContract
{
    /**
     * Adds a new toast message to the queue.
     *
     * This is the primary method for adding toasts. It allows for full customization
     * of the toast's properties, reflecting the comprehensive configuration.
     *
     * @param string      $type            The type of the toast (e.g., 'success', 'error', 'warning', 'info', 'custom', 'critical').
     * @param string      $message         The main content of the toast message.
     * @param string|null $title           Optional title for the toast.
     * @param int|null    $duration        Optional custom duration for this toast in milliseconds.
     * @param string|null $priority        Optional priority for this toast ('high', 'normal', 'low').
     * @param bool|null   $autoDismiss     Optional override for auto-dismiss behavior.
     * @param bool|null   $pauseOnHover    Optional override for pause-on-hover behavior.
     * @param bool|null   $showProgressBar Optional override for progress bar visibility.
     * @param string|null $animationPreset Optional animation preset to use (e.g., 'fade', 'slide_from_bottom').
     * @param string|null $layoutPreset    Optional layout preset to use (e.g., 'default', 'with_actions').
     * @param string|null $soundAsset      Optional sound asset name to use (e.g., 'success', 'error' mapping to sound files).
     * @param array       $actions         Optional array of actions (buttons) for the toast.
     * @param array       $customData      Optional array of any additional custom data to pass with the toast.
     *
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function add(
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
    ): ToastMessageDTO;

    /**
     * Adds a 'success' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function success(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO;

    /**
     * Adds an 'error' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function error(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO;

    /**
     * Adds a 'warning' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function warning(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO;

    /**
     * Adds an 'info' type toast message.
     *
     * @param string      $message  The main content of the toast message.
     * @param string|null $title    Optional title for the toast.
     * @param int|null    $duration Optional custom duration for this toast in milliseconds.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function info(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO;

    /**
     * Adds a 'custom' type toast message with more flexible options.
     *
     * This method allows for providing a custom type name and an array of options
     * to define the toast's appearance and behavior, without strictly adhering
     * to predefined types in the configuration.
     *
     * @param string      $message The main content of the toast message.
     * @param string|null $title   Optional title for the toast.
     * @param array       $options An associative array of custom options to apply to the toast.
     * Keys can include 'duration', 'priority', 'auto_dismiss', 'pause_on_hover',
     * 'show_progress_bar', 'animation_preset', 'layout_preset', 'sound_asset',
     * 'actions', 'custom_data', and any 'types.{type}' specific keys like 'bg', 'text_color', 'icon'.
     * @return ToastMessageDTO The DTO of the newly created toast message.
     */
    public function custom(string $message, ?string $title = null, array $options = []): ToastMessageDTO;

    /**
     * Retrieves the list of toast messages that are currently active and should be displayed.
     *
     * This method applies the global and priority display limits, sorts toasts by priority,
     * and filters out any dismissed or expired toasts.
     *
     * @return Collection<int, ToastMessageDTO> A collection of ToastMessageDTO objects ready for display.
     */
    public function get(): Collection;

    /**
     * Clears all toast messages from the session.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Marks a specific toast message as dismissed.
     *
     * This typically happens when a user clicks a close button on a toast.
     * The toast will then be filtered out from subsequent `get()` calls.
     *
     * @param string $id The unique ID of the toast message to dismiss.
     * @return void
     */
    public function dismiss(string $id): void;

    /**
     * Checks if there are any active (non-dismissed, non-expired, and within limits) toasts.
     *
     * @return bool True if there are toasts to display, false otherwise.
     */
    public function hasToasts(): bool;
}
