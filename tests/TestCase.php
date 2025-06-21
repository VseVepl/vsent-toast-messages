<?php

declare(strict_types=1);

namespace Vsent\LaravelToastify\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Vsent\LaravelToastify\ToastServiceProvider;
use Livewire\LivewireServiceProvider; // Required for Livewire component testing

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // You can add model factories an_DIR_ . '/../database/factories');
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class, // Need to register Livewire's provider for its components
            ToastServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup a default config for 'toasts' if your package relies on it being present.
        // This would be the package's default config.
        // The actual config file (config/toasts.php) should be loaded by the ServiceProvider itself.
        // However, for testing, explicitly setting some defaults here can be useful
        // or ensuring the SP loads its default config correctly into the test app's config.
        $defaultToastConfig = require __DIR__ . '/../config/toasts.php'; // Load package default config
        $app['config']->set('toasts', $defaultToastConfig);

        // If your facade is not auto-discovered or you want to be explicit:
        // $app['config']->set('app.aliases.Toastify', \Vsent\LaravelToastify\Facades\Toastify::class);
    }
}
