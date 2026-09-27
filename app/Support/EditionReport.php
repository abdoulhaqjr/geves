<?php

namespace App\Support;

use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Agrège les données de reporting d'une édition : synthèse chiffrée et jeux de
 * lignes (participants, communications, paiements) prêts à l'export Excel/PDF.
 * Source unique pour le classeur .xlsx, le rapport PDF et les tuiles Livewire.
 */
class EditionReport
{
    public function __construct(public Edition $edition) {}

    /**
     * Indicateurs de synthèse.
     *
     * @return array{
     *   participants:int,
     *   submissions:int,
     *   submissionsByStatus:array<string,int>,
     *   accepted:int,
     *   paidCount:int,
     *   collected:float,
     *   pending:float,
     *   currency:string
     * }
     */
    public function summary(): array
    {
        $editionId = $this->edition->id;

        $submissionsByStatus = [];
        foreach (array_keys(Submission::statuses()) as $status) {
            $submissionsByStatus[$status] = Submission::where('edition_id', $editionId)
                ->where('revision_of', 0)
                ->where('status', $status)
                ->count();
        }

        $collected = (float) Payment::where('edition_id', $editionId)->where('status', 'paid')->sum('amount');
        $pending = (float) Payment::where('edition_id', $editionId)->where('status', 'pending')->sum('amount');

        return [
            'participants' => Participant::where('edition_id', $editionId)->count(),
            'submissions' => Submission::where('edition_id', $editionId)->where('revision_of', 0)->count(),
            'submissionsByStatus' => $submissionsByStatus,
            'accepted' => $submissionsByStatus['accepted'] ?? 0,
            'paidCount' => Payment::where('edition_id', $editionId)->where('status', 'paid')->count(),
            'collected' => $collected,
            'pending' => $pending,
            'currency' => (string) (Payment::where('edition_id', $editionId)->value('currency') ?: 'XOF'),
        ];
    }

    /** @return list<string> */
    public function participantHeaders(): array
    {
        return ['Nom', 'Prénom', 'E-mail', 'Téléphone', 'Institution', 'Pays', 'Rôle', 'Grade', 'Payé', 'Montant', 'Invité'];
    }

    /** @return array<int, array<int, string|float>> */
    public function participantRows(): array
    {
        return Participant::where('edition_id', $this->edition->id)
            ->with('participationPayments')
            ->orderBy('last_name')
            ->get()
            ->map(function (Participant $p) {
                $paid = $p->participationPayments->firstWhere('status', 'paid');

                return [
                    $p->last_name,
                    $p->first_name,
                    (string) $p->email,
                    (string) $p->phone,
                    (string) $p->institution,
                    (string) $p->country,
                    $p->roleLabel(),
                    (string) $p->title,
                    $paid ? 'Oui' : 'Non',
                    $paid ? (float) $paid->amount : 0,
                    $p->isInvited() ? 'Oui' : 'Non',
                ];
            })
            ->all();
    }

    /** @return list<string> */
    public function submissionHeaders(): array
    {
        return ['Réf.', 'Titre', 'Auteur correspondant', 'E-mail', 'Axe', 'Type', 'Statut', 'Note moyenne', 'Version'];
    }

    /** @return array<int, array<int, string|float|int>> */
    public function submissionRows(): array
    {
        return Submission::where('edition_id', $this->edition->id)
            ->where('revision_of', 0)
            ->with('participant')
            ->withAvg('reviews', 'score')
            ->orderBy('ref_code')
            ->get()
            ->map(fn (Submission $s) => [
                (string) $s->ref_code,
                (string) $s->title,
                (string) ($s->correspondingAuthorName() ?? ''),
                (string) ($s->correspondingAuthorEmail() ?? ''),
                (string) $s->track,
                $s->type === 'poster' ? 'Poster' : 'Oral',
                $s->statusLabel(),
                $s->reviews_avg_score !== null ? round((float) $s->reviews_avg_score, 2) : '',
                (int) $s->version,
            ])
            ->all();
    }

    /** @return list<string> */
    public function paymentHeaders(): array
    {
        return ['Reçu', 'Participant', 'E-mail', 'Montant', 'Devise', 'Passerelle', 'Méthode', 'Statut', 'Date'];
    }

    /** @return array<int, array<int, string|float>> */
    public function paymentRows(): array
    {
        return Payment::where('edition_id', $this->edition->id)
            ->with('participant')
            ->latest('id')
            ->get()
            ->map(fn (Payment $p) => [
                (string) $p->receipt_no,
                (string) ($p->participant?->full_name ?? ''),
                (string) ($p->participant?->email ?? ''),
                (float) $p->amount,
                (string) $p->currency,
                (string) $p->gateway,
                $p->methodLabel(),
                $p->statusLabel(),
                $p->paid_at?->format('Y-m-d H:i') ?? '',
            ])
            ->all();
    }

    /** @return list<string> */
    public function attestationHeaders(): array
    {
        return ['UUID', 'Destinataire', 'E-mail', 'Type', 'Émise le', 'Statut'];
    }

