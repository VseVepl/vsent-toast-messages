<?php

namespace Vsent\LaravelToastify\Tests\Feature; // Updated Namespace

use Vsent\LaravelToastify\Contracts\ToastManagerContract; // Updated Namespace
use Vsent\LaravelToastify\DTOs\ToastMessageDTO;         // Updated Namespace
use Vsent\LaravelToastify\Facades\Toastify;             // Updated Namespace
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;

// For feature tests, Laravel's bootstrap process should make helpers available if they are
// correctly autoloaded by the package's composer.json (which they will be via "files" autoload).
// So, no explicit require_once should be needed here.

beforeEach(function () {
    // Mock the ToastManagerContract implementation
    $this->toastManagerMock = Mockery::mock(ToastManagerContract::class);
    // Replace the service container binding with our mock
    // This binding key 'toastify.manager' must match what the Facade uses
    // and what the ServiceProvider registers as an alias.
    $this->app->instance('toastify.manager', $this->toastManagerMock);

    // Minimal config for DTO creation in mock returns, or if helpers rely on config defaults
    // This assumes the main app's config would have this after package is installed and config published/merged.
    Config::set('toasts.types.defaults.duration', 5000);
});

afterEach(function () {
    Mockery::close();
});

// --- Facade Tests ---

it('Toastify facade calls "add" on the manager', function () {
    $this->toastManagerMock
        ->shouldReceive('add')
        ->once()
        ->with('success', 'Facade add test', 'Title', 3000, 'high', true, true, true, 'slide', 'compact', 'sound.mp3', ['action1'], ['custom1'])
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    Toastify::add('success', 'Facade add test', 'Title', 3000, 'high', true, true, true, 'slide', 'compact', 'sound.mp3', ['action1'], ['custom1']);
});

it('Toastify facade calls "success" on the manager', function () {
    $this->toastManagerMock
        ->shouldReceive('success')
        ->once()
        ->with('Facade success test', 'Success Title', 5000)
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    Toastify::success('Facade success test', 'Success Title', 5000);
});

it('Toastify facade calls "error" on the manager', function () {
    $this->toastManagerMock
        ->shouldReceive('error')
        ->once()
        ->with('Facade error test', null, null) // Test with default duration/title
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    Toastify::error('Facade error test');
});

it('Toastify facade calls "warning" on the manager', function () {
    $this->toastManagerMock
        ->shouldReceive('warning')
        ->once()
        ->with('Facade warning test', 'Warn Title', null)
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    Toastify::warning('Facade warning test', 'Warn Title');
});

it('Toastify facade calls "info" on the manager', function () {
    $this->toastManagerMock
        ->shouldReceive('info')
        ->once()
        ->with('Facade info test', null, 7000)
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    Toastify::info('Facade info test', null, 7000);
});

it('Toastify facade calls "custom" on the manager', function () {
    $options = ['type' => 'my_custom', 'priority' => 'low'];
    $this->toastManagerMock
        ->shouldReceive('custom')
        ->once()
        ->with('Facade custom test', 'Custom Title', $options)
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    Toastify::custom('Facade custom test', 'Custom Title', $options);
});

it('Toastify facade calls "get" on the manager', function () {
    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([]));

    Toastify::get();
});

it('Toastify facade calls "clear" on the manager', function () {
    $this->toastManagerMock
        ->shouldReceive('clear')
        ->once();

    Toastify::clear();
});

it('Toastify facade calls "dismiss" on the manager', function () {
    $this->toastManagerMock
        ->shouldReceive('dismiss')
        ->once()
        ->with('toast-id-to-dismiss');

    Toastify::dismiss('toast-id-to-dismiss');
});

it('Toastify facade calls "hasToasts" on the manager', function () {
    $this->toastManagerMock
        ->shouldReceive('hasToasts')
        ->once()
        ->andReturn(true);

    expect(Toastify::hasToasts())->toBeTrue();
});


// --- Helper Function Tests ---

it('toastify() helper calls "add" on the manager via facade', function () {
    $this->toastManagerMock
        ->shouldReceive('add')
        ->once()
        ->with('info', 'Helper add test', null, 1000, null, null, null, null, null, null, null, [], [])
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    toastify('info', 'Helper add test', null, 1000);
});

it('toastify_success() helper calls "success" on the manager via facade', function () {
    $this->toastManagerMock
        ->shouldReceive('success')
        ->once()
        ->with('Helper success test', 'Helper Title', 3000)
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    toastify_success('Helper success test', 'Helper Title', 3000);
});

it('toastify_error() helper calls "error" on the manager via facade', function () {
    $this->toastManagerMock
        ->shouldReceive('error')
        ->once()
        ->with('Helper error test', null, null)
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    toastify_error('Helper error test');
});

it('toastify_warning() helper calls "warning" on the manager via facade', function () {
    $this->toastManagerMock
        ->shouldReceive('warning')
        ->once()
        ->with('Helper warning test', 'Warn', null)
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    toastify_warning('Helper warning test', 'Warn');
});

it('toastify_info() helper calls "info" on the manager via facade', function () {
    $this->toastManagerMock
        ->shouldReceive('info')
        ->once()
        ->with('Helper info test', null, 1234)
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    toastify_info('Helper info test', null, 1234);
});

it('toastify_custom() helper calls "custom" on the manager via facade', function () {
    $options = ['type' => 'another_custom', 'priority' => 'high'];
    $this->toastManagerMock
        ->shouldReceive('custom')
        ->once()
        ->with('Helper custom test', 'Custom', $options)
        ->andReturn(Mockery::mock(ToastMessageDTO::class));

    toastify_custom('Helper custom test', 'Custom', $options);
});
