<?php

namespace App\Support;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
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
            ->with('payments')
            ->orderBy('last_name')
            ->get()
            ->map(function (Participant $p) {
                $paid = $p->payments->firstWhere('status', 'paid');

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

    public function filenameBase(): string
    {
        $slug = Str::slug($this->edition->title ?: ('edition-'.$this->edition->id)) ?: 'edition';

        return 'rapport-'.$slug;
    }
}
