<?php

namespace Vsent\LaravelToastify\Tests\Feature; // Updated Namespace

use Vsent\LaravelToastify\Contracts\ToastManagerContract; // Updated Namespace
use Vsent\LaravelToastify\DTOs\ToastMessageDTO;         // Updated Namespace
use Vsent\LaravelToastify\Http\Livewire\ToastContainer; // Updated Namespace
// No need to use Vsent\LaravelToastify\ToastManager directly in this feature test, contract is enough
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire; // Livewire test helper
use Mockery;
use Carbon\CarbonImmutable;

// Helper to create DTOs for testing (can be kept global for tests or namespaced)
// To avoid conflicts if other tests define it, let's make it specific or ensure it's only defined once.
// For this refactor, assuming it's fine as is if this is the only definition.
// Or rename: function create_toastify_test_dto(...)
function create_test_dto_for_container(string $id, string $message, array $overrides = []): ToastMessageDTO
{
    $defaults = [
        'id' => $id,
        'type' => 'info',
        'message' => $message,
        'title' => null,
        'priority' => 'normal',
        'duration' => 5000,
        'position' => 'top-right',
        'bgColor' => 'bg-blue-500',
        'textColor' => 'text-white',
        'icon' => '',
        'sound' => null,
        'autoDismiss' => true,
        'pauseOnHover' => true,
        'showProgressBar' => true,
        'createdAt' => new CarbonImmutable(),
        'dismissed' => false,
        'animation' => [],
        'layout' => [],
        'closeButtonEnabled' => true,
        'soundVolume' => null,
        'soundLoop' => null,
        'ariaRole' => 'status',
        'actions' => [],
        'customData' => [],
    ];
    return new ToastMessageDTO(...array_merge($defaults, $overrides));
}

beforeEach(function () {
    // Set up a basic config for toasts
    // This would typically be your actual config/toasts.php content or a simplified version
    Config::set('toasts', [
        'session_key' => 'toastify_messages',
        'display' => [
            'position' => 'bottom-right',
            'mobile_position' => 'bottom-center',
            'max_width' => 'max-w-sm',
            'mobile_max_width' => 'max-w-xs',
        ],
        'behavior' => [
            'clear_all_on_navigate' => true,
        ],
        // Add other minimal required config sections for ToastManager to load
        'animations' => ['presets' => ['fade' => []], 'global' => [], 'preset' => 'fade'],
        'types' => ['defaults' => ['duration' => 1000], 'layouts' => ['default' => []]],
        'close_button' => [],
        'progress_bar' => [],
        'queue' => ['priority' => ['enabled' => false, 'levels' => []]],
        'sounds' => ['global' => ['enabled' => false, 'base_path' => 'sounds/toastify']],
    ]);

    // Mock the ToastManagerContract
    // We are testing the Livewire component, so we mock the manager it depends on.
    $this->toastManagerMock = Mockery::mock(ToastManagerContract::class);
    $this->app->instance(ToastManagerContract::class, $this->toastManagerMock);
});

afterEach(function () {
    Mockery::close();
});

it('renders the toast container and loads initial toasts', function () {
    $toast1 = create_test_dto('t1', 'Toast 1');
    $toast2 = create_test_dto('t2', 'Toast 2');

    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([$toast1, $toast2]));

    Livewire::test(ToastContainer::class)
        ->assertSet('toastsForJs', [
            $toast1->jsonSerialize(),
            $toast2->jsonSerialize(),
        ])
        ->assertViewHas('config') // Check if config is passed
        ->assertSee($toast1->message) // Check if messages are rendered (via sub-view)
        ->assertSee($toast2->message);
});

it('refreshes toasts when toast-created event is dispatched', function () {
    $initialToast = create_test_dto('t_initial', 'Initial Toast');
    $newToast = create_test_dto('t_new', 'A New Toast Appeared');

    // Initial load
    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([$initialToast]));

    // Load after event
    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([$initialToast, $newToast]));

    Livewire::test(ToastContainer::class)
        ->assertSet('toastsForJs', [$initialToast->jsonSerialize()])
        ->dispatch('toast-created', toastId: $newToast->id) // Dispatch event
        ->assertSet('toastsForJs', [
            $initialToast->jsonSerialize(),
            $newToast->jsonSerialize(),
        ]);
});

