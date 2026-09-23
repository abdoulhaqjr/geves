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
            'formations' => $edition
                ? Formation::with(['trainers', 'enrollments' => fn ($q) => $q->with('user')->latest('id')])
                    ->withCount(['enrollments', 'enrollments as paid_count' => fn ($q) => $q->where('status', 'paid')])
                    ->where('edition_id', $edition->id)
                    ->orderByRaw('starts_at is null, starts_at')->orderBy('id')->get()
                : collect(),
        ]);
    }

    public function store(Request $request, Edition $edition): RedirectResponse
    {
        $data = $this->validated($request);
        $data['edition_id'] = $edition->id;
        $formation = Formation::create($data);
        $this->handleFlyer($request, $formation);

        return back()->with('ukc_status', 'Formation créée.');
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
            $request->validate(['flyer' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:8192']]);
            if (filled($formation->flyer_path)) {
                Storage::disk('public')->delete((string) $formation->flyer_path);
            }
            $formation->update(['flyer_path' => $request->file('flyer')->store('formations', 'public')]);
        }
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
