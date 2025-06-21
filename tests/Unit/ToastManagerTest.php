<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Toastify\Contracts\ToastManagerContract;
use App\Toastify\DTOs\ToastMessageDTO;
use App\Toastify\Events\ToastCreated;
use App\Toastify\ToastManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Event; // For event faking
use Mockery; // For mocking dependencies
use Mockery\MockInterface;

// Helper function to get a mocked ToastManager instance
function create_toast_manager_instance(array $configOverrides = [], ?Session $session = null): ToastManager
{
    $defaultConfig = include __DIR__ . '/../../config/toasts.php'; // Assuming config is in project root
    $configData = array_replace_recursive($defaultConfig, $configOverrides);

    $mockConfig = Mockery::mock(ConfigRepository::class);
    $mockConfig->shouldReceive('get')->with('toasts', [])->andReturn($configData);
    // Allow other config gets with defaults
    $mockConfig->shouldReceive('get')->andReturnUsing(function ($key, $default = null) use ($configData) {
        return data_get($configData, str_replace('toasts.', '', $key), $default);
    });


    $mockSession = $session ?? Mockery::mock(Session::class);
    // Default session expectations
    if (!$session) { // only set default expectations if session is not passed in (allowing test-specific session mocks)
        $mockSession->shouldReceive('get')->with('toastify_messages', [])->andReturn([])->byDefault();
        $mockSession->shouldReceive('put')->with('toastify_messages', Mockery::any())->andReturnNull()->byDefault();
        $mockSession->shouldReceive('forget')->with('toastify_messages')->andReturnNull()->byDefault();
    }


    $mockEventDispatcher = Mockery::mock(EventDispatcher::class);
    $mockEventDispatcher->shouldReceive('dispatch')->with(Mockery::type(ToastCreated::class))->andReturnNull()->byDefault();

    $mockLogger = Mockery::mock(LogManager::class);
    $mockLogger->shouldReceive('warning')->andReturnNull()->byDefault();
    $mockLogger->shouldReceive('error')->andReturnNull()->byDefault();
    $mockLogger->shouldReceive('info')->andReturnNull()->byDefault();


    return new ToastManager($mockSession, $mockConfig, $mockEventDispatcher, $mockLogger);
}

beforeEach(function () {
    // It's often useful to have a fresh config for each test if not overriding specifically
    // but create_toast_manager_instance handles this.
    // You might want to copy the real toasts.php into a fixture if it's not in the project root for tests.
    // For now, assuming it's accessible via __DIR__ . '/../../config/toasts.php' relative to this test file's eventual location
    // which means it expects toasts.php in the root `config` directory of the Laravel application.
    if (!file_exists(__DIR__ . '/../../config/toasts.php')) {
        // Create a dummy toasts.php if it doesn't exist for tests to run
        // This is a simplified version. Ideally, use a proper fixture.
        $dummyConfigContent = "<?php return ['session_key' => 'toastify_messages', 'types' => ['defaults' => [], 'success' => ['bg' => 'bg-green-500']], 'display' => ['default_duration' => 3000], 'sounds' => ['global' => ['base_path' => 'sounds/toastify', 'enabled' => true], 'assets' => ['default_sound' => ['src' => 'default.mp3']]], 'queue' => ['priority' => ['enabled' => false, 'levels' => []]], 'animations' => ['presets' => ['fade' => []], 'global' => []], 'behavior' => [], 'close_button' => [], 'progress_bar' => []];";
        // Ensure config directory exists
        if (!is_dir(__DIR__ . '/../../config')) {
            mkdir(__DIR__ . '/../../config', 0755, true);
        }
        file_put_contents(__DIR__ . '/../../config/toasts.php', $dummyConfigContent);
    }

    Event::fake(); // Fake Laravel events
});

afterEach(function () {
    Mockery::close();
});

it('adds a toast with default settings correctly', function () {
    $manager = create_toast_manager_instance();
    $toast = $manager->add('success', 'Default toast message');

    expect($toast)->toBeInstanceOf(ToastMessageDTO::class);
    expect($toast->type)->toBe('success');
    expect($toast->message)->toBe('Default toast message');
    expect($toast->duration)->toBe(3000); // from dummy config
    expect($toast->bgColor)->toBe('bg-green-500'); // from dummy config
    Event::assertDispatched(ToastCreated::class, function (ToastCreated $event) use ($toast) {
        return $event->toast->id === $toast->id;
    });
});

