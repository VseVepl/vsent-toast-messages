<?php

declare(strict_types=1);

namespace App\Toastify\Facades;

use Illuminate\Support\Facades\Facade;
use App\Toastify\Contracts\ToastManagerContract; // For @method phpdoc
use App\Toastify\DTOs\ToastMessageDTO; // For @method phpdoc
use Illuminate\Support\Collection; // For @method phpdoc

/**
 * @method static ToastMessageDTO add(string $type, string $message, ?string $title = null, ?int $duration = null, ?string $priority = null, ?bool $autoDismiss = null, ?bool $pauseOnHover = null, ?bool $showProgressBar = null, ?string $animationPreset = null, ?string $layoutPreset = null, ?string $soundAsset = null, array $actions = [], array $customData = [])
 * @method static ToastMessageDTO success(string $message, ?string $title = null, ?int $duration = null)
 * @method static ToastMessageDTO error(string $message, ?string $title = null, ?int $duration = null)
 * @method static ToastMessageDTO warning(string $message, ?string $title = null, ?int $duration = null)
 * @method static ToastMessageDTO info(string $message, ?string $title = null, ?int $duration = null)
 * @method static ToastMessageDTO custom(string $message, ?string $title = null, array $options = [])
 * @method static Collection<int, ToastMessageDTO> get()
 * @method static void clear()
 * @method static void dismiss(string $id)
 * @method static bool hasToasts()
 *
 * @see \App\Toastify\ToastManager
 */
class Toastify extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        // This should match the alias used in ToastServiceProvider register method
        return 'toastify.manager';
        // Alternatively, if not using an alias and binding ToastManagerContract directly:
        // return ToastManagerContract::class;
        // For this implementation, 'toastify.manager' was used in the service provider.
    }
}