    /** @return array<int, array<int, string>> */
    public function attestationRows(): array
    {
        return Attestation::where('edition_id', $this->edition->id)
            ->with('participant')
            ->latest('id')
            ->get()
            ->map(fn (Attestation $a) => [
                (string) $a->uuid,
                (string) ($a->recipient_name ?: $a->participant?->full_name ?? ''),
                (string) ($a->recipient_email ?: $a->participant?->email ?? ''),
                $a->typeLabel(),
                $a->issued_at?->format('Y-m-d H:i') ?? '',
                $a->revoked ? 'Révoquée' : 'Valide',
            ])
            ->all();
    }

    /** @return list<string> */
    public function accountingHeaders(): array
    {
        return ['Passerelle', 'Transactions payées', 'Montant encaissé', 'Devise'];
    }

    /**
     * Récapitulatif comptable : recettes réglées agrégées par passerelle, avec
     * ligne de total (mockup « Export comptable »).
     *
     * @return array<int, array<int, string|float|int>>
     */
    public function accountingRows(): array
    {
        $currency = $this->summary()['currency'];

        $rows = Payment::where('edition_id', $this->edition->id)
            ->where('status', 'paid')
            ->selectRaw('gateway, count(*) as c, sum(amount) as total')
            ->groupBy('gateway')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($r) => [
                (string) ($r->gateway ?: 'Autre'),
                (int) $r->c,
                (float) $r->total,
                $currency,
            ])
            ->all();

        $rows[] = [
            'Total',
            (int) array_sum(array_column($rows, 1)),
            (float) array_sum(array_column($rows, 2)),
            $currency,
        ];