it('overrides default settings with explicit parameters in add()', function () {
    $manager = create_toast_manager_instance();
    $toast = $manager->add(
        type: 'info',
        message: 'Custom message',
        title: 'Custom Title',
        duration: 7000,
        priority: 'high',
        soundAsset: 'custom_sound', // Assuming 'custom_sound' is configured in sounds.assets
        customData: ['key' => 'value']
    );

    expect($toast->type)->toBe('info');
    expect($toast->title)->toBe('Custom Title');
    expect($toast->duration)->toBe(7000);
    expect($toast->priority)->toBe('high'); // 'high' might become 'low' if not in validPriorities & strict
    expect($toast->customData['key'])->toBe('value');
    // Sound path needs more specific config for this test
});

it('constructs correct web-accessible sound path', function () {
    $manager = create_toast_manager_instance([
        'sounds' => [
            'global' => ['enabled' => true, 'base_path' => 'sounds/custom_vendor/'],
            'assets' => ['my_sound' => ['src' => 'ping.mp3']],
            'types' => ['special' => 'my_sound'] // Map type 'special' to 'my_sound' asset key
        ]
    ]);
    $toast = $manager->add('special', 'Sound test');
    expect($toast->sound)->toBe('/sounds/custom_vendor/ping.mp3');
});

it('handles duplicate detection when enabled', function () {
    $mockSession = Mockery::mock(Session::class);
    $existingToasts = new Collection([
        (new ToastMessageDTO('old-id', 'info', 'Same message', null, 'low', 5000, 'top-right', '', '', '', null, true, true, true, new \DateTimeImmutable('-1 second')))->jsonSerialize()
    ]);
    $mockSession->shouldReceive('get')->with('toastify_messages', [])->andReturn($existingToasts->toArray())->once(); // First get
    $mockSession->shouldReceive('get')->with('toastify_messages', [])->andReturn($existingToasts->toArray())->byDefault(); // Subsequent gets

    $manager = create_toast_manager_instance(
        ['behavior' => ['duplicate_detection' => ['enabled' => true, 'duration_threshold' => 2000]]],
        $mockSession
    );

    // This should be detected as duplicate
    $toast1 = $manager->add('info', 'Same message');
    expect($toast1->id)->toBe('old-id'); // Returns existing DTO
    Event::assertNotDispatched(ToastCreated::class, fn(ToastCreated $e) => $e->toast->id !== 'old-id');

    // This should be a new toast (different message)
    $mockSession->shouldReceive('put')->once(); // Expect a put for the new toast
    $toast2 = $manager->add('info', 'Different message');
    expect($toast2->id)->not->toBe('old-id');
    Event::assertDispatched(ToastCreated::class, fn(ToastCreated $e) => $e->toast->id === $toast2->id);
});


it('retrieves toasts respecting global limit', function () {
    $mockSession = Mockery::mock(Session::class);
    $toastsInSession = new Collection();
    for ($i = 1; $i <= 7; $i++) {
        $toastsInSession->push((new ToastMessageDTO("id-$i", "info", "Msg $i", null, 'low', 5000, 'top-right', '', '', '', null, true, true, true, new \DateTimeImmutable("-{$i} seconds")))->jsonSerialize());
    }
    $mockSession->shouldReceive('get')->with('toastify_messages', [])->andReturn($toastsInSession->toArray());
    $mockSession->shouldReceive('put'); // Allow puts

    $manager = create_toast_manager_instance(['queue' => ['max_toasts' => 3]], $mockSession);
    $displayedToasts = $manager->get();
    expect($displayedToasts)->toHaveCount(3);
});

it('clears all toasts', function () {
    $mockSession = Mockery::mock(Session::class);
    $mockSession->shouldReceive('get')->with('toastify_messages', [])->andReturn([]); // Initial get
    $mockSession->shouldReceive('forget')->with('toastify_messages')->once();

    $manager = create_toast_manager_instance([], $mockSession);
    $manager->clear();
    // Assertion is via mock expectation
});

