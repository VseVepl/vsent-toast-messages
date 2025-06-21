<?php

declare(strict_types=1);

namespace App\Toastify\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallToastifyCommand extends Command
{
    protected $signature = 'toastify:install
                            {--force : Overwrite any existing files}
                            {--config-only : Publish only the configuration file}
                            {--views-only : Publish only the Blade views}
                            {--assets-only : Publish only the sound assets}';

    protected $description = 'Install Toastify: Publish configuration, views, and assets.';

    public function handle(): int
    {
        $this->info('Installing Toastify...');

        $force = $this->option('force');
        $configOnly = $this->option('config-only');
        $viewsOnly = $this->option('views-only');
        $assetsOnly = $this->option('assets-only');

        $publishAll = !$configOnly && !$viewsOnly && !$assetsOnly;

        if ($publishAll || $configOnly) {
            $this->comment('Publishing configuration...');
            $this->call('vendor:publish', [
                '--provider' => \App\Toastify\ToastServiceProvider::class,
                '--tag' => 'toastify-config',
                '--force' => $force,
            ]);
        }

        if ($publishAll || $viewsOnly) {
            $this->comment('Publishing views...');
            $this->call('vendor:publish', [
                '--provider' => \App\Toastify\ToastServiceProvider::class,
                '--tag' => 'toastify-views',
                '--force' => $force,
            ]);
        }

        if ($publishAll || $assetsOnly) {
            $this->comment('Publishing assets (sounds)...');
            $this->call('vendor:publish', [
                '--provider' => \App\Toastify\ToastServiceProvider::class,
                '--tag' => 'toastify-assets',
                '--force' => $force,
            ]);
             // Check if the directory was created and if it has content (basic check)
            $assetsPath = public_path('sounds/toastify');
            if (File::exists($assetsPath) && count(File::files($assetsPath)) > 0) {
                $this->info('Assets published to: ' . $assetsPath);
            } elseif (File::exists($assetsPath)) {
                $this->warn('Assets directory created but might be empty: ' . $assetsPath . '. Ensure source assets exist in package.');
            } else {
                 $this->warn('Assets directory not found: ' . $assetsPath . '. This might be okay if there are no assets to publish or an issue with paths.');
            }
        }

        $this->info('Toastify installation complete!');
        $this->line('Please ensure you have added the Livewire component to your layout:');
        $this->comment('<livewire:toastify-container />');
        $this->line('And ensure your `config/toasts.php` is configured, and `App\Toastify\ToastServiceProvider` is registered if not auto-discovered.');

        return Command::SUCCESS;
    }
}
