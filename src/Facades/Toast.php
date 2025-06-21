<?php

declare(strict_types=1);

namespace Vsent\ToastMessages\Facades;

use Illuminate\Support\Facades\Facade;
use Vsent\ToastMessages\Contracts\ToastManagerContract;
use Vsent\ToastMessages\DTOs\ToastMessageDTO;

/**
 * Class Toast
 *
 * @package VsE\ToastMessages\Facades
 *
 * @method static ToastMessageDTO add(string $type, string $message, ?string $title = null, ?int $duration = null, ?string $priority = null, ?bool $autoDismiss = null, ?bool $pauseOnHover = null, ?bool $showProgressBar = null, ?string $animationPreset = null, ?string $layoutPreset = null, ?string $soundAsset = null, array $actions = [], array $customData = [])
 * @method static ToastMessageDTO success(string $message, ?string $title = null, ?int $duration = null)
 * @method static ToastMessageDTO error(string $message, ?string $title = null, ?int $duration = null)
 * @method static ToastMessageDTO warning(string $message, ?string $title = null, ?int $duration = null)
 * @method static ToastMessageDTO info(string $message, ?string $title = null, ?int $duration = null)
 * @method static ToastMessageDTO custom(string $message, ?string $title = null, array $options = [])
 * @method static \Illuminate\Support\Collection<int, ToastMessageDTO> get()
 * @method static void clear()
 * @method static void dismiss(string $id)
 * @method static bool hasToasts()
 *
 * @see \VsE\ToastMessages\ToastManager
 * @see \VsE\ToastMessages\Contracts\ToastManagerContract
 *
 * This Facade provides a static-like interface to the ToastManager service.
 * It allows for convenient access to toast management methods from anywhere
 * within the Laravel application without needing to inject the ToastManager
 * instance directly.
 */
class Toast extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * This method defines the key by which the ToastManagerContract is bound
     * in the Laravel service container (as aliased in ToastServiceProvider).
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return ToastManagerContract::class;
    }
}
