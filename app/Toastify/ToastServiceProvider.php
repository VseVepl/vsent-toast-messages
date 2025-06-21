<?php

declare(strict_types=1);

namespace App\Toastify;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use Livewire\Livewire;
use App\Toastify\Contracts\ToastManagerContract;
use App\Toastify\Http\Livewire\ToastContainer;
use App\Toastify\Console\InstallToastifyCommand;
use App\Toastify\Console\UninstallToastifyCommand;

class ToastServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/toasts.php', // Path to the primary config file
            'toasts'
        );

        // Bind ToastManagerContract to ToastManager implementation
        $this->app->singleton(ToastManagerContract::class, function ($app) {
            return new ToastManager(
                $app['session.store'], // More specific binding for session store
                $app['config'],
                $app['events'],
                $app['log'] // Default logger
            );
        });

        // Alias for the facade
        $this->app->alias(ToastManagerContract::class, 'toastify.manager');

        // Helpers are now loaded via composer.json's "files" autoload.
        // If you still need to conditionally load them or prefer this way:
        // $this->loadHelpers();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views/vendor/toastify', 'toastify');

        // Register Livewire component
        Livewire::component('toastify-container', ToastContainer::class);

        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../../config/toasts.php' => config_path('toasts.php'),
            ], 'toastify-config');

            // Publish views
            $this->publishes([
                __DIR__ . '/../../resources/views/vendor/toastify' => resource_path('views/vendor/toastify'),
            ], 'toastify-views');

            // Publish sound assets
            // Assuming you'll create a 'resources/assets/sounds' directory in your package structure
            // and want them published to 'public/sounds/toastify/'
            $this->publishes([
                __DIR__ . '/../../resources/assets/sounds' => public_path('sounds/toastify'),
            ], 'toastify-assets');

            // Register commands
            $this->commands([
                InstallToastifyCommand::class,
                UninstallToastifyCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ToastManagerContract::class,
            'toastify.manager',
        ];
    }
}
