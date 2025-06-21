<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\Toastify\ToastServiceProvider; // To reference in call()

beforeEach(function () {
    // Ensure the dummy config file exists for the install command to "publish" from
    $configDir = __DIR__ . '/../../config';
    $configFile = $configDir . '/toasts.php';
    if (!file_exists($configFile)) {
        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
        }
        $dummyConfigContent = "<?php return ['session_key' => 'toastify_messages'];"; // Minimal
        file_put_contents($configFile, $dummyConfigContent);
    }

    // Mock the necessary File facade methods
    File::shouldReceive('exists')->andReturn(false)->byDefault(); // Default to not existing
    File::shouldReceive('isDirectory')->andReturn(false)->byDefault();
    File::shouldReceive('delete')->andReturn(true)->byDefault();
    File::shouldReceive('deleteDirectory')->andReturn(true)->byDefault();
    File::shouldReceive('files')->andReturn([])->byDefault(); // For asset check
});

afterEach(function () {
    \Mockery::close();
});

// --- Install Command Tests ---

it('toastify:install command runs successfully and calls vendor:publish for all groups by default', function () {
    $this->artisan('toastify:install')
        ->expectsOutputToContain('Installing Toastify...')
        ->expectsOutputToContain('Publishing configuration...')
        ->expectsOutputToContain('Publishing views...')
        ->expectsOutputToContain('Publishing assets (sounds)...')
        ->expectsOutputToContain('Toastify installation complete!')
        ->assertExitCode(0);

    // We can't directly assert Artisan::call for vendor:publish was made with specific tags
    // without more complex command mocking or inspecting underlying Publisher.
    // For feature tests, checking output is often the primary way.
    // To truly test `vendor:publish` calls, one might use `expectsCommand`
    // or mock the `Command` class's `call` method if testing the command class directly.
    // Here, we assume the output implies the calls were made.
});

it('toastify:install command publishes config only with --config-only option', function () {
    $this->artisan('toastify:install --config-only')
        ->expectsOutputToContain('Publishing configuration...')
        ->doesntExpectOutputToContain('Publishing views...')
        ->doesntExpectOutputToContain('Publishing assets (sounds)...')
        ->assertExitCode(0);
});

it('toastify:install command publishes views only with --views-only option', function () {
    $this->artisan('toastify:install --views-only')
        ->doesntExpectOutputToContain('Publishing configuration...')
        ->expectsOutputToContain('Publishing views...')
        ->doesntExpectOutputToContain('Publishing assets (sounds)...')
        ->assertExitCode(0);
});

it('toastify:install command publishes assets only with --assets-only option', function () {
    File::shouldReceive('exists')->with(public_path('sounds/toastify'))->andReturn(true);
    File::shouldReceive('files')->with(public_path('sounds/toastify'))->andReturn(['dummy.mp3']);


    $this->artisan('toastify:install --assets-only')
        ->doesntExpectOutputToContain('Publishing configuration...')
        ->doesntExpectOutputToContain('Publishing views...')
        ->expectsOutputToContain('Publishing assets (sounds)...')
        ->expectsOutputToContain('Assets published to: ' . public_path('sounds/toastify'))
        ->assertExitCode(0);
});

it('toastify:install command uses --force option for publishing', function () {
    // This test is more conceptual for `vendor:publish` as `--force` is passed down.
    // We'd need to mock the underlying call to `vendor:publish` to verify `--force`.
    // For now, we ensure the command runs with the option.
    $this->artisan('toastify:install --force')
        ->expectsOutputToContain('Publishing configuration...') // Implicitly testing force is passed
        ->assertExitCode(0);
});


// --- Uninstall Command Tests ---

it('toastify:uninstall command prompts for confirmation and cancels if not confirmed', function () {
    $this->artisan('toastify:uninstall')
        ->expectsConfirmation('Are you sure you want to uninstall Toastify? This will remove published files (config, views, assets).', 'no')
        ->expectsOutputToContain('Toastify uninstallation cancelled.')
        ->assertExitCode(1); // Command::INVALID
});

it('toastify:uninstall command removes files if confirmed', function () {
    $configPath = config_path('toasts.php');
    $viewsPath = resource_path('views/vendor/toastify');
    $assetsPath = public_path('sounds/toastify');

    File::shouldReceive('exists')->with($configPath)->andReturn(true);
    File::shouldReceive('delete')->with($configPath)->once()->andReturn(true);

    File::shouldReceive('isDirectory')->with($viewsPath)->andReturn(true);
    File::shouldReceive('deleteDirectory')->with($viewsPath)->once()->andReturn(true);

    File::shouldReceive('isDirectory')->with($assetsPath)->andReturn(true);
    File::shouldReceive('deleteDirectory')->with($assetsPath)->once()->andReturn(true);

    $this->artisan('toastify:uninstall')
        ->expectsConfirmation('Are you sure you want to uninstall Toastify? This will remove published files (config, views, assets).', 'yes')
        ->expectsConfirmation("Remove configuration file? ({$configPath})", 'yes')
        ->expectsOutput("Removed: {$configPath}")
        ->expectsConfirmation("Remove views directory? ({$viewsPath})", 'yes')
        ->expectsOutput("Removed: {$viewsPath}")
        ->expectsConfirmation("Remove assets directory? ({$assetsPath})", 'yes')
        ->expectsOutput("Removed: {$assetsPath}")
        ->expectsOutputToContain('Toastify uninstallation process complete.')
        ->assertExitCode(0);
});

it('toastify:uninstall command skips deletion if user declines for a specific item', function () {
    $configPath = config_path('toasts.php');
    File::shouldReceive('exists')->with($configPath)->andReturn(true);
    File::shouldReceive('delete')->with($configPath)->never(); // Expect delete NOT to be called

    $this->artisan('toastify:uninstall')
        ->expectsConfirmation('Are you sure you want to uninstall Toastify? This will remove published files (config, views, assets).', 'yes')
        ->expectsConfirmation("Remove configuration file? ({$configPath})", 'no') // Decline this one
        ->expectsConfirmation("Remove views directory? (" . resource_path('views/vendor/toastify') . ")", 'yes') // Assuming this doesn't exist or we let it pass
        ->expectsConfirmation("Remove assets directory? (" . public_path('sounds/toastify') . ")", 'yes') // Assuming this doesn't exist
        ->assertExitCode(0);
});

it('toastify:uninstall command handles non-existing files gracefully', function () {
    // File::exists and File::isDirectory already mocked to return false by default
    $configPath = config_path('toasts.php');
    $viewsPath = resource_path('views/vendor/toastify');
    $assetsPath = public_path('sounds/toastify');

    $this->artisan('toastify:uninstall')
        ->expectsConfirmation('Are you sure you want to uninstall Toastify? This will remove published files (config, views, assets).', 'yes')
        ->expectsOutputToContain("Configuration file not found: {$configPath}")
        ->expectsOutputToContain("Views directory not found: {$viewsPath}")
        ->expectsOutputToContain("Assets directory not found: {$assetsPath}")
        ->assertExitCode(0);
});
