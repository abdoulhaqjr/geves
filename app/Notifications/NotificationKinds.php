<?php

namespace App\Notifications;

/**
 * Registre des types de notifications (source unique). Pour chaque `kind` :
 * libellé lisible, audience (account | staff | both), et canaux activés par
 * défaut. L'e-mail est par défaut OFF quand un Mailable dédié couvre déjà
 * l'événement (soumission, invitation, relecture, reçu), ON quand aucun e-mail
 * n'existait (attestation, nouvelles communications côté staff, contact).
 */
class NotificationKinds
{
    /**
     * @return array<string, array{label: string, audience: string, inapp: bool, email: bool}>
     */
    public static function all(): array
    {
        return [
            'submission_status' => ['label' => 'Décisions sur mes communications', 'audience' => 'account', 'inapp' => true, 'email' => false],
            'review_assigned' => ['label' => 'Relectures qui me sont assignées', 'audience' => 'account', 'inapp' => true, 'email' => false],
            'attestation' => ['label' => 'Attestations disponibles', 'audience' => 'account', 'inapp' => true, 'email' => true],
            'invitation' => ['label' => 'Invitations au colloque', 'audience' => 'account', 'inapp' => true, 'email' => false],
            'payment' => ['label' => 'Paiements', 'audience' => 'both', 'inapp' => true, 'email' => false],
            'submission_new' => ['label' => 'Nouvelles communications', 'audience' => 'staff', 'inapp' => true, 'email' => true],
            'contact' => ['label' => 'Messages de contact', 'audience' => 'staff', 'inapp' => true, 'email' => true],
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
     * @return array<string, array{label: string, audience: string, inapp: bool, email: bool}>
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
}
