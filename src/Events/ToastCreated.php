<?php

declare(strict_types=1);

namespace Vsent\LaravelToastify\Events; // Updated Namespace

use Vsent\LaravelToastify\DTOs\ToastMessageDTO; // Updated Namespace for DTO
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a new toast message is created by the ToastManager.
 *
 * This event allows other parts of the application to react to toast creation,
 * for example, for logging, analytics, or triggering other actions.
 */
class ToastCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The ToastMessageDTO instance representing the newly created toast.
     *
     * This property is public readonly, ensuring it can be accessed by listeners
     * but not modified after the event object is instantiated.
     */
    public readonly ToastMessageDTO $toast;

    /**
     * Create a new event instance.
     *
     * @param ToastMessageDTO $toast The DTO of the toast message that was created.
     */
    public function __construct(ToastMessageDTO $toast)
    {
        $this->toast = $toast;
    }

    // If broadcasting is needed in the future:
    // /**
    //  * Get the channels the event should broadcast on.
    //  *
    //  * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Database\Eloquent\Model>
    //  */
    // public function broadcastOn(): array
    // {
    //     return [
    //         // new \Illuminate\Broadcasting\PrivateChannel('channel-name'),
    //     ];
    // }
}
