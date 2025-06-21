<?php

declare(strict_types=1);

namespace Vsent\LaravelToastify\Tests\Unit; // Updated Namespace

use Vsent\LaravelToastify\DTOs\ToastMessageDTO; // Updated Namespace
use Carbon\CarbonImmutable; // Using Carbon for easier date manipulation in tests

// Helper function to create a valid base DTO for tests
function create_default_toast_data_for_dto_test(array $overrides = []): array // Renamed helper
{
    $now = CarbonImmutable::now();
    return array_merge([
        'id' => 'test-id-123',
        'type' => 'success',
        'message' => 'Test message',
        'title' => 'Test Title',
        'priority' => 'normal',
        'duration' => 5000,
        'position' => 'top-right',
        'bgColor' => 'bg-green-500',
        'textColor' => 'text-white',
        'icon' => '<svg></svg>',
        'sound' => '/sounds/toastify/success.mp3',
        'autoDismiss' => true,
        'pauseOnHover' => true,
        'showProgressBar' => true,
        'createdAt' => $now->toAtomString(), // Store as string, DTO will parse
        'dismissed' => false,
        'animation' => ['name' => 'fade', 'duration' => 300],
        'layout' => ['wrapper' => 'p-4'],
        'closeButtonEnabled' => true,
        'soundVolume' => 0.8,
        'soundLoop' => false,
        'ariaRole' => 'status',
        'actions' => [['label' => 'Undo', 'handler' => 'undoAction']],
        'customData' => ['foo' => 'bar'],
    ], $overrides);
}


