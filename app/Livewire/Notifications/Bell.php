<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Cloche de notifications — îlot Livewire déposé dans les topbars (participant
 * et back-office). Guard-aware : cible le compte ou l'utilisateur staff connecté.
 * Affiche le nombre de non-lus et les dernières notifications ; marque comme lu
 * (et redirige) au clic. Rafraîchi périodiquement (wire:poll).
 */
class Bell extends Component
{
    /** Utilisateur notifiable courant, quel que soit le guard actif. */
    private function notifiable(): ?object
    {
        return Auth::guard('account')->user() ?? Auth::guard('web')->user();
    }

    /** Nom de la route « tout voir » selon le guard. */
    public function allRoute(): string
    {
        return Auth::guard('account')->check() ? 'account.notifications' : 'admin.notifications';
    }

    /** Marque une notification comme lue puis redirige vers sa cible. */
    public function open(string $id)
    {
        $user = $this->notifiable();
        $notification = $user?->notifications()->whereKey($id)->first();

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

        return view('livewire.notifications.bell', [
            'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
            'recent' => $user ? $user->notifications()->latest()->limit(8)->get() : collect(),
        ]);
    }
}
