<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/** Signale une notification enregistrée ; son contenu reste chargé depuis GEVES. */
class NotificationArrived implements ShouldBroadcastNow
{
    public function __construct(
        public int $userId,
        public string $notificationId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('ukc.notifications.'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'notification.arrived';
    }

    /** @return array{id: string} */
    public function broadcastWith(): array
    {
        return ['id' => $this->notificationId];
    }
}
