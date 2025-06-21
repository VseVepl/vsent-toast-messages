<?php

declare(strict_types=1);

namespace Vsent\LaravelToastify\Console; // Updated Namespace

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UninstallCommand extends Command // Renamed Class
{
    protected $signature = 'toastify:uninstall';

    protected $description = 'Uninstall Laravel Toastify: Remove published configuration, views, and assets.'; // Updated Description

    public function handle(): int
    {
        if (!$this->confirm('Are you sure you want to uninstall Laravel Toastify? This will remove published files (config, views, assets).', false)) {
            $this->info('Laravel Toastify uninstallation cancelled.');
            return Command::INVALID;
        }

        $this->info('Uninstalling Laravel Toastify...');

        // Remove configuration
        $configPath = config_path('toasts.php');
        if (File::exists($configPath)) {
            if ($this->confirm("Remove configuration file? ({$configPath})", true)) {
                File::delete($configPath);
                $this->info("Removed: {$configPath}");
            }
        } else {
            $this->comment("Configuration file not found: {$configPath}");
        }

        // Remove views
        $viewsPath = resource_path('views/vendor/laravel-toastify'); // Updated Path
        if (File::isDirectory($viewsPath)) {
            if ($this->confirm("Remove views directory? ({$viewsPath})", true)) {
                File::deleteDirectory($viewsPath);
                $this->info("Removed: {$viewsPath}");
            }
        } else {
            $this->comment("Views directory not found: {$viewsPath}");
        }

        // Remove assets
        $assetsPath = public_path('vendor/vsent/laravel-toastify/sounds'); // Updated Path
        if (File::isDirectory($assetsPath)) {
            if ($this->confirm("Remove assets directory? ({$assetsPath})", true)) {
                File::deleteDirectory($assetsPath);
                $this->info("Removed: {$assetsPath}");
            }
        } else {
            $this->comment("Assets directory not found: {$assetsPath}");
        }

        $this->info('Laravel Toastify uninstallation process complete.');
        $this->line('Remember to remove the service provider `Vsent\LaravelToastify\ToastServiceProvider` from your `config/app.php` if it was manually registered and not auto-discovered.');
        $this->line('Also, remove the facade alias for `Toastify` from `config/app.php` if manually added.');
        $this->line('Finally, remove the `<livewire:toastify-container />` component from your layouts and `composer remove vsent/laravel-toastify`.');

        return Command::SUCCESS;
    }
}
