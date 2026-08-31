<?php

namespace App\Support;

use App\Models\Participant;
use App\Models\Submission;

/**
 * Modèles d'e-mails éditables : sujet, titre et texte d'introduction
 * personnalisables par l'admin (stockés dans le réglage `email_templates`),
 * avec repli sur des défauts. Les détails structurés (références, boutons) et le
 * gabarit de marque restent fixes. Placeholders remplacés au rendu.
 */
class EmailTemplates
{
    /** Placeholders disponibles (selon le contexte du modèle). */
    public const PLACEHOLDERS = ['{prenom}', '{nom_complet}', '{ref}', '{titre}', '{edition}'];

    /**
     * Modèles et leurs valeurs par défaut.
     *
     * @var array<string, array{label:string, subject:string, heading:string, intro:string}>
     */
    public const TEMPLATES = [
        'submission_submitted' => [
            'label' => 'Soumission — accusé de réception',
            'subject' => 'Accusé de réception de votre communication',
            'heading' => 'Communication bien reçue',
            'intro' => 'Nous accusons réception de votre communication. Elle sera examinée par le comité scientifique.',
        ],
        'submission_under_review' => [
            'label' => 'Soumission — mise en relecture',
            'subject' => 'Votre communication est en relecture',
            'heading' => 'Communication en relecture',
            'intro' => 'Votre communication a été transmise à la relecture. Vous serez informé(e) de la décision.',
        ],
        'submission_revision' => [
            'label' => 'Soumission — correction demandée',
            'subject' => 'Correction demandée pour votre communication',
            'heading' => 'Correction demandée',
            'intro' => 'Le comité vous demande d’apporter des corrections à votre communication avant décision.',
        ],
        'submission_accepted' => [
            'label' => 'Soumission — acceptée',
            'subject' => 'Votre communication est acceptée',
            'heading' => 'Communication acceptée',
            'intro' => 'Nous avons le plaisir de vous informer que votre communication est acceptée.',
        ],
        'submission_rejected' => [
            'label' => 'Soumission — refusée',
            'subject' => 'Décision concernant votre communication',
            'heading' => 'Communication non retenue',
            'intro' => 'Après examen, votre communication n’a pas été retenue pour cette édition.',
        ],
        'participant_invitation' => [
            'label' => 'Invitation participant',
            'subject' => 'Invitation',
            'heading' => 'Vous êtes invité(e) 🎓',
            'intro' => 'Nous avons le plaisir de vous convier à cet événement.',
        ],
    ];

    /** Balises de fusion proposées dans l'éditeur de modèle (puces cliquables). */
    public const MERGE_TAGS = [
        '{{participant_name}}', '{{participant_email}}', '{{edition_name}}', '{{edition_dates}}',
        '{{communication_title}}', '{{amount}}', '{{payment_ref}}', '{{attestation_url}}',
        '{{badge_url}}', '{{login_url}}', '{{session_date}}', '{{session_room}}',
    ];

    /** Options de déclencheur proposées à la création/édition d'un modèle. */
    public const TRIGGER_OPTIONS = [
        'Manuel / programmé', 'Paiement reçu', 'Soumission reçue', 'Correction demandée',
        'Résumé accepté', 'Résumé refusé', 'Envoi attestation', 'Badge disponible', 'Rappel (programmé)',
    ];

    /** Déclencheur (événement) qui envoie automatiquement chaque modèle. */
    public const TRIGGERS = [
        'submission_submitted' => 'Soumission reçue',
        'submission_under_review' => 'Mise en relecture',
        'submission_revision' => 'Correction demandée',
        'submission_accepted' => 'Résumé accepté',
        'submission_rejected' => 'Résumé refusé',
        'participant_invitation' => 'Invitation (manuel)',
    ];

    /** Modèles personnalisés créés depuis le back-office (hors modèles intégrés). */
    public static function customKeys(): array
    {
        $store = (array) Settings::get('email_templates', []);

        return array_values(array_diff(array_keys($store), array_keys(self::TEMPLATES)));
    }

