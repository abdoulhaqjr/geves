<?php

namespace App\Support;

use App\Models\Account;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

/**
 * Point d'entrée unique pour émettre des notifications in-app (canal base de
 * données). Découple les appelants (Actions, listeners) de la mécanique
 * Notification de Laravel. Les e-mails transactionnels restent gérés à part —
 * l'in-app est additif.
 */
class Notifier
{
    /** Notifie un compte participant. Ignore silencieusement un compte absent. */
    public static function account(?Account $account, string $kind, string $title, string $body = '', ?string $url = null, string $icon = 'bell'): void
    {
        $account?->notify(new SystemNotification($kind, $title, $body, $url, $icon));
    }

    /**
     * L'e-mail d'un Mailable dédié est-il autorisé pour ce destinataire ?
     * Un destinataire sans compte (pas de préférences) reçoit toujours l'e-mail ;
     * sinon la préférence e-mail du type fait foi. Sert à gater les Mailables
     * riches existants (soumission, reçu, invitation, relecture).
     */
    public static function emailAllowed(?Account $account, string $kind): bool
    {
        return $account === null || $account->wantsNotification($kind, 'email');
    }

    /**
     * Notifie l'ensemble du personnel back-office (permission manage_colloques).
     * Repli sur tous les utilisateurs si la permission n'est portée par personne.
     */
    public static function staff(string $kind, string $title, string $body = '', ?string $url = null, string $icon = 'bell'): void
    {
        // La permission peut ne pas être enregistrée (contextes hors seed :
        // webhooks, tests) → le scope spatie lèverait PermissionDoesNotExist.
        // Repli sur l'ensemble des utilisateurs (tous membres du back-office).
        $permissionExists = Permission::where('name', 'manage_colloques')
            ->where('guard_name', 'web')->exists();

        $staff = $permissionExists
            ? User::permission('manage_colloques')->get()
            : User::all();

        if ($staff->isEmpty()) {
            return;
        }

        Notification::send($staff, new SystemNotification($kind, $title, $body, $url, $icon));
    }
}
