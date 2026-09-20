<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ScopesEditionAccess;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Annonces du déroulement (page publique « Déroulement » → onglet Annonces).
 * Gérées depuis le back-office par l'administrateur, le gestionnaire et le
 * secrétariat (capacité `announcements`). Stockées dans les réglages de l'édition
 * (`settings['announcements']`), du plus récent au plus ancien.
 */
class AnnouncementController extends Controller
{
    use ScopesEditionAccess;

    public function index(Request $request): View
    {
        $edition = $this->currentEdition($request);

        return view('admin.announcements.index', [
            'editions' => $this->accessibleEditions($request, 'announcements'),
            'edition' => $edition,
            'edition_id' => $edition ? (string) $edition->id : '',
            'announcements' => $edition ? $this->normalized($edition) : [],
        ]);
    }

    public function store(Request $request, Edition $edition): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'text' => ['nullable', 'string', 'max:2000'],
            'urgent' => ['nullable', 'boolean'],
        ]);

        $settings = (array) $edition->settings;
        $list = $this->listOf($settings);

        array_unshift($list, [
            'id' => (string) Str::uuid(),
            'when' => now()->format('H:i'),
            'at' => now()->toDateTimeString(),
            'urgent' => (bool) ($data['urgent'] ?? false),
            'title' => $data['title'],
            'text' => (string) ($data['text'] ?? ''),
        ]);

        $settings['announcements'] = array_slice($list, 0, 100);
        $edition->update(['settings' => $settings]);

        return back()->with('ukc_status', 'Annonce publiée.');
    }

    public function destroy(Edition $edition, string $announcement): RedirectResponse
    {
        $settings = (array) $edition->settings;
        $settings['announcements'] = array_values(array_filter(
            $this->listOf($settings),
            fn ($a) => (string) ($a['id'] ?? '') !== $announcement,
        ));
        $edition->update(['settings' => $settings]);

        return back()->with('ukc_status', 'Annonce retirée.');
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return list<array<string, mixed>>
     */
    private function listOf(array $settings): array
    {
        return is_array($settings['announcements'] ?? null)
            ? array_values($settings['announcements'])
            : [];
    }

    /**
     * Annonces avec un identifiant garanti (backfill des anciennes annonces
     * amorcées sans id), persisté au besoin pour permettre la suppression.
     *
     * @return list<array<string, mixed>>
     */
    private function normalized(Edition $edition): array
    {
        $settings = (array) $edition->settings;
        $list = $this->listOf($settings);
        $changed = false;

        foreach ($list as $i => $a) {
            if (blank($a['id'] ?? null)) {
                $list[$i]['id'] = (string) Str::uuid();
                $changed = true;
            }
        }

        if ($changed) {
            $settings['announcements'] = $list;
            $edition->update(['settings' => $settings]);
        }

        return $list;
    }

    private function currentEdition(Request $request): ?Edition
    {
        $editions = $this->accessibleEditions($request, 'announcements');
        $id = (string) $request->query('edition_id', '');

        if ($id === '') {
            // Priorité au colloque en cours (déroulement), sinon ouvert, sinon le plus récent.
            return $editions->first(fn (Edition $e) => $e->phase()->isOngoing())
                ?? $editions->firstWhere('status', 'open')
                ?? $editions->first();
        }

        return $editions->firstWhere('id', (int) $id);
    }
}
