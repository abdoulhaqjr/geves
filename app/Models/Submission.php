<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Observers\SubmissionObserver;
use App\Support\EventNameFormat;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Soumission d'un résumé : titre, co-auteurs (JSON), axe, type oral/poster,
 * mode de présentation propre à cette communication (online/onsite),
 * statut du workflow d’évaluation et versionnage (resoumission).
 * Statuts : {@see SubmissionStatus}.
 *
 * @property array<int, array<string, mixed>>|null $authors
 * @property array<string, mixed>|null $meta
 * @property Event|null $event
 * @property Participant|null $participant
 * @property float|null $reviews_avg_score agrégat withAvg('reviews', 'score')
 */
#[ObservedBy([SubmissionObserver::class])]
class Submission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** @return array<string, string> valeur => libellé (filtres/affichage). */
    public static function statuses(): array
    {
        return SubmissionStatus::options();
    }

    /** Modalité de présentation propre à cette communication. */
    public function presentationModeLabel(): ?string
    {
        return match ($this->presentation_mode) {
            'online' => 'En ligne',
            'onsite' => 'En présentiel',
            default => null,
        };
    }

    /**
     * Phase de réponse aux évaluateurs (rebuttal), ou null.
     *
     * @return array<string, mixed>|null
     */
    public function rebuttal(): ?array
    {
        $r = $this->meta['rebuttal'] ?? null;

        return is_array($r) ? $r : null;
    }

    /** La phase de réponse est-elle ouverte pour cette communication ? */
    public function rebuttalOpen(): bool
    {
        return ! empty($this->rebuttal()['opened_at']);
    }

    /** L'auteur a-t-il déposé sa réponse ? */
    public function hasRebuttal(): bool
    {
        return filled($this->rebuttal()['text'] ?? null);
    }

    public function rebuttalText(): ?string
    {
        $t = $this->rebuttal()['text'] ?? null;

        return filled($t) ? (string) $t : null;
    }

    /** @return array<string, string> valeur => variante de pastille. */
    public static function statusPills(): array
    {
        return SubmissionStatus::pills();
    }

    /** Communications originales (non révisions) déjà déposées par un compte sur un événement. */
    public static function usedCountFor(Event $event, User $account): int
    {
        return self::where('event_id', $event->id)
            ->where('revision_of', 0)
            ->whereHas('participant', fn ($q) => $q->where('user_id', $account->id))
            ->count();
    }

    /** Communications encore déposables par ce compte sur cet événement (0 = quota atteint). */
    public static function remainingFor(Event $event, User $account): int
    {
        $max = (int) ($event->settings['max_communications'] ?? 3);

        return max(0, $max - self::usedCountFor($event, $account));
    }

    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'meta' => 'array',
            'revision_of' => 'integer',
            'version' => 'integer',
        ];
    }

    /** Statut typé (ou null si valeur inconnue). */
    public function statusEnum(): ?SubmissionStatus
    {
        return SubmissionStatus::tryFrom((string) $this->status);
    }

    public function statusLabel(): string
    {
        return $this->statusEnum()?->label() ?? (string) $this->status;
    }

    public function statusPill(): string
    {
        return $this->statusEnum()?->pill() ?? 'muted';
    }

    /** Nom de l'auteur correspondant (1er des authors). */
    public function correspondingAuthorName(): ?string
    {
        return isset($this->authors[0])
            ? EventNameFormat::author($this->event, $this->authors[0])
            : $this->participant?->full_name;
    }

    /** E-mail de l'auteur correspondant (pour les notifications). */
    public function correspondingAuthorEmail(): ?string
    {
        return $this->authors[0]['email'] ?? $this->participant?->email;
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

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * ⚠️⚠️ **CE QUI EST ENCORE DANS LE CIRCUIT D'ÉVALUATION**, soit DEUX statuts :
     * une communication déposée n'a pas encore été mise en évaluation, mais elle
     * attend bel et bien une décision. L'indicateur « En revue » les compte
     * ensemble depuis toujours ; le filtre composite `in_review` lui permet
     * d'être CLIQUABLE sans mentir sur son propre chiffre - un `status` simple
     * aurait rendu moins de lignes que la carte n'en annonce.
     */
    public const STATUS_IN_REVIEW = 'in_review';

    /** @var list<string> */
    public const IN_REVIEW_STATUSES = ['submitted', 'under_review'];

    /**
     * États d'avancement de l'évaluation, pour le filtre de la liste.
     * ⚠️ Ils PARTITIONNENT la liste (`done` + `partial` + `none` = tout) ;
     * `late` les recoupe, c'est une alerte, pas une quatrième tranche.
     *
     * @var array<string, string>
     */
    public const REVIEW_STATES = [
        'done' => 'Toutes rendues',
        'partial' => 'Partiellement rendues',
        'none' => 'Aucune rendue',
        'late' => 'En retard',
    ];

    /**
     * Filtre sur l'avancement des évaluations.
     *
     * ⚠️⚠️ **ON NE DÉDUIT JAMAIS L'ATTENTE DE L'INACHÈVEMENT** : un
     * `status != 'completed'` compterait aussi les **refus** et les
     * **annulations**, qui ne tiennent plus de place - la communication
     * serait annoncée « en attente » alors que plus personne ne lui doit
     * rien. On passe donc par {@see Review::PENDING_STATUSES} et
     * {@see Review::ACTIVE_STATUSES}, exactement comme
     * {@see Review::progressOf()} qui alimente la colonne de la liste. Les
     * deux doivent dire la même chose : c'est le chiffre « 1/1 » affiché que
     * le filtre « Toutes rendues » prétend isoler.
     *
     * ⚠️ `none` = **aucune rendue**, qu'un évaluateur ait été assigné ou non :
     * pour le comité, « rien à lire » est un seul et même cas.
     *
     * @param  Builder<Submission>  $query
     * @return Builder<Submission>
     */
    public function scopeReviewsState(Builder $query, string $state): Builder
    {
        $rendue = fn (Builder $r) => $r->where('status', 'completed');
        $due = fn (Builder $r) => $r->whereIn('status', Review::PENDING_STATUSES);

        return match ($state) {
            'done' => $query->whereHas('reviews', $rendue)->whereDoesntHave('reviews', $due),
            'partial' => $query->whereHas('reviews', $rendue)->whereHas('reviews', $due),
            'none' => $query->whereDoesntHave('reviews', $rendue),
            // Même définition que Review::isLate() : encore due ET échéance
            // dépassée. ⚠️ Sans échéance, une évaluation n'est jamais en retard.
            'late' => $query->whereHas('reviews', fn (Builder $r) => $due($r)
                ->whereNotNull('due_at')->where('due_at', '<', now())),
            default => $query,
        };
    }

    /**
     * Liens auteurs ↔ comptes (correspondant + co-auteurs rapprochés/invités).
     *
     * @return HasMany<SubmissionAuthor, $this>
     */
    public function authorLinks(): HasMany
    {
        return $this->hasMany(SubmissionAuthor::class);
    }

    /**
     * Versions antérieures archivées lors des resoumissions.
     *
     * @return HasMany<Submission, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Submission::class, 'revision_of')->latest('version');
    }

    /** Ne garde que les soumissions canoniques (pas les archives de version). */
    public function scopeCanonical($query)
    {
        return $query->where('revision_of', 0);
    }

    /**
     * **Les communications que l'événement présente** : acceptées, dans leur
     * version canonique. C'est la règle du **livre des résumés** et de tout
     * **chiffre public** portant sur les communications.
     *
     * ⚠️ **Une seule définition, parce qu'elle a déjà divergé trois fois.** Les
     * résumés du livret filtraient ; son PROGRAMME lisait les créneaux sans rien
     * vérifier, donc une communication planifiée **puis refusée** gardait son
     * créneau et le livre imprimé annonçait son titre et ses auteurs à l'heure
     * dite. L'assistante publique comptait de même les communications d'une
     * séance **sans regarder leur statut**, et le compteur des éditions passées
     * filtrait le statut **mais pas** les resoumissions, qui comptaient double.
     */
    public function scopeAcceptedCanonical($query)
    {
        return $query->canonical()->where('status', SubmissionStatus::Accepted->value);
    }

    /** La même règle, sur une communication déjà chargée (cf. `scopeAcceptedCanonical`). */
    public function isAcceptedCanonical(): bool
    {
        return (int) $this->revision_of === 0 && $this->statusEnum() === SubmissionStatus::Accepted;
    }

    /** L'auteur peut resoumettre uniquement quand une correction est demandée. */
    public function canBeRevised(): bool
    {
        return $this->status === 'revision' && (int) $this->revision_of === 0;
    }

    /**
     * L'auteur peut encore corriger librement sa communication (titre/résumé/
     * mots-clés) TANT QU'aucun évaluateur, membre du comité ou gestionnaire n'y a
     * touché : statut initial « déposée » et aucune évaluation créée. Après la
     * première intervention du staff, la correction passe par le circuit officiel
     * de resoumission ({@see canBeRevised()}).
     *
     * ⚠️ Une troisième condition portait sur la **version définitive**, retirée du
     * processus : la demande de correction la remplace.
     */
    public function isAuthorEditable(): bool
    {
        return (int) $this->revision_of === 0
            && $this->status === 'submitted'
            && ! $this->reviews()->exists();
    }
}
