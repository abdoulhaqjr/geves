<?php

namespace App\Models;

use App\Observers\AttestationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Attestation émise (participation, communication, modération, rapporteur) avec
 * UUID de vérification publique.
 *
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $issued_at
 * @property Carbon|null $delivered_at
 * @property string|null $credential
 * @property string|null $key_id
 * @property Carbon|null $signed_at
 */
#[ObservedBy([AttestationObserver::class])]
class Attestation extends Model
{
    protected $guarded = ['id'];

    /** Types d'attestation. */
    public const TYPES = [
        'soumission' => 'Soumission',
        'acceptation' => 'Acceptation',
        'participation' => 'Participation',
        'communication' => 'Communication',
        'moderation' => 'Modération',
        'rapporteur' => 'Rapporteur',
        'organisation' => 'Participation à l’organisation',
        'comite_organisation' => 'Comité d’organisation',
        'formation' => 'Formation',
        'formation_animation' => 'Animation de formation',
        'instruction_resumes' => 'Instruction de résumés',
    ];

    /**
     * Intitulé COMPLET d'une attestation, tel qu'il se lit sur un écran.
     *
     * ⚠️ **Une seule source.** Le hub des documents du back-office dressait cette
     * liste pour lui seul (`DocumentHubController::$typeMeta`), et « Mes
     * documents » allait en inventer une seconde - elles divergeaient déjà sur
     * l'animation de formation (« Attestation d'animation » d'un côté,
     * « … de formation » de l'autre). C'est le libellé long qui est retenu : il
     * dit de quoi il s'agit sans contexte, et une page de participant n'a pas
     * d'en-tête de colonne pour le préciser.
     *
     * ⚠️ `TYPES` reste le libellé COURT (« Modération »), celui des filtres et
     * des pastilles ; les deux ne se remplacent pas.
     *
     * @return array<string, string> type => intitulé
     */
    public static function titles(): array
    {
        return [
            'communication' => 'Attestation de communication',
            'participation' => 'Attestation de participation',
            'moderation' => 'Attestation de modération',
            'rapporteur' => 'Attestation de rapporteur',
            'organisation' => 'Attestation de participation à l’organisation',
            'comite_organisation' => 'Attestation du comité d’organisation',
            'instruction_resumes' => 'Attestation d’instruction de résumés',
            'formation' => 'Attestation de formation',
            'formation_animation' => 'Attestation d’animation de formation',
        ];
    }

    /**
     * Intitulé complet de CETTE attestation.
     *
     * ⚠️ Repli sur le libellé court : « soumission » et « acceptation » ne sont
     * plus offerts mais restent en base, et une attestation délivrée autrefois
     * doit continuer de s'afficher avec un nom.
     */
    public function title(): string
    {
        return self::titles()[$this->type] ?? ('Attestation - '.$this->typeLabel());
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function isValid(): bool
    {
        return ! $this->revoked;
    }

    /**
     * Remise en main propre ? (délivrance papier)
     *
     * ⚠️ **Émettre n'est pas remettre.** Une attestation peut être émise,
     * imprimée, et attendre des mois dans une chemise parce que personne ne
     * sait ce qui reste à distribuer.
     */
    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    /**
     * Titre de la formation que cette attestation porte (vide sinon).
     *
     * ⚠️ `meta` d'abord : il est **figé à l'émission**, alors que la formation
     * peut avoir été renommée depuis. Une attestation doit dire ce qu'elle disait
     * le jour où elle a été délivrée, pas ce que le catalogue dit aujourd'hui.
     */
    public function formationTitle(): string
    {
        return (string) ($this->meta['formation_title'] ?? '')
            ?: (string) $this->formation?->title;
    }

    /** L'attestation porte-t-elle une signature cryptographique ? */
    public function isSigned(): bool
    {
        return filled($this->credential);
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'revoked' => 'boolean',
            'issued_at' => 'datetime',
            'delivered_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Participant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /** Communication concernée (attestations de communication uniquement ; sinon null). */
    /** @return BelongsTo<Submission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /** Formation concernée (attestations de formation / animation ; sinon null). */
    /** @return BelongsTo<Formation, $this> */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }
}
