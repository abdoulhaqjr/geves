<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liste des éditions du back-office (Blade, fidèle au mockup admin-editions.html) :
 * indicateurs, recherche + filtre de statut (via query string), cartes d'édition,
 * actions activer/supprimer. Le formulaire créer/éditer est géré par
 * {@see EditionFormController}.
 */
class EditionController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $scoped = $this->scopedEditionIds($request, 'editions_read');

        $editions = Edition::query()
            ->with('colloque')
            ->withCount([
                'submissions as submissions_count' => fn ($q) => $q->where('revision_of', 0),
                'participants as participants_count',
            ])
            ->when($scoped !== null, fn ($q) => $q->whereIn('id', $scoped))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('year')
            ->orderByDesc('id')
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

    /**
     * Exporte UNE édition (paramètres + données de configuration) en un fichier
     * JSON téléchargeable — sauvegarde / transfert / clonage. Réimportable sur une
     * autre édition via {@see import()}.
     */
    public function export(Edition $edition): StreamedResponse
    {
        $edition->loadMissing('colloque');

        $row = [
            'colloque' => ['name' => $edition->colloque?->name, 'slug' => $edition->colloque?->slug],
            'title' => $edition->title,
            'edition_label' => $edition->edition_label,
            'subtitle' => $edition->subtitle,
            'theme' => $edition->theme,
            'year' => $edition->year,
            'date_start' => $edition->date_start?->toDateString(),
            'date_end' => $edition->date_end?->toDateString(),
            'location' => $edition->location,
            'status' => $edition->status,
            'slug' => $edition->slug,
            'submission_open' => (bool) $edition->submission_open,
            'submission_deadline' => $edition->submission_deadline?->toDateTimeString(),
            'settings' => $edition->settings ?? [],
        ];

        $payload = ['exported_at' => now()->toIso8601String(), 'version' => 1, 'editions' => [$row]];
        $filename = 'edition-'.$edition->slug.'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /**
     * Importe la configuration d'un fichier JSON (produit par {@see export()})
     * DANS l'édition courante : titre, thème, dates, statut et paramètres sont
     * restaurés. L'identité (slug, colloque de rattachement) de l'édition est
     * conservée pour éviter les collisions.
     */
    public function import(Request $request, Edition $edition): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json,txt', 'max:8192'],
        ]);

        try {
            $data = json_decode((string) $request->file('file')->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return back()->with('ukc_error', 'Fichier invalide : JSON illisible.');
        }

        $rows = $data['editions'] ?? (array_is_list($data ?? []) ? $data : [$data]);
        $row = is_array($rows[0] ?? null) ? $rows[0] : null;
        if (! $row || blank($row['title'] ?? null)) {
            return back()->with('ukc_error', 'Aucune configuration d’édition valide dans le fichier.');
        }

        $edition->update([
            // Identité conservée (slug + colloque_id) ; le reste est importé.
            'title' => (string) $row['title'],
            'edition_label' => (string) ($row['edition_label'] ?? ''),
            'subtitle' => $row['subtitle'] ?? null,
            'theme' => $row['theme'] ?? null,
            'year' => (int) ($row['year'] ?? $edition->year),
            'date_start' => $row['date_start'] ?? null,
            'date_end' => $row['date_end'] ?? null,
            'location' => $row['location'] ?? null,
            'status' => in_array($row['status'] ?? '', ['draft', 'open', 'closed', 'archived'], true) ? $row['status'] : $edition->status,
            'submission_open' => (bool) ($row['submission_open'] ?? false),
            'submission_deadline' => $row['submission_deadline'] ?? null,
            'settings' => is_array($row['settings'] ?? null) ? $row['settings'] : ($edition->settings ?? []),
        ]);

        return back()->with('ukc_status', 'Configuration importée dans « '.$edition->title.' ».');
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
