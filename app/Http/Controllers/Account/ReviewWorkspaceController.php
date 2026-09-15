<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Espace relecteur (côté participant) : « Mes relectures ». Regroupe les
 * relectures confiées à l'utilisateur au titre de ses inscriptions « relecteur »
 * (par édition). L'évaluation elle-même se fait dans le portail relecteur (accès
 * par jeton, en aveugle) déjà existant.
 */
class ReviewWorkspaceController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('account')->user();

        // Inscriptions relecteur de l'utilisateur (une par édition), avec jeton.
        $reviewerParticipants = $user->reviewerParticipations()->with('edition.colloque')->get();

        $reviews = Review::whereIn('reviewer_id', $reviewerParticipants->pluck('id'))
            ->with('submission.edition')
            ->latest('id')
            ->get();

        return view('account.reviews.index', [
            'reviewers' => $reviewerParticipants->keyBy('id'),
            'reviews' => $reviews,
        ]);
    }
}