it('dismisses a toast when dismiss method is called', function () {
    $toast1 = create_test_dto('t1_dismiss', 'Toast to dismiss');
    $toast2 = create_test_dto('t2_keep', 'Toast to keep');

    // Initial load
    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([$toast1, $toast2]));

    // Mock dismiss call
    $this->toastManagerMock
        ->shouldReceive('dismiss')
        ->with('t1_dismiss')
        ->once();

    // Load after dismissal
    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([$toast2])); // Only toast2 remains

    Livewire::test(ToastContainer::class)
        ->call('dismiss', 't1_dismiss')
        ->assertSet('toastsForJs', [$toast2->jsonSerialize()]);
});

it('clears toasts on navigation if configured', function () {
    Config::set('toasts.behavior.clear_all_on_navigate', true);
    $toast1 = create_test_dto('t1_nav_clear', 'Toast 1 before nav');

    // Initial load
    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([$toast1]));

    // Mock clear call
    $this->toastManagerMock
        ->shouldReceive('clear')
        ->once();

    // Load after clear (empty collection)
    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([]));

    Livewire::test(ToastContainer::class)
        ->assertSet('toastsForJs', [$toast1->jsonSerialize()])
        ->dispatch('navigate') // Simulate Livewire navigation
        ->assertSet('toastsForJs', []);
});

it('does not clear toasts on navigation if not configured', function () {
    Config::set('toasts.behavior.clear_all_on_navigate', false);
    $toast1 = create_test_dto('t1_nav_keep', 'Toast 1 before nav (keep)');

    // Initial load
    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([$toast1]));

    // Ensure clear is NOT called
    $this->toastManagerMock
        ->shouldNotReceive('clear');

    // get() will be called again by loadToasts inside handleNavigation,
    // but since clear() isn't called, it should return the same toast.
    $this->toastManagerMock
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([$toast1]));


    Livewire::test(ToastContainer::class)
        ->assertSet('toastsForJs', [$toast1->jsonSerialize()])
        ->dispatch('navigate') // Simulate Livewire navigation
        ->assertSet('toastsForJs', [$toast1->jsonSerialize()]); // Toasts should remain
});

it('resolves container classes correctly based on config', function () {
    // Config is set in beforeEach for bottom-right and bottom-center (mobile)
    // Expected: mobile first classes, then sm:desktop classes if different
    // mobile: bottom-0 left-1/2 -translate-x-1/2 (for bottom-center)
    // desktop: sm:bottom-0 sm:right-0 (for bottom-right)
    // common: fixed z-[9999] p-4
    $expectedPositionClasses = "bottom-0 left-1/2 -translate-x-1/2 sm:bottom-0 sm:right-0 fixed z-[9999] p-4";

    // mobile: max-w-xs, desktop: sm:max-w-sm
    $expectedMaxWidthClasses = "max-w-xs sm:max-w-sm";


    $this->toastManagerMock->shouldReceive('get')->andReturn(collect([])); // For mount

    Livewire::test(ToastContainer::class)
        ->assertSet('positionClasses', trim($expectedPositionClasses))
        ->assertSet('maxWidthClasses', trim($expectedMaxWidthClasses));

    // Test another combination: top-left for both
    Config::set('toasts.display.position', 'top-left');
    Config::set('toasts.display.mobile_position', 'top-left');
    // Expected: top-0 left-0 (no sm: prefix needed if identical)
    // The current logic in ToastContainer might produce "top-0 left-0 sm:top-0 sm:left-0"
    // Let's test current logic's output.
    // Current logic: $posClasses = "top-0 left-0"; $desktopOverrideClasses = "sm:top-0 sm:left-0";
    // result = $posClasses . ($position !== $mobilePosition ? ' ' . $desktopOverrideClasses : '');
    // Since position === mobilePosition, it becomes "top-0 left-0"
    // Then common classes are added.
    $expectedPositionClassesTopLeft = "top-0 left-0 fixed z-[9999] p-4";

    Livewire::test(ToastContainer::class)
        ->assertSet('positionClasses', trim($expectedPositionClassesTopLeft));

});