    /** Toutes les clés de modèles : intégrés puis personnalisés. */
    public static function keys(): array
    {
        return array_merge(array_keys(self::TEMPLATES), self::customKeys());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::TEMPLATES)
            || array_key_exists($key, (array) Settings::get('email_templates', []));
    }

    /** Un modèle personnalisé (créé par l'admin) vs. un modèle intégré. */
    public static function isCustom(string $key): bool
    {
        return ! array_key_exists($key, self::TEMPLATES)
            && array_key_exists($key, (array) Settings::get('email_templates', []));
    }

    /** Libellé lisible d'un modèle (repli sur « Campagne » / la clé). */
    public static function label(string $key): string
    {
        if ($key === 'campaign') {
            return 'Campagne';
        }
        if (isset(self::TEMPLATES[$key]['label'])) {
            return (string) self::TEMPLATES[$key]['label'];
        }
        $store = (array) Settings::get('email_templates', []);

        return (string) ($store[$key]['label'] ?? $key);
    }

    /** Déclencheur lisible d'un modèle. */
    public static function trigger(string $key): string
    {
        if (isset(self::TRIGGERS[$key])) {
            return self::TRIGGERS[$key];
        }
        $store = (array) Settings::get('email_templates', []);

        return (string) ($store[$key]['trigger'] ?? self::TRIGGER_OPTIONS[0]);
    }

    /** Corps du message (override admin, sinon dérivé du titre + intro par défaut). */
    public static function body(string $key): string
    {
        $override = (array) (Settings::get('email_templates', [])[$key] ?? []);
        if (filled($override['body'] ?? null)) {
            return (string) $override['body'];
        }

        $raw = self::raw($key);

        return trim(($raw['heading'] ? $raw['heading']."\n\n" : '').$raw['intro']);
    }

    /** Modèle actif (override admin, sinon actif par défaut). */
    public static function active(string $key): bool
    {
        $override = (array) (Settings::get('email_templates', [])[$key] ?? []);

        return ! array_key_exists('active', $override) || (bool) $override['active'];
    }

    /**
     * Valeurs (brutes, non rendues) d'un modèle : override admin ou défaut.
     *
     * @return array{subject:string, heading:string, intro:string}
     */
    public static function raw(string $key): array
    {
        $default = self::TEMPLATES[$key] ?? ['subject' => '', 'heading' => '', 'intro' => ''];
        $overrides = (array) Settings::get('email_templates', []);
        $o = $overrides[$key] ?? [];

        return [
            'subject' => filled($o['subject'] ?? null) ? (string) $o['subject'] : (string) $default['subject'],
            'heading' => filled($o['heading'] ?? null) ? (string) $o['heading'] : (string) $default['heading'],
            'intro' => filled($o['intro'] ?? null) ? (string) $o['intro'] : (string) $default['intro'],
        ];
    }

    /** Remplace les placeholders `{cle}` par les valeurs du contexte. */
    public static function render(string $text, array $context): string
    {
        $map = [];
        foreach ($context as $k => $v) {
            $map['{'.$k.'}'] = (string) $v;
        }

        return strtr($text, $map);
    }

    /**
     * Modèle rendu pour une soumission (statut du workflow).
     *
     * @return array{subject:string, heading:string, intro:string}
     */
    public static function forSubmission(Submission $submission, string $statusKey): array
    {
        $tpl = self::raw('submission_'.$statusKey);

        $context = [
            'prenom' => self::firstName($submission->correspondingAuthorName()),
            'nom_complet' => (string) ($submission->correspondingAuthorName() ?? ''),
            'ref' => (string) $submission->ref_code,
            'titre' => (string) $submission->title,
            'edition' => (string) ($submission->edition?->title ?? ''),
        ];

        return self::renderAll($tpl, $context);
    }

    /**
     * Modèle rendu pour l'invitation d'un participant.
     *
     * @return array{subject:string, heading:string, intro:string}
     */
    public static function forParticipant(Participant $participant): array
    {
        $tpl = self::raw('participant_invitation');

        $context = [
            'prenom' => (string) $participant->first_name,
            'nom_complet' => $participant->full_name,
            'edition' => (string) ($participant->edition?->title ?? ''),
        ];

        return self::renderAll($tpl, $context);
    }

    /**
     * @param  array{subject:string, heading:string, intro:string}  $tpl
     * @return array{subject:string, heading:string, intro:string}
     */
    protected static function renderAll(array $tpl, array $context): array
    {
        return [
            'subject' => self::render($tpl['subject'], $context),
            'heading' => self::render($tpl['heading'], $context),
            'intro' => self::render($tpl['intro'], $context),
        ];
    }

    protected static function firstName(?string $fullName): string
    {
        return trim(explode(' ', trim((string) $fullName), 2)[0] ?? '');
    }
}
