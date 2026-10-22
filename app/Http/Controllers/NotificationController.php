<?php

namespace App\Http\Controllers;

use App\Notifications\NotificationKinds;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Cloche + page « Notifications », partagées par l'espace participant (guard
 * « account ») et le back-office (guard « web »). Le notifiable et l'audience de
 * préférences sont résolus selon le guard actif. Remplace les anciens îlots
 * Livewire `Notifications\Bell` et `Notifications\Center` (refonte sans Livewire).
 */
class NotificationController extends Controller
{
    /** Notifiable courant, quel que soit le guard actif. */
    private function notifiable(): ?object
    {
        return Auth::guard('account')->user() ?? Auth::guard('web')->user();
    }

    /** Audience de préférences selon le guard actif. */
    private function audience(): string
    {
        return Auth::guard('account')->check() ? 'account' : 'staff';
    }

    /** Relecture de la cloche après un signal WebSocket (source : base de données). */
    public function preview(): JsonResponse
    {
        $user = $this->notifiable();
        abort_unless($user, 403);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items_html' => view('components.ukc.notification-bell-items', [
                'recent' => $user->notifications()->latest()->limit(8)->get(),
            ])->render(),
        ])->header('Cache-Control', 'private, no-store');
    }

    /** Page complète « Notifications » (liste paginée + préférences). */
    public function index(): View
    {
        $user = $this->notifiable();
        $isAccount = Auth::guard('account')->check();

        $resolved = $user ? $user->resolvedNotificationPreferences($this->audience()) : [];
        $prefs = [];
        foreach ($resolved as $kind => $p) {
            $prefs[$kind] = ['inapp' => $p['inapp'], 'email' => $p['email']];
        }

        return view('notifications.center', [
            'isAccount' => $isAccount,
            'notifications' => $user ? $user->notifications()->latest()->paginate(20)->withQueryString() : collect(),
            'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
            'kinds' => NotificationKinds::forAudience($this->audience()),
            'prefs' => $prefs,
        ]);
    }

    /** Enregistre les préférences de notification (par type / canal). */
    public function savePrefs(Request $request): RedirectResponse
    {
        $user = $this->notifiable();
        $audience = $this->audience();

        $prefs = [];
        foreach (NotificationKinds::forAudience($audience) as $kind => $def) {
            $prefs[$kind] = [
                'inapp' => $request->boolean("prefs.$kind.inapp"),
                'email' => $request->boolean("prefs.$kind.email"),
            ];
        }

        $user?->saveNotificationPreferences($prefs, $audience);

        return back()->with('ukc_status', 'Préférences de notification enregistrées.');
    }

    /** Marque une notification comme lue puis redirige vers sa cible. */
    public function open(string $id): RedirectResponse
    {
        $user = $this->notifiable();
        if (! $user) {
            abort(403);
        }

        $notification = $user->notifications()->whereKey($id)->first();

        if ($notification) {
            $notification->markAsRead();

            $url = $notification->data['url'] ?? null;
            if ($url) {
                return redirect()->to($url);
            }
        }

        return back();
    }

    /** Marque toutes les notifications non lues comme lues. */
    public function readAll(): RedirectResponse
    {
        $this->notifiable()?->unreadNotifications->markAsRead();

        return back();
    }
}
