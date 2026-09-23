<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ScopesEditionAccess;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Formation;
use App\Models\FormationTrainer;
use App\Models\User;
use App\Support\EditionSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Module Formations d'une édition (capacité `formation`). Activable par édition
 * (`settings['formation']['enabled']`) ; plusieurs formations possibles, avec
 * formateurs, frais (0 = gratuite), lieu/salle, dates. Paiements, inscriptions,
 * page publique, e-mails, attestations et visio arrivent aux phases suivantes.
 */
class FormationController extends Controller
{
    use ScopesEditionAccess;

    public function index(Request $request): View
    {
        $edition = $this->currentEdition($request);

        return view('admin.formations.index', [
            'editions' => $this->accessibleEditions($request, 'formation'),
            'edition' => $edition,
            'edition_id' => $edition ? (string) $edition->id : '',
            'moduleEnabled' => $edition ? (bool) EditionSettings::get($edition, 'formation.enabled', false) : false,
            'formations' => $edition
                ? Formation::with('trainers')->where('edition_id', $edition->id)->orderByRaw('starts_at is null, starts_at')->orderBy('id')->get()
                : collect(),
        ]);
    }

    /** Active / désactive le module Formations pour l'édition. */
    public function toggleModule(Request $request, Edition $edition): RedirectResponse
    {
        EditionSettings::setMany($edition, ['formation.enabled' => $request->boolean('enabled')]);

        return back()->with('ukc_status', $request->boolean('enabled')
            ? 'Module Formations activé pour cette édition.'
            : 'Module Formations désactivé.');
    }

    public function store(Request $request, Edition $edition): RedirectResponse
    {
        $data = $this->validated($request);
        $data['edition_id'] = $edition->id;
        Formation::create($data);

        return back()->with('ukc_status', 'Formation créée.');
    }

    public function update(Request $request, Formation $formation): RedirectResponse
    {
        $formation->update($this->validated($request));

        return back()->with('ukc_status', 'Formation mise à jour.');
    }

    public function destroy(Formation $formation): RedirectResponse
    {
        $formation->delete();

        return back()->with('ukc_status', 'Formation supprimée.');
    }

    public function storeTrainer(Request $request, Formation $formation): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:200'],
            'affiliation' => ['nullable', 'string', 'max:200'],
            'is_moderator' => ['nullable', 'boolean'],
        ]);

        $formation->trainers()->create([
            'user_id' => $this->userIdForEmail($data['email'] ?? null),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'affiliation' => $data['affiliation'] ?? null,
            'is_moderator' => (bool) ($data['is_moderator'] ?? true),
        ]);

        return back()->with('ukc_status', 'Formateur ajouté.');
    }

    public function destroyTrainer(Formation $formation, FormationTrainer $trainer): RedirectResponse
    {
        abort_unless($trainer->formation_id === $formation->id, 404);
        $trainer->delete();

        return back()->with('ukc_status', 'Formateur retiré.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'theme' => ['nullable', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:200'],
            'room' => ['nullable', 'string', 'max:120'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'published' => ['nullable', 'boolean'],
            'public' => ['nullable', 'boolean'],
        ]);

        // Cases à cocher absentes = false ; frais vide = 0 (gratuite).
        $data['fee'] = (float) ($data['fee'] ?? 0);
        $data['published'] = $request->boolean('published');
        $data['public'] = $request->boolean('public');

        return $data;
    }

    /** Rattache le formateur au compte unifié (users) si l'e-mail correspond. */
    private function userIdForEmail(?string $email): ?int
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email === '') {
            return null;
        }

        return User::whereRaw('LOWER(email) = ?', [$email])->value('id');
    }

    private function currentEdition(Request $request): ?Edition
    {
        $editions = $this->accessibleEditions($request, 'formation');
        $id = (string) $request->query('edition_id', '');

        if ($id === '') {
            return $editions->firstWhere('status', 'open') ?? $editions->first();
        }

        return $editions->firstWhere('id', (int) $id);
    }
}
