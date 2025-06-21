<?php

declare(strict_types=1);

namespace Vsent\ToastMessages\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File; // Not strictly needed for `call`, but good for general file ops.

/**
 * Class InstallCommand
 *
 * @package VsE\ToastMessages\Console
 *
 * This Artisan command facilitates the installation of the VsE ToastMessages package.
 * It allows users to publish the package's configuration, views, and public assets (sounds).
 * It provides a guided interactive experience for the installation process.
 *
 * This version is updated to reflect the new `config/toasts.php` file name.
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'toasts:install {--force : Overwrite any existing files}'; // Updated signature

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs the VsE ToastMessages package: Publishes config, views, and assets.';

    /**
     * Execute the console command.
     *
     * This method contains the main logic for the installation process.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting VsE ToastMessages installation...');

        $force = $this->option('force');

        // 1. Publish Configuration
        if ($this->confirm('Publish configuration file (config/toasts.php)?', true)) {
            $this->call('vendor:publish', [
                '--tag' => 'toasts-config', // Updated tag
                '--force' => $force,
            ]);
            $this->info('Configuration file published to config/toasts.php');
        } else {
            $this->warn('Skipping configuration file publication.');
        }

        // 2. Publish Views
        if ($this->confirm('Publish Blade views (resources/views/vendor/toast-messages/)?', true)) {
            $this->call('vendor:publish', [
                '--tag' => 'toasts-views', // Updated tag
                '--force' => $force,
            ]);
            $this->info('Blade views published to resources/views/vendor/toast-messages/');
        } else {
            $this->warn('Skipping Blade views publication.');
        }

        // 3. Publish Public Assets (Sounds)
        if ($this->confirm('Publish public sound assets (public/vendor/toast-messages/sounds/)?', true)) {
            $this->call('vendor:publish', [
                '--tag' => 'toasts-assets', // Updated tag
                '--force' => $force,
            ]);
            $this->info('Public sound assets published to public/vendor/toast-messages/sounds/');
        } else {
            $this->warn('Skipping public sound assets publication.');
        }

        // Final instructions for the user
        $this->newLine();
        $this->comment('VsE ToastMessages installation complete!');
        $this->newLine();
        $this->comment('Next Steps:');
        $this->comment('1. Ensure your application layout includes the Livewire ToastContainer component.');
        $this->comment('   Add <livewire:toast-messages /> to your main Blade layout file (e.g., app.blade.php):');
        $this->line('   <info>    @livewireStyles</info>');
        $this->line('   <info>    @livewireScripts</info>');
        $this->line('   <info>    <livewire:toast-messages /></info>');
        $this->line('   <info>    </script></info>');
        $this->comment('2. Customize the config/toasts.php file as needed.'); // Updated file name
        $this->comment('3. Add sound files to public/vendor/toast-messages/sounds/ if you skipped asset publication.');
        $this->comment('4. You can now use the Toast facade or helper functions (e.g., `Toast::success("Your message!");` or `toast_success("Your message!");`).');

        return Command::SUCCESS;
    }
}
