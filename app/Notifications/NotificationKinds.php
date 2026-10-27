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
            'review_assigned' => ['label' => 'Évaluations qui me sont assignées', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'reviewer_application_accepted' => ['label' => 'Acceptation de ma candidature d’évaluateur', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'session_role' => ['label' => 'Désignations (modérateur / rapporteur)', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => false],
            'schedule_change' => ['label' => 'Changement d’horaire de mon passage', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => false],
            'report_reminder' => ['label' => 'Relances de rapport de séance', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'attestation' => ['label' => 'Attestations disponibles', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => false],
            'formation' => ['label' => 'Formations (inscriptions)', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'invitation' => ['label' => 'Invitations au colloque', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'authorship' => ['label' => 'Communications où je suis auteur/co-auteur', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => true],
            'payment' => ['label' => 'Paiements', 'audience' => 'both', 'inapp' => true, 'email' => true, 'hasMail' => true],
            // ⚠️ `hasMail => false` : il n'y a pas de Mailable dédié, c'est
            // l'e-mail GÉNÉRIQUE de la notification qui part. Posé à true, la
            // notification supprimerait son propre envoi en attendant un
            // Mailable qui n'existe pas - et personne ne serait prévenu.
            'payment_open' => ['label' => 'Ouverture du règlement des frais', 'audience' => 'account', 'inapp' => true, 'email' => true, 'hasMail' => false],
            'task_assigned' => ['label' => 'Tâches de projet qui me sont assignées', 'audience' => 'staff', 'inapp' => true, 'email' => true, 'hasMail' => false],
            'submission_new' => ['label' => 'Nouvelles communications', 'audience' => 'staff', 'inapp' => true, 'email' => false, 'hasMail' => false],
            // ⚠️ `email => true` et `hasMail => false` : le gestionnaire / co-gestionnaire
            // ciblé reçoit l'e-mail GÉNÉRIQUE en plus de l'in-app, et peut le décocher.
            // Une correction resoumise repasse en « Soumise » et se noierait sinon.
            'submission_resubmitted' => ['label' => 'Corrections resoumises', 'audience' => 'staff', 'inapp' => true, 'email' => true, 'hasMail' => false],
            'review_response' => ['label' => 'Réponses des évaluateurs (acceptation / refus)', 'audience' => 'staff', 'inapp' => true, 'email' => false, 'hasMail' => false],
            'contact' => ['label' => 'Messages de contact', 'audience' => 'staff', 'inapp' => true, 'email' => false, 'hasMail' => false],
            // ⚠️ `email => true` et `hasMail => false` : le destinataire (gestionnaire,
            // co-gestionnaire ou admin) reçoit l'e-mail GÉNÉRIQUE en plus de l'in-app,
            // et peut le décocher dans ses préférences. Posé à `hasMail => true`, la
            // notification supprimerait son propre envoi en attendant un Mailable qui
            // n'existe pas, et personne ne serait prévenu par e-mail.
            'claim_new' => ['label' => 'Réclamations & incidents signalés', 'audience' => 'staff', 'inapp' => true, 'email' => true, 'hasMail' => false],
            'testimonial' => ['label' => 'Témoignages déposés', 'audience' => 'staff', 'inapp' => true, 'email' => false, 'hasMail' => false],
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
