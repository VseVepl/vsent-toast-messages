<?php

declare(strict_types=1);

namespace App\Toastify\Contracts;

use Illuminate\Support\Collection;
use App\Toastify\DTOs\ToastMessageDTO;

interface ToastManagerContract
{
    /**
     * Adds a new toast message to the queue.
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

    public function success(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO;

    public function error(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO;

    public function warning(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO;

    public function info(string $message, ?string $title = null, ?int $duration = null): ToastMessageDTO;

    /**
     * Adds a 'custom' type toast message with flexible options.
     *
     * @param string      $message The main content of the toast message.
     * @param string|null $title   Optional title for the toast.
     * @param array       $options An associative array of custom options.
     * @return ToastMessageDTO
     */
    public function custom(string $message, ?string $title = null, array $options = []): ToastMessageDTO;

    /**
     * Retrieves the list of toast messages to be displayed.
     *
     * @return Collection<int, ToastMessageDTO>
     */
    public function get(): Collection;

    /**
     * Clears all toast messages from the session.
     */
    public function clear(): void;

    /**
     * Marks a specific toast message as dismissed.
     *
     * @param string $id The unique ID of the toast message.
     */
    public function dismiss(string $id): void;

    /**
     * Checks if there are any active toasts.
     *
     * @return bool
     */
    public function hasToasts(): bool;
}