it('dismisses a specific toast', function () {
    $toastToDismiss = new ToastMessageDTO("id-2", "info", "Msg 2", null, 'low', 5000, 'top-right', '', '', '', null, true, true, true, new \DateTimeImmutable("-2 seconds"));
    $otherToast = new ToastMessageDTO("id-1", "success", "Msg 1", null, 'low', 5000, 'top-right', '', '', '', null, true, true, true, new \DateTimeImmutable("-1 seconds"));

    $mockSession = Mockery::mock(Session::class);
    $mockSession->shouldReceive('get')->with('toastify_messages', [])->andReturn([
        $otherToast->jsonSerialize(), $toastToDismiss->jsonSerialize()
    ]);
    // Expect session 'put' with the toast marked as dismissed (actually, it will be filtered out by updateToastsInSession)
    $mockSession->shouldReceive('put')->with('toastify_messages', Mockery::on(function ($argument) use ($otherToast) {
        // The dismissed toast should be gone, only otherToast remains
        if (count($argument) !== 1) return false;
        return $argument[0]['id'] === $otherToast->id && $argument[0]['dismissed'] === false;
    }))->once();


    $manager = create_toast_manager_instance([], $mockSession);
    $manager->dismiss("id-2");
});


it('returns true for hasToasts when active toasts exist', function () {
    $mockSession = Mockery::mock(Session::class);
    $activeToast = (new ToastMessageDTO("id-1", "success", "Msg 1", null, 'low', 5000, 'top-right', '', '', '', null, true, true, true, new \DateTimeImmutable()))->jsonSerialize();
    $mockSession->shouldReceive('get')->with('toastify_messages', [])->andReturn([$activeToast]);
    $mockSession->shouldReceive('put');

    $manager = create_toast_manager_instance([], $mockSession);
    expect($manager->hasToasts())->toBeTrue();
});

it('returns false for hasToasts when no active toasts exist', function () {
    $manager = create_toast_manager_instance(); // Uses default session mock which returns []
    expect($manager->hasToasts())->toBeFalse();
});

it('correctly applies per-type limit', function () {
    $mockSession = Mockery::mock(Session::class);
    $toastsInSession = new Collection();
    for ($i = 1; $i <= 5; $i++) { // 5 info toasts
        $toastsInSession->push((new ToastMessageDTO("id-info-$i", "info", "Info Msg $i", null, 'low', 5000, 'tr', '', '', '', null, true, true, true, new \DateTimeImmutable("-{$i} sec")))->jsonSerialize());
    }
    $toastsInSession->push((new ToastMessageDTO("id-succ-1", "success", "Success Msg 1", null, 'low', 5000, 'tr', '', '', '', null, true, true, true, new \DateTimeImmutable("-6 sec")))->jsonSerialize());

    $mockSession->shouldReceive('get')->andReturn($toastsInSession->toArray());
    $mockSession->shouldReceive('put');

    $manager = create_toast_manager_instance([
        'queue' => ['max_toasts' => 10, 'per_type_limit' => 2] // Max 2 per type
    ], $mockSession);

    $displayedToasts = $manager->get();
    expect($displayedToasts->where('type', 'info')->count())->toBe(2);
    expect($displayedToasts->where('type', 'success')->count())->toBe(1);
    expect($displayedToasts)->toHaveCount(3);
});

it('respects priority limits when enabled', function () {
    $mockSession = Mockery::mock(Session::class);
    $toastsInSession = new Collection([
        (new ToastMessageDTO("h1", "error", "H1", null, 'high', 0, 'tr','','','',null,true,true,true, new \DateTimeImmutable("-1 sec")))->jsonSerialize(),
        (new ToastMessageDTO("h2", "error", "H2", null, 'high', 0, 'tr','','','',null,true,true,true, new \DateTimeImmutable("-2 sec")))->jsonSerialize(),
        (new ToastMessageDTO("h3", "error", "H3", null, 'high', 0, 'tr','','','',null,true,true,true, new \DateTimeImmutable("-3 sec")))->jsonSerialize(),
        (new ToastMessageDTO("n1", "info", "N1", null, 'normal', 0, 'tr','','','',null,true,true,true, new \DateTimeImmutable("-4 sec")))->jsonSerialize(),
    ]);
    $mockSession->shouldReceive('get')->andReturn($toastsInSession->toArray());
    $mockSession->shouldReceive('put');

    $manager = create_toast_manager_instance([
        'queue' => [
            'max_toasts' => 5,
            'priority' => [
                'enabled' => true,
                'levels' => [
                    'high' => ['limit' => 2, 'types' => ['error']], // High priority, limit 2
                    'normal' => ['limit' => 3, 'types' => ['info']],
                    'low' => ['limit' => 3, 'types' => ['default']],
                ]
            ]
        ]
    ], $mockSession);

    $displayedToasts = $manager->get();
    // Expect 2 high priority toasts (h1, h2 because they are newer or as per sorting)
    // and 1 normal priority toast (n1)
    expect($displayedToasts->where('priority', 'high')->count())->toBe(2);
    expect($displayedToasts->where('priority', 'normal')->count())->toBe(1);
    expect($displayedToasts->pluck('id')->all())->toEqualCanonicalizing(['h1', 'h2', 'n1']); // Order might vary based on exact sorting
});

