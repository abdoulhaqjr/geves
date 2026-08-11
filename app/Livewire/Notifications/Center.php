<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Page « Notifications » — liste complète, partagée par les deux guards. Montée
 * à la fois sur /mes-notifications (participant) et /admin/notifications (staff) ;
 * détecte le guard actif pour cibler le bon notifiable et le bon layout.
 */
class Center extends Component
{
    use WithPagination;

    private function notifiable(): ?object
    {
        return Auth::guard('account')->user() ?? Auth::guard('web')->user();
    }

    /** Marque une notification comme lue puis redirige vers sa cible. */
    public function open(string $id)
    {
        $notification = $this->notifiable()?->notifications()->whereKey($id)->first();

        if (! $notification) {
            return null;
        }

        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return $url ? $this->redirect($url) : null;
    }

    public function markAllRead(): void
    {
        $this->notifiable()?->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $user = $this->notifiable();
        $layout = Auth::guard('account')->check() ? 'components.layouts.participant' : 'components.layouts.admin';

        return view('livewire.notifications.center', [
            'notifications' => $user
                ? $user->notifications()->latest()->paginate(20)
                : collect(),
            'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
        ])->layout($layout, ['title' => 'Notifications']);
    }
}
