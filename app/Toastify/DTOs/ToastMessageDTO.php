<?php

declare(strict_types=1);

namespace App\Toastify\DTOs;

use DateTimeImmutable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @property-read string $id
 * @property-read string $type
 * @property-read string $message
 * @property-read string|null $title
 * @property-read string $priority
 * @property-read int $duration
 * @property-read string $position
 * @property-read string $bgColor
 * @property-read string $textColor
 * @property-read string $icon // HTML string for icon
 * @property-read string|null $sound // Web-accessible path to sound file
 * @property-read bool $autoDismiss
 * @property-read bool $pauseOnHover
 * @property-read bool $showProgressBar
 * @property-read DateTimeImmutable $createdAt
 * @property-read bool $dismissed
 * @property-read array<string, mixed> $animation // Resolved animation config
 * @property-read array<string, string> $layout // Resolved layout classes
 * @property-read bool $closeButtonEnabled
 * @property-read float|null $soundVolume
 * @property-read bool|null $soundLoop
 * @property-read string $ariaRole
 * @property-read array<int, array<string, mixed>> $actions // Array of action button configs
 * @property-read array<string, mixed> $customData
 */
readonly class ToastMessageDTO implements JsonSerializable, Arrayable
{
    public function __construct(
        public string $id,
        public string $type,
        public string $message,
        public ?string $title,
        public string $priority,
        public int $duration, // milliseconds
        public string $position,
        public string $bgColor,
        public string $textColor,
        public string $icon,
        public ?string $sound,
        public bool $autoDismiss,
        public bool $pauseOnHover,
        public bool $showProgressBar,
        public DateTimeImmutable $createdAt,
        public bool $dismissed = false,
        public array $animation = [],
        public array $layout = [],
        public bool $closeButtonEnabled = true,
        public ?float $soundVolume = null,
        public ?bool $soundLoop = null,
        public string $ariaRole = 'status',
        public array $actions = [],
        public array $customData = []
    ) {}

    public function markAsDismissed(): static
    {
        return new static(
            id: $this->id,
            type: $this->type,
            message: $this->message,
            title: $this->title,
            priority: $this->priority,
            duration: $this->duration,
            position: $this->position,
            bgColor: $this->bgColor,
            textColor: $this->textColor,
            icon: $this->icon,
            sound: $this->sound,
            autoDismiss: $this->autoDismiss,
            pauseOnHover: $this->pauseOnHover,
            showProgressBar: $this->showProgressBar,
            createdAt: $this->createdAt,
            dismissed: true, // Key change
            animation: $this->animation,
            layout: $this->layout,
            closeButtonEnabled: $this->closeButtonEnabled,
            soundVolume: $this->soundVolume,
            soundLoop: $this->soundLoop,
            ariaRole: $this->ariaRole,
            actions: $this->actions,
            customData: $this->customData
        );
    }

    public function isExpired(DateTimeImmutable $currentTime): bool
    {
        if (!$this->autoDismiss || $this->duration <= 0) {
            return false;
        }
        // Using timestamp arithmetic for millisecond precision
        $expirationTimestamp = $this->createdAt->getTimestamp() + ($this->duration / 1000);
        return $currentTime->getTimestamp() >= $expirationTimestamp;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'message' => $this->message,
            'title' => $this->title,
            'priority' => $this->priority,
            'duration' => $this->duration,
            'position' => $this->position,
            'bgColor' => $this->bgColor,
            'textColor' => $this->textColor,
            'icon' => $this->icon,
            'sound' => $this->sound,
            'autoDismiss' => $this->autoDismiss,
            'pauseOnHover' => $this->pauseOnHover,
            'showProgressBar' => $this->showProgressBar,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'dismissed' => $this->dismissed,
            'animation' => $this->animation,
            'layout' => $this->layout,
            'closeButtonEnabled' => $this->closeButtonEnabled,
            'soundVolume' => $this->soundVolume,
            'soundLoop' => $this->soundLoop,
            'ariaRole' => $this->ariaRole,
            'actions' => $this->actions,
            'customData' => $this->customData,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<string, mixed> $data
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): static
    {
        $requiredFields = ['id', 'type', 'message', 'priority', 'duration', 'position', 'bgColor', 'textColor', 'icon', 'autoDismiss', 'pauseOnHover', 'showProgressBar', 'createdAt'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException(sprintf('Missing required field "%s" for ToastMessageDTO reconstruction.', $field));
            }
        }

        try {
            $createdAt = new DateTimeImmutable($data['createdAt']);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid "createdAt" date format for ToastMessageDTO reconstruction: ' . $data['createdAt'], 0, $e);
        }

        return new static(
            id: (string) $data['id'],
            type: (string) $data['type'],
            message: (string) $data['message'],
            title: isset($data['title']) ? (string) $data['title'] : null,
            priority: (string) $data['priority'],
            duration: (int) $data['duration'],
            position: (string) $data['position'],
            bgColor: (string) $data['bgColor'],
            textColor: (string) $data['textColor'],
            icon: (string) $data['icon'],
            sound: isset($data['sound']) ? (string) $data['sound'] : null,
            autoDismiss: (bool) $data['autoDismiss'],
            pauseOnHover: (bool) $data['pauseOnHover'],
            showProgressBar: (bool) $data['showProgressBar'],
            createdAt: $createdAt,
            dismissed: (bool) ($data['dismissed'] ?? false),
            animation: (array) ($data['animation'] ?? []),
            layout: (array) ($data['layout'] ?? []),
            closeButtonEnabled: (bool) ($data['closeButtonEnabled'] ?? true),
            soundVolume: (isset($data['soundVolume']) && is_numeric($data['soundVolume'])) ? (float) $data['soundVolume'] : null,
            soundLoop: isset($data['soundLoop']) ? (bool) $data['soundLoop'] : null,
            ariaRole: (string) ($data['ariaRole'] ?? 'status'),
            actions: (array) ($data['actions'] ?? []),
            customData: (array) ($data['customData'] ?? [])
        );
    }
}
