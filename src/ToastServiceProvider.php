<?php

declare(strict_types=1);

namespace Vsent\ToastMessages;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Psr\Log\LoggerInterface; // Import LoggerInterface for dependency injection
use Vsent\ToastMessages\Console\InstallCommand;
use Vsent\ToastMessages\Console\UninstallCommand;
use Vsent\ToastMessages\Contracts\ToastManagerContract;
use Vsent\ToastMessages\Facades\Toast;
use Vsent\ToastMessages\Http\Livewire\ToastContainer; // This will be created in the next phase
use Illuminate\Session\Store as SessionStore; // Import SessionStore for type hinting clarity

/**
 * Class ToastServiceProvider
 *
 * @package VsE\ToastMessages
 *
 * This Service Provider is responsible for registering the package's services,
 * loading its configuration, views, and global helpers, and publishing assets.
 * It's the entry point for integrating the VsE ToastMessages package into a Laravel application.
 *
 * This version is updated to reflect the new `config/toasts.php` file name and structure.
 */
class ToastServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This method is where the package's services are bound into the service container.
     * It's called very early in the request lifecycle.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package configuration with the application's configuration.
        // This allows users to override default settings by publishing the config file.
        // The config file name is now 'toasts.php'.
        $this->mergeConfigFrom(
            __DIR__ . '/../config/toasts.php',
            'toasts' // The key under which the config will be accessible (e.g., config('toasts.session_key'))
        );

        // Bind the ToastManagerContract to its concrete implementation, ToastManager.
        // It's registered as a singleton, meaning only one instance will be created and reused throughout the application.
        // We now inject the LoggerInterface for robust error logging within ToastManager.
        $this->app->singleton(ToastManagerContract::class, function ($app) {
            return new ToastManager(
                // Correctly resolve the Illuminate\Session\Store instance
                $app->make(SessionStore::class), // Use make to get the specific Store instance
                $app['config'],
                $app['events'],
                $app->make(LoggerInterface::class) // Inject the default Laravel logger
            );
        });

        // Register the 'toast' facade accessor.
        // This makes `Toast::method()` calls possible, leveraging the bound ToastManagerContract.
        $this->app->alias(ToastManagerContract::class, 'toast');

        // Load global helper functions.
        // These provide convenient, short-hand functions like `toast_success()` for quick usage.
        $this->loadHelpers();
    }

    /**
     * Bootstrap any application services.
     *
     * This method is called after all other service providers have been registered,
     * meaning all other services are available. It's ideal for publishing assets,
     * registering views, and setting up commands.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration file
        // Users can run `php artisan vendor:publish --tag=toasts-config`
        // The config file name is now 'toasts.php'.
        $this->publishes([
            __DIR__ . '/../config/toasts.php' => config_path('toasts.php'),
        ], 'toasts-config');

        // Publish views
        // Users can run `php artisan vendor:publish --tag=toasts-views`
        // Views will be published to resources/views/vendor/toast-messages/
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/toast-messages'),
        ], 'toasts-views');

        // Publish public assets (sounds)
        // Users can run `php artisan vendor:publish --tag=toasts-assets`
        // Assets will be published to public/vendor/toast-messages/sounds/
        $this->publishes([
            __DIR__ . '/../public/sounds' => public_path('vendor/toast-messages/sounds'),
        ], 'toasts-assets');

        // Load package views
        // This makes views accessible via `view('toast-messages::container')` etc.
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'toast-messages');

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                UninstallCommand::class,
            ]);
        }

        // Register Livewire component
        // This is done in the boot method to ensure Livewire is fully loaded.
        // The component name is 'toast-messages' and it maps to the ToastContainer class.
        // This is a placeholder for now, as ToastContainer.php is in the next phase.
        Livewire::component('toast-messages', ToastContainer::class);
    }

    /**
     * Loads the global helper functions file.
     *
     * This ensures that functions like `toast_success()` are available globally.
     * It includes a check to prevent redeclaration errors if the file is included elsewhere.
     *
     * @return void
     */
    protected function loadHelpers(): void
    {
        // Ensure helpers.php is loaded only once.
        if (file_exists($file = __DIR__ . '/helpers.php')) {
            require_once $file;
        }
    }
}