        return $rows;
    }

    /**
     * Nombre de lignes par jeu de données (pour l'affichage des cartes d'export).
     *
     * @return array{submissions:int, participants:int, payments:int, attestations:int}
     */
    public function counts(): array
    {
        $editionId = $this->edition->id;

        return [
            'submissions' => Submission::where('edition_id', $editionId)->where('revision_of', 0)->count(),
            'participants' => Participant::where('edition_id', $editionId)->count(),
            'payments' => Payment::where('edition_id', $editionId)->count(),
            'attestations' => Attestation::where('edition_id', $editionId)->count(),
        ];
    }

    // ---------------------------------------------------------------------
    // Agrégations par onglet (fidèle au mockup admin-reports)
    // ---------------------------------------------------------------------

    /** @return Builder<Submission> */
    protected function subs()
    {
        return Submission::query()->where('edition_id', $this->edition->id)->where('revision_of', 0);
    }

    /** @return Builder<Payment> */
    protected function pay()
    {
        return Payment::query()->where('edition_id', $this->edition->id);
    }

    /**
     * Répartition des soumissions par axe : total, en revue, acceptées,
     * refusées, taux d'acceptation.
     *
     * @return array<int, array{axe:string, total:int, review:int, accepted:int, rejected:int, rate:int}>
     */
    public function submissionsByAxis(): array
    {
        return $this->subs()
            ->selectRaw("COALESCE(NULLIF(track, ''), 'Non classé') as axe, count(*) as total")
            ->selectRaw("sum(case when status in ('under_review','revision') then 1 else 0 end) as review")
            ->selectRaw("sum(case when status = 'accepted' then 1 else 0 end) as accepted")
            ->selectRaw("sum(case when status = 'rejected' then 1 else 0 end) as rejected")
            ->groupBy('axe')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(function ($r) {
                $decided = (int) $r->accepted + (int) $r->rejected;

                return [
                    'axe' => (string) $r->axe,
                    'total' => (int) $r->total,
                    'review' => (int) $r->review,
                    'accepted' => (int) $r->accepted,
                    'rejected' => (int) $r->rejected,
                    'rate' => $decided > 0 ? (int) round((int) $r->accepted / $decided * 100) : 0,
                ];
            })
            ->all();
    }

    /**
     * Répartition par type de communication (oral / poster).
     *
     * @return array<int, array{type:string, total:int, accepted:int, part:int}>
     */
    public function submissionsByType(): array
    {
        $grand = max(1, $this->subs()->count());

        return $this->subs()
            ->selectRaw("COALESCE(NULLIF(type, ''), 'oral') as type, count(*) as total")
            ->selectRaw("sum(case when status = 'accepted' then 1 else 0 end) as accepted")
            ->groupBy('type')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($r) => [
                'type' => $r->type === 'poster' ? 'Poster' : 'Communication orale',
                'total' => (int) $r->total,
                'accepted' => (int) $r->accepted,
                'part' => (int) round((int) $r->total / $grand * 100),
            ])
            ->all();
    }

    /**
     * Participants par rôle : inscrits et payés.
     *
     * @return array<int, array{role:string, count:int, paid:int}>
     */
    public function participantsByRole(): array
    {
        return Participant::where('edition_id', $this->edition->id)
            ->with('participationPayments')
            ->get()
            ->groupBy('role')
            ->map(fn ($group, $role) => [
                'role' => Participant::ROLES[$role] ?? ucfirst((string) $role),
                'count' => $group->count(),
                'paid' => $group->filter(fn (Participant $p) => $p->participationPayments->firstWhere('status', 'paid') !== null)->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * Participants par pays (top 6 + « Autres »), avec part en %.
     *
     * @return array<int, array{country:string, count:int, part:int}>
     */
    public function participantsByCountry(): array
    {
        $grand = max(1, Participant::where('edition_id', $this->edition->id)->count());

        $byCountry = Participant::where('edition_id', $this->edition->id)
            ->selectRaw("COALESCE(NULLIF(country, ''), 'Non précisé') as country, count(*) as c")
            ->groupBy('country')
            ->orderByDesc('c')
            ->toBase()
            ->get();

        $top = $byCountry->take(6);
        $rest = $byCountry->slice(6)->sum('c');

        $rows = $top->map(fn ($r) => [
            'country' => (string) $r->country,
            'count' => (int) $r->c,
            'part' => (int) round((int) $r->c / $grand * 100),
        ])->all();

        if ($rest > 0) {
            $rows[] = ['country' => 'Autres', 'count' => (int) $rest, 'part' => (int) round($rest / $grand * 100)];
        }

        return $rows;
    }

    /**
     * Statistiques de paiement : OK, en attente, échoués/annulés.
     *
     * @return array{ok:int, pending:int, failed:int}
     */
    public function paymentStats(): array
    {
        return [
            'ok' => $this->pay()->where('status', 'paid')->count(),
            'pending' => $this->pay()->where('status', 'pending')->count(),
            'failed' => $this->pay()->whereIn('status', ['failed', 'cancelled', 'refunded'])->count(),
        ];
    }

    /**
     * Recettes réglées par tarif (montant unitaire), avec nombre et total.
     *
     * @return array<int, array{tarif:float, count:int, total:float}>
     */
    public function paymentsByTarif(): array
    {
        return $this->pay()
            ->where('status', 'paid')
            ->selectRaw('amount as tarif, count(*) as c, sum(amount) as total')
            ->groupBy('amount')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($r) => ['tarif' => (float) $r->tarif, 'count' => (int) $r->c, 'total' => (float) $r->total])
            ->all();
    }

    /**
     * Statistiques de relecture : relecteurs actifs, relectures rendues, en retard,
     * délai moyen (jours entre assignation et complétion).
     *
     * ⚠️ « En retard » = échéance dépassée sur une relecture encore due
     * (Review::isLate()), et non « pas encore rendue » : une relecture confiée
     * hier n'est pas en retard, et une relecture refusée ou annulée ne l'est
     * jamais - la place a été libérée.
     *
     * @return array{reviewers:int, done:int, late:int, pending:int, declined:int, avgDays:float}
     */
    public function reviewStats(): array
    {
        $reviews = Review::whereHas('submission', fn ($q) => $q->where('edition_id', $this->edition->id))->get();

        $completed = $reviews->where('status', 'completed');
        $avgDays = $completed->isNotEmpty()
            ? round($completed->avg(fn ($r) => $r->created_at && $r->updated_at ? $r->created_at->diffInDays($r->updated_at) : 0), 1)
            : 0.0;

        return [
            'reviewers' => (int) $reviews->pluck('reviewer_id')->filter()->unique()->count(),
            'done' => $completed->count(),
            'late' => $reviews->filter(fn (Review $r) => $r->isLate())->count(),
            'pending' => $reviews->filter(fn (Review $r) => $r->isPending())->count(),
            'declined' => $reviews->where('status', 'declined')->count(),
            'avgDays' => (float) $avgDays,
        ];
    }

    /**
     * Charge par relecteur : assignées, rendues, en retard, complétion (%).
     * Seules les relectures actives comptent dans la charge (un refus ou une
     * annulation ne pèse pas sur la complétion du relecteur), et « en retard »
     * suit l'échéance (Review::isLate()).
     *
     * @return array<int, array{name:string, assigned:int, done:int, pending:int, late:int, declined:int, completion:int}>
     */
    public function reviewsByReviewer(): array
    {
        return Review::whereHas('submission', fn ($q) => $q->where('edition_id', $this->edition->id))
            ->with('reviewer')
            ->get()
            ->groupBy('reviewer_id')
            ->map(function ($group) {
                $active = $group->filter(fn (Review $r) => $r->isActive());
                $assigned = $active->count();
                $done = $active->where('status', 'completed')->count();

                return [
                    'name' => $group->first()->reviewer?->full_name ?: 'Relecteur inconnu',
                    'assigned' => $assigned,
                    'done' => $done,
                    'pending' => $active->filter(fn (Review $r) => $r->isPending())->count(),
                    'late' => $active->filter(fn (Review $r) => $r->isLate())->count(),
                    'declined' => $group->where('status', 'declined')->count(),
                    'completion' => $assigned > 0 ? (int) round($done / $assigned * 100) : 0,
                ];
            })
            ->sortByDesc('assigned')
            ->values()
            ->all();
    }

    public function filenameBase(): string
    {
        $slug = Str::slug($this->edition->title ?: ('edition-'.$this->edition->id)) ?: 'edition';

        return 'rapport-'.$slug;
    }
}
