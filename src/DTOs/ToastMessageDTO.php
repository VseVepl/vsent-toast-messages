<?php

declare(strict_types=1);

namespace Vsent\ToastMessages\DTOs;

use DateTimeImmutable;
use JsonSerializable; // To allow the DTO to be easily serialized to JSON if needed

/**
 * Class ToastMessageDTO
 *
 * @package VsE\ToastMessages\DTOs
 *
 * This Data Transfer Object (DTO) defines the structure and properties for a single toast message.
 * It ensures consistency and type safety across the application when handling toast data.
 * Properties are declared as `readonly` to ensure immutability once a DTO instance is created,
 * which helps in preventing unexpected state changes and simplifying debugging.
 *
 * This version of the DTO is updated to reflect the comprehensive configuration
 * from 'config/toasts.php', including animation, layout, sound properties,
 * and additional behavior flags.
 */
readonly class ToastMessageDTO implements JsonSerializable
{
    /**
     * Constructor for the ToastMessageDTO.
     *
     * @param string            $id                 A unique identifier for the toast message.
     * @param string            $type               The type of the toast (e.g., 'success', 'error', 'warning', 'info', 'custom', 'critical').
     * @param string            $message            The main content or body of the toast message.
     * @param string|null       $title              An optional title for the toast message. Defaults to null.
     * @param string            $priority           The display priority of the toast (e.g., 'high', 'normal', 'low').
     * @param int               $duration           The duration in milliseconds the toast should be displayed before auto-dismissing.
     * @param string            $position           The screen position where the toast should appear (e.g., 'top-right', 'bottom-left').
     * @param string            $bgColor            Tailwind CSS class for the background color (e.g., 'bg-green-600').
     * @param string            $textColor          Tailwind CSS class for the text color (e.g., 'text-white').
     * @param string            $icon               An SVG string or HTML for the icon to display in the toast. Can be empty.
     * @param string|null       $sound              The full path or name of the sound file to play when the toast appears.
     * @param bool              $autoDismiss        Determines if the toast should automatically dismiss after its duration.
     * @param bool              $pauseOnHover       Determines if the auto-dismiss timer should pause when the user hovers over the toast.
     * @param bool              $showProgressBar    Determines if a progress bar indicating remaining display time should be shown.
     * @param DateTimeImmutable $createdAt          The immutable DateTime object representing when the toast was created.
     * @param bool              $dismissed          Internal flag indicating if the toast has been explicitly dismissed by the user or system.
     * @param array<string, mixed> $animation       Resolved animation settings for this specific toast.
     * @param array<string, string> $layout          Resolved layout classes for this specific toast.
     * @param bool              $closeButtonEnabled Whether the close button is enabled for this toast.
     * @param float|null        $soundVolume        The volume for the sound (0.0 to 1.0).
     * @param bool|null         $soundLoop          Whether the sound should loop.
     * @param string            $ariaRole           The ARIA role for accessibility ('status', 'alert').
     * @param array<array<string, string>> $actions Optional array of interactive actions (e.g., buttons) for the toast.
     * @param array<string, mixed> $customData      Any additional custom data to be stored with the toast.
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $message,
        public ?string $title,
        public string $priority,
        public int $duration,
        public string $position, // This refers to the stack position, not necessarily the overall screen position
        public string $bgColor,
        public string $textColor,
        public string $icon,
        public ?string $sound, // Full path for sound asset
        public bool $autoDismiss,
        public bool $pauseOnHover,
        public bool $showProgressBar,
        public DateTimeImmutable $createdAt,
        public bool $dismissed = false, // Default to not dismissed upon creation
        public array $animation = [], // New: Detailed animation properties
        public array $layout = [], // New: Tailwind classes for toast layout structure
        public bool $closeButtonEnabled = true, // New: Whether close button is enabled for this toast
        public ?float $soundVolume = null, // New: Specific volume for this toast's sound
        public ?bool $soundLoop = null, // New: Specific loop behavior for this toast's sound
        public string $ariaRole = 'status', // New: ARIA role for accessibility
        public array $actions = [], // New: Array of actions
        public array $customData = [] // New: Generic custom data
    ) {}

    /**
     * Creates a new DTO instance with the 'dismissed' status set to true.
     *
     * This method respects the immutability of the DTO by returning a new instance
     * rather than modifying the current one.
     *
     * @return static A new ToastMessageDTO instance with the dismissed flag set.
     */
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
            dismissed: true, // Only this property changes
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

    /**
     * Determines if the toast message has expired based on its creation time and duration.
     * A duration of 0 or less means it does not auto-dismiss.
     *
     * @param DateTimeImmutable $currentTime The current time to compare against.
     * @return bool True if the toast has expired, false otherwise.
     */
    public function isExpired(DateTimeImmutable $currentTime): bool
    {
        if (!$this->autoDismiss || $this->duration <= 0) {
            return false; // Toast does not auto-dismiss or has no duration
        }

        // Calculate expiration time by adding duration (milliseconds) to creation time
        // Create a DateInterval from milliseconds
        $intervalSpec = sprintf('PT%dS', floor($this->duration / 1000)); // Whole seconds
        $millisecondsRemainder = $this->duration % 1000;
        if ($millisecondsRemainder > 0) {
            $intervalSpec .= sprintf('%dMS', $millisecondsRemainder); // Add remaining milliseconds
        }

        try {
            $interval = new \DateInterval($intervalSpec);
            $expirationTime = $this->createdAt->add($interval);
        } catch (\Exception $e) {
            // Fallback for extreme cases or invalid duration
            error_log(sprintf('Error creating DateInterval for toast expiration: %s. Toast ID: %s', $e->getMessage(), $this->id));
            // If interval creation fails, treat as not expired to avoid early dismissal
            return false;
        }

        return $currentTime >= $expirationTime;
    }

    /**
     * Serializes the DTO into a format that can be JSON encoded.
     * This is useful for storing the DTO in session or transmitting it to the frontend.
     *
     * @return array<string, mixed> An associative array representing the toast message.
     */
    public function jsonSerialize(): array
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
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM), // ISO 8601 format
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

    /**
     * Reconstructs a ToastMessageDTO instance from an array, typically from JSON deserialization.
     * This acts as a named constructor for easier reconstruction from session data.
     *
     * @param array<string, mixed> $data The array data to create the DTO from.
     * @return static A new ToastMessageDTO instance.
     * @throws \InvalidArgumentException If required data is missing or malformed.
     */
    public static function fromArray(array $data): static
    {
        // Validate core required fields
        $requiredFields = ['id', 'type', 'message', 'priority', 'duration', 'position', 'bgColor', 'textColor', 'icon', 'autoDismiss', 'pauseOnHover', 'showProgressBar', 'createdAt'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException(sprintf('Missing required field "%s" for ToastMessageDTO reconstruction.', $field));
            }
        }

        try {
            $createdAt = new DateTimeImmutable($data['createdAt']);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid "createdAt" date format for ToastMessageDTO reconstruction.', 0, $e);
        }

        return new static(
            id: $data['id'],
            type: $data['type'],
            message: $data['message'],
            title: $data['title'] ?? null,
            priority: $data['priority'],
            duration: (int) $data['duration'],
            position: $data['position'],
            bgColor: $data['bgColor'],
            textColor: $data['textColor'],
            icon: $data['icon'],
            sound: $data['sound'] ?? null,
            autoDismiss: (bool) $data['autoDismiss'],
            pauseOnHover: (bool) $data['pauseOnHover'],
            showProgressBar: (bool) $data['showProgressBar'],
            createdAt: $createdAt,
            dismissed: (bool) ($data['dismissed'] ?? false),
            animation: $data['animation'] ?? [],
            layout: $data['layout'] ?? [],
            closeButtonEnabled: (bool) ($data['closeButtonEnabled'] ?? true),
            soundVolume: (isset($data['soundVolume']) && is_numeric($data['soundVolume'])) ? (float) $data['soundVolume'] : null,
            soundLoop: isset($data['soundLoop']) ? (bool) $data['soundLoop'] : null,
            ariaRole: $data['ariaRole'] ?? 'status',
            actions: $data['actions'] ?? [],
            customData: $data['customData'] ?? []
        );
    }
}
