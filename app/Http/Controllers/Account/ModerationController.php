<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Espace modérateur (côté participant) : « Mes séances à animer ». Liste les
 * séances dont l'utilisateur connecté est le modérateur assigné, avec un accès
 * direct à la visio et à l'espace d'échange live (Q&A voté + sondages).
 */
class ModerationController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('account')->user();

        $sessions = $user->sessionsAsModerator()
            ->with(['edition.colloque', 'atelier'])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return view('account.moderation.index', [
            'sessions' => $sessions,
        ]);
    }
}
