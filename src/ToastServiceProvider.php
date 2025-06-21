<?php

declare(strict_types=1);

namespace Vsent\LaravelToastify; // Updated Namespace

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider; // For optimized loading
use Livewire\Livewire;
use Vsent\LaravelToastify\Contracts\ToastManagerContract; // Updated Namespace
use Vsent\LaravelToastify\Http\Livewire\ToastContainer;   // Updated Namespace
use Vsent\LaravelToastify\Console\InstallCommand;         // Updated Namespace
use Vsent\LaravelToastify\Console\UninstallCommand;       // Updated Namespace

class ToastServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Default package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/toasts.php', // Path relative to this file in the package
            'toasts' // Key under which the config will be accessible (config('toasts.xyz'))
        );

        // Bind the ToastManagerContract to its concrete implementation
        $this->app->singleton(ToastManagerContract::class, function ($app) {
            return new ToastManager(
                $app['session.store'], // Or $app->make(\Illuminate\Session\Store::class)
                $app['config'],        // Or $app->make(\Illuminate\Config\Repository::class)
                $app['events'],        // Or $app->make(\Illuminate\Contracts\Events\Dispatcher::class)
                $app['log']            // Or $app->make(\Psr\Log\LoggerInterface::class)
            );
        });

        // Register an alias for the manager if developers want to resolve it directly
        // This is also used by the Facade if getFacadeAccessor returns this key.
        $this->app->alias(ToastManagerContract::class, 'toastify.manager');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load package views, namespaced to 'laravel-toastify'
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-toastify');

        // Register the Livewire component
        // The first parameter is the tag used in Blade (<livewire:laravel-toastify-container />)
        Livewire::component('laravel-toastify-container', ToastContainer::class);

        // Registering commands and publishable assets only if running in console
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/toasts.php' => config_path('toasts.php'),
        ], 'toastify-config'); // Tag for selective publishing

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laravel-toastify'),
        ], 'toastify-views'); // Tag for selective publishing

        // Publish sound assets
        // Source: your-package/resources/assets/sounds
        // Destination: public/vendor/vsent/laravel-toastify/sounds
        $this->publishes([
            __DIR__ . '/../resources/assets/sounds' => public_path('vendor/vsent/laravel-toastify/sounds'),
        ], 'toastify-assets'); // Tag for selective publishing

        // Register Artisan commands
        $this->commands([
            InstallCommand::class,
            UninstallCommand::class,
        ]);
    }

    /**
     * Get the services provided by the provider.
     * For DeferrableProvider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ToastManagerContract::class,
            'toastify.manager', // The alias we registered
        ];
    }
}
