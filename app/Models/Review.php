<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relecture en aveugle d'une soumission par un relecteur (participant).
 *
 * @property array<string, int>|null $scores notes par critère de la grille
 * @property Carbon|null $assigned_at
 * @property Carbon|null $due_at échéance de rendu (cf. ReviewGrid::dueDays)
 * @property Carbon|null $responded_at
 */
class Review extends Model
{
    protected $guarded = ['id'];

    /** Recommandations possibles d'un relecteur. */
    public const RECOMMENDATIONS = [
        'accept' => 'Accepter en l’état',
        'minor' => 'Révisions mineures',
        'major' => 'Révisions majeures',
        'reject' => 'Rejeter',
    ];

    /**
     * Cycle de vie d'une relecture. `invited` : confiée, le relecteur n'a pas
     * encore répondu. `accepted` : il s'est engagé. `declined` : il a refusé (la
     * place est libérée, la soumission redevient à couvrir). `completed` : rendue.
     * `cancelled` : retirée par l'organisation.
     */
    public const STATUSES = [
        'invited' => 'Invitée',
        'accepted' => 'Acceptée',
        'declined' => 'Refusée',
        'completed' => 'Rendue',
        'cancelled' => 'Annulée',
    ];

    /**
     * Statuts qui « occupent une place » sur la soumission. Un refus ou une
     * annulation ne comptent pas : la soumission est de nouveau à couvrir.
     */
    public const ACTIVE_STATUSES = ['invited', 'accepted', 'completed'];

    /**
     * Statuts « encore dus » : le relecteur a quelque chose à faire (confirmer
     * ou évaluer). ⚠️ C'est la bonne liste pour un compteur « à traiter » -
     * un `status != completed` compterait aussi les refus et les annulations.
     */
    public const PENDING_STATUSES = ['invited', 'accepted'];

    /** Durée d'évaluation par défaut, à défaut de réglage de l'édition (jours). */
    public const DEFAULT_DUE_DAYS = 21;

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'scores' => 'array',
            'assigned_at' => 'datetime',
            'due_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Avancement compact d'un lot de relectures, pour une liste :
     * `['done' => 1, 'total' => 2, 'late' => 1, 'lateDays' => 3]`. Même règle
     * que summarize() - seules les relectures actives comptent.
     *
     * @param  iterable<int, self>  $reviews
     * @return array{done:int, total:int, late:int, lateDays:int}
     */
    public static function progressOf(iterable $reviews): array
    {
        $active = collect($reviews)->filter(fn (self $r) => $r->isActive());
        $late = $active->filter(fn (self $r) => $r->isLate());

        return [
            'done' => $active->where('status', 'completed')->count(),
            'total' => $active->count(),
            'late' => $late->count(),
            'lateDays' => $late->isEmpty() ? 0 : (int) $late->max(fn (self $r) => $r->daysLate()),
        ];
    }

    /**
     * Phrase d'état d'un lot de relectures, telle que le comité la lit :
     * « 2 relectures demandées - 1 rendue, 1 en retard de 3 j ». Les relectures
     * annulées ne comptent pas ; les refus sont mentionnés à part (ils ont bien
     * eu lieu, mais la place est libre).
     *
     * @param  iterable<int, self>  $reviews
     */
    public static function summarize(iterable $reviews): string
    {
        $all = collect($reviews);
        $active = $all->filter(fn (self $r) => $r->isActive());
        $declined = $all->where('status', 'declined')->count();

        if ($active->isEmpty()) {
            return $declined > 0
                ? 'Aucune relecture en cours - '.$declined.' refus.'
                : 'Aucun relecteur assigné.';
        }

        $late = $active->filter(fn (self $r) => $r->isLate());
        $done = $active->where('status', 'completed')->count();
        $waiting = $active->filter(fn (self $r) => $r->isPending())->count() - $late->count();

        $parts = [];
        if ($done > 0) {
            $parts[] = $done.' rendue'.($done > 1 ? 's' : '');
        }
        if ($waiting > 0) {
            $parts[] = $waiting.' en attente';
        }
        if ($late->isNotEmpty()) {
            $worst = (int) $late->max(fn (self $r) => $r->daysLate());
            $parts[] = $late->count().' en retard'.($worst > 0 ? ' de '.$worst.' j' : '');
        }

        $count = $active->count();
        $text = $count.' relecture'.($count > 1 ? 's' : '').' demandée'.($count > 1 ? 's' : '');
        if ($parts !== []) {
            $text .= ' - '.implode(', ', $parts);
        }
        if ($declined > 0) {
            $text .= ' ('.$declined.' refus)';
        }

        return $text;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Pastille de la charte associée au statut. */
    /**
     * Teinte de la pastille d'ÉTAT. ⚠️ Le retard n'est pas un état : il a son
     * propre marqueur rouge à côté (échéance / « N j de retard »). Teinter la
     * pastille en rouge en plus rendait les deux signaux redondants et faisait
     * lire « Invitée » en rouge, ce qui n'a pas de sens.
     */
    public function statusPill(): string
    {
        return match ($this->status) {
            'completed' => 'ok',
            'accepted' => 'info',
            'declined', 'cancelled' => 'muted',
            default => 'warn',
        };
    }

    /** Relecture attendue : confiée ou acceptée, pas encore rendue. */
    public function isPending(): bool
    {
        return in_array($this->status, self::PENDING_STATUSES, true);
    }

    /** Occupe une place sur la soumission (compte dans la couverture). */
    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /**
     * En retard : attendue ET échéance dépassée. ⚠️ Ne pas confondre avec
     * « non rendue » - sans échéance, une relecture n'est jamais en retard.
     */
    public function isLate(): bool
    {
        return $this->isPending() && $this->due_at !== null && $this->due_at->isPast();
    }

    /** Nombre de jours de retard (0 si dans les temps). */
    public function daysLate(): int
    {
        return $this->isLate() ? (int) $this->due_at->diffInDays(now()) : 0;
    }

    public function recommendationLabel(): ?string
    {
        return self::RECOMMENDATIONS[$this->recommendation] ?? ($this->recommendation ?: null);
    }

    /**
     * Notes par critère (grille structurée).
     *
     * @return array<string, int>
     */
    public function criterionScores(): array
    {
        return is_array($this->scores) ? $this->scores : [];
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /** @return BelongsTo<Submission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /** @return BelongsTo<Participant, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'reviewer_id');
    }
}
