<?php

namespace App\Http\Controllers\Account;

use App\Documents\LivretDocx;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documents de l'édition accessibles au participant connecté : livret des
 * résumés d'une édition à laquelle il a participé (contrôle de propriété).
 */
class DocumentController extends Controller
{
    /**
     * « Mes documents » (Blade, fidèle au mockup) : espace consolidé — attestations
     * vérifiables, reçus de paiement, et documents des éditions du participant
     * (ressources + livret des résumés). Lecture seule → Blade.
     */
    public function index(): View
    {
        $account = Auth::guard('account')->user();

        $participantIds = Participant::where('account_id', $account->id)->pluck('id');

        $editionIds = Participant::where('account_id', $account->id)
            ->pluck('edition_id')->unique()->filter();

        $editionDocuments = Edition::whereIn('id', $editionIds)
            ->with(['media' => fn ($q) => $q->orderBy('ordre')->orderBy('id')])
            ->latest('id')
            ->get()
            ->map(fn (Edition $e) => [
                'edition' => $e,
                'media' => $e->media,
                'hasBooklet' => $e->submissions()
                    ->where('revision_of', 0)->where('status', 'accepted')->exists(),
            ])
            ->filter(fn ($d) => $d['media']->isNotEmpty() || $d['hasBooklet'])
            ->values();

        return view('account.documents', [
            'payments' => Payment::whereIn('participant_id', $participantIds)
                ->where('status', 'paid')->with('edition')->latest('id')->get(),
            'attestations' => Attestation::whereIn('participant_id', $participantIds)
                ->where('revoked', false)->with('edition')->latest('id')->get(),
            'editionDocuments' => $editionDocuments,
        ]);
    }

    public function livret(Edition $edition, Request $request): View|Response
    {
        $account = Auth::guard('account')->user();

        $participates = Participant::where('edition_id', $edition->id)
            ->where('account_id', $account->id)
            ->exists();

        abort_unless($participates, 403);

        $groups = $edition->submissions()
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->orderBy('track')
            ->get()
            ->groupBy(fn ($s) => $s->track ?: 'Autres');

        if ($request->query('format') === 'docx') {
            return (new LivretDocx($edition, $groups))
                ->download('livret-'.($edition->slug ?: 'edition-'.$edition->id).'.docx');
        }

        return view('documents.livret', [
            'edition' => $edition,
            'groups' => $groups,
        ]);
    }
}
