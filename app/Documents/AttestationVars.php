<?php

namespace App\Documents;

use App\Models\Attestation;
use App\Support\EventSettings;

/**
 * Valeurs de rendu d'une attestation délivrée (aperçu HTML comme PDF).
 *
 * Les deux chemins de rendu dressaient la même liste chacun de leur côté : un
 * jeton ajouté à l'un manquait à l'autre. Source unique ici, pour que le PDF
 * téléchargé dise exactement ce que montre la page.
 */
class AttestationVars
{
    /** @return array<string, mixed> */
    public static function for(Attestation $attestation, string $verifyUrl): array
    {
        $event = $attestation->event;
        $meta = (array) ($attestation->meta ?? []);

        return [
            'org' => $event?->series?->name ?? '',
            'event' => $event?->title ?? '',
            'year' => (string) ($event?->year ?: ''),
            'location' => $event?->location ?? '',
            // Valeurs nommées par la palette du concepteur ([edition.theme],
            // [recipient.role]…) : sans elles, ses jetons restaient vides.
            'theme' => (string) ($event?->theme ?? ''),
            'dates' => $event?->dateRangeLabel() ?? '',
            'institution' => (string) ($attestation->participant?->institution ?? ''),
            'role' => (string) ($attestation->participant?->roleLabel() ?? ''),
            // Civilité réelle du bénéficiaire (M. / Mme / Mlle), lue sur son compte.
            // data_get : la relation user est nullable au runtime (Larastan la croit
            // non-nulle → `?->` déclencherait nullsafe.neverNull).
            'civility' => (string) (data_get($attestation, 'participant.user.civility') ?? ''),
            'name' => (string) $attestation->recipient_name,
            'type' => mb_strtolower($attestation->typeLabel()),
            'date' => $attestation->issued_at?->format('d/m/Y') ?? '',
            'ref' => $attestation->uuid,
            'verify_url' => $verifyUrl,
            // Signataire de l'événement : les modèles livrés portent [signatory.name]
            // sous la mention « Le Président du comité d'organisation ». Sans
            // valeur, la ligne sortait vide sur le document délivré alors que le
            // concepteur, lui, affichait un exemple.
            'signatory' => (string) EventSettings::get($event, 'attestations.signatory', ''),
            'signatory_role' => (string) EventSettings::get($event, 'attestations.signatory_role', ''),
            // Communication concernée (attestation de communication) - vide pour les autres types.
            'communication' => (string) ($meta['communication_title'] ?? ''),
            'communication_ref' => (string) ($meta['communication_ref'] ?? ''),
            // Format de présentation (« Orale » / « Affichée »), figé à l'émission.
            'communication_type' => (string) ($meta['communication_type'] ?? ''),
            // Formation concernée (attestation de formation / animation) - vide sinon.
            'formation' => (string) ($meta['formation_title'] ?? ''),
            'formation_theme' => (string) ($meta['formation_theme'] ?? ''),
            'formateurs' => implode(', ', (array) ($meta['formation_trainers'] ?? [])),
            // Signatures figées à l'émission (élément « signatures » du concepteur).
            'signatures' => (array) ($meta['formation_signatures'] ?? []),
        ];
    }
}
