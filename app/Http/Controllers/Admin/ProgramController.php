<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MeetingStatus;
use App\Enums\MeetingVisibility;
use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Slot;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Programme d'une édition (contrôleur + Blade) : ateliers (regroupant des axes),
 * sessions (plénières ou d'atelier, avec modérateur/rapporteur), contrôles de
 * visio par session, et créneaux de passage (assignation des communications
 * acceptées). L'édition est choisie via query string.
 */
class ProgramController extends Controller
{
    // --- Ateliers & sessions --------------------------------------------------

    public function sessions(Request $request): View
    {
        $edition = $this->currentEdition($request);

        return view('admin.program.sessions', [
            'editions' => Edition::latest('id')->get(),
            'edition_id' => $edition ? (string) $edition->id : '',
            'tracks' => $edition->settings['tracks'] ?? [],
            'ateliers' => $edition ? $edition->ateliers()->with('sessions')->orderBy('ordre')->get() : collect(),
            'plenaries' => $edition ? $edition->sessions()->where('type', 'plenary')->orderBy('ordre')->get() : collect(),
            'stats' => [
                'ateliers' => $edition ? $edition->ateliers()->count() : 0,
                'sessions' => $edition ? $edition->sessions()->count() : 0,
                'slots' => $edition ? $edition->slots()->count() : 0,
                'comms' => $edition ? $edition->slots()->where('submission_id', '>', 0)->count() : 0,
            ],
        ]);
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
            'sRapporteur' => ['nullable', 'string', 'max:200'],
        ]);

        EventSession::create([
            'edition_id' => $data['edition_id'],
            'atelier_id' => $data['sType'] === 'atelier' && ! empty($data['sAtelierId']) ? (int) $data['sAtelierId'] : 0,
            'type' => $data['sType'],
            'label' => $data['sLabel'],
            'session_day' => $data['sDay'] ?: null,
            'starts_at' => $this->composeDateTime($data['sDay'] ?? null, $data['sStart'] ?? null),
            'ends_at' => $this->composeDateTime($data['sDay'] ?? null, $data['sEnd'] ?? null),
            'room' => ($data['sRoom'] ?? null) ?: null,
            'moderator_name' => ($data['sModerator'] ?? null) ?: null,
            'rapporteur_name' => ($data['sRapporteur'] ?? null) ?: null,
            'ordre' => EventSession::where('edition_id', $data['edition_id'])->count(),
        ]);

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

    public function slots(Request $request): View
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

        return view('admin.program.slots', [
            'editions' => Edition::latest('id')->get(),
            'edition' => $edition,
            'edition_id' => $edition ? (string) $edition->id : '',
            'ateliers' => $ateliers,
            'sessions' => $sessions,
            'creneaux' => $slots,
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

        $assigned = 0;
        foreach ($unplaced as $sub) {
            // Créneau préféré : même axe (atelier couvrant le track) si demandé.
            $slot = null;
            if ($matchAxis && filled($sub->track)) {
                $slot = $freeSlots->first(fn (Slot $sl) => in_array(
                    $sub->track, $sl->session?->atelier?->tracks ?? [], true
                ));
            }
            $slot ??= $freeSlots->first();
            if (! $slot) {
                break; // plus aucun créneau libre
            }

            $slot->update([
                'submission_id' => $sub->id,
                'participant_id' => $sub->participant_id ?? 0,
                'chosen_by_participant' => false,
            ]);
            $freeSlots = $freeSlots->reject(fn (Slot $s) => $s->id === $slot->id)->values();
            $assigned++;
        }

        return back()->with('ukc_status', $assigned > 0
            ? $assigned.' communication(s) affectée(s) automatiquement.'
            : 'Aucune affectation : pas de créneau libre ou rien à planifier.');
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

        $oralMin = max(5, (int) ($this->programConfig($edition)['params']['oral_min'] ?? 20));
        $created = 0;

        $sessions = $edition->sessions()->whereNotNull('starts_at')->whereNotNull('ends_at')->get();
        foreach ($sessions as $session) {
            if ($edition->slots()->where('session_id', $session->id)->exists()) {
                continue; // déjà pourvue
            }

            $cursor = Carbon::parse($session->starts_at);
            $end = Carbon::parse($session->ends_at);
            $guard = 0;
            while ($cursor->copy()->addMinutes($oralMin)->lessThanOrEqualTo($end) && $guard < 60) {
                $slotEnd = $cursor->copy()->addMinutes($oralMin);
                $edition->slots()->create([
                    'session_id' => $session->id,
                    'session_label' => $session->label,
                    'room' => $session->room ?: '',
                    'starts_at' => $cursor->toDateTimeString(),
                    'ends_at' => $slotEnd->toDateTimeString(),
                    'submission_id' => 0,
                    'participant_id' => 0,
                ]);
                $cursor = $slotEnd;
                $created++;
                $guard++;
            }
        }

        return back()->with('ukc_status', $created > 0
            ? $created.' créneau(x) généré(s) depuis les sessions. Vous pouvez lancer l’affectation automatique.'
            : 'Aucun créneau généré : sessions sans horaires, ou déjà pourvues en créneaux.');
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
            'starts_at' => (! empty($data['slDay']) && ! empty($data['slStart'])) ? $data['slDay'].' '.$data['slStart'].':00' : null,
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
            'sRapporteur' => ['nullable', 'string', 'max:200'],
        ]);

        $session->update([
            'atelier_id' => $data['sType'] === 'atelier' && ! empty($data['sAtelierId']) ? (int) $data['sAtelierId'] : 0,
            'type' => $data['sType'],
            'label' => $data['sLabel'],
            'session_day' => $data['sDay'] ?: null,
            'starts_at' => $this->composeDateTime($data['sDay'] ?? null, $data['sStart'] ?? null),
            'ends_at' => $this->composeDateTime($data['sDay'] ?? null, $data['sEnd'] ?? null),
            'room' => ($data['sRoom'] ?? null) ?: null,
            'moderator_name' => ($data['sModerator'] ?? null) ?: null,
            'rapporteur_name' => ($data['sRapporteur'] ?? null) ?: null,
        ]);

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
            'starts_at' => (! empty($data['slDay']) && ! empty($data['slStart'])) ? $data['slDay'].' '.$data['slStart'].':00' : $slot->starts_at,
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
        $id = (string) $request->query('edition_id', '');
        if ($id === '') {
            $id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
                ?? Edition::latest('id')->value('id') ?? '');
        }

        return $id !== '' ? Edition::find($id) : null;
    }

    private function composeDateTime(?string $day, ?string $time): ?string
    {
        return ($day && $time) ? $day.' '.$time.':00' : null;
    }
}