it('can be instantiated with valid data', function () {
    $data = create_default_toast_data();
    $dto = ToastMessageDTO::fromArray($data);

    expect($dto->id)->toBe($data['id']);
    expect($dto->type)->toBe($data['type']);
    expect($dto->message)->toBe($data['message']);
    expect($dto->title)->toBe($data['title']);
    expect($dto->priority)->toBe($data['priority']);
    expect($dto->duration)->toBe($data['duration']);
    expect($dto->autoDismiss)->toBe($data['autoDismiss']);
    expect($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
    expect($dto->createdAt->toAtomString())->toBe(CarbonImmutable::parse($data['createdAt'])->toAtomString());
    expect($dto->animation)->toBe($data['animation']);
    expect($dto->actions[0]['label'])->toBe('Undo');
});

it('marks toast as dismissed immutably', function () {
    $dto = ToastMessageDTO::fromArray(create_default_toast_data());
    expect($dto->dismissed)->toBeFalse();

    $dismissedDto = $dto->markAsDismissed();
    expect($dto->dismissed)->toBeFalse(); // Original DTO is unchanged
    expect($dismissedDto->dismissed)->toBeTrue();
    expect($dismissedDto->id)->toBe($dto->id); // Other properties remain the same
});

describe('isExpired', function () {
    it('is not expired if autoDismiss is false', function () {
        $dto = ToastMessageDTO::fromArray(create_default_toast_data(['autoDismiss' => false, 'duration' => 1000]));
        $futureTime = CarbonImmutable::parse($dto->createdAt)->addSeconds(10);
        expect($dto->isExpired($futureTime))->toBeFalse();
    });

    it('is not expired if duration is zero or negative', function () {
        $dtoZero = ToastMessageDTO::fromArray(create_default_toast_data(['duration' => 0]));
        $dtoNegative = ToastMessageDTO::fromArray(create_default_toast_data(['duration' => -100]));
        $futureTime = CarbonImmutable::parse($dtoZero->createdAt)->addSeconds(10);

        expect($dtoZero->isExpired($futureTime))->toBeFalse();
        expect($dtoNegative->isExpired($futureTime))->toBeFalse();
    });

    it('is not expired if current time is before expiration', function () {
        $dto = ToastMessageDTO::fromArray(create_default_toast_data(['duration' => 5000])); // 5 seconds
        $currentTime = CarbonImmutable::parse($dto->createdAt)->addMilliseconds(4999);
        expect($dto->isExpired($currentTime))->toBeFalse();
    });

    it('is expired if current time is at or after expiration', function () {
        $dto = ToastMessageDTO::fromArray(create_default_toast_data(['duration' => 5000])); // 5 seconds
        $expirationTime = CarbonImmutable::parse($dto->createdAt)->addMilliseconds(5000);
        $afterExpirationTime = CarbonImmutable::parse($dto->createdAt)->addMilliseconds(5001);

        expect($dto->isExpired($expirationTime))->toBeTrue();
        expect($dto->isExpired($afterExpirationTime))->toBeTrue();
    });
});

it('serializes to array correctly', function () {
    $data = create_default_toast_data();
    $dto = ToastMessageDTO::fromArray($data);
    $array = $dto->toArray();

    expect($array['id'])->toBe($data['id']);
    expect($array['message'])->toBe($data['message']);
    expect($array['createdAt'])->toBe(CarbonImmutable::parse($data['createdAt'])->toAtomString());
    // Check a few more specific fields
    expect($array['animation'])->toBe($data['animation']);
    expect($array['soundVolume'])->toBe($data['soundVolume']);
});

it('jsonSerializes to array correctly', function () {
    $data = create_default_toast_data();
    $dto = ToastMessageDTO::fromArray($data);
    $array = $dto->jsonSerialize(); // Should be same as toArray()

    expect($array['id'])->toBe($data['id']);
    expect($array['message'])->toBe($data['message']);
    expect($array['createdAt'])->toBe(CarbonImmutable::parse($data['createdAt'])->toAtomString());
});

describe('fromArray static factory', function () {
    it('creates DTO with default values for optional fields', function () {
        $minimalData = create_default_toast_data([
            'title' => null, // Explicitly test null
            'sound' => null,
            // Omitting many optional fields like animation, layout, etc.
            'animation' => [],
            'layout' => [],
            'closeButtonEnabled' => true, // Default
            'soundVolume' => null,
            'soundLoop' => null,
            'ariaRole' => 'status', // Default
            'actions' => [],
            'customData' => [],
        ]);
        // Remove keys that should have defaults if not provided
        unset($minimalData['animation'], $minimalData['layout'], $minimalData['closeButtonEnabled'], $minimalData['soundVolume'], $minimalData['soundLoop'], $minimalData['ariaRole'], $minimalData['actions'], $minimalData['customData']);


        $dto = ToastMessageDTO::fromArray($minimalData);

        expect($dto->title)->toBeNull();
        expect($dto->sound)->toBeNull();
        expect($dto->animation)->toBe([]);
        expect($dto->layout)->toBe([]);
        expect($dto->closeButtonEnabled)->toBeTrue(); // Default from constructor
        expect($dto->soundVolume)->toBeNull();
        expect($dto->soundLoop)->toBeNull(); // Default from constructor
        expect($dto->ariaRole)->toBe('status'); // Default from constructor
        expect($dto->actions)->toBe([]);
        expect($dto->customData)->toBe([]);
    });

    it('throws exception for missing required fields', function (string $missingField) {
        $data = create_default_toast_data();
        unset($data[$missingField]);
        ToastMessageDTO::fromArray($data);
    })->with([
        'id', 'type', 'message', 'priority', 'duration', 'position',
        'bgColor', 'textColor', 'icon', 'autoDismiss', 'pauseOnHover',
        'showProgressBar', 'createdAt',
    ])->throws(\InvalidArgumentException::class);

    it('throws exception for invalid createdAt date format', function () {
        $data = create_default_toast_data(['createdAt' => 'invalid-date']);
        ToastMessageDTO::fromArray($data);
    })->throws(\InvalidArgumentException::class, 'Invalid "createdAt" date format');
});

it('handles integer and float type casting correctly in fromArray', function () {
    $data = create_default_toast_data([
        'duration' => '7000', // String that should be cast to int
        'soundVolume' => '0.5', // String that should be cast to float
    ]);
    $dto = ToastMessageDTO::fromArray($data);

    expect($dto->duration)->toBeInt()->toBe(7000);
    expect($dto->soundVolume)->toBeFloat()->toBe(0.5);

    // Test with actual numeric types
    $dataNumeric = create_default_toast_data([
        'duration' => 7000,
        'soundVolume' => 0.5,
    ]);
    $dtoNumeric = ToastMessageDTO::fromArray($dataNumeric);
    expect($dtoNumeric->duration)->toBeInt()->toBe(7000);
    expect($dtoNumeric->soundVolume)->toBeFloat()->toBe(0.5);
});

it('handles boolean type casting correctly in fromArray', function () {
    $data = create_default_toast_data([
        'autoDismiss' => 'false', // String "false"
        'pauseOnHover' => 0,      // Integer 0
        'showProgressBar' => '1', // String "1"
        'dismissed' => '',        // Empty string
        'soundLoop' => 'true'
    ]);
    $dto = ToastMessageDTO::fromArray($data);

    expect($dto->autoDismiss)->toBeFalse();
    expect($dto->pauseOnHover)->toBeFalse();
    expect($dto->showProgressBar)->toBeTrue();
    expect($dto->dismissed)->toBeFalse(); // Empty string casts to false
    expect($dto->soundLoop)->toBeTrue();
});
