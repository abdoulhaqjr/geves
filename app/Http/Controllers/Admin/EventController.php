<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liste des événements du back-office (Blade, fidèle au mockup admin-editions.html) :
 * indicateurs, recherche + filtre de statut (via query string), cartes d'événement,
 * actions activer/supprimer. Le formulaire créer/éditer est géré par
 * {@see EventFormController}.
 */
class EventController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $scoped = $this->scopedEditionIds($request, 'editions_read');

        $events = Event::query()
            ->with('series')
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

        $active = Event::where('status', 'open')->latest('id')->first();
        $paid = $active
            ? Payment::where('event_id', $active->id)->forParticipation()->where('status', 'paid')->distinct('participant_id')->count('participant_id')
            : 0;

        return view('admin.evenements.index', [
            'events' => $events,
            'search' => $search,
            'status' => $status,
            'stats' => [
                'total' => Event::count(),
                'activeYear' => $active?->year,
                'activeSubmissions' => $active ? Submission::where('event_id', $active->id)->where('revision_of', 0)->count() : 0,
                'paidParticipants' => $paid,
            ],
        ]);
    }

    /**
     * Exporte UNE événement (paramètres + données de configuration) en un fichier
     * JSON téléchargeable - sauvegarde / transfert / clonage. Réimportable sur une
     * autre événement via {@see import()}.
     */
    public function export(Event $event): StreamedResponse
    {
        $event->loadMissing('series');

        $row = [
            'series' => ['name' => $event->series?->name, 'slug' => $event->series?->slug],
            'title' => $event->title,
            'event_label' => $event->event_label,
            'subtitle' => $event->subtitle,
            'theme' => $event->theme,
            'year' => $event->year,
            'date_start' => $event->date_start?->toDateString(),
            'date_end' => $event->date_end?->toDateString(),
            'location' => $event->location,
            'status' => $event->status,
            'slug' => $event->slug,
            'submission_open' => $event->submissionsAreOpen(),
            'submission_deadline' => $event->submission_deadline?->toDateTimeString(),
            'settings' => $event->settings ?? [],
        ];

        $payload = ['exported_at' => now()->toIso8601String(), 'version' => 1, 'events' => [$row]];
        $filename = 'edition-'.$event->slug.'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /**
     * Importe la configuration d'un fichier JSON (produit par {@see export()})
     * DANS l'événement courant : titre, thème, dates, statut et paramètres sont
     * restaurés. L'identité (slug, colloque de rattachement) de l'événement est
     * conservée pour éviter les collisions.
     */
    public function import(Request $request, Event $event): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json,txt', 'max:8192'],
        ]);

        try {
            $data = json_decode((string) $request->file('file')->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return back()->with('ukc_error', 'Fichier invalide : JSON illisible.');
        }

        $rows = $data['events'] ?? (array_is_list($data ?? []) ? $data : [$data]);
        $row = is_array($rows[0] ?? null) ? $rows[0] : null;
        if (! $row || blank($row['title'] ?? null)) {
            return back()->with('ukc_error', 'Aucune configuration d’événement valide dans le fichier.');
        }

        $event->update([
            // Identité conservée (slug + event_series_id) ; le reste est importé.
            'title' => (string) $row['title'],
            'event_label' => (string) ($row['event_label'] ?? ''),
            'subtitle' => $row['subtitle'] ?? null,
            'theme' => $row['theme'] ?? null,
            'year' => (int) ($row['year'] ?? $event->year),
            'date_start' => $row['date_start'] ?? null,
            'date_end' => $row['date_end'] ?? null,
            'location' => $row['location'] ?? null,
            'status' => in_array($row['status'] ?? '', ['draft', 'open', 'closed', 'archived'], true) ? $row['status'] : $event->status,
            'submission_open' => (bool) ($row['submission_open'] ?? false),
            'submission_deadline' => $row['submission_deadline'] ?? null,
            'settings' => is_array($row['settings'] ?? null) ? $row['settings'] : ($event->settings ?? []),
        ]);

        return back()->with('ukc_status', 'Configuration importée dans « '.$event->title.' ».');
    }

    /** Définit un événement comme actif (publié/ouvert sur le site public). */
    public function activate(Event $event): RedirectResponse
    {
        $event->update(['status' => 'open']);

        return back()->with('ukc_status', 'Événement « '.$event->title.' » activée.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return back()->with('ukc_status', 'Événement supprimée.');
    }
}
