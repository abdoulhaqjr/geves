<?php

namespace App\Http\Controllers;

use App\Models\SubmissionAuthor;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Déclinaison publique d'une association auteur ↔ communication, par jeton (lien
 * envoyé par e-mail). Utile surtout pour les rapprochements par nom : la personne
 * peut retirer l'association si ce n'est pas elle. Landing (GET) + confirmation
 * (POST) pour éviter une déclinaison involontaire (préchargement de lien).
 */
class AuthorLinkController extends Controller
{
    public function show(string $token): View
    {
        $link = $this->findLink($token);

        return view('author.decline', ['link' => $link, 'submission' => $link->submission]);
    }

    public function decline(string $token): RedirectResponse
    {
        $link = $this->findLink($token);

        if ($link->status !== 'declined') {
            $link->update(['status' => 'declined', 'notified_at' => null]);
        }

        return redirect()->route('author.decline', ['token' => $token])
            ->with('ukc_status', 'Association retirée. Merci de nous l’avoir signalé.');
    }

    private function findLink(string $token): SubmissionAuthor
    {
        $link = SubmissionAuthor::where('token', $token)->with('submission.edition')->first();
        abort_unless($link && $token !== '', 404);

        return $link;
    }
}
