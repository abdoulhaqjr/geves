<?php

namespace App\Models;

use App\Enums\EventPhase;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Événement : une occurrence datée d'un colloque, avec son cycle de vie et son
 * contenu public. Les réglages libres (frais, contenu enrichi…) sont en JSON.
 *
 * @property array<string, mixed>|null $settings réglages libres (frais, axes, contenu public…)
 * @property Carbon|null $date_start
 * @property Carbon|null $date_end
 * @property Carbon|null $submission_deadline
 * @property int $year
 * @property bool $submission_open
 * @property string $status
 */
class Event extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public const STATUSES = ['draft', 'open', 'closed', 'archived'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'date_start' => 'date',
            'date_end' => 'date',
            'submission_deadline' => 'datetime',
            'submission_open' => 'boolean',
            'year' => 'integer',
        ];
    }

    /** @return BelongsTo<EventSeries, $this> */
    public function series(): BelongsTo
    {
        // FK explicite : le nom de méthode « series » ferait déduire « series_id »,
        // or la colonne est « event_series_id ».
        return $this->belongsTo(EventSeries::class, 'event_series_id');
    }

    /**
     * Type de l'événement, hérité de sa série ({@see EventSeries}). Défaut : colloque
     * (le type historique) quand la série est absente.
     */
    public function type(): EventType
    {
        return $this->series?->type ?? EventType::Colloque;
    }

    /** @return HasMany<Participant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Équipe staff affectée à cette événement (fonction dans le pivot).
     *
     * @return BelongsToMany<User, $this>
     */
    public function team(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')->withPivot('role')->withTimestamps();
    }

    /** @return HasMany<Submission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Atelier, $this> */
    public function ateliers(): HasMany
    {
        return $this->hasMany(Atelier::class);
    }

    /** @return HasMany<EventSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    /** @return HasMany<Slot, $this> */
    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    /** @return HasMany<Attestation, $this> */
    public function attestations(): HasMany
    {
        return $this->hasMany(Attestation::class);
    }

    /** @return HasMany<Email, $this> */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    /** @return HasMany<CommitteeMember, $this> */
    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    /** @return HasMany<Media, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function isPublished(): bool
    {
        return in_array($this->status, ['open', 'closed', 'archived'], true);
    }

    /** Le module Formations est-il activé pour cette événement ? (réglage du formulaire d'événement). */
    public function formationEnabled(): bool
    {
        return (bool) ($this->settings['formation_enabled'] ?? false);
    }

    /** Champs de contenu traduisibles (i18n) : attribut de base = version française. */
    public const TRANSLATABLE = ['title', 'theme', 'event_label', 'location'];

    /**
     * Valeur d'un champ de contenu dans la langue courante (i18n) : renvoie la
     * traduction stockée dans `settings['i18n'][<locale>]` si elle existe, sinon
     * la valeur de base (français). Extensible à d'autres champs via TRANSLATABLE.
     */
    public function tr(string $field, ?string $locale = null): string
    {
        $base = (string) ($this->{$field} ?? '');
        $locale ??= app()->getLocale();

        if ($locale === config('app.fallback_locale')) {
            return $base;
        }

        $value = $this->settings['i18n'][$locale][$field] ?? null;

        return filled($value) ? (string) $value : $base;
    }

    /**
     * Traductions saisies pour une locale (champ => valeur), pour préremplir le
     * back-office.
     *
     * @return array<string, string>
     */
    public function translations(string $locale): array
    {
        $set = $this->settings['i18n'][$locale] ?? [];

        return is_array($set) ? $set : [];
    }

    /**
     * Phase du cycle de vie (guide §2.2), dérivée sans requête depuis le statut de
     * publication, les dates de l'événement et l'ouverture des soumissions.
     * Source unique pour les libellés/pastilles et les 3 vues publiques.
     */
    /**
     * Libellé lisible de la période de l'événement, robuste aux plages qui
     * chevauchent deux mois ou deux années (ex. « 27 septembre - 4 octobre 2026 »
     * et non « 27 - 4 octobre 2026 » qui masque le mois de début). Tiret simple
     * conformément à la charte (jamais de tiret typographique). Source unique
     * réutilisée par les vues publiques.
     */
    public function dateRangeLabel(): ?string
    {
        $start = $this->date_start;
        if (! $start) {
            return null;
        }
        $end = $this->date_end;

        if (! $end || $end->isSameDay($start)) {
            return $start->translatedFormat('j F Y');
        }
        if ($start->isSameMonth($end)) { // même mois ET même année
            return $start->translatedFormat('j').' - '.$end->translatedFormat('j F Y');
        }
        if ($start->year === $end->year) { // même année, mois différents
            return $start->translatedFormat('j F').' - '.$end->translatedFormat('j F Y');
        }

        return $start->translatedFormat('j F Y').' - '.$end->translatedFormat('j F Y');
    }

    public function phase(): EventPhase
    {
        if ($this->status === 'draft') {
            return EventPhase::Draft;
        }
        // Clôture explicite par l'admin.
        if ($this->status === 'archived' || $this->status === 'closed') {
            return EventPhase::Closed;
        }

        $today = Carbon::today();
        $now = Carbon::now();

        // Déroulement : les dates de l'événement sont atteintes.
        if ($this->date_start && $this->date_end
            && $this->date_start->lte($today) && $this->date_end->gte($today)) {
            return EventPhase::Ongoing;
        }
        // Événement passé (date de fin dépassée).
        if ($this->date_end && $this->date_end->lt($today)) {
            return EventPhase::Closed;
        }

        // Pré-événement (futur ou sans dates) : phase selon l'appel à communications.
        if ($this->submission_open
            && (! $this->submission_deadline || $this->submission_deadline->gte($now))) {
            return EventPhase::CallOpen;
        }
        if ($this->submission_deadline && $this->submission_deadline->lt($now)) {
            return EventPhase::UnderReview; // soumissions closes → évaluation
        }

        return EventPhase::Registration; // pas d'appel actif, événement à venir → inscriptions
    }
}
