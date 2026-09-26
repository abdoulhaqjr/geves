<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ExtractFormationFromDocuments;
use App\Actions\IssueAttestation;
use App\Actions\UpsertParticipantForAccount;
use App\AI\AiManager;
use App\Http\Controllers\Admin\Concerns\ScopesEditionAccess;
use App\Http\Controllers\Controller;
use App\Mail\CampaignMail;
use App\Models\Edition;
use App\Models\Email;
use App\Models\Formation;
use App\Models\FormationTrainer;
use App\Models\Participant;
use App\Models\User;
use App\Support\AuthorMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'moduleEnabled' => $edition ? $edition->formationEnabled() : false,
            'aiAvailable' => app(AiManager::class)->resolve() !== null,
            // Liste compacte : pas de détail ici (formateurs/inscrits/visio sont sur
            // la page de la formation, qu'une carte ne pouvait pas contenir).
            'formations' => $edition
                ? Formation::withCount([
                    'enrollments',
                    'enrollments as paid_count' => fn ($q) => $q->where('status', 'paid'),
                    'trainers',
                ])
                    ->where('edition_id', $edition->id)
                    ->orderByRaw('starts_at is null, starts_at')->orderBy('id')->get()
                : collect(),
        ]);
    }

    /**
     * Page d'une formation : tout ce qui ne tient pas dans une ligne de liste
     * (formateurs, inscrits nominatifs, visio, attestations, édition).
     */
    public function show(Formation $formation): View
    {
        $formation->load([
            'trainers.user',
            'enrollments' => fn ($q) => $q->with(['user', 'payment'])->latest('id'),
            'edition',
        ]);

        $paid = $formation->enrollments->where('status', 'paid');

        return view('admin.formations.show', [
            'f' => $formation,
            'edition' => $formation->edition,
            'stats' => [
                'enrolled' => $formation->enrollments->count(),
                'paid' => $paid->count(),
                'revenue' => (float) $paid->sum('amount'),
                'trainers' => $formation->trainers->count(),
            ],
        ]);
    }

    public function store(Request $request, Edition $edition): RedirectResponse
    {
        $data = $this->validated($request);
        $data['edition_id'] = $edition->id;
        $formation = Formation::create($data);
        $this->handleFlyer($request, $formation);

        // On ouvre directement la formation : c'est là que se règlent formateurs,
        // inscrits et visio.
        return redirect()->route('admin.formations.show', $formation)->with('ukc_status', 'Formation créée.');
    }

    public function update(Request $request, Formation $formation): RedirectResponse
    {
        $formation->update($this->validated($request));
        $this->handleFlyer($request, $formation);

        return back()->with('ukc_status', 'Formation mise à jour.');
    }

    /** Téléversement / retrait du flyer (bannière) de la formation, disque public. */
    private function handleFlyer(Request $request, Formation $formation): void
    {
        if ($request->boolean('flyer_remove') && filled($formation->flyer_path)) {
            Storage::disk('public')->delete((string) $formation->flyer_path);
            $formation->update(['flyer_path' => null]);

            return;
        }

        if ($request->hasFile('flyer')) {
            $request->validate(['flyer' => ['image', 'mimes:png,jpg,jpeg', 'max:8192']]);
            if (filled($formation->flyer_path)) {
                Storage::disk('public')->delete((string) $formation->flyer_path);
            }
            $formation->update(['flyer_path' => $request->file('flyer')->store('formations', 'public')]);
        }
    }

    public function destroy(Formation $formation): RedirectResponse
    {
        $editionId = $formation->edition_id;
        $formation->delete();

        // `back()` renverrait sur la page de la formation supprimée.
        return redirect()->route('admin.formations', ['edition_id' => $editionId])
            ->with('ukc_status', 'Formation supprimée.');
    }

    public function storeTrainer(Request $request, Formation $formation): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:200'],
            'specialty' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_moderator' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:4096'],
        ]);

        $account = $this->trainerAccount($data['email'] ?? null, $data['phone'] ?? null);

        $trainer = $formation->trainers()->create($this->trainerAttributes($data, $account) + [
            'is_moderator' => (bool) ($data['is_moderator'] ?? true),
        ]);

        if ($request->hasFile('photo')) {
            $trainer->update(['photo_path' => $request->file('photo')->store('formation-trainers', 'public')]);
        }

        return back()->with('ukc_status', 'Formateur ajouté.');
    }

    /** Change la photo d'un formateur (clic sur l'avatar). */
    public function updateTrainerPhoto(Request $request, Formation $formation, FormationTrainer $trainer): JsonResponse|RedirectResponse
    {
        abort_unless($trainer->formation_id === $formation->id, 404);
        $request->validate(['photo' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:4096']]);

        if (filled($trainer->photo_path)) {
            Storage::disk('public')->delete((string) $trainer->photo_path);
        }
        $trainer->update(['photo_path' => $request->file('photo')->store('formation-trainers', 'public')]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'url' => $trainer->photoUrl()]);
        }

        return back()->with('ukc_status', 'Photo du formateur mise à jour.');
    }

    /** Met à jour les informations d'un formateur (grade, nom, spécialité, e-mail). */
    public function updateTrainer(Request $request, Formation $formation, FormationTrainer $trainer): JsonResponse|RedirectResponse
    {
        abort_unless($trainer->formation_id === $formation->id, 404);
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:200'],
            'specialty' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_moderator' => ['nullable', 'boolean'],
        ]);

        $account = $this->trainerAccount($data['email'] ?? null, $data['phone'] ?? null);

        $trainer->update($this->trainerAttributes($data, $account) + [
            'is_moderator' => $request->boolean('is_moderator'),
        ]);

        if ($request->expectsJson()) {
            $trainer->refresh();

            return response()->json([
                'ok' => true,
                'is_moderator' => $trainer->is_moderator,
                'display_name' => trim(($trainer->title ? $trainer->title.' ' : '').$trainer->name),
                'sub' => collect([$trainer->specialty, $trainer->email])->filter()->join(' · '),
            ]);
        }

        return back()->with('ukc_status', 'Formateur mis à jour.');
    }

    public function destroyTrainer(Formation $formation, FormationTrainer $trainer): RedirectResponse
    {
        abort_unless($trainer->formation_id === $formation->id, 404);
        $trainer->delete();

        return back()->with('ukc_status', 'Formateur retiré.');
    }

    /**
     * Émet (et notifie/envoie) les attestations de la formation : participation
     * aux inscrits éligibles (réglés, ou inscrits si gratuite) + animation aux
     * formateurs. Idempotent (une attestation par personne/formation/type).
     */
    public function issueAttestations(Formation $formation, IssueAttestation $issue, UpsertParticipantForAccount $upsert): RedirectResponse
    {
        $edition = $formation->edition;
        $count = 0;

        foreach ($formation->enrollments()->with('participant')->get() as $enrollment) {
            $eligible = $enrollment->status === 'paid'
                || ($formation->isFree() && $enrollment->status === 'registered');
            if (! $eligible || ! $enrollment->participant) {
                continue;
            }
            $issue->handle($enrollment->participant, 'formation', null, $formation);
            $count++;
        }

        foreach ($formation->trainers as $trainer) {
            $participant = $this->participantForTrainer($edition, $trainer, $upsert);
            $issue->handle($participant, 'formation_animation', null, $formation);
            $count++;
        }

        return back()->with('ukc_status', $count.' attestation(s) de formation émise(s).');
    }

    /** Participant support d'un formateur (compte lié si possible, sinon par e-mail/nom). */
    private function participantForTrainer(Edition $edition, FormationTrainer $trainer, UpsertParticipantForAccount $upsert): Participant
    {
        if ($trainer->user_id && ($user = User::find($trainer->user_id))) {
            return $upsert->handle($edition, $user, role: 'trainer');
        }

        $key = filled($trainer->email)
            ? ['edition_id' => $edition->id, 'email' => $trainer->email]
            : ['edition_id' => $edition->id, 'first_name' => $trainer->name, 'last_name' => ''];

        return Participant::firstOrCreate($key, [
            'first_name' => $trainer->name,
            'last_name' => '',
            'role' => 'trainer',
            'email' => $trainer->email,
        ]);
    }

    /** Envoie un message (convocation / rappel) aux inscrits de la formation. */
    public function message(Request $request, Formation $formation): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'audience' => ['nullable', 'in:all,paid'],
        ]);
        $audience = $data['audience'] ?? 'all';

        $query = $formation->enrollments()->with('user');
        if ($audience === 'paid') {
            $query->where('status', 'paid');
        }

        $batchId = (string) Str::uuid();
        $count = 0;
        foreach ($query->get() as $enrollment) {
            $email = $enrollment->user?->email;
            if (! $email) {
                continue;
            }
            Email::create([
                'edition_id' => $formation->edition_id,
                'participant_id' => $enrollment->participant_id,
                'template' => 'formation',
                'audience' => $audience,
                'batch_id' => $batchId,
                'recipient' => $email,
                'subject' => $data['subject'],
                'body' => $data['body'],
                'scheduled_at' => now(),
                'status' => 'sent',
            ]);
            Mail::to($email)->send(new CampaignMail($data['subject'], $data['body']));
            $count++;
        }

        return back()->with('ukc_status', $count.' message(s) envoyé(s) aux inscrits.');
    }

    /**
     * Paramétrage assisté par IA : analyse des documents / une consigne et renvoie
     * les champs proposés (JSON) que le formulaire applique côté client.
     */
    public function aiAssist(Request $request, ExtractFormationFromDocuments $action): JsonResponse
    {
        $request->validate([
            'aiFiles' => ['nullable', 'array', 'max:6'],
            'aiFiles.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
            'aiPrompt' => ['nullable', 'string', 'max:4000'],
        ]);

        $files = collect($request->file('aiFiles') ?? [])
            ->map(fn ($f) => ['mime' => (string) $f->getMimeType(), 'data' => base64_encode((string) $f->get())])
            ->all();

        if (trim((string) $request->input('aiPrompt')) !== '') {
            $files[] = ['mime' => 'text/plain', 'data' => base64_encode((string) $request->input('aiPrompt'))];
        }

        if ($files === []) {
            return response()->json(['ok' => false, 'message' => 'Fournissez un document ou une consigne.'], 422);
        }

        try {
            $fields = $action->handle($files);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Échec de l’analyse : '.$e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'fields' => $fields,
            'message' => $fields === [] ? 'Aucune information exploitable extraite.' : count($fields).' champ(s) pré-rempli(s). Vérifiez puis créez.',
        ]);
    }

    /** Configure la visio de la formation (fournisseur, salle/lien, état, visibilité). */
    public function setMeeting(Request $request, Formation $formation): RedirectResponse
    {
        $data = $request->validate([
            'meeting_provider' => ['nullable', 'in:livekit,jitsi,custom'],
            'meeting_room' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9 _-]*$/'],
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'meeting_status' => ['required', 'in:none,scheduled,live,ended'],
            'meeting_visibility' => ['nullable', 'in:public,participants'],
        ]);

        $formation->update([
            'meeting_provider' => $data['meeting_provider'] ?? null,
            'meeting_room' => $data['meeting_room'] ?? null,
            'meeting_url' => $data['meeting_url'] ?? null,
            'meeting_status' => $data['meeting_status'],
            'meeting_visibility' => $data['meeting_visibility'] ?? 'participants',
        ]);

        return back()->with('ukc_status', 'Visio de la formation mise à jour.');
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
    /**
     * Compte correspondant à un **identifiant fort** saisi pour un formateur
     * (e-mail OU téléphone), sur le modèle des co-auteurs. Jamais par nom : on ne
     * rattache pas un homonyme.
     */
    private function trainerAccount(?string $email, ?string $phone): ?User
    {
        $email = AuthorMatcher::normEmail($email);
        $phone = AuthorMatcher::normPhone($phone);
        if ($email === '' && $phone === '') {
            return null;
        }

        $match = app(AuthorMatcher::class)->match(['email' => $email, 'phone' => $phone]);

        return $match['type'] === 'name' ? null : $match['user'];
    }

    /** Nom d'affichage d'un compte (identité du profil, repli sur `users.name`). */
    private function accountName(User $account): string
    {
        return trim(trim((string) $account->first_name).' '.trim((string) $account->last_name))
            ?: (string) $account->name;
    }

    /**
     * Attributs d'un formateur. ⚠️ Si un identifiant fort correspond à un compte,
     * **l'identité vient du compte** (nom, e-mail, téléphone) et non de la saisie :
     * cela évite les fautes de nom et empêche d'inscrire un formateur sous
     * l'e-mail d'un autre utilisateur existant. Grade et spécialité restent
     * propres à la formation (pré-remplis côté client, modifiables).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function trainerAttributes(array $data, ?User $account): array
    {
        return [
            'user_id' => $account?->id,
            'title' => $data['title'] ?? null,
            'name' => $account ? $this->accountName($account) : (string) $data['name'],
            'specialty' => $data['specialty'] ?? null,
            'email' => $account ? (string) $account->email : ($data['email'] ?? null),
            'phone' => $account && filled($account->phone) ? (string) $account->phone : ($data['phone'] ?? null),
        ];
    }

    /**
     * Rapprochement à la saisie (e-mail/téléphone → compte) pour pré-remplir la
     * ligne « formateur », comme pour les co-auteurs.
     */
    public function lookupTrainer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $account = $this->trainerAccount($data['email'] ?? null, $data['phone'] ?? null);

        if (! $account) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'name' => $this->accountName($account),
            'email' => (string) $account->email,
            'phone' => (string) ($account->phone ?? ''),
            'title' => (string) ($account->civility ?? ''),
            'specialty' => (string) ($account->grade ?: $account->affiliation ?: ''),
            'photo' => filled($account->photo_path) ? Storage::url((string) $account->photo_path) : null,
        ]);
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
