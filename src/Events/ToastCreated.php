<?php

declare(strict_types=1);

namespace Vsent\ToastMessages\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vsent\ToastMessages\DTOs\ToastMessageDTO;

/**
 * Class ToastCreated
 *
 * @package VsE\ToastMessages\Events
 *
 * This event is dispatched whenever a new toast notification is created by the ToastManager.
 * It allows other parts of the application (e.g., listeners for logging, analytics,
 * or custom integrations) to react to toast creation without coupling directly
 * to the ToastManager's internal logic.
 */
class ToastCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The ToastMessageDTO instance that was just created.
     *
     * This property is public readonly, meaning it can be accessed directly
     * by listeners but cannot be modified after the event object is created.
     *
     * @param ToastMessageDTO $toast The data transfer object representing the new toast.
     */
    public function __construct(
        public readonly ToastMessageDTO $toast
    ) {}

    /**
     * Get the channels the event should broadcast on.
     * (Optional: For Livewire, typically not needed for a simple toast event,
     * but included for standard Laravel event structure if broadcasting is desired).
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    // public function broadcastOn(): array
    // {
    //     return [
    //         // new PrivateChannel('channel-name'),
    //     ];
    // }
}
