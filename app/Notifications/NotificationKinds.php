<?php

namespace App\Notifications;

/**
 * Registre des types de notifications (source unique). Pour chaque `kind` :
 * libellé, audience (account | staff | both), canaux par défaut (in-app /
 * e-mail), et `hasMail` = un Mailable dédié (riche) couvre déjà l'e-mail côté
 * participant. Quand `hasMail` est vrai, la notification n'envoie PAS son e-mail
 * générique (pour éviter le doublon) : c'est le Mailable dédié qui part, mais il
 * est désormais soumis à la préférence e-mail du destinataire. Les Mailables
 * dédiés étant tous côté participant, le staff (User) reçoit l'e-mail générique.
 */
class NotificationKinds
{
    /**
     * @return array<string, array{label: string, audience: string, inapp: bool, email: bool, hasMail: bool}>
     */
    public static function all(): array
    {
        return [
            'submission_status' => ['label' => 'Décisions sur mes communications', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'review_assigned' => ['label' => 'Relectures qui me sont assignées', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'attestation' => ['label' => 'Attestations disponibles', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => false],
            'invitation' => ['label' => 'Invitations au colloque', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'payment' => ['label' => 'Paiements', 'audience' => 'both', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'submission_new' => ['label' => 'Nouvelles communications', 'audience' => 'staff', 'inapp' => true, 'email' => false, 'hasMail' => false],
            'contact' => ['label' => 'Messages de contact', 'audience' => 'staff', 'inapp' => true, 'email' => false, 'hasMail' => false],
        ];
    }

    /** Définition d'un type (ou null si inconnu). */
    public static function get(string $kind): ?array
    {
        return self::all()[$kind] ?? null;
    }

    /**
     * Types pertinents pour une audience donnée ('account' ou 'staff').
     *
     * @return array<string, array{label: string, audience: string, inapp: bool, email: bool, hasMail: bool}>
     */
    public static function forAudience(string $audience): array
    {
        return array_filter(
            self::all(),
            fn ($def) => $def['audience'] === $audience || $def['audience'] === 'both'
        );
    }

    /** Canaux activés par défaut pour un type, sous forme { inapp, email }. */
    public static function defaults(string $kind): array
    {
        $def = self::get($kind);

        return [
            'inapp' => $def['inapp'] ?? true,
            'email' => $def['email'] ?? false,
        ];
    }

    /** Un Mailable dédié couvre déjà l'e-mail (côté participant) pour ce type. */
    public static function hasMail(string $kind): bool
    {
        return (bool) (self::get($kind)['hasMail'] ?? false);
    }
}
