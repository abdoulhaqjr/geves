<?php

namespace App\Livewire\Notifications;

use App\Notifications\NotificationKinds;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Page « Notifications » — liste complète + préférences par type/canal, partagée
 * par les deux guards. Montée sur /mes-notifications (participant) et
 * /admin/notifications (staff) ; détecte le guard actif pour cibler le bon
 * notifiable, le bon layout et la bonne audience de préférences.
 */
class Center extends Component
{
    use WithPagination;

    /** Préférences éditables : kind => { inapp: bool, email: bool }. */
    public array $prefs = [];

    public function mount(): void
    {
        $user = $this->notifiable();
        $resolved = $user ? $user->resolvedNotificationPreferences($this->audience()) : [];

        foreach ($resolved as $kind => $p) {
            $this->prefs[$kind] = ['inapp' => $p['inapp'], 'email' => $p['email']];
        }
    }

    private function notifiable(): ?object
    {
        return Auth::guard('account')->user() ?? Auth::guard('web')->user();
    }

    private function audience(): string
    {
        return Auth::guard('account')->check() ? 'account' : 'staff';
    }

    public function savePrefs(): void
    {
        $this->notifiable()?->saveNotificationPreferences($this->prefs, $this->audience());
        session()->flash('ukc_status', 'Préférences de notification enregistrées.');
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
            'kinds' => NotificationKinds::forAudience($this->audience()),
        ])->layout($layout, ['title' => 'Notifications']);
    }
}
