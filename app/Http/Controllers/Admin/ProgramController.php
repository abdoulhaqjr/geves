<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MeetingStatus;
use App\Enums\MeetingVisibility;
use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\SessionReport;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\User;
use App\Support\ListSort;
use App\Support\Notifier;
use App\Support\ProgramConflicts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Programme d'une édition (contrôleur + Blade) : ateliers (regroupant des axes),
 * sessions (plénières ou d'atelier, avec modérateur/rapporteur), contrôles de
 * visio par session, et créneaux de passage (assignation des communications
 * acceptées). L'édition est choisie via query string.
 */
class ProgramController extends Controller
{
    use Concerns\ScopesEditionAccess;

    // --- Ateliers & sessions --------------------------------------------------

    /**
     * ⚠️ Anciennes entrées du module, conservées en REDIRECTION : le programme
     * vivait sur deux pages (`/programme` et `/programme/creneaux`) qui géraient
     * les mêmes ateliers et les mêmes sessions, avec deux interfaces. Tout est
     * regroupé dans `index()`, mais les liens et signets existants doivent
     * continuer de tomber juste.
     */
    public function sessions(Request $request): RedirectResponse
    {
        return redirect()->route('admin.program.index', array_filter([
            'edition_id' => $request->query('edition_id'),
            'tab' => 'sessions',
        ]));
    }

    public function slots(Request $request): RedirectResponse
    {
        return redirect()->route('admin.program.index', array_filter([
            'edition_id' => $request->query('edition_id'),
            'tab' => $request->query('tab', 'planning'),
        ]));
    }

    /**
     * Rapports de séance : consultation par le comité des synthèses rédigées par
     * les rapporteurs (état à rédiger / brouillon / soumis).
     */
    /**
     * ⚠️ Les rapports de séance relèvent du **Secrétariat** : c'est lui qui les
     * lit, les améliore et les VALIDE (la validation verrouille le rapport côté
     * rapporteur). Le module Programme n'en porte plus de copie ; l'ancienne
     * adresse redirige, pour les liens et signets existants.
     */
    public function reports(Request $request): RedirectResponse
    {
        return redirect()->route('admin.secretariat', array_filter([
            'edition_id' => $request->query('edition_id'),
        ]));
    }

    /**
     * Secrétariat : améliore et VALIDE le rapport de séance. La validation
     * verrouille le rapport côté rapporteur (il ne peut plus le modifier).
     */
    public function saveReport(Request $request, EventSession $session): RedirectResponse
    {
        $data = $request->validate([
            'summary' => ['nullable', 'string', 'max:20000'],
            'highlights' => ['nullable', 'string', 'max:5000'],
            'attendance' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $report = SessionReport::firstOrNew(['session_id' => $session->id]);
        $report->edition_id = $session->edition_id;
        // Ne touche au contenu que si les champs sont fournis : un POST « nu »
        // (validation en un clic) verrouille sans écraser le rapport du rapporteur.
        if ($request->has('summary')) {
            $report->summary = ($data['summary'] ?? null) ?: null;
        }
        if ($request->has('highlights')) {
            $report->highlights = ($data['highlights'] ?? null) ?: null;
        }
        if ($request->has('attendance')) {
            $report->attendance = $data['attendance'] ?? null;
        }
        $report->status = 'validated';
        $report->submitted_at = $report->submitted_at ?? now();
        $report->validated_at = now();
        $report->validated_by = $request->user()?->id;
        $report->save();

        return back()->with('ukc_status', 'Rapport validé. Il est verrouillé pour le rapporteur.');
    }

    /** Rouvre le rapport au rapporteur (annule la validation du secrétariat). */
    public function reopenReport(EventSession $session): RedirectResponse
    {
        $report = SessionReport::where('session_id', $session->id)->first();
        if ($report !== null) {
            $report->update([
                'status' => filled($report->summary) ? 'submitted' : 'draft',
                'validated_at' => null,
                'validated_by' => null,
            ]);
        }

        return back()->with('ukc_status', 'Rapport rouvert : le rapporteur peut de nouveau le modifier.');
    }

    /**
     * Notifie la personne désignée modérateur / rapporteur d'une séance, si un
     * compte correspond à l'e-mail ET que la désignation est nouvelle (e-mail
     * nouvellement renseigné ou modifié). Silencieux sinon.
     */
    private function notifyRoleAssignment(EventSession $session, string $roleKey, ?string $email, ?string $previousEmail): void
    {
        $email = filled($email) ? mb_strtolower(trim($email)) : null;
        $previousEmail = filled($previousEmail) ? mb_strtolower(trim($previousEmail)) : null;

        if ($email === null || $email === $previousEmail) {
            return;
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user === null) {
            return;
        }

        $label = $session->label ?: 'une séance';
        [$title, $body, $url, $icon] = $roleKey === 'moderator'
            ? [
                'Vous animez une séance',
                'Vous avez été désigné(e) modérateur de « '.$label.' ».',
                route('account.moderation'),
                'mic',
            ]
            : [
                'Vous rapportez une séance',
                'Vous avez été désigné(e) rapporteur de « '.$label.' ».',
                route('account.reports'),
                'sheet',
            ];

        Notifier::account($user, kind: 'session_role', title: $title, body: $body, url: $url, icon: $icon);
    }

    public function storeAtelier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'atelierTitle' => ['required', 'string', 'max:255'],
            'atelierTracks' => ['array'],
            'atelierTracks.*' => ['string'],
        ]);

        Atelier::create([
            'edition_id' => $data['edition_id'],
            'title' => $data['atelierTitle'],
            'tracks' => array_values($data['atelierTracks'] ?? []),
            'ordre' => Atelier::where('edition_id', $data['edition_id'])->count(),
        ]);

        return back()->with('ukc_status', 'Atelier ajouté.');
    }

    public function deleteAtelier(Atelier $atelier): RedirectResponse
    {
        EventSession::where('atelier_id', $atelier->id)->update(['atelier_id' => 0, 'type' => 'plenary']);
        $atelier->delete();

        return back()->with('ukc_status', 'Atelier supprimé.');
    }

    public function storeSession(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'sType' => ['required', 'in:plenary,atelier'],
            'sAtelierId' => ['nullable'],
            'sLabel' => ['required', 'string', 'max:255'],
            'sDay' => ['nullable', 'date'],
            'sStart' => ['nullable', 'date_format:H:i'],
            'sEnd' => ['nullable', 'date_format:H:i'],
            'sRoom' => ['nullable', 'string', 'max:150'],
            'sModerator' => ['nullable', 'string', 'max:200'],
            'sModeratorEmail' => ['nullable', 'email', 'max:200'],
            'sRapporteur' => ['nullable', 'string', 'max:200'],
            'sRapporteurEmail' => ['nullable', 'email', 'max:200'],
        ]);

        $start = $this->composeDateTime($data['sDay'] ?? null, $data['sStart'] ?? null);
        $end = $this->composeDateTime($data['sDay'] ?? null, $data['sEnd'] ?? null);

        // Exclusivité des plénières : bloque une session en conflit horaire.
        $edition = Edition::find($data['edition_id']);
        if ($edition && ($conflict = ProgramConflicts::blockingSession(
            $edition, $start ? Carbon::parse($start) : null, $end ? Carbon::parse($end) : null, $data['sType'] === 'plenary'
        ))) {
            return back()->withInput()->with('ukc_error', ProgramConflicts::message($conflict));
        }
        if ($edition && ($break = ProgramConflicts::lunchClash($edition, $start ? Carbon::parse($start) : null, $end ? Carbon::parse($end) : null))) {
            return back()->withInput()->with('ukc_error', ProgramConflicts::breakMessage($break));
        }

        $session = EventSession::create([
            'edition_id' => $data['edition_id'],
            'atelier_id' => $data['sType'] === 'atelier' && ! empty($data['sAtelierId']) ? (int) $data['sAtelierId'] : 0,
            'type' => $data['sType'],
            'label' => $data['sLabel'],
            'session_day' => $data['sDay'] ?: null,
            'starts_at' => $start,
            'ends_at' => $end,
            'room' => ($data['sRoom'] ?? null) ?: null,
            'moderator_name' => ($data['sModerator'] ?? null) ?: null,
            'moderator_email' => ($data['sModeratorEmail'] ?? null) ?: null,
            'rapporteur_name' => ($data['sRapporteur'] ?? null) ?: null,
            'rapporteur_email' => ($data['sRapporteurEmail'] ?? null) ?: null,
            'ordre' => EventSession::where('edition_id', $data['edition_id'])->count(),
        ]);

        $this->notifyRoleAssignment($session, 'moderator', $session->moderator_email, null);
        $this->notifyRoleAssignment($session, 'rapporteur', $session->rapporteur_email, null);

        return back()->with('ukc_status', 'Session ajoutée.');
    }

    public function deleteSession(EventSession $session): RedirectResponse
    {
        $session->delete();

        return back()->with('ukc_status', 'Session supprimée.');
    }

    public function setMeetingStatus(Request $request, EventSession $session): RedirectResponse
    {
        $status = (string) $request->input('status');
        if (in_array($status, MeetingStatus::values(), true)) {
            $session->update(['meeting_status' => $status]);
        }

        return back()->with('ukc_status', 'Visio : '.MeetingStatus::from($status)->label().'.');
    }

    public function setMeetingVisibility(Request $request, EventSession $session): RedirectResponse
    {
        $visibility = (string) $request->input('visibility');
        if (in_array($visibility, MeetingVisibility::values(), true)) {
            $session->update(['meeting_visibility' => $visibility]);
        }

        return back();
    }

    public function saveMeeting(Request $request, EventSession $session): RedirectResponse
    {
        $provider = (string) $request->input('mProvider');
        $data = $request->validate([
            'mProvider' => ['required', 'in:jitsi,livekit,custom'],
            'mRoom' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'mUrl' => [$provider === 'custom' ? 'required' : 'nullable', 'url', 'max:500'],
        ], [], ['mRoom' => 'nom de salle', 'mUrl' => 'lien de réunion']);

        $usesRoom = in_array($provider, ['jitsi', 'livekit'], true);

        $session->update([
            'meeting_provider' => $data['mProvider'],
            'meeting_room' => $usesRoom ? (($data['mRoom'] ?? null) ?: null) : null,
            'meeting_url' => $provider === 'custom' ? (($data['mUrl'] ?? null) ?: null) : null,
        ]);

        return back()->with('ukc_status', 'Visio personnalisée.');
    }

    // --- Créneaux -------------------------------------------------------------

    /**
     * Module Programme : planning, ateliers, sessions, créneaux, espaces,
     * affectation auto, rapports et options - un onglet par `?tab=`.
     */
    public function index(Request $request): View
    {
        $edition = $this->currentEdition($request);

        $ateliers = $edition ? $edition->ateliers()->with(['sessions' => fn ($q) => $q->orderBy('starts_at')->orderBy('ordre')])->orderBy('ordre')->get() : collect();
        $sessions = $edition ? $edition->sessions()->with('atelier')->orderBy('starts_at')->orderBy('ordre')->get() : collect();
        $slots = $edition ? $edition->slots()->with(['submission', 'session.atelier'])->orderBy('starts_at')->get() : collect();
        $accepted = $edition ? $edition->submissions()->where('revision_of', 0)->where('status', 'accepted')
            ->with('participant.payments')->get() : collect();

        // Communications acceptées non encore placées sur un créneau.
        $placedIds = $slots->where('submission_id', '>', 0)->pluck('submission_id');
        $unplaced = $accepted->reject(fn (Submission $s) => $placedIds->contains($s->id))->values();

        // Jours du programme = jours distincts portés par les sessions et créneaux.
        $days = $sessions->map(fn (EventSession $s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d'))
            ->merge($slots->map(fn (Slot $sl) => optional($sl->starts_at)?->format('Y-m-d')))
            ->filter()->unique()->sort()->values();

        // Configuration du programme (affectation auto, paramètres, espaces),
        // persistée dans edition->settings['program'].
        $config = $this->programConfig($edition);

        // Salles physiquement utilisées (dérivées des sessions/créneaux).
        $usedRooms = $sessions->pluck('room')->merge($slots->pluck('room'))
            ->filter()->unique()->sort()->values();

        $tabs = ['apercu', 'planning', 'ateliers', 'sessions', 'creneaux', 'espaces', 'auto', 'options'];
        $tab = in_array($request->query('tab'), $tabs, true) ? (string) $request->query('tab') : 'apercu';

        // ── Onglets Sessions & Créneaux = tableaux paginés + triés CÔTÉ SERVEUR
        //    (comme admin/soumissions). Les collections complètes ci-dessus
        //    restent pour le board Planning ; ici on filtre/trie/pagine en mémoire.
        //    Paramètres préfixés (s_*/c_*) pour que les deux tableaux soient
        //    indépendants sur la même page.
        $oralMin = $edition ? $this->oralMinutes($edition) : 20;
        $sessionsView = $this->sessionsTable($request, $sessions);
        $slotsView = $this->slotsTable($request, $slots, $oralMin);

        return view('admin.program.index', [
            'tab' => $tab,
            // Programme synthétique (onglet « Programme ») : saisi ici, publié tel quel.
            'programSynthesis' => $edition
                ? array_merge(['title' => '', 'days' => []], $edition->settings['program_synthesis'] ?? [])
                : ['title' => '', 'days' => []],
            'plenaries' => $sessions->where('type', 'plenary')->values(),
            'editions' => $this->accessibleEditions($request, 'editions_read'),
            'edition' => $edition,
            'edition_id' => $edition ? (string) $edition->id : '',
            'ateliers' => $ateliers,
            'sessions' => $sessions,
            'creneaux' => $slots,
            'oralMin' => $oralMin,
            'sessionsPage' => $sessionsView['page'],
            'sessSort' => $sessionsView['sort'],
            'sessDir' => $sessionsView['dir'],
            'sessFilters' => $sessionsView['filters'],
            'sessActive' => $sessionsView['active'],
            'slotsPage' => $slotsView['page'],
            'slotSort' => $slotsView['sort'],
            'slotDir' => $slotsView['dir'],
            'slotFilters' => $slotsView['filters'],
            'slotActive' => $slotsView['active'],
            'accepted' => $accepted,
            'unplaced' => $unplaced,
            'days' => $days,
            'tracks' => $edition ? ($edition->settings['tracks'] ?? []) : [],
            'config' => $config,
            'spaces' => $config['spaces'],
            'atelierRooms' => $config['atelier_rooms'],
            'usedRooms' => $usedRooms,
            'stats' => [
                'ateliers' => $ateliers->count(),
                'sessions' => $sessions->count(),
                'slots' => $slots->count(),
                'planned' => $slots->where('submission_id', '>', 0)->count(),
                'toPlan' => $unplaced->count(),
                'autoAssign' => (bool) ($config['auto']['enabled'] ?? false),
            ],
        ]);
    }

    /**
     * Onglet Sessions : filtre (s_*), trie (s_sort/s_dir) et pagine (s_page) la
     * collection des sessions déjà chargée.
     *
     * @param  Collection<int, EventSession>  $sessions
     * @return array{page: LengthAwarePaginator<int, EventSession>, sort: string, dir: string, filters: array<string, string>, active: bool}
     */
    private function sessionsTable(Request $request, Collection $sessions): array
    {
        $f = [
            'day' => (string) $request->query('s_day', ''),
            'type' => (string) $request->query('s_type', ''),
            'atelier' => (string) $request->query('s_atelier', ''),
            'room' => (string) $request->query('s_room', ''),
            'mod' => (string) $request->query('s_mod', ''),
            'q' => trim((string) $request->query('s_q', '')),
        ];
        $q = mb_strtolower($f['q']);

        $filtered = $sessions->filter(function (EventSession $s) use ($f, $q) {
            $day = optional($s->session_day ?? $s->starts_at)?->format('Y-m-d');
            $type = $s->atelier_id ? 'atelier' : 'plenary';
            $mod = $s->moderator_name ? 'assigned' : 'pending';
            $text = mb_strtolower(collect([$s->label, $s->atelier?->title, $s->moderator_name, $s->rapporteur_name, $s->room])->filter()->implode(' '));

            return ($f['day'] === '' || $day === $f['day'])
                && ($f['type'] === '' || $type === $f['type'])
                && ($f['atelier'] === '' || $s->atelier?->title === $f['atelier'])
                && ($f['room'] === '' || $s->room === $f['room'])
                && ($f['mod'] === '' || $mod === $f['mod'])
                && ($q === '' || str_contains($text, $q));
        })->values();

        $sort = ListSort::resolve($request, ['day', 'atelier', 'label', 'time', 'moderator', 'rapporteur'], 'time', 'asc', 's_sort', 's_dir');
        $keys = [
            'day' => fn (EventSession $s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d H:i') ?? '',
            // optional()/data_get : ces valeurs sont bien nullables au runtime
            // (séance sans horaire, plénière sans atelier) ; on évite le nullsafe
            // direct que Larastan croit à tort inutile.
            'time' => fn (EventSession $s) => optional($s->starts_at)->timestamp ?? 0,
            'atelier' => fn (EventSession $s) => mb_strtolower((string) data_get($s, 'atelier.title')),
            'label' => fn (EventSession $s) => mb_strtolower($s->label ?? ''),
            'moderator' => fn (EventSession $s) => mb_strtolower($s->moderator_name ?? ''),
            'rapporteur' => fn (EventSession $s) => mb_strtolower($s->rapporteur_name ?? ''),
        ];
        $sorted = $filtered->sortBy($keys[$sort['sort']], SORT_REGULAR, $sort['dir'] === 'desc')->values();

        return [
            'page' => $this->paginateCollection($sorted, 20, 's_page'),
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
            'filters' => $f,
            'active' => collect($f)->contains(fn ($v) => $v !== ''),
        ];
    }

    /**
     * Onglet Créneaux : filtre (c_*), trie (c_sort/c_dir) et pagine (c_page) la
     * collection des créneaux déjà chargée.
     *
     * @param  Collection<int, Slot>  $slots
     * @return array{page: LengthAwarePaginator<int, Slot>, sort: string, dir: string, filters: array<string, string>, active: bool}
     */
    private function slotsTable(Request $request, Collection $slots, int $oralMin): array
    {
        $f = [
            'day' => (string) $request->query('c_day', ''),
            'atelier' => (string) $request->query('c_atelier', ''),
            'room' => (string) $request->query('c_room', ''),
            'status' => (string) $request->query('c_status', ''),
            'type' => (string) $request->query('c_type', ''),
            'q' => trim((string) $request->query('c_q', '')),
        ];
        $q = mb_strtolower($f['q']);

        $filtered = $slots->filter(function (Slot $s) use ($f, $q) {
            $day = $s->starts_at?->format('Y-m-d');
            $type = $s->submission?->type === 'poster' ? 'poster' : ($s->submission ? 'oral' : '');
            $status = $s->submission ? 'assigned' : 'free';
            $text = mb_strtolower(collect([$s->session_label, $s->session?->atelier?->title, $s->submission?->title, $s->submission?->ref_code, $s->room])->filter()->implode(' '));

            return ($f['day'] === '' || $day === $f['day'])
                && ($f['atelier'] === '' || $s->session?->atelier?->title === $f['atelier'])
                && ($f['room'] === '' || $s->room === $f['room'])
                && ($f['status'] === '' || $status === $f['status'])
                && ($f['type'] === '' || $type === $f['type'])
                && ($q === '' || str_contains($text, $q));
        })->values();

        $sort = ListSort::resolve($request, ['day', 'time', 'room', 'atelier', 'title', 'type', 'code'], 'time', 'asc', 'c_sort', 'c_dir');
        $keys = [
            'day' => fn (Slot $s) => $s->starts_at?->format('Y-m-d H:i') ?? '',
            'time' => fn (Slot $s) => optional($s->starts_at)->timestamp ?? 0,
            'room' => fn (Slot $s) => mb_strtolower($s->room ?? ''),
            'atelier' => fn (Slot $s) => mb_strtolower((string) data_get($s, 'session.atelier.title')),
            'title' => fn (Slot $s) => mb_strtolower((string) data_get($s, 'submission.title')),
            'type' => fn (Slot $s) => $s->submission?->type === 'poster' ? 'poster' : ($s->submission ? 'oral' : 'zzz'),
            'code' => fn (Slot $s) => (string) data_get($s, 'submission.ref_code'),
        ];
        $sorted = $filtered->sortBy($keys[$sort['sort']], SORT_REGULAR, $sort['dir'] === 'desc')->values();

        return [
            'page' => $this->paginateCollection($sorted, 20, 'c_page'),
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
            'filters' => $f,
            'active' => collect($f)->contains(fn ($v) => $v !== ''),
        ];
    }

    /** Valeurs par défaut de la config programme, fusionnées avec settings['program']. */
    private function programConfig(?Edition $edition): array
    {
        $defaults = [
            'auto' => [
                'enabled' => false, 'only_paid' => true, 'match_axis' => true,
                'notify' => true, 'use_ai' => false, 'order' => 'chrono', 'fallback' => 'wait',
            ],
            'params' => [
                'oral_min' => 20, 'keynote_min' => 45, 'poster_min' => 90, 'gap_min' => 0,
                'auto_break' => true, 'break_after' => 3, 'break_min' => 15,
                'lunch' => true, 'lunch_start' => '12:30', 'lunch_end' => '14:00',
                'day_start' => '08:30', 'day_end' => '17:30',
                'no_overlap' => true, 'no_double_author' => true, 'match_axis' => true, 'require_mod' => true,
                'show_room' => true, 'show_mod' => true, 'show_empty' => false,
            ],
            'spaces' => [],
            'atelier_rooms' => [],
        ];

        $saved = $edition->settings['program'] ?? [];

        return [
            'auto' => array_merge($defaults['auto'], $saved['auto'] ?? []),
            'params' => array_merge($defaults['params'], $saved['params'] ?? []),
            'spaces' => array_values($saved['spaces'] ?? []),
            'atelier_rooms' => $saved['atelier_rooms'] ?? [],
        ];
    }

    /** Écrit la config programme dans settings['program'] en préservant le reste. */
    private function writeProgramConfig(Edition $edition, array $patch): void
    {
        $current = $this->programConfig($edition);

        // auto/params : fusion clé à clé ; spaces/atelier_rooms : remplacement en bloc.
        $program = [
            'auto' => array_merge($current['auto'], $patch['auto'] ?? []),
            'params' => array_merge($current['params'], $patch['params'] ?? []),
            'spaces' => array_key_exists('spaces', $patch) ? array_values($patch['spaces']) : $current['spaces'],
            // Union (+) et non array_merge : préserve les clés = ids d'ateliers
            // (array_merge réindexe les clés numériques et corromprait la carte).
            'atelier_rooms' => array_key_exists('atelier_rooms', $patch)
                ? array_filter($patch['atelier_rooms'] + $current['atelier_rooms'])
                : $current['atelier_rooms'],
        ];

        $settings = $edition->settings ?? [];
        $settings['program'] = $program;
        $edition->update(['settings' => $settings]);
    }

    /**
     * Affectation automatique : place les communications acceptées non planifiées
     * sur les créneaux libres, dans l'ordre chronologique. Si « respecter l'axe »
     * est demandé, on privilégie un créneau dont la session appartient à un atelier
     * couvrant l'axe (track) de la communication, avec repli sur le premier libre.
     */
    public function autoAssignSlots(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back();
        }

        $matchAxis = $request->boolean('match_axis');

        $freeSlots = $edition->slots()->where('submission_id', 0)
            ->with('session.atelier')->orderBy('starts_at')->get();

        $placedIds = $edition->slots()->where('submission_id', '>', 0)->pluck('submission_id');
        $unplaced = $edition->submissions()->where('revision_of', 0)->where('status', 'accepted')
            ->get()->reject(fn (Submission $s) => $placedIds->contains($s->id))->values();

        $oralMin = $this->oralMinutes($edition);

        // Ateliers simultanés autorisés, MAIS un même auteur ne peut pas être placé
        // sur deux créneaux qui se chevauchent. On mémorise les créneaux déjà pris
        // par participant (y compris ceux déjà planifiés) pour éviter le télescopage.
        $busy = $edition->slots()->where('participant_id', '>', 0)->whereNotNull('starts_at')
            ->get()->groupBy('participant_id')
            ->map(fn ($slots) => $slots->map(fn (Slot $s) => [$s->starts_at, $s->ends_at ?? $s->starts_at?->copy()->addMinutes($oralMin)])->all())
            ->all();

        $overlaps = function (?Carbon $start, ?Carbon $end, int $participantId) use (&$busy): bool {
            if (! $start) {
                return false;
            }
            $end ??= $start->copy()->addMinutes(1);
            foreach ($busy[$participantId] ?? [] as [$s, $e]) {
                if ($s && $start->lt($e ?? $s) && ($s)->lt($end)) {
                    return true;
                }
            }

            return false;
        };

        $assigned = $skippedConflict = 0;
        foreach ($unplaced as $sub) {
            $pid = (int) ($sub->participant_id ?? 0);

            // Créneau préféré : même axe (atelier couvrant le track) si demandé, en
            // évitant tout chevauchement avec un autre créneau du même auteur.
            $free = $freeSlots->reject(fn (Slot $sl) => $pid > 0 && $overlaps(
                $sl->starts_at, $sl->ends_at ?? $sl->starts_at?->copy()->addMinutes($oralMin), $pid
            ))->values();

            $slot = null;
            if ($matchAxis && filled($sub->track)) {
                $slot = $free->first(fn (Slot $sl) => in_array(
                    $sub->track, $sl->session?->atelier?->tracks ?? [], true
                ));
            }
            $slot ??= $free->first();
            if (! $slot) {
                if ($pid > 0 && $freeSlots->isNotEmpty()) {
                    $skippedConflict++; // il restait des créneaux, mais tous en conflit horaire
                }

                continue; // pas de créneau compatible pour cet auteur
            }

            $slot->update([
                'submission_id' => $sub->id,
                'participant_id' => $pid,
                'chosen_by_participant' => false,
            ]);
            if ($pid > 0) {
                $end = $slot->ends_at ?? $slot->starts_at?->copy()->addMinutes($oralMin);
                $busy[$pid][] = [$slot->starts_at, $end];
            }
            $freeSlots = $freeSlots->reject(fn (Slot $s) => $s->id === $slot->id)->values();
            $assigned++;
        }

        $msg = $assigned > 0
            ? $assigned.' communication(s) affectée(s) automatiquement.'
            : 'Aucune affectation : pas de créneau libre ou rien à planifier.';
        if ($skippedConflict > 0) {
            $msg .= ' '.$skippedConflict.' non affectée(s) faute de créneau compatible (auteur déjà occupé au même moment).';
        }

        return back()->with('ukc_status', $msg);
    }

    /**
     * Génère des créneaux libres à partir des sessions horodatées : chaque session
     * est découpée en créneaux de la durée d'une communication orale (réglages),
     * héritant du libellé et de la salle de la session. Les sessions déjà pourvues
     * sont ignorées. C'est le préalable à l'affectation automatique (qui remplit
     * les créneaux libres avec les communications acceptées, comme le plugin WP).
     */
    public function generateSlots(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back();
        }

        // Paramètres de créneaux (onglet Options), appliqués par sliceSessionSlots().
        $params = $this->programConfig($edition)['params'];
        $created = 0;

        // Les sessions plénières sont EXCLUSIVES et ne reçoivent pas de communications
        // orales : on ne génère de créneaux que pour les sessions d'atelier.
        $sessions = $edition->sessions()->where('type', '!=', 'plenary')
            ->whereNotNull('starts_at')->whereNotNull('ends_at')->get();
        foreach ($sessions as $session) {
            if ($edition->slots()->where('session_id', $session->id)->exists()) {
                continue; // déjà pourvue
            }
            $created += count($this->sliceSessionSlots($edition, $session, $params));
        }

        return back()->with('ukc_status', $created > 0
            ? $created.' créneau(x) généré(s) depuis les sessions. Vous pouvez lancer l’affectation automatique.'
            : 'Aucun créneau généré : sessions sans horaires, ou déjà pourvues en créneaux.');
    }

    /**
     * Découpe une session horodatée en créneaux libres de la durée d'une orale,
     * en respectant transition, pause-café après N créneaux et fenêtre déjeuner.
     *
     * @return array<int, int> ids des créneaux créés
     */
    private function sliceSessionSlots(Edition $edition, EventSession $session, array $params): array
    {
        if (! $session->starts_at || ! $session->ends_at) {
            return [];
        }

        $oralMin = max(5, (int) ($params['oral_min'] ?? 20));
        $gap = max(0, (int) ($params['gap_min'] ?? 0));
        $breakAfter = ! empty($params['auto_break']) ? max(0, (int) ($params['break_after'] ?? 0)) : 0;
        $breakMin = max(0, (int) ($params['break_min'] ?? 0));
        $lunchOn = ! empty($params['lunch']);

        $cursor = Carbon::parse($session->starts_at);
        $end = Carbon::parse($session->ends_at);
        // Fenêtre déjeuner calée sur le jour de la session.
        $lunchFrom = $lunchOn ? $cursor->copy()->setTimeFromTimeString((string) ($params['lunch_start'] ?? '12:30')) : null;
        $lunchTo = $lunchOn ? $cursor->copy()->setTimeFromTimeString((string) ($params['lunch_end'] ?? '14:00')) : null;

        $ids = [];
        $n = 0;
        $guard = 0;
        while ($guard++ < 120) {
            $slotEnd = $cursor->copy()->addMinutes($oralMin);
            if ($slotEnd->greaterThan($end)) {
                break;
            }
            // Le créneau empiète sur le déjeuner : on saute la pause et on reprend après.
            if ($lunchFrom && $cursor->lessThan($lunchTo) && $slotEnd->greaterThan($lunchFrom)) {
                $cursor = $lunchTo->copy();

                continue;
            }
            $slot = $edition->slots()->create([
                'session_id' => $session->id,
                'session_label' => $session->label,
                'room' => $session->room ?: '',
                'starts_at' => $cursor->toDateTimeString(),
                'ends_at' => $slotEnd->toDateTimeString(),
                'submission_id' => 0,
                'participant_id' => 0,
            ]);
            $ids[] = $slot->id;
            $n++;
            // Transition entre créneaux, puis pause-café après N créneaux.
            $cursor = $slotEnd->copy()->addMinutes($gap);
            if ($breakAfter > 0 && $breakMin > 0 && $n % $breakAfter === 0) {
                $cursor->addMinutes($breakMin);
            }
        }

        return $ids;
    }

    /**
     * Associe (ou change) la salle d'un atelier. La salle est mémorisée dans la
     * config programme et propagée aux sessions de l'atelier ainsi qu'à leurs
     * créneaux, pour un affichage cohérent dans le planning.
     */
    public function assignAtelierRoom(Request $request, Atelier $atelier): RedirectResponse
    {
        $data = $request->validate(['atelierRoom' => ['nullable', 'string', 'max:150']]);
        $room = ($data['atelierRoom'] ?? null) ?: null;

        $this->writeProgramConfig($atelier->edition, [
            'atelier_rooms' => [(string) $atelier->id => $room],
        ]);

        if ($room) {
            $sessionIds = $atelier->sessions()->pluck('id');
            $atelier->sessions()->update(['room' => $room]);
            if ($sessionIds->isNotEmpty()) {
                $atelier->edition->slots()->whereIn('session_id', $sessionIds)->update(['room' => $room]);
            }
        }

        return back()->with('ukc_status', $room
            ? 'Salle « '.$room.' » associée à l’atelier « '.$atelier->title.' ».'
            : 'Salle dissociée de l’atelier.');
    }

    public function storeSlot(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'slSessionId' => ['nullable'],
            'slRoom' => ['nullable', 'string', 'max:150'],
            'slDay' => ['nullable', 'date'],
            'slStart' => ['nullable', 'date_format:H:i'],
            'slTitle' => ['nullable', 'string', 'max:255'],
            'submission_id' => ['nullable'],
        ]);

        $session = ! empty($data['slSessionId']) ? EventSession::find($data['slSessionId']) : null;

        // Exclusivité des plénières : un créneau ne peut pas tomber dans une plénière.
        $edition = Edition::find($data['edition_id']);
        $start = (! empty($data['slDay']) && ! empty($data['slStart'])) ? Carbon::parse($data['slDay'].' '.$data['slStart']) : null;
        if ($edition && $start && ($conflict = ProgramConflicts::blockingSession(
            $edition, $start, $start->copy()->addMinutes($this->oralMinutes($edition)), false
        ))) {
            return back()->withInput()->with('ukc_error', ProgramConflicts::message($conflict));
        }
        if ($edition && $start && ($break = ProgramConflicts::lunchClash($edition, $start, $start->copy()->addMinutes($this->oralMinutes($edition))))) {
            return back()->withInput()->with('ukc_error', ProgramConflicts::breakMessage($break));
        }

        // Communication optionnelle (place directement l'auteur sur le créneau créé).
        $submission = ! empty($data['submission_id']) ? Submission::find($data['submission_id']) : null;
        if ($submission && (int) $submission->edition_id !== (int) $data['edition_id']) {
            $submission = null;
        }
        if ($submission) {
            Slot::where('edition_id', $data['edition_id'])->where('submission_id', $submission->id)
                ->update(['submission_id' => 0, 'participant_id' => 0]);
        }

        Slot::create([
            'edition_id' => $data['edition_id'],
            'session_id' => $session?->id ?? 0,
            'session_label' => ($data['slTitle'] ?? null) ?: $session?->label,
            'room' => ($data['slRoom'] ?? null) ?: $session?->room,
            'starts_at' => $start?->toDateTimeString(),
            // L'heure de fin découle de la durée présumée (orale/poster) : on la
            // fige à la création pour que la liste des créneaux l'affiche.
            'ends_at' => ($edition && $start) ? $start->copy()->addMinutes($this->slotMinutes($edition, $submission))->toDateTimeString() : null,
            'submission_id' => $submission?->id ?? 0,
            'participant_id' => $submission?->participant_id ?? 0,
        ]);

        return back()->with('ukc_status', 'Créneau ajouté.');
    }

    public function deleteSlot(Slot $slot): RedirectResponse
    {
        $slot->delete();

        return back()->with('ukc_status', 'Créneau supprimé.');
    }

    public function assignSlot(Request $request, Slot $slot): RedirectResponse
    {
        $submissionId = $request->input('submission_id');

        if ($submissionId === '' || $submissionId === null) {
            $slot->update(['submission_id' => 0, 'participant_id' => 0]);

            return back();
        }

        $submission = Submission::find($submissionId);
        if (! $submission || $submission->edition_id !== $slot->edition_id) {
            return back();
        }

        // Libère un éventuel autre créneau de cette communication.
        Slot::where('edition_id', $slot->edition_id)
            ->where('submission_id', $submission->id)
            ->whereKeyNot($slot->id)
            ->update(['submission_id' => 0, 'participant_id' => 0]);

        $slot->update([
            'submission_id' => $submission->id,
            'participant_id' => $submission->participant_id ?? 0,
            'chosen_by_participant' => false,
        ]);

        return back()->with('ukc_status', 'Communication assignée.');
    }

    /** Édition d'un atelier (intitulé, axes, salle associée). */
    public function updateAtelier(Request $request, Atelier $atelier): RedirectResponse
    {
        $data = $request->validate([
            'atelierTitle' => ['required', 'string', 'max:255'],
            'atelierTracks' => ['array'],
            'atelierTracks.*' => ['string'],
            'atelierRoom' => ['nullable', 'string', 'max:150'],
        ]);

        $atelier->update([
            'title' => $data['atelierTitle'],
            'tracks' => array_values($data['atelierTracks'] ?? []),
        ]);

        // Salle associée : stockée dans la config programme (pas de colonne dédiée).
        $this->writeProgramConfig($atelier->edition, [
            'atelier_rooms' => [(string) $atelier->id => ($data['atelierRoom'] ?? null) ?: null],
        ]);

        return back()->with('ukc_status', 'Atelier mis à jour.');
    }

    /** Édition d'une session (intitulé, atelier, jour/horaires, salle, rôles). */
    public function updateSession(Request $request, EventSession $session): RedirectResponse
    {
        $data = $request->validate([
            'sType' => ['required', 'in:plenary,atelier'],
            'sAtelierId' => ['nullable'],
            'sLabel' => ['required', 'string', 'max:255'],
            'sDay' => ['nullable', 'date'],
            'sStart' => ['nullable', 'date_format:H:i'],
            'sEnd' => ['nullable', 'date_format:H:i'],
            'sRoom' => ['nullable', 'string', 'max:150'],
            'sModerator' => ['nullable', 'string', 'max:200'],
            'sModeratorEmail' => ['nullable', 'email', 'max:200'],
            'sRapporteur' => ['nullable', 'string', 'max:200'],
            'sRapporteurEmail' => ['nullable', 'email', 'max:200'],
        ]);

        $beforeModerator = $session->moderator_email;
        $beforeRapporteur = $session->rapporteur_email;

        $start = $this->composeDateTime($data['sDay'] ?? null, $data['sStart'] ?? null);
        $end = $this->composeDateTime($data['sDay'] ?? null, $data['sEnd'] ?? null);

        // Exclusivité des plénières : bloque un horaire en conflit (hors elle-même).
        if ($session->edition && ($conflict = ProgramConflicts::blockingSession(
            $session->edition, $start ? Carbon::parse($start) : null, $end ? Carbon::parse($end) : null,
            $data['sType'] === 'plenary', $session->id
        ))) {
            return back()->withInput()->with('ukc_error', ProgramConflicts::message($conflict));
        }
        if ($session->edition && ($break = ProgramConflicts::lunchClash($session->edition, $start ? Carbon::parse($start) : null, $end ? Carbon::parse($end) : null))) {
            return back()->withInput()->with('ukc_error', ProgramConflicts::breakMessage($break));
        }

        $session->update([
            'atelier_id' => $data['sType'] === 'atelier' && ! empty($data['sAtelierId']) ? (int) $data['sAtelierId'] : 0,
            'type' => $data['sType'],
            'label' => $data['sLabel'],
            'session_day' => $data['sDay'] ?: null,
            'starts_at' => $start,
            'ends_at' => $end,
            'room' => ($data['sRoom'] ?? null) ?: null,
            'moderator_name' => ($data['sModerator'] ?? null) ?: null,
            'moderator_email' => ($data['sModeratorEmail'] ?? null) ?: null,
            'rapporteur_name' => ($data['sRapporteur'] ?? null) ?: null,
            'rapporteur_email' => ($data['sRapporteurEmail'] ?? null) ?: null,
        ]);

        $this->notifyRoleAssignment($session, 'moderator', $session->moderator_email, $beforeModerator);
        $this->notifyRoleAssignment($session, 'rapporteur', $session->rapporteur_email, $beforeRapporteur);

        return back()->with('ukc_status', 'Session mise à jour.');
    }

    /** Édition d'un créneau (session, jour/heure, salle, communication assignée). */
    public function updateSlot(Request $request, Slot $slot): RedirectResponse
    {
        $data = $request->validate([
            'slSessionId' => ['nullable'],
            'slRoom' => ['nullable', 'string', 'max:150'],
            'slDay' => ['nullable', 'date'],
            'slStart' => ['nullable', 'date_format:H:i'],
            'slTitle' => ['nullable', 'string', 'max:255'],
            'submission_id' => ['nullable'],
        ]);

        $session = ! empty($data['slSessionId']) ? EventSession::find($data['slSessionId']) : null;

        // Exclusivité des plénières : un créneau ne peut pas tomber dans une plénière.
        $newStart = (! empty($data['slDay']) && ! empty($data['slStart']))
            ? Carbon::parse($data['slDay'].' '.$data['slStart'])
            : $slot->starts_at;
        if ($slot->edition && $newStart && ($conflict = ProgramConflicts::blockingSession(
            $slot->edition, $newStart, $newStart->copy()->addMinutes($this->oralMinutes($slot->edition)), false
        ))) {
            return back()->withInput()->with('ukc_error', ProgramConflicts::message($conflict));
        }
        if ($slot->edition && $newStart && ($break = ProgramConflicts::lunchClash($slot->edition, $newStart, $newStart->copy()->addMinutes($this->oralMinutes($slot->edition))))) {
            return back()->withInput()->with('ukc_error', ProgramConflicts::breakMessage($break));
        }

        $submission = ! empty($data['submission_id']) ? Submission::find($data['submission_id']) : null;
        if ($submission && $submission->edition_id !== $slot->edition_id) {
            $submission = null;
        }
        if ($submission) {
            // Une communication ne peut occuper qu'un seul créneau.
            Slot::where('edition_id', $slot->edition_id)->where('submission_id', $submission->id)
                ->whereKeyNot($slot->id)->update(['submission_id' => 0, 'participant_id' => 0]);
        }

        $slot->update([
            'session_id' => $session?->id ?? 0,
            'session_label' => ($data['slTitle'] ?? null) ?: $session?->label,
            'room' => ($data['slRoom'] ?? null) ?: $session?->room,
            'starts_at' => $newStart?->toDateTimeString(),
            // Recalcul de l'heure de fin sur la nouvelle durée présumée.
            'ends_at' => ($slot->edition && $newStart) ? $newStart->copy()->addMinutes($this->slotMinutes($slot->edition, $submission))->toDateTimeString() : $slot->ends_at,
            'submission_id' => $submission?->id ?? 0,
            'participant_id' => $submission?->participant_id ?? 0,
        ]);

        return back()->with('ukc_status', 'Créneau mis à jour.');
    }

    /** Sauvegarde des réglages « Affectation auto » et « Paramètres créneaux ». */
    public function saveProgramConfig(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back();
        }

        $this->writeProgramConfig($edition, [
            'auto' => [
                'enabled' => $request->boolean('auto_enabled'),
                'only_paid' => $request->boolean('auto_only_paid'),
                'match_axis' => $request->boolean('auto_match_axis'),
                'notify' => $request->boolean('auto_notify'),
                'use_ai' => $request->boolean('auto_use_ai'),
                'order' => (string) $request->input('auto_order', 'chrono'),
                'fallback' => (string) $request->input('auto_fallback', 'wait'),
            ],
            'params' => [
                'oral_min' => (int) $request->input('oral_min', 20),
                'keynote_min' => (int) $request->input('keynote_min', 45),
                'poster_min' => (int) $request->input('poster_min', 90),
                'gap_min' => (int) $request->input('gap_min', 0),
                'auto_break' => $request->boolean('auto_break'),
                'break_after' => (int) $request->input('break_after', 3),
                'break_min' => (int) $request->input('break_min', 15),
                'lunch' => $request->boolean('lunch'),
                'lunch_start' => (string) $request->input('lunch_start', '12:30'),
                'lunch_end' => (string) $request->input('lunch_end', '14:00'),
                'day_start' => (string) $request->input('day_start', '08:30'),
                'day_end' => (string) $request->input('day_end', '17:30'),
                'no_overlap' => $request->boolean('no_overlap'),
                'no_double_author' => $request->boolean('no_double_author'),
                'match_axis' => $request->boolean('params_match_axis'),
                'require_mod' => $request->boolean('require_mod'),
                'show_room' => $request->boolean('show_room'),
                'show_mod' => $request->boolean('show_mod'),
                'show_empty' => $request->boolean('show_empty'),
            ],
        ]);

        return back()->with('ukc_status', 'Réglages enregistrés.');
    }

    /** Types de ligne du programme synthétique. */
    private const SYNTHESIS_TYPES = ['plenary', 'atelier', 'break', 'other'];

    /**
     * Enregistre le PROGRAMME SYNTHÉTIQUE (onglet « Programme ») : un titre et
     * une liste de jours (date + intitulé + lignes typées « type / début / fin /
     * libellé »). C'est ce contenu qui s'affiche en onglets sur la page publique,
     * et la source de la génération automatique des sessions/créneaux.
     */
    public function saveSynthesis(Request $request): RedirectResponse
    {
        $edition = $this->synthesisEdition($request);
        if (! $edition) {
            return back();
        }

        $this->persistSynthesis($request, $edition);

        return back()->with('ukc_status', 'Programme synthétique enregistré.');
    }

    /** Édition ciblée par le formulaire du programme synthétique (id dans le corps). */
    private function synthesisEdition(Request $request): ?Edition
    {
        return $this->accessibleEditions($request, 'editions_read')
            ->firstWhere('id', (int) $request->input('edition_id'));
    }

    /** Valide, nettoie et écrit le programme synthétique ; retourne la structure propre. */
    private function persistSynthesis(Request $request, Edition $edition): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'days' => ['array'],
            'days.*.date' => ['nullable', 'date'],
            'days.*.heading' => ['nullable', 'string', 'max:200'],
            'days.*.rows' => ['array'],
            'days.*.rows.*.type' => ['nullable', 'string'],
            'days.*.rows.*.start' => ['nullable', 'date_format:H:i'],
            'days.*.rows.*.end' => ['nullable', 'date_format:H:i'],
            'days.*.rows.*.label' => ['nullable', 'string', 'max:300'],
            'days.*.rows.*.highlight' => ['nullable'],
        ]);

        // Nettoyage : on écarte les lignes vides puis les jours vides.
        $days = collect($data['days'] ?? [])
            ->map(function (array $day) {
                $rows = collect($day['rows'] ?? [])
                    ->map(fn ($r) => [
                        'type' => in_array($r['type'] ?? '', self::SYNTHESIS_TYPES, true) ? $r['type'] : 'other',
                        'start' => trim((string) ($r['start'] ?? '')),
                        'end' => trim((string) ($r['end'] ?? '')),
                        'label' => trim((string) ($r['label'] ?? '')),
                        'highlight' => (bool) ($r['highlight'] ?? false),
                    ])
                    ->filter(fn ($r) => $r['label'] !== '' || $r['start'] !== '')
                    ->values()->all();

                return ['date' => trim((string) ($day['date'] ?? '')), 'heading' => trim((string) ($day['heading'] ?? '')), 'rows' => $rows];
            })
            ->filter(fn ($d) => $d['heading'] !== '' || $d['date'] !== '' || $d['rows'] !== [])
            ->values()->all();

        $synthesis = ['title' => trim((string) ($data['title'] ?? '')), 'days' => $days];

        $settings = $edition->settings ?? [];
        $settings['program_synthesis'] = $synthesis;
        $edition->update(['settings' => $settings]);

        return $synthesis;
    }

    /**
     * Enregistre le programme PUIS génère les sessions et créneaux à partir de lui.
     * - une ligne « Plénière »/« Autre » horodatée → une session plénière ;
     * - une ligne « Ateliers en parallèle » → une session par atelier de l'édition
     *   (en parallèle, dans des salles distinctes), découpée en créneaux libres ;
     * - les lignes « Pause » sont ignorées.
     * ⚠️ Ne remplace QUE les sessions/créneaux déjà issus du programme (mémorisés) ;
     * les sessions/créneaux saisis à la main sont conservés.
     */
    public function generateFromSynthesis(Request $request): RedirectResponse
    {
        $edition = $this->synthesisEdition($request);
        if (! $edition) {
            return back();
        }

        $synthesis = $this->persistSynthesis($request, $edition);
        $params = $this->programConfig($edition)['params'];

        // Salles d'atelier : mapping enregistré, repli « Salle N ».
        $ateliers = $edition->ateliers()->orderBy('ordre')->get();
        $atelierRooms = $this->programConfig($edition)['atelier_rooms'];
        $roomFor = function (Atelier $a, int $i) use ($atelierRooms): string {
            $room = $atelierRooms[(string) $a->id] ?? '';

            return $room !== '' ? $room : 'Salle '.($i + 1);
        };

        return DB::transaction(function () use ($edition, $synthesis, $params, $ateliers, $roomFor) {
            // 1) Purge de ce qui avait déjà été généré depuis le programme.
            $prev = $edition->settings['program_synthesis_generated'] ?? ['sessions' => [], 'slots' => []];
            if (! empty($prev['slots'])) {
                $edition->slots()->whereIn('id', $prev['slots'])->delete();
            }
            if (! empty($prev['sessions'])) {
                $edition->sessions()->whereIn('id', $prev['sessions'])->delete();
            }

            $sessionIds = [];
            $slotIds = [];
            $sessionCount = 0;

            // 2) Création des sessions (et créneaux) depuis chaque ligne datée.
            foreach ($synthesis['days'] as $day) {
                if (($day['date'] ?? '') === '') {
                    continue; // sans date, on ne peut pas horodater
                }
                foreach ($day['rows'] as $row) {
                    $type = $row['type'] ?? 'other';
                    $start = $row['start'] ?? '';
                    if ($type === 'break' || $start === '') {
                        continue;
                    }
                    $startsAt = Carbon::parse($day['date'].' '.$start);
                    $endsAt = ($row['end'] ?? '') !== ''
                        ? Carbon::parse($day['date'].' '.$row['end'])
                        : $startsAt->copy()->addMinutes(max(30, (int) ($params['oral_min'] ?? 20)));
                    $label = $row['label'] !== '' ? $row['label'] : ($type === 'atelier' ? 'Communications en ateliers' : 'Séance');

                    if ($type === 'atelier') {
                        // Une session par atelier, en parallèle, salles distinctes.
                        foreach ($ateliers as $i => $atelier) {
                            $session = $edition->sessions()->create([
                                'type' => 'atelier',
                                'atelier_id' => $atelier->id,
                                'label' => $atelier->title,
                                'session_day' => $startsAt->toDateString(),
                                'starts_at' => $startsAt->toDateTimeString(),
                                'ends_at' => $endsAt->toDateTimeString(),
                                'room' => $roomFor($atelier, $i),
                                'ordre' => $sessionCount++,
                            ]);
                            $sessionIds[] = $session->id;
                            $slotIds = array_merge($slotIds, $this->sliceSessionSlots($edition, $session, $params));
                        }
                    } else {
                        // Plénière / Autre : session exclusive, sans créneau oral.
                        $session = $edition->sessions()->create([
                            'type' => 'plenary',
                            'label' => $label,
                            'session_day' => $startsAt->toDateString(),
                            'starts_at' => $startsAt->toDateTimeString(),
                            'ends_at' => $endsAt->toDateTimeString(),
                            'ordre' => $sessionCount++,
                        ]);
                        $sessionIds[] = $session->id;
                    }
                }
            }

            $settings = $edition->settings ?? [];
            $settings['program_synthesis_generated'] = ['sessions' => $sessionIds, 'slots' => $slotIds];
            $edition->update(['settings' => $settings]);

            return back()->with('ukc_status', count($sessionIds) > 0
                ? count($sessionIds).' session(s) et '.count($slotIds).' créneau(x) générés depuis le programme.'
                : 'Rien à générer : ajoutez des dates aux jours et des heures aux lignes (hors pauses).');
        });
    }

    /** Ajoute un espace (salle/amphi/hall) à la config programme. */
    public function storeSpace(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back();
        }

        $data = $request->validate([
            'spaceName' => ['required', 'string', 'max:150'],
            'spaceCapacity' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'spaceBuilding' => ['nullable', 'string', 'max:150'],
            'spaceNote' => ['nullable', 'string', 'max:255'],
        ]);

        $spaces = $this->programConfig($edition)['spaces'];
        $spaces[] = [
            'name' => $data['spaceName'],
            'capacity' => $data['spaceCapacity'] ?? null,
            'building' => $data['spaceBuilding'] ?? null,
            'note' => $data['spaceNote'] ?? null,
        ];

        $this->writeProgramConfig($edition, ['spaces' => array_values($spaces)]);

        return back()->with('ukc_status', 'Espace ajouté.');
    }

    /** Retire un espace de la config programme (par index). */
    public function deleteSpace(Request $request, int $index): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back();
        }

        $spaces = $this->programConfig($edition)['spaces'];
        unset($spaces[$index]);

        $this->writeProgramConfig($edition, ['spaces' => array_values($spaces)]);

        return back()->with('ukc_status', 'Espace supprimé.');
    }

    private function currentEdition(Request $request): ?Edition
    {
        $editions = $this->accessibleEditions($request, 'editions_read');
        $id = (string) $request->query('edition_id', '');

        if ($id === '') {
            return $editions->firstWhere('status', 'open') ?? $editions->first();
        }

        return $editions->firstWhere('id', (int) $id);
    }

    private function composeDateTime(?string $day, ?string $time): ?string
    {
        return ($day && $time) ? $day.' '.$time.':00' : null;
    }

    /** Durée d'une communication orale (= durée présumée d'un créneau), en minutes. */
    private function oralMinutes(Edition $edition): int
    {
        return max(5, (int) ($this->programConfig($edition)['params']['oral_min'] ?? 20));
    }

    /**
     * Durée présumée d'un créneau selon la nature de la communication placée :
     * un poster occupe `poster_min`, une orale (ou un créneau libre) `oral_min`.
     */
    private function slotMinutes(Edition $edition, ?Submission $submission): int
    {
        $params = $this->programConfig($edition)['params'];
        if ($submission && $submission->type === 'poster') {
            return max(5, (int) ($params['poster_min'] ?? 90));
        }

        return max(5, (int) ($params['oral_min'] ?? 20));
    }

    /**
     * Pagine une collection déjà filtrée/triée en mémoire (les sessions et
     * créneaux sont chargés en entier pour le board Planning ; on n'ouvre pas de
     * requête dédiée pour les onglets liste). Le paramètre de page est nommé
     * (`s_page`/`c_page`) pour que les deux tableaux paginent indépendamment.
     *
     * @param  Collection<int, mixed>  $items
     * @return LengthAwarePaginator<int, mixed>
     */
    private function paginateCollection(Collection $items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = max(1, (int) Paginator::resolveCurrentPage($pageName));
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ],
        );
    }
}
