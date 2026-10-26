<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use App\Support\EventLock;
use App\Support\Fees;
use App\Support\ParticipantTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Déclaration d'une catégorie tarifaire HORS GRADE (ONG, entreprise, association…).
 *
 * ⚠️⚠️ **C'EST LA SEULE EXCEPTION À « LE TARIF NE SE CHOISIT PAS ».** Partout
 * ailleurs le montant se déduit du grade, et trois écrans ont dû être repris
 * pour cesser de laisser croire le contraire. Mais aucune valeur de
 * {@see ParticipantTypes} ne désigne une ONG ou une entreprise :
 * il n'y a rien à déduire, donc c'est l'intéressé qui l'annonce. Les catégories
 * déclarables sont celles que l'organisation a marquées « hors grade » - on ne
 * choisit pas parmi toute la grille.
 */
class FeeCategoryController extends Controller
{
    public function __invoke(Request $request, Event $event): RedirectResponse
    {
        EventLock::guard($event);

        $account = Auth::guard('account')->user();
        $participant = Participant::query()->where('event_id', $event->id)
            ->where('user_id', $account->id)->firstOrFail();

        // ⚠️ Une fois réglé, le tarif est acquis : le laisser changer ferait
        // diverger la somme encaissée et la catégorie affichée sur le reçu.
        abort_if($participant->hasPaid(), 403);

        $offertes = Fees::selectable($event);
        abort_if($offertes === [], 404);

        $data = $request->validate([
            // La chaîne vide est un choix : « aucune, appliquez mon grade ».
            'fee_category' => ['nullable', Rule::in(array_merge([''], array_column($offertes, 'key')))],
        ], [
            'fee_category.in' => 'Cette catégorie n’est pas proposée pour cet événement.',
        ]);

        $participant->setFeeCategory($data['fee_category'] ?? null);
        $participant->save();

        return redirect()->to(route('account.payment', $event).'#categorie-tarifaire')
            ->with('ukc_status', filled($data['fee_category'] ?? null)
                ? 'Catégorie tarifaire enregistrée.'
                : 'Catégorie retirée : votre tarif suit de nouveau votre grade.');
    }
}
