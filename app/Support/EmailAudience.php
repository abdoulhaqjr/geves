<?php

namespace App\Support;

use App\Models\Edition;
use App\Models\Participant;
use Illuminate\Support\Collection;

/**
 * Résout la liste des destinataires d'une campagne d'e-mails selon une
 * audience ciblée sur une édition. Ne retient que les participants ayant une
 * adresse e-mail, dédoublonnés par e-mail (insensible à la casse).
 */
class EmailAudience
{
    /** Audiences disponibles (clé => libellé). */
    public const AUDIENCES = [
        'all' => 'Tous les participants',
        'paid' => 'Ont réglé leurs frais',
        'unpaid' => 'N’ont pas réglé',
        'accepted' => 'Communication acceptée',
        'reviewers' => 'Relecteurs',
        'not_invited' => 'Pas encore invités',
    ];

    public static function label(string $audience): string
    {
        return self::AUDIENCES[$audience] ?? $audience;
    }

    public static function isValid(string $audience): bool
    {
        return array_key_exists($audience, self::AUDIENCES);
    }

    /**
     * @return Collection<int, Participant>
     */
    public static function resolve(Edition $edition, string $audience): Collection
    {
        $query = Participant::query()
            ->where('edition_id', $edition->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->with('edition');

        match ($audience) {
            'paid' => $query->whereHas('participationPayments', fn ($q) => $q->where('status', 'paid')),
            'unpaid' => $query->whereDoesntHave('participationPayments', fn ($q) => $q->where('status', 'paid')),
            'accepted' => $query->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted')),
            'reviewers' => $query->where('role', 'reviewer'),
            default => null, // 'all' + audiences filtrées en mémoire ci-dessous
        };

        $participants = $query->get()
            ->unique(fn (Participant $p) => mb_strtolower((string) $p->email))
            ->values();

        if ($audience === 'not_invited') {
            $participants = $participants->reject(fn (Participant $p) => $p->isInvited())->values();
        }

        return $participants;
    }

    public static function count(Edition $edition, string $audience): int
    {
        return self::resolve($edition, $audience)->count();
    }
}