it('handles session persistence correctly', function () {
    $sessionStore = []; // In-memory session for this test

    $mockSession = Mockery::mock(Session::class);
    $mockSession->shouldReceive('get')
        ->with('toastify_messages', [])
        ->andReturnUsing(function () use (&$sessionStore) {
            return $sessionStore['toastify_messages'] ?? [];
        });
    $mockSession->shouldReceive('put')
        ->with('toastify_messages', Mockery::any())
        ->andReturnUsing(function ($key, $value) use (&$sessionStore) {
            $sessionStore[$key] = $value;
        });

    $manager = create_toast_manager_instance([], $mockSession);

    $toast1 = $manager->add('success', 'First toast');
    expect($sessionStore['toastify_messages'])->toHaveCount(1);
    expect($sessionStore['toastify_messages'][0]['id'])->toBe($toast1->id);

    $toast2 = $manager->add('error', 'Second toast');
    expect($sessionStore['toastify_messages'])->toHaveCount(2);
    // Newest is prepended
    expect($sessionStore['toastify_messages'][0]['id'])->toBe($toast2->id);
    expect($sessionStore['toastify_messages'][1]['id'])->toBe($toast1->id);

    $displayed = $manager->get(); // This also calls updateToastsInSession
    expect($displayed)->toHaveCount(2);
});

// Test for custom() method
it('adds a custom toast using the custom method', function () {
    $manager = create_toast_manager_instance([
        'types' => [
            'defaults' => ['duration' => 1000, 'priority' => 'low'],
            'special_custom' => ['bg' => 'bg-purple-500', 'icon' => '<svg>custom</svg>', 'priority' => 'high']
        ]
    ]);

    $options = [
        'type' => 'special_custom',
        'duration' => 9999,
        'priority' => 'high', // This should be used
        'bgColor' => 'bg-override-purple', // This should be used by custom, if add() allows direct prop override
        'customData' => ['extra' => 'info']
    ];
    $toast = $manager->custom('My custom message', 'My Custom Title', $options);

    expect($toast->message)->toBe('My custom message');
    expect($toast->title)->toBe('My Custom Title');
    expect($toast->type)->toBe('special_custom');
    expect($toast->duration)->toBe(9999);
    expect($toast->priority)->toBe('high');
    // Depending on how `add` merges: if type config is taken first, then explicit params.
    // The provided ToastManager's add method prioritizes explicit params.
    // So, if 'bgColor' was an explicit param of `add`, it would override.
    // Since it's in `options` and not a direct param of `add`, it will be part of `customData`
    // unless `add` specifically looks for `bgColor` in `customData` to override.
    // The current `ToastManager::custom` maps known keys from options to `add` params.
    // `bgColor` is not one of them, so it would fall into customData or be ignored for DTO top-level.
    // The DTO's `bgColor` would come from the type 'special_custom' config.
    expect($toast->bgColor)->toBe('bg-purple-500'); // From 'special_custom' type config
    expect($toast->icon)->toBe('<svg>custom</svg>'); // From 'special_custom' type config
    expect($toast->customData['extra'])->toBe('info');
    // If bgColor was a direct param to add, it would take precedence.
    // Let's test if customData holds bgColor if it's not a direct DTO property setter in add()
    // expect($toast->customData['bgColor'])->toBe('bg-override-purple'); // This depends on custom() impl.
});
