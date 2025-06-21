<?php

declare(strict_types=1);

namespace Vsent\LaravelToastify\Console; // Updated Namespace

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Vsent\LaravelToastify\ToastServiceProvider; // Updated Namespace

class InstallCommand extends Command // Renamed Class
{
    protected $signature = 'toastify:install
                            {--force : Overwrite any existing files}
                            {--config-only : Publish only the configuration file}
                            {--views-only : Publish only the Blade views}
                            {--assets-only : Publish only the sound assets}';

    protected $description = 'Install Laravel Toastify: Publish configuration, views, and assets.'; // Updated Description

    public function handle(): int
    {
        $this->info('Installing Laravel Toastify...'); // Updated Name

        $force = $this->option('force');
        $configOnly = $this->option('config-only');
        $viewsOnly = $this->option('views-only');
        $assetsOnly = $this->option('assets-only');

        $publishAll = !$configOnly && !$viewsOnly && !$assetsOnly;

        if ($publishAll || $configOnly) {
            $this->comment('Publishing configuration...');
            $this->call('vendor:publish', [
                '--provider' => ToastServiceProvider::class, // Updated Class Reference
                '--tag' => 'toastify-config',
                '--force' => $force,
            ]);
        }

        if ($publishAll || $viewsOnly) {
            $this->comment('Publishing views...');
            $this->call('vendor:publish', [
                '--provider' => ToastServiceProvider::class, // Updated Class Reference
                '--tag' => 'toastify-views',
                '--force' => $force,
            ]);
        }

        if ($publishAll || $assetsOnly) {
            $this->comment('Publishing assets (sounds)...');
            $this->call('vendor:publish', [
                '--provider' => ToastServiceProvider::class, // Updated Class Reference
                '--tag' => 'toastify-assets',
                '--force' => $force,
            ]);
             // Path for published assets for the package `vsent/laravel-toastify`
            $assetsPath = public_path('vendor/vsent/laravel-toastify/sounds');
            if (File::exists($assetsPath) && count(File::files($assetsPath)) > 0) {
                $this->info('Assets published to: ' . $assetsPath);
            } elseif (File::exists($assetsPath)) {
                $this->warn('Assets directory created but might be empty: ' . $assetsPath . '. Ensure source assets exist in package `resources/assets/sounds`.');
            } else {
                 $this->warn('Assets directory not found: ' . $assetsPath . '. This might be okay if there are no assets to publish or an issue with paths in the ServiceProvider publish command.');
            }
        }

        $this->info('Laravel Toastify installation complete!');
        $this->line('Please ensure you have added the Livewire component to your layout:');
        $this->comment('<livewire:toastify-container />'); // Component name might need to match what's in ServiceProvider
        $this->line('And ensure your `config/toasts.php` is configured. The ServiceProvider should be auto-discovered.');

        return Command::SUCCESS;
    }
}
