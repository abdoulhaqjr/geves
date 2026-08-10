<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Documents imprimables d'une édition : livret des résumés et planche de badges.
 * (Imprimer / Enregistrer en PDF depuis le navigateur.)
 */
class DocumentController extends Controller
{
    /** Livret des résumés : communications acceptées, groupées par axe. */
    public function livret(Edition $edition): View
    {
        $submissions = $edition->submissions()
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->orderBy('track')
            ->get()
            ->groupBy(fn ($s) => $s->track ?: 'Autres');

        return view('documents.livret', [
            'edition' => $edition,
            'groups' => $submissions,
        ]);
    }

    /** Fiches de session imprimables : chaque session avec ses communications. */
    public function sessionSheets(Edition $edition): View
    {
        $sessions = $edition->sessions()
            ->with('atelier')
            ->orderBy('session_day')
            ->orderBy('ordre')
            ->get();

        $commsBySession = $edition->slots()
            ->where('submission_id', '>', 0)
            ->with('submission')
            ->orderBy('starts_at')
            ->get()
            ->groupBy('session_id');

        return view('documents.session-sheets', [
            'edition' => $edition,
            'sessions' => $sessions,
            'commsBySession' => $commsBySession,
        ]);
    }

    /** Planche de badges (A4, 2 colonnes), couleur par rôle. */
    public function badges(Edition $edition, Request $request): View
    {
        $query = $edition->participants();

        if ($request->boolean('paid')) {
            $query->whereHas('payments', fn ($q) => $q->where('status', 'paid'));
        }

        return view('documents.badges', [
            'edition' => $edition,
            'participants' => $query->orderBy('last_name')->get(),
        ]);
    }
}
