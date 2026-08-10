<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Participant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Documents de l'édition accessibles au participant connecté : livret des
 * résumés d'une édition à laquelle il a participé (contrôle de propriété).
 */
class DocumentController extends Controller
{
    public function livret(Edition $edition): View
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

        return view('documents.livret', [
            'edition' => $edition,
            'groups' => $groups,
        ]);
    }
}
