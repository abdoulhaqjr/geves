<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Liste des éditions du back-office (Blade, fidèle au mockup admin-editions.html) :
 * indicateurs, recherche + filtre de statut (via query string), cartes d'édition,
 * actions activer/supprimer. Le formulaire créer/éditer est géré par
 * {@see EditionFormController}.
 */
class EditionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $editions = Edition::query()
            ->with('colloque')
            ->withCount([
                'submissions as submissions_count' => fn ($q) => $q->where('revision_of', 0),
                'participants as participants_count',
            ])
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $active = Edition::where('status', 'open')->latest('id')->first();
        $paid = $active
            ? Payment::where('edition_id', $active->id)->where('status', 'paid')->distinct('participant_id')->count('participant_id')
            : 0;

        return view('admin.editions.index', [
            'editions' => $editions,
            'search' => $search,
            'status' => $status,
            'stats' => [
                'total' => Edition::count(),
                'activeYear' => $active?->year,
                'activeSubmissions' => $active ? Submission::where('edition_id', $active->id)->where('revision_of', 0)->count() : 0,
                'paidParticipants' => $paid,
            ],
        ]);
    }

    /** Définit une édition comme active (publiée/ouverte sur le site public). */
    public function activate(Edition $edition): RedirectResponse
    {
        $edition->update(['status' => 'open']);

        return back()->with('ukc_status', 'Édition « '.$edition->title.' » activée.');
    }

    public function destroy(Edition $edition): RedirectResponse
    {
        $edition->delete();

        return back()->with('ukc_status', 'Édition supprimée.');
    }
}
