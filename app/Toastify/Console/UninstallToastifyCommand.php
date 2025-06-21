<?php

declare(strict_types=1);

namespace App\Toastify\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UninstallToastifyCommand extends Command
{
    protected $signature = 'toastify:uninstall';

    protected $description = 'Uninstall Toastify: Remove published configuration, views, and assets.';

    public function handle(): int
    {
        if (!$this->confirm('Are you sure you want to uninstall Toastify? This will remove published files (config, views, assets).', false)) {
            $this->info('Toastify uninstallation cancelled.');
            return Command::INVALID;
        }

        $this->info('Uninstalling Toastify...');

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
        $viewsPath = resource_path('views/vendor/toastify');
        if (File::isDirectory($viewsPath)) {
            if ($this->confirm("Remove views directory? ({$viewsPath})", true)) {
                File::deleteDirectory($viewsPath);
                $this->info("Removed: {$viewsPath}");
            }
        } else {
            $this->comment("Views directory not found: {$viewsPath}");
        }

        // Remove assets
        $assetsPath = public_path('sounds/toastify');
        if (File::isDirectory($assetsPath)) {
            if ($this->confirm("Remove assets directory? ({$assetsPath})", true)) {
                File::deleteDirectory($assetsPath); // Be cautious with deleteDirectory
                $this->info("Removed: {$assetsPath}");
            }
        } else {
            $this->comment("Assets directory not found: {$assetsPath}");
        }

        $this->info('Toastify uninstallation process complete.');
        $this->line('Remember to remove the service provider `App\Toastify\ToastServiceProvider` from your `config/app.php` if you added it manually and it\'s not auto-discovered.');
        $this->line('Also, remove the facade alias for `Toastify` from `config/app.php`.');
        $this->line('Finally, remove the `<livewire:toastify-container />` component from your layouts.');

        return Command::SUCCESS;
    }
}
