<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\CurrentColloque;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Espace modérateur (côté participant) : « Mes séances à modérer ». Liste les
 * séances dont l'utilisateur connecté est le modérateur assigné, avec un accès
 * direct à la visio et à l'espace d'échange live (Q&A voté + sondages).
 */
class ModerationController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('account')->user();

        // ⚠️ La page appartient aux modérateurs. Elle n'était fermée qu'au visiteur
        // anonyme : n'importe quel compte connecté l'ouvrait - vide, mais ouverte,
        // et avec elle l'idée qu'il y a là des outils qui ne le regardent pas.
        abort_unless($user !== null && $user->isModeratorSomewhere(), 403);

        $edition = CurrentColloque::current($user);
        $aModererIci = $edition !== null
            && $user->sessionsAsModerator()->where('edition_id', $edition->id)->exists();

        // Comme toutes les pages personnelles, le contenu suit le colloque
        // sélectionné.
        //
        // ⚠️ Sauf quand la sélection ne veut rien dire ici : un modérateur est
        // rapproché par E-MAIL et n'est pas forcément INSCRIT à l'édition qu'il
        // anime - le sélecteur de colloque ne la lui proposerait donc jamais, et la
        // page resterait vide à jamais. Dans ce cas on montre tout ce qu'il modère,
        // chaque carte portant déjà le nom de son colloque.
        $sessions = $user->sessionsAsModerator()
            ->when($aModererIci, fn ($q) => $q->where('edition_id', $edition->id))
            ->with(['edition.colloque', 'atelier'])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        // ⚠️ La liste n'est jamais vide : la garde ci-dessus exige au moins une
        // séance confiée, et le repli montre tout ce que la personne modère. L'état
        // vide de la vue ne reste que par prudence (une séance supprimée entre les
        // deux requêtes).
        return view('account.moderation.index', [
            'sessions' => $sessions,
        ]);
    }
}
