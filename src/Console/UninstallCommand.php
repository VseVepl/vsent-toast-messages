<?php

declare(strict_types=1);

namespace Vsent\ToastMessages\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Class UninstallCommand
 *
 * @package VsE\ToastMessages\Console
 *
 * This Artisan command facilitates the clean uninstallation of the VsE ToastMessages package.
 * It removes the published configuration, views, and public assets (sounds).
 * It provides a guided interactive experience for the uninstallation process.
 *
 * This version is updated to reflect the new `config/toasts.php` file name.
 */
class UninstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'toasts:uninstall'; // Updated signature

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstalls the VsE ToastMessages package: Removes published config, views, and assets.';

    /**
     * Execute the console command.
     *
     * This method contains the main logic for the uninstallation process.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting VsE ToastMessages uninstallation...');

        if (!$this->confirm('Are you sure you want to uninstall VsE ToastMessages and remove ALL published files? This action cannot be undone.', false)) {
            $this->info('Uninstallation cancelled.');
            return Command::CANCEL;
        }

        // 1. Remove Configuration
        $configPath = config_path('toasts.php'); // Updated path
        if (File::exists($configPath)) {
            if ($this->confirm(sprintf('Remove config file: %s?', $configPath), true)) {
                File::delete($configPath);
                $this->info('Configuration file removed.');
            } else {
                $this->warn('Skipping config file removal.');
            }
        } else {
            $this->comment('Config file not found, skipping removal.');
        }

        // 2. Remove Views
        $viewsPath = resource_path('views/vendor/toast-messages');
        if (File::isDirectory($viewsPath)) {
            if ($this->confirm(sprintf('Remove views directory: %s?', $viewsPath), true)) {
                File::deleteDirectory($viewsPath);
                $this->info('Views directory removed.');
            } else {
                $this->warn('Skipping views directory removal.');
            }
        } else {
            $this->comment('Views directory not found, skipping removal.');
        }

        // 3. Remove Public Assets (Sounds)
        $assetsPath = public_path('vendor/toast-messages/sounds');
        if (File::isDirectory($assetsPath)) {
            if ($this->confirm(sprintf('Remove public assets directory: %s?', $assetsPath), true)) {
                File::deleteDirectory($assetsPath);
                $this->info('Public assets directory removed.');
            } else {
                $this->warn('Skipping public assets directory removal.');
            }
        } else {
            $this->comment('Public assets directory not found, skipping removal.');
        }

        $this->newLine();
        $this->comment('VsE ToastMessages uninstallation complete!');
        $this->newLine();
        $this->warn('Remember to remove `<livewire:toast-messages />` from your Blade layouts and `vse/toast-messages` from your composer.json.');

        return Command::SUCCESS;
    }
}
