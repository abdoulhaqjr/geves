<?php

namespace App\Livewire\Admin\Submissions;

use App\Actions\AssignReviewer;
use App\Actions\NotifyCoauthorsForPayment;
use App\Actions\NotifySubmissionStatus;
use App\Mail\ReviewerInvitationMail;
use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Fiche d'une soumission au back-office : détail, actions de workflow
 * (mise en relecture, correction, acceptation, refus) avec e-mail à l'auteur,
 * et gestion des relecteurs (assignation, retrait, synthèse).
 */
#[Title('Soumission')]
class Show extends Component
{
    public Submission $submission;

    public string $note = '';

    // Assignation d'un relecteur
    public string $reviewerName = '';

    public string $reviewerEmail = '';

    public string $existingReviewerId = '';

    /** Statuts que l'on peut appliquer depuis la fiche (le dépôt initial exclu). */
    public const ACTIONS = [
        'under_review' => ['Mettre en relecture', 'ghost'],
        'revision' => ['Demander une correction', 'gold'],
        'accepted' => ['Accepter', 'green'],
        'rejected' => ['Refuser', 'danger'],
    ];

    public function mount(Submission $submission): void
    {
        $this->submission = $submission->load(['edition', 'participant']);
    }

    public function applyStatus(string $status, NotifySubmissionStatus $notify, NotifyCoauthorsForPayment $notifyCoauthors): void
    {
        abort_unless(array_key_exists($status, self::ACTIONS), 400);

        $this->validate(['note' => ['nullable', 'string', 'max:3000']]);

        $this->submission->update(['status' => $status]);
        $notify->handle($this->submission, $status, $this->note ?: null);

        // À l'acceptation : inviter les co-auteurs à régler leurs frais.
        $coauthorNote = '';
        if ($status === 'accepted') {
            $this->submission->refresh();
            $n = $notifyCoauthors->handle($this->submission);
            if ($n > 0) {
                $coauthorNote = " {$n} co-auteur(s) invité(s) à payer.";
            }
        }

        $this->note = '';
        $this->submission->refresh();

        session()->flash('ukc_status', 'Statut mis à jour : '.$this->submission->statusLabel()
            .' — l’auteur a été notifié.'.$coauthorNote);
    }

    public function assignReviewer(AssignReviewer $action): void
    {
        // Relecteur existant sélectionné : on récupère nom + e-mail.
        if ($this->existingReviewerId !== '') {
            $reviewer = Participant::find($this->existingReviewerId);
            if (! $reviewer) {
                $this->addError('existingReviewerId', 'Relecteur introuvable.');

                return;
            }
            $this->reviewerName = $reviewer->full_name;
            $this->reviewerEmail = $reviewer->email;
        }

        $this->validate([
            'reviewerName' => ['required', 'string', 'max:200'],
            'reviewerEmail' => ['required', 'email', 'max:200'],
        ]);

        $review = $action->handle($this->submission, $this->reviewerName, $this->reviewerEmail);

        // Invitation par e-mail (journalisée par le mailer applicatif).
        Mail::to($this->reviewerEmail)->send(new ReviewerInvitationMail($review->reviewer));

        $this->reset(['reviewerName', 'reviewerEmail', 'existingReviewerId']);
        $this->submission->refresh();

        session()->flash('ukc_status', 'Relecteur assigné et invité par e-mail.');
    }

    public function removeReview(int $reviewId): void
    {
        Review::whereKey($reviewId)->where('submission_id', $this->submission->id)->delete();
        session()->flash('ukc_status', 'Assignation retirée.');
    }

    public function render()
    {
        $reviews = $this->submission->reviews()->with('reviewer')->get();
        $completed = $reviews->where('status', 'completed');

        return view('livewire.admin.submissions.show', [
            'actions' => self::ACTIONS,
            'reviews' => $reviews,
            'recommendations' => Review::RECOMMENDATIONS,
            'averageScore' => $completed->whereNotNull('score')->avg('score'),
            'completedCount' => $completed->count(),
            'existingReviewers' => Participant::where('edition_id', $this->submission->edition_id)
                ->where('role', 'reviewer')
                ->orderBy('last_name')
                ->get(),
        ])->layout('components.layouts.admin', ['title' => 'Soumission '.$this->submission->ref_code]);
    }
}
