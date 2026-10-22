<?php

namespace App\Listeners;

use App\Events\NotificationArrived;
use App\Models\User;
use App\Support\Realtime;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

/** La notification en base reste disponible même si le service WebSocket échoue. */
class BroadcastStoredNotification
{
    public function handle(NotificationSent $sent): void
    {
        if ($sent->channel !== 'database'
            || ! $sent->notifiable instanceof User
            || ! Realtime::configured()
            || ! $sent->response?->getKey()) {
            return;
        }

        $userId = (int) $sent->notifiable->getKey();
        $notificationId = (string) $sent->response->getKey();
        $publish = static function () use ($userId, $notificationId): void {
            try {
                Event::dispatch(new NotificationArrived($userId, $notificationId));
            } catch (Throwable $error) {
                Log::warning('Notification enregistrée, mais signal WebSocket indisponible.', [
                    'user_id' => $userId,
                    'notification_id' => $notificationId,
                    'reason' => $error->getMessage(),
                ]);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($publish);
        } else {
            $publish();
        }
    }
}
