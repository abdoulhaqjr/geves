@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    // Communication payée ? (colonne Paiement de « Non planifiées »)
    $isPaid = fn ($sub) => (bool) optional($sub->participant?->participationPayments)->firstWhere('status', 'paid');
    // Durée d'un créneau selon le type de communication (réglages).
    $slotDuration = fn ($sub) => (int) (($sub && $sub->type === 'poster') ? $config['params']['poster_min'] : $config['params']['oral_min']);
    $atelierRoomName = fn ($atelier) => $atelierRooms[(string) $atelier->id] ?? ($atelier->sessions->firstWhere('room', '!=', null)?->room);
    // Charge utile d'événement d'une session (modale), pour un jour donné.
    $sessJsFor = fn ($s, $day) => [
        'id' => $s->id, 'type' => $s->atelier_id ? 'atelier' : 'plenary',
        'atelier_id' => (string) $s->atelier_id, 'label' => $s->label, 'day' => $day,
        'start' => $s->starts_at?->format('H:i') ?? '', 'end' => $s->ends_at?->format('H:i') ?? '',
        'room' => $s->room ?? '',
        'moderator' => $s->moderator_name ?? '', 'moderator_email' => $s->moderator_email ?? '',
        'rapporteur' => $s->rapporteur_name ?? '', 'rapporteur_email' => $s->rapporteur_email ?? '',
    ];
@endphp

<x-layouts.admin title="Programme" subtitle="Ateliers, sessions, créneaux et salles de l’événement">
    @if ($event)
        <x-slot:actions>
            <a href="{{ route('public.colloque', $event->slug) }}#programme" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                <x-ukc.icon name="external" :size="15" /> Aperçu public
            </a>
        </x-slot:actions>
    @endif

    <div class="admin-page admin-program"
         x-data="{
            day: '{{ $days->first() }}',
            pview: 'day',
            setPview(v) { this.pview = v; try { localStorage.setItem('ukcPlanView', v); } catch (e) {} },
            // Restaure la vue Jour/Semaine mémorisée. ⚠️ Dans une MÉTHODE, pas
            // en x-init inline : Alpine évalue x-init comme une expression (il la
            // préfixe par `return`), donc un `try {…}` direct y est une erreur de
            // syntaxe (« Unexpected token 'try' »).
            restorePlanView() { try { this.pview = localStorage.getItem('ukcPlanView') || this.pview; } catch (e) {} },
            modal: null,
            form: {},
            // Auto-complétion « personne » (modérateur/rapporteur) : on lie la séance
            // à un COMPTE par e-mail, sinon la liaison se perd à la modification.
            peopleUrl: '{{ route('admin.program.people') }}',
            peopleField: null,
            peopleResults: [],
            searchPeople(field) {
                this.peopleField = field;
                const q = (this.form[field] || '').trim();
                if (q.length < 2) { this.peopleResults = []; return; }
                fetch(this.peopleUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
                    .then(r => r.json()).then(d => { this.peopleResults = (d && d.people) || []; })
                    .catch(() => { this.peopleResults = []; });
            },
            onPersonInput(field) {
                // La saisie manuelle casse le lien tant qu'une suggestion n'est pas
                // choisie : un nom tapé à la main ne désigne aucun compte.
                this.form[field + '_email'] = '';
                this.searchPeople(field);
            },
            pickPerson(field, p) {
                this.form[field] = p.name;
                this.form[field + '_email'] = p.email;
                this.peopleField = null;
                this.peopleResults = [];
            },
            openAtelier(a) { this.form = a ? {...a} : { id:'', title:'', tracks:[], room:'' }; this.modal='atelier'; },
            openSession(s) { this.form = s ? { moderator_email:'', rapporteur_email:'', ...s } : { id:'', type:'atelier', atelier_id:'', label:'', day:'', start:'', end:'', room:'', moderator:'', moderator_email:'', rapporteur:'', rapporteur_email:'' }; this.peopleField=null; this.peopleResults=[]; this.modal='session'; },
            openSlot(s) { this.form = s ? {...s} : { id:'', session_id:'', day:'', start:'', room:'', submission_id:'', title:'' }; this.modal='slot'; },
            openSpace() { this.form = { name:'', capacity:'', building:'', note:'' }; this.modal='space'; },
            openRoom(a) { this.form = { ...a }; this.modal='room'; },
         }"
         @keydown.escape.window="modal=null">

        {{-- Filtres (GET) : sélecteur d'événement --}}
        <div class="page-head" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px;">
            <div class="ukc-field" style="min-width:320px;max-width:460px;margin:0;">
                <label for="event_id">Événement</label>
                <select id="event_id" name="event_id"
                        onchange="window.location = '{{ route('admin.program.index') }}?event_id=' + this.value + '&tab={{ $tab }}'">
                    <option value="">- Choisir -</option>
                    @foreach ($events as $ed)
                        <option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($event_id === '')
            <div class="ukc-empty"><h3>Choisissez un événement</h3></div>
        @else
            @include('admin.program._nav', ['current' => $tab])

            {{-- Indicateurs --}}
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="grid" :size="20" /></div><div class="lbl">Ateliers</div><div class="val blue">{{ $stats['ateliers'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="layers" :size="20" /></div><div class="lbl">Sessions</div><div class="val">{{ $stats['sessions'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">Créneaux</div><div class="val">{{ $stats['slots'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="calendar-check" :size="20" /></div><div class="lbl">Planifiées</div><div class="val green">{{ $stats['planned'] }}</div><div class="hint">orales</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="calendar" :size="20" /></div><div class="lbl">À planifier</div><div class="val warn">{{ $stats['toPlan'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="refresh" :size="20" /></div><div class="lbl">Affect. auto</div><div class="val" style="font-size:1.05rem;">{{ $stats['autoAssign'] ? 'Activée' : '-' }}</div><div class="hint">{{ $stats['autoAssign'] ? 'après paiement' : 'désactivée' }}</div></div>
            </div>

            @if ($tab === 'planning')
            <div class="hierarchy-note">
                <p>
                    Un <strong>atelier</strong> regroupe un ou plusieurs axes et contient des <strong>sessions</strong>
                    (modérateur &amp; rapporteur). Chaque <strong>créneau</strong> place une communication acceptée
                    dans une session, à une salle et une heure. La salle peut être associée plus tard.
                </p>
            </div>
            @endif

            {{-- ============================ PROGRAMME (synthèse saisie) ============================ --}}
            {{-- Éditeur du programme synthétique : titre + jours (date + intitulé) +
                 lignes (type, heures, libellé). C'est CE contenu qui s'affiche sur la
                 page publique (partials.program-synthesis) ET la SOURCE de génération
                 automatique des sessions/créneaux (bouton dédié). --}}
            <div @if ($tab !== 'apercu') style="display:none;" @endif>
                <form method="POST" action="{{ route('admin.program.synthesis') }}" class="ukc-card" x-data="programSynthesis(@js($programSynthesis))">
                    @csrf
                    <input type="hidden" name="event_id" value="{{ $event_id }}">
                    <h2>
                        <span><x-ukc.icon name="calendar" :size="18" /> Programme</span>
                        @if ($event)
                            <a href="{{ route('public.colloque', $event->slug) }}#programme" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <x-ukc.icon name="external" :size="14" /> Voir sur la page publique
                            </a>
                        @endif
                    </h2>
                    <div class="desc">Déroulé synthétique du colloque. Il s'affiche sur la page publique (en onglets jour par jour) et sert à <strong>générer automatiquement</strong> les sessions et créneaux (bouton en bas).</div>

                    <div class="ukc-field" style="max-width:640px;">
                        <label>Titre du programme</label>
                        <input type="text" name="title" x-model="title" placeholder="Ex. Programme synthétique du colloque d'octobre 2025" maxlength="200">
                    </div>

                    {{-- Jours / sections --}}
                    <template x-for="(day, di) in days" :key="di">
                        <div class="prog-syn-day">
                            <div class="prog-syn-day__head">
                                <input type="date" :name="'days['+di+'][date]'" x-model="day.date" class="prog-syn-day__date" title="Date (nécessaire pour la génération auto)">
                                <input type="text" :name="'days['+di+'][heading]'" x-model="day.heading" placeholder="Intitulé (ex. Matin, Après-midi, Début des ateliers…)" class="prog-syn-day__title">
                                <div class="prog-syn-moves">
                                    <button type="button" class="ukc-btn--icon" @click="moveDay(di, -1)" :disabled="di===0" title="Monter"><x-ukc.icon name="arrow-up" :size="15" /></button>
                                    <button type="button" class="ukc-btn--icon" @click="moveDay(di, 1)" :disabled="di===days.length-1" title="Descendre"><x-ukc.icon name="arrow-down" :size="15" /></button>
                                    <button type="button" class="ukc-btn--icon danger" @click="removeDay(di)" title="Supprimer ce jour"><x-ukc.icon name="trash" :size="15" /></button>
                                </div>
                            </div>

                            {{-- En-tête de colonnes des lignes --}}
                            <div class="prog-syn-row prog-syn-row--head" x-show="day.rows.length">
                                <span>Type</span><span>Début</span><span>Fin</span><span>Libellé</span><span>Gras</span><span></span>
                            </div>

                            {{-- Lignes du jour --}}
                            <template x-for="(row, j) in day.rows" :key="j">
                                <div class="prog-syn-row">
                                    <select :name="'days['+di+'][rows]['+j+'][type]'" x-model="row.type" class="prog-syn-row__type">
                                        <option value="plenary">Plénière</option>
                                        <option value="atelier">Ateliers en parallèle</option>
                                        <option value="break">Pause</option>
                                        <option value="other">Autre</option>
                                    </select>
                                    <input type="time" :name="'days['+di+'][rows]['+j+'][start]'" x-model="row.start" class="prog-syn-row__time" title="Heure de début">
                                    <input type="time" :name="'days['+di+'][rows]['+j+'][end]'" x-model="row.end" class="prog-syn-row__time" title="Heure de fin">
                                    <input type="text" :name="'days['+di+'][rows]['+j+'][label]'" x-model="row.label" placeholder="Libellé (ex. Cérémonie d'ouverture, Communications en ateliers…)" class="prog-syn-row__label">
                                    <label class="prog-syn-row__hl" title="Mettre en évidence (gras)">
                                        <input type="hidden" :name="'days['+di+'][rows]['+j+'][highlight]'" value="0">
                                        <input type="checkbox" :name="'days['+di+'][rows]['+j+'][highlight]'" value="1" x-model="row.highlight">
                                    </label>
                                    <div class="prog-syn-moves">
                                        <button type="button" class="ukc-btn--icon" @click="moveRow(di, j, -1)" :disabled="j===0" title="Monter"><x-ukc.icon name="arrow-up" :size="14" /></button>
                                        <button type="button" class="ukc-btn--icon" @click="moveRow(di, j, 1)" :disabled="j===day.rows.length-1" title="Descendre"><x-ukc.icon name="arrow-down" :size="14" /></button>
                                        <button type="button" class="ukc-btn--icon danger" @click="removeRow(di, j)" title="Supprimer la ligne"><x-ukc.icon name="close" :size="14" /></button>
                                    </div>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addRow(di)"><x-ukc.icon name="plus" :size="13" /> Ajouter une ligne</button>
                        </div>
                    </template>

                    <div style="margin-top:8px;">
                        <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="addDay()"><x-ukc.icon name="plus" :size="14" /> Ajouter un jour / une section</button>
                    </div>

                    <div style="margin-top:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;border-top:1px solid var(--ukc-border);padding-top:16px;">
                        <button type="submit" formaction="{{ route('admin.program.synthesis.generate') }}" class="ukc-btn ukc-btn--green"
                                data-confirm="Enregistrer ce programme PUIS générer les sessions et créneaux ? Les sessions et créneaux déjà générés depuis le programme seront remplacés (les sessions/créneaux saisis à la main sont conservés).">
                            <x-ukc.icon name="refresh" :size="16" /> Enregistrer & générer sessions/créneaux
                        </button>
                        <button type="submit" class="ukc-btn ukc-btn--primary"><x-ukc.icon name="check" :size="16" /> Enregistrer le programme</button>
                    </div>
                </form>

                {{-- Aperçu : rendu EXACT de la page publique (contenu enregistré). --}}
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="eye" :size="18" /> Aperçu public</span></h2>
                    <div class="desc">Tel que la section « Programme » apparaît sur la page publique (dernier enregistrement).</div>
                    <div class="prog-public-preview">
                        @if (($programSynthesis['title'] ?? '') !== '')
                            <h3 style="text-align:center;font-size:18px;font-weight:900;color:var(--ukc-blue);margin-bottom:18px;">{{ $programSynthesis['title'] }}</h3>
                        @endif
                        @include('partials.program-synthesis', ['synthesis' => $programSynthesis])
                    </div>
                </div>
            </div>

            {{-- ============================ PLANNING ============================ --}}
            <div @if ($tab !== 'planning') style="display:none;" @endif data-ajax-root="prog-planning">
                @if ($days->isEmpty())
                    <div class="ukc-empty"><h3>Aucun jour planifié</h3><p>Ajoutez des sessions datées et des créneaux pour bâtir le planning.</p></div>
                @else
                    <div class="plan-topbar" x-init="restorePlanView()">
                        <div class="plan-viewtoggle" role="tablist">
                            <button type="button" role="tab" :class="{ active: pview==='day' }" @click="setPview('day')">Jour</button>
                            <button type="button" role="tab" :class="{ active: pview==='week' }" @click="setPview('week')">Semaine</button>
                        </div>
                        <div class="plan-legend">
                            <span><i class="sw sw--plen"></i> Plénière (exclusive)</span>
                            <span><i class="sw sw--atel"></i> Atelier (parallèle)</span>
                            <span><i class="sw sw--free"></i> Créneau libre</span>
                            <span><i class="sw sw--conf"></i> Conflit d’auteur</span>
                            <span><i class="sw sw--room"></i> Salle en double</span>
                        </div>
                    </div>

                    {{-- ---------------------------- Vue JOUR ---------------------------- --}}
                    <div x-show="pview==='day'">
                    <div class="day-tabs">
                        @foreach ($days as $i => $d)
                            @php $cd = Carbon::parse($d); @endphp
                            <button type="button" @click="day='{{ $d }}'" :class="{ active: day==='{{ $d }}' }">
                                Jour {{ $i + 1 }}<span class="d-sub">{{ $cd->translatedFormat('d M - D') }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="layout-plan">
                        <div>
                            @foreach ($days as $d)
                                <div x-show="day==='{{ $d }}'" x-cloak>
                                    @php
                                        // Sessions du jour, avec/sans horaires.
                                        $dayS = $sessions->filter(fn ($s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d') === $d);
                                        $timed = $dayS->filter(fn ($s) => $s->starts_at && $s->ends_at)->values();
                                        $untimed = $dayS->filter(fn ($s) => ! ($s->starts_at && $s->ends_at))->values();
                                        $roomOf = fn ($s) => $s->room ?: ($s->atelier ? $atelierRoomName($s->atelier) : null) ?: 'À définir';
                                        $mins = fn ($c) => $c ? ((int) $c->format('H') * 60 + (int) $c->format('i')) : 0;
                                        $ateliersT = $timed->filter(fn ($s) => $s->atelier_id)->values();
                                        $plenT = $timed->filter(fn ($s) => ! $s->atelier_id)->values();
                                        // Colonnes = salles distinctes des ateliers du jour.
                                        $columns = $ateliersT->map($roomOf)->unique()->sort(SORT_NATURAL)->values();
                                        if ($columns->isEmpty()) { $columns = collect(['À définir']); }
                                        // Sous-colonnes (lanes) : dans une même salle, des sessions qui se
                                        // chevauchent sont placées CÔTE À CÔTE (et non l'une sur l'autre).
                                        $sessionLane = [];
                                        $roomLanes = [];
                                        foreach ($columns as $room) {
                                            $laneEnds = [];
                                            foreach ($ateliersT->filter(fn ($s) => $roomOf($s) === $room)->sortBy(fn ($s) => $mins($s->starts_at)) as $s) {
                                                $st = $mins($s->starts_at);
                                                $lane = null;
                                                foreach ($laneEnds as $li => $le) {
                                                    if ($st >= $le) { $lane = $li; break; }
                                                }
                                                if ($lane === null) { $lane = count($laneEnds); }
                                                $laneEnds[$lane] = $mins($s->ends_at);
                                                $sessionLane[$s->id] = $lane;
                                            }
                                            $roomLanes[$room] = max(1, count($laneEnds));
                                        }
                                        $roomColStart = [];
                                        $cStart = 2;
                                        foreach ($columns as $room) { $roomColStart[$room] = $cStart; $cStart += $roomLanes[$room]; }
                                        $totalLanes = max(1, array_sum($roomLanes));
                                        // Axe temporel : bornes arrondies à l'heure.
                                        $dayStart = $timed->isNotEmpty() ? (int) (floor($timed->map(fn ($s) => $mins($s->starts_at))->min() / 60) * 60) : 480;
                                        $dayEnd = $timed->isNotEmpty() ? (int) (ceil($timed->map(fn ($s) => $mins($s->ends_at))->max() / 60) * 60) : 1080;
                                        if ($dayEnd <= $dayStart) { $dayEnd = $dayStart + 60; }
                                        $rowCount = (int) (($dayEnd - $dayStart) / 30);
                                        $rowLine = fn ($m) => 2 + (int) round(($m - $dayStart) / 30);
                                        $sessJs = fn ($s) => [
                                            'id' => $s->id, 'type' => $s->atelier_id ? 'atelier' : 'plenary',
                                            'atelier_id' => (string) $s->atelier_id, 'label' => $s->label, 'day' => $d,
                                            'start' => $s->starts_at?->format('H:i') ?? '', 'end' => $s->ends_at?->format('H:i') ?? '',
                                            'room' => $s->room ?? '',
                                            'moderator' => $s->moderator_name ?? '', 'moderator_email' => $s->moderator_email ?? '',
                                            'rapporteur' => $s->rapporteur_name ?? '', 'rapporteur_email' => $s->rapporteur_email ?? '',
                                        ];

                                        // Conflits d'auteur : un même auteur correspondant placé sur deux
                                        // créneaux du jour qui se chevauchent (ateliers parallèles). On
                                        // surligne les créneaux fautifs (télescopage physiquement impossible).
                                        $slotEnd = fn ($sl) => $sl->ends_at ?: ($sl->starts_at ? $sl->starts_at->copy()->addMinutes($slotDuration($sl->submission)) : null);
                                        $placedDay = $creneaux->filter(fn ($sl) => $sl->submission_id > 0 && $sl->participant_id > 0 && $sl->starts_at && $sl->starts_at->format('Y-m-d') === $d)->values();
                                        $conflictSlots = [];
                                        foreach ($placedDay->groupBy('participant_id') as $group) {
                                            $g = $group->values();
                                            for ($i = 0; $i < $g->count(); $i++) {
                                                for ($j = $i + 1; $j < $g->count(); $j++) {
                                                    $ae = $slotEnd($g[$i]);
                                                    $be = $slotEnd($g[$j]);
                                                    if ($ae && $be && $g[$i]->starts_at->lt($be) && $g[$j]->starts_at->lt($ae)) {
                                                        $conflictSlots[$g[$i]->id] = $conflictSlots[$g[$j]->id] = true;
                                                    }
                                                }
                                            }
                                        }

                                        // Salles en double : deux séances horodatées qui se chevauchent dans
                                        // la MÊME salle (impossible physiquement). Les ateliers en parallèle
                                        // ne sont légitimes que dans des salles distinctes ; une salle
                                        // laissée « À définir » n'est pas comptée (pas encore attribuée).
                                        $realRoom = fn ($s) => $s->room ?: ($s->atelier ? $atelierRoomName($s->atelier) : null);
                                        $roomConflicts = [];
                                        foreach ($timed->filter(fn ($s) => $realRoom($s))->groupBy(fn ($s) => $realRoom($s)) as $group) {
                                            $g = $group->values();
                                            for ($i = 0; $i < $g->count(); $i++) {
                                                for ($j = $i + 1; $j < $g->count(); $j++) {
                                                    if ($mins($g[$i]->starts_at) < $mins($g[$j]->ends_at) && $mins($g[$j]->starts_at) < $mins($g[$i]->ends_at)) {
                                                        $roomConflicts[$g[$i]->id] = $realRoom($g[$i]);
                                                        $roomConflicts[$g[$j]->id] = $realRoom($g[$j]);
                                                    }
                                                }
                                            }
                                        }
                                    @endphp

                                    @if ($conflictSlots !== [])
                                        <div class="plan-conflict-banner">
                                            <x-ukc.icon name="flag" :size="15" />
                                            {{ count($conflictSlots) }} créneau(x) en conflit : un auteur est programmé sur deux séances au même moment (surlignés en rouge).
                                        </div>
                                    @endif

                                    @if ($roomConflicts !== [])
                                        <div class="plan-conflict-banner plan-conflict-banner--room">
                                            <x-ukc.icon name="alert-triangle" :size="15" />
                                            {{ count($roomConflicts) }} séance(s) en conflit de salle : une même salle est occupée par deux séances au même moment (surlignées en orange). Déplacez l’une d’elles dans une autre salle ou à un autre horaire.
                                        </div>
                                    @endif

                                    @if ($timed->isEmpty())
                                        <div class="ukc-card"><p style="margin:0;color:var(--ukc-muted);font-size:13.5px;">Aucune session horodatée ce jour. Ajoutez un début et une fin à une session pour la voir ici.</p></div>
                                    @else
                                        <div class="plan-scroll">
                                            <div class="plan-board" style="grid-template-columns:60px repeat({{ $totalLanes }}, minmax(130px,1fr));grid-template-rows:36px repeat({{ $rowCount }}, 34px);">
                                                <div class="plan-head plan-head--time">Heure</div>
                                                @foreach ($columns as $room)
                                                    <div class="plan-head" style="grid-column:{{ $roomColStart[$room] }} / span {{ $roomLanes[$room] }};grid-row:1;">{{ $room }}</div>
                                                @endforeach

                                                @for ($m = $dayStart; $m < $dayEnd; $m += 60)
                                                    <div class="plan-timelabel" style="grid-column:1;grid-row:{{ $rowLine($m) }} / {{ $rowLine($m + 60) }};">{{ sprintf('%02d:00', intdiv($m, 60)) }}</div>
                                                @endfor
                                                @foreach ($columns as $room)
                                                    <div class="plan-lane" style="grid-column:{{ $roomColStart[$room] }} / span {{ $roomLanes[$room] }};grid-row:2 / {{ $rowCount + 2 }};"></div>
                                                @endforeach

                                                {{-- Plénières : bandeaux pleine largeur (exclusives) --}}
                                                @foreach ($plenT as $s)
                                                    @php $roomClash = isset($roomConflicts[$s->id]); @endphp
                                                    <div class="plan-ev plan-ev--plenary{{ $roomClash ? ' room-conflict' : '' }}" style="grid-column:2 / {{ 2 + $totalLanes }};grid-row:{{ $rowLine($mins($s->starts_at)) }} / {{ $rowLine($mins($s->ends_at)) }};"
                                                         @click='openSession(@json($sessJs($s)))' title="{{ $roomClash ? 'Conflit de salle : « '.$roomConflicts[$s->id].' » est occupée par une autre séance au même moment.' : 'Éditer la session' }}">
                                                        <span class="badge">Plénière</span>
                                                        <span class="t">{{ Str::limit($s->label, 70) }}</span>
                                                        <span class="m">{{ $s->starts_at->format('H:i') }}-{{ $s->ends_at->format('H:i') }}@if ($s->room) - <x-ukc.icon name="pin" :size="11" /> {{ $s->room }}@endif @if ($s->moderator_name)- {{ $s->moderator_name }}@endif</span>
                                                    </div>
                                                @endforeach

                                                {{-- Pause déjeuner : bandeau plein largeur (paramètres de créneaux) --}}
                                                @php
                                                    $pp = $config['params'];
                                                    $hm = fn ($t) => (int) substr((string) $t, 0, 2) * 60 + (int) substr((string) $t, 3, 2);
                                                    $lunchOn = ! empty($pp['lunch']);
                                                    $ls = $lunchOn ? $hm($pp['lunch_start'] ?? '12:30') : 0;
                                                    $le = $lunchOn ? $hm($pp['lunch_end'] ?? '14:00') : 0;
                                                @endphp
                                                @if ($lunchOn && $le > $ls && $le > $dayStart && $ls < $dayEnd)
                                                    @php $bs = max($ls, $dayStart); $be = min($le, $dayEnd); @endphp
                                                    <div class="plan-break" style="grid-column:2 / {{ 2 + $totalLanes }};grid-row:{{ $rowLine($bs) }} / {{ $rowLine($be) }};">
                                                        <span>Pause déjeuner - {{ sprintf('%02d:%02d', intdiv($ls, 60), $ls % 60) }}-{{ sprintf('%02d:%02d', intdiv($le, 60), $le % 60) }}</span>
                                                    </div>
                                                @endif

                                                {{-- Ateliers : colonnes parallèles --}}
                                                @foreach ($ateliersT as $s)
                                                    @php
                                                        $col = ($roomColStart[$roomOf($s)] ?? 2) + ($sessionLane[$s->id] ?? 0);
                                                        $sslots = $creneaux->where('session_id', $s->id)->sortBy('starts_at');
                                                        $roomClash = isset($roomConflicts[$s->id]);
                                                        $atelierTitle = $s->atelier?->title ?: $s->label;
                                                    @endphp
                                                    <div class="plan-ev plan-ev--atelier{{ $roomClash ? ' room-conflict' : '' }}" style="grid-column:{{ $col }};grid-row:{{ $rowLine($mins($s->starts_at)) }} / {{ $rowLine($mins($s->ends_at)) }};">
                                                        <div class="ahead">
                                                            <div>
                                                                <span class="t" title="{{ $roomClash ? $atelierTitle.' - conflit de salle : « '.$roomConflicts[$s->id].' » occupée par une autre séance au même moment.' : $atelierTitle }}">@if ($roomClash)<span class="rflag"><x-ukc.icon name="alert-triangle" :size="11" /></span>@endif{{ $atelierTitle }}</span>
                                                                <span class="time">{{ $s->starts_at->format('H:i') }}-{{ $s->ends_at->format('H:i') }}</span>
                                                            </div>
                                                            <button type="button" @click='openSession(@json($sessJs($s)))' title="Éditer la session"><x-ukc.icon name="pen" :size="13" /></button>
                                                        </div>
                                                        @foreach ($sslots as $slot)
                                                            @if ($slot->submission)
                                                                @php
                                                                    $isConflict = isset($conflictSlots[$slot->id]);
                                                                    $slotTitle = $slot->submission->ref_code.' - '.$slot->submission->title.($isConflict ? ' (conflit : cet auteur est déjà en séance au même moment)' : '');
                                                                @endphp
                                                                <div class="plan-slot placed{{ $isConflict ? ' conflict' : '' }}" draggable="true"
                                                                     data-sub-id="{{ $slot->submission_id }}" data-slot-id="{{ $slot->id }}" data-unassign-url="{{ route('admin.program.slots.assign', $slot) }}"
                                                                     title="{{ $slotTitle }}">@if ($isConflict)<span class="flag"><x-ukc.icon name="flag" :size="11" /></span>@endif<span class="ref">{{ $slot->submission->ref_code }}</span> {{ $slot->submission->title }}</div>
                                                            @else
                                                                <div class="plan-slot free" data-slot-id="{{ $slot->id }}" data-assign-url="{{ route('admin.program.slots.assign', $slot) }}"
                                                                     @click="openSlot({ id:{{ $slot->id }}, session_id:@js((string) $slot->session_id), day:'{{ $d }}', start:@js($slot->starts_at?->format('H:i') ?? ''), room:@js($roomOf($s)), submission_id:'', title:@js($slot->session_label ?? '') })" title="Déposer une communication ici, ou cliquer pour l’assigner">+ libre</div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Sessions du jour sans horaire : à compléter pour les placer --}}
                                    @if ($untimed->isNotEmpty())
                                        <div class="ukc-card" style="margin-top:14px;">
                                            <h3 style="font-size:13.5px;font-weight:800;margin-bottom:8px;">Sessions sans horaire ({{ $untimed->count() }})</h3>
                                            <div class="plan-untimed">
                                                @foreach ($untimed as $s)
                                                    <div class="u" @click='openSession(@json($sessJs($s)))' title="Ajouter un horaire">
                                                        <strong>{{ Str::limit($s->label, 40) }}</strong>
                                                        <span style="color:var(--ukc-muted);"> - {{ $s->atelier?->title ? Str::limit($s->atelier->title, 22) : 'Plénière' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Colonne « À planifier » (filtrable) --}}
                        <div>
                            @php
                                $poolAxes = $unplaced->map(fn ($s) => $s->track)->filter()->unique()->sort()->values();
                            @endphp
                            <div class="ukc-card plan-pool" style="position:sticky;top:12px;" x-data="tableFilters(['axe', 'paid'])" data-pool-drop>
                                <h2><span><x-ukc.icon name="inbox" :size="18" /> À planifier</span></h2>
                                <div class="desc" style="font-size:12.5px;color:var(--ukc-muted);margin-bottom:10px;">Communications acceptées sans créneau. Glissez-y un créneau placé pour le déplanifier.</div>

                                @if ($unplaced->isNotEmpty())
                                    <div class="filter-bar" style="margin-bottom:10px;">
                                        <select x-model="f.axe" aria-label="Filtrer par axe" style="flex:1;min-width:120px;">
                                            <option value="">Tous les axes</option>
                                            @foreach ($poolAxes as $a)<option value="{{ $a }}">{{ Str::limit($a, 34) }}</option>@endforeach
                                        </select>
                                        <select x-model="f.paid" aria-label="Filtrer par paiement">
                                            <option value="">Paiement : tous</option>
                                            <option value="paid">Payé</option>
                                            <option value="due">En attente</option>
                                        </select>
                                        <input type="search" x-model="f.q" placeholder="Titre, auteur…" style="flex:1;min-width:120px;">
                                    </div>
                                    <div style="font-size:12px;color:var(--ukc-muted);margin-bottom:8px;"><span x-text="visibleIds.length"></span> sur {{ $unplaced->count() }}<button type="button" class="ukc-link" x-show="active" x-cloak @click="reset()" style="margin-left:8px;">Réinitialiser</button></div>
                                @endif

                                <div class="pool-list">
                                    @forelse ($unplaced as $sub)
                                        <div class="pool-item" x-show="visibleIds.includes({{ $sub->id }})" data-filter-row
                                             draggable="true" data-sub-id="{{ $sub->id }}"
                                             data-id="{{ $sub->id }}" data-axe="{{ $sub->track }}" data-paid="{{ $isPaid($sub) ? 'paid' : 'due' }}"
                                             data-text="{{ Str::lower($sub->title.' '.($sub->participant?->full_name ?? '')) }}"
                                             @click="openSlot({ id:'', session_id:'', day:'', start:'', room:'', submission_id:'{{ $sub->id }}', title:'' })" title="Glisser sur un créneau libre du board, ou cliquer pour créer un créneau">
                                            <div class="t">{{ Str::limit($sub->title, 60) }}</div>
                                            <div class="m">
                                                <span>{{ $sub->participant?->full_name ?: 'Auteur' }}</span>
                                                @if ($sub->track)<span class="ukc-pill ukc-pill--info">{{ Str::limit($sub->track, 24) }}</span>@endif
                                                <span class="ukc-pill ukc-pill--{{ $isPaid($sub) ? 'ok' : 'warn' }}">{{ $isPaid($sub) ? 'Payé' : 'En attente' }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <p style="font-size:12.5px;color:var(--ukc-green);display:flex;align-items:center;gap:6px;"><x-ukc.icon name="check-circle" :size="15" /> Tout est planifié.</p>
                                    @endforelse
                                    @if ($unplaced->isNotEmpty())
                                        <p x-show="visibleIds.length === 0" x-cloak style="font-size:12.5px;color:var(--ukc-muted);text-align:center;padding:8px;">Aucune communication ne correspond.</p>
                                    @endif
                                </div>
                                <p class="ukc-hint" style="margin-top:10px;font-size:12px;color:var(--ukc-light);">Cliquez une communication pour la placer sur un créneau, ou un créneau « libre » du board pour l’y assigner.</p>
                            </div>
                        </div>
                    </div>
                    </div>{{-- fin vue JOUR --}}

                    {{-- --------------------------- Vue SEMAINE -------------------------- --}}
                    {{-- Agenda par jour : chaque jour = une colonne, blocs empilés dans
                         l'ordre chronologique (aucun chevauchement / rognage possible). --}}
                    <div x-show="pview==='week'" x-cloak>
                        @php
                            $wk = $sessions->filter(fn ($s) => $s->starts_at && $s->ends_at);
                            $wkMin = fn ($c) => (int) $c->format('H') * 60 + (int) $c->format('i');
                            $wkRealRoom = fn ($s) => $s->room ?: ($s->atelier ? $atelierRoomName($s->atelier) : null);
                            $wkRoomOf = fn ($s) => $wkRealRoom($s) ?: 'À définir';
                        @endphp
                        @if ($wk->isEmpty())
                            <div class="ukc-card"><p style="margin:0;color:var(--ukc-muted);font-size:13.5px;">Aucune session horodatée. Ajoutez des horaires aux sessions pour les voir sur la semaine.</p></div>
                        @else
                            <div class="plan-scroll">
                                <div class="plan-weekgrid" style="grid-template-columns:repeat({{ count($days) }}, minmax(210px,1fr));">
                                    @foreach ($days as $dd)
                                        @php $cd = \Illuminate\Support\Carbon::parse($dd); @endphp
                                        <div class="wk-dhead">
                                            <span class="dow">{{ $cd->translatedFormat('D') }}</span>
                                            <span class="dnum">{{ $cd->translatedFormat('d M') }}</span>
                                        </div>
                                    @endforeach

                                    @foreach ($days as $dd)
                                        @php
                                            $dt = $wk->filter(fn ($s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d') === $dd)
                                                     ->sortBy(fn ($s) => $wkMin($s->starts_at))->values();

                                            // Conflits de salle du jour (même salle réelle, horaires qui se chevauchent).
                                            $wkConf = [];
                                            foreach ($dt->filter(fn ($s) => $wkRealRoom($s))->groupBy(fn ($s) => $wkRealRoom($s)) as $grp) {
                                                $g = $grp->values();
                                                for ($i = 0; $i < $g->count(); $i++) {
                                                    for ($j = $i + 1; $j < $g->count(); $j++) {
                                                        if ($wkMin($g[$i]->starts_at) < $wkMin($g[$j]->ends_at) && $wkMin($g[$j]->starts_at) < $wkMin($g[$i]->ends_at)) {
                                                            $wkConf[$g[$i]->id] = $wkConf[$g[$j]->id] = true;
                                                        }
                                                    }
                                                }
                                            }

                                            // Blocs chronologiques : plénières isolées + ateliers regroupés par tranche.
                                            $entries = collect();
                                            foreach ($dt->filter(fn ($s) => ! $s->atelier_id) as $s) {
                                                $entries->push(['k' => $wkMin($s->starts_at), 'e' => $wkMin($s->ends_at), 'type' => 'plen', 'plen' => $s]);
                                            }
                                            foreach ($dt->filter(fn ($s) => $s->atelier_id)->groupBy(fn ($s) => $wkMin($s->starts_at).'-'.$wkMin($s->ends_at)) as $grp) {
                                                $g0 = $grp->first();
                                                $entries->push(['k' => $wkMin($g0->starts_at), 'e' => $wkMin($g0->ends_at), 'type' => 'band', 'grp' => $grp, 'g0' => $g0]);
                                            }
                                            // Pause déjeuner (paramètres de créneaux), insérée dans le fil du jour.
                                            $pp = $config['params'];
                                            if (! empty($pp['lunch']) && $dt->isNotEmpty()) {
                                                $lhm = fn ($t) => (int) substr((string) $t, 0, 2) * 60 + (int) substr((string) $t, 3, 2);
                                                $entries->push([
                                                    'k' => $lhm($pp['lunch_start'] ?? '12:30'), 'e' => $lhm($pp['lunch_end'] ?? '14:00'),
                                                    'type' => 'break', 'ls' => $pp['lunch_start'] ?? '12:30', 'le' => $pp['lunch_end'] ?? '14:00',
                                                ]);
                                            }
                                            $entries = $entries->sortBy([['k', 'asc'], ['e', 'asc']])->values();
                                        @endphp
                                        <div class="wk-col">
                                            @forelse ($entries as $en)
                                                @if ($en['type'] === 'break')
                                                    <div class="wk-block wk-block--break">
                                                        <span class="wk-time">{{ $en['ls'] }}-{{ $en['le'] }}</span>
                                                        <span class="wk-brk">Pause déjeuner</span>
                                                    </div>
                                                @elseif ($en['type'] === 'plen')
                                                    @php $s = $en['plen']; @endphp
                                                    <button type="button" class="wk-block wk-block--plenary"
                                                            @click='openSession(@json($sessJsFor($s, $dd)))'
                                                            title="Plénière : {{ $s->label }} ({{ $s->starts_at->format('H:i') }}-{{ $s->ends_at->format('H:i') }})">
                                                        <span class="wk-time">{{ $s->starts_at->format('H:i') }}-{{ $s->ends_at->format('H:i') }}</span>
                                                        <span class="wk-badge">Plénière</span>
                                                        <span class="wk-title">{{ Str::limit($s->label, 48) }}</span>
                                                    </button>
                                                @else
                                                    @php
                                                        $grp = $en['grp'];
                                                        $g0 = $en['g0'];
                                                        $bandConf = $grp->contains(fn ($s) => isset($wkConf[$s->id]));
                                                    @endphp
                                                    <div class="wk-block wk-block--band{{ $bandConf ? ' room-conflict' : '' }}">
                                                        <div class="wk-bhead">
                                                            <span class="wk-time">{{ $g0->starts_at->format('H:i') }}-{{ $g0->ends_at->format('H:i') }}</span>
                                                            <span class="wk-count">{{ $grp->count() }} atelier{{ $grp->count() > 1 ? 's en parallèle' : '' }}</span>
                                                        </div>
                                                        <div class="wk-chips">
                                                            @foreach ($grp as $s)
                                                                @php
                                                                    $ss = $creneaux->where('session_id', $s->id);
                                                                    $placed = $ss->where('submission_id', '>', 0)->count();
                                                                    $tot = $ss->count();
                                                                    $roomClash = isset($wkConf[$s->id]);
                                                                    $atelierShort = $s->atelier ? Str::before($s->atelier->title, ' -') : $s->label;
                                                                @endphp
                                                                <button type="button" class="wk-chip{{ $roomClash ? ' conflict' : '' }}"
                                                                        @click='openSession(@json($sessJsFor($s, $dd)))'
                                                                        title="{{ ($s->atelier?->title ?: $s->label).' - '.$wkRoomOf($s).' - '.$placed.'/'.$tot.' communication(s) placée(s)'.($roomClash ? ' (conflit : salle occupée par une autre séance au même moment)' : '') }}">
                                                                    @if ($roomClash)<span class="cflag"><x-ukc.icon name="alert-triangle" :size="10" /></span>@endif
                                                                    <span class="a">{{ $atelierShort }}</span>
                                                                    <span class="r"><x-ukc.icon name="pin" :size="10" /> {{ $wkRoomOf($s) }}</span>
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            @empty
                                                <div class="wk-empty">Aucune séance</div>
                                            @endforelse
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <p class="ukc-hint" style="margin-top:10px;font-size:12px;color:var(--ukc-light);">Vue d’ensemble de la semaine - chaque colonne = un jour, les blocs sont classés par heure. Chaque pastille indique un <strong>atelier</strong> et sa <strong>salle</strong> ; survolez-la pour voir le nombre de communications placées. Cliquez une plénière ou un atelier pour l’éditer ; le placement des communications se fait en vue « Jour ».</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ============================ ATELIERS ============================ --}}
            @php
                // Options de filtres (dérivées des ateliers de l'événement).
                $atelierRoomOpts = $ateliers->map(fn ($a) => $atelierRoomName($a))->filter()->unique()->sort()->values();
                $atelierTrackOpts = $ateliers->flatMap(fn ($a) => $a->tracks ?: [])->filter()->unique()->sort()->values();
            @endphp
            <div @if ($tab !== 'ateliers') style="display:none;" @endif data-ajax-root="prog-ateliers">
                <div class="ukc-card" x-data="tableFilters(['room', 'roomstate', 'axe'])">
                    <h2>
                        <span><x-ukc.icon name="grid" :size="18" /> Ateliers</span>
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="openAtelier(null)"><x-ukc.icon name="plus" :size="14" /> Nouvel atelier</button>
                    </h2>
                    <div class="desc">Un atelier regroupe un ou plusieurs axes et contient des sessions. La salle peut être associée plus tard.</div>

                    {{-- Filtres (client-side, instantanés). --}}
                    <div class="filter-bar filter-bar--inline" style="margin:4px 0 14px;">
                        <select x-model="f.room" aria-label="Filtrer par salle">
                            <option value="">Toutes les salles</option>
                            @foreach ($atelierRoomOpts as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                        </select>
                        <select x-model="f.axe" aria-label="Filtrer par axe">
                            <option value="">Tous les axes</option>
                            @foreach ($atelierTrackOpts as $t)<option value="{{ $t }}">{{ Str::limit($t, 40) }}</option>@endforeach
                        </select>
                        <select x-model="f.roomstate" aria-label="Filtrer par état de salle">
                            <option value="">Salle : toutes</option>
                            <option value="assigned">Salle définie</option>
                            <option value="pending">Salle à définir</option>
                        </select>
                        <input type="search" x-model="f.q" placeholder="Atelier, axe, salle…" style="min-width:190px;flex:1;">
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" x-show="active" x-cloak @click="reset()">Réinitialiser</button>
                    </div>
                    <div style="font-size:12.5px;color:var(--ukc-muted);margin-bottom:8px;">
                        <span x-text="visibleIds.length"></span> atelier(s) affiché(s)<template x-if="active"> sur {{ $ateliers->count() }}</template>.
                    </div>

                    @forelse ($ateliers as $atelier)
                        @php
                            $room = $atelierRoomName($atelier);
                            $atelierSearch = Str::lower(collect([$atelier->title, $room])->merge($atelier->tracks ?: [])->filter()->implode(' '));
                        @endphp
                        <div class="entity-card" x-show="visibleIds.includes({{ $atelier->id }})" data-filter-row
                             data-id="{{ $atelier->id }}" data-room="{{ $room }}"
                             data-roomstate="{{ $room ? 'assigned' : 'pending' }}"
                             data-axe="{{ implode("\n", $atelier->tracks ?: []) }}" data-text="{{ $atelierSearch }}">
                            <h3>
                                {{ $atelier->title }}
                                @if ($room)<span class="ukc-pill ukc-pill--ok">{{ $room }}</span>
                                @else<span class="ukc-pill ukc-pill--warn">Salle à définir</span>@endif
                            </h3>
                            <div class="meta">
                                {{ $atelier->sessions->count() }} session(s) - {{ $creneaux->whereIn('session_id', $atelier->sessions->pluck('id'))->count() }} créneau(x)
                            </div>
                            <div class="chip-list">
                                @forelse ($atelier->tracks ?: [] as $t)
                                    <span class="ukc-pill ukc-pill--info">{{ $t }}</span>
                                @empty
                                    <span class="ukc-pill ukc-pill--muted">Aucun axe rattaché</span>
                                @endforelse
                            </div>
                            <div class="ukc-actions row-actions">
                                <button type="button" class="ukc-btn--icon" title="Modifier l’atelier"
                                        @click="openAtelier({ id:{{ $atelier->id }}, title:@js($atelier->title), tracks:@js($atelier->tracks ?: []), room:@js($room ?: '') })"><x-ukc.icon name="pen" :size="15" /></button>
                                {{-- Associer une salle reste mis en avant tant qu'il n'y en a pas. --}}
                                @if ($room)
                                    <button type="button" class="ukc-btn--icon" title="Changer la salle"
                                            @click="openRoom({ id:{{ $atelier->id }}, title:@js($atelier->title), room:@js($room) })"><x-ukc.icon name="pin" :size="15" /></button>
                                @else
                                    <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm"
                                            @click="openRoom({ id:{{ $atelier->id }}, title:@js($atelier->title), room:'' })"><x-ukc.icon name="pin" :size="14" /> Associer une salle</button>
                                @endif
                                <form method="POST" action="{{ route('admin.program.ateliers.destroy', $atelier) }}" data-ajax data-ajax-target="prog-ateliers" data-ajax-msg="Atelier supprimé." data-confirm="Supprimer cet atelier ? Ses sessions deviendront plénières.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ukc-btn--icon danger" title="Supprimer l’atelier"><x-ukc.icon name="trash" :size="15" /></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="ukc-empty"><h3>Aucun atelier</h3><p>Créez un premier atelier pour organiser les sessions.</p></div>
                    @endforelse
                    @if ($ateliers->isNotEmpty())
                        <div class="ukc-empty" x-show="visibleIds.length === 0" x-cloak>
                            <p>Aucun atelier ne correspond aux filtres. <button type="button" class="ukc-link" @click="reset()">Réinitialiser</button></p>
                        </div>
                    @endif
                </div>

                {{-- Lien avec le programme de l'événement (fidèle au mockup). --}}
                @if ($event)
                    <div class="ukc-card">
                        <h2>Lien avec le programme de l’événement</h2>
                        <div class="desc">
                            Le planning et les sessions s’appuient sur le programme défini dans
                            <a href="{{ route('admin.evenements.edit', $event) }}?tab=programme" style="color:var(--ukc-blue);font-weight:600;">Événement → Programme</a>
                            (jours, horaires, type Session / Atelier, intitulés).
                        </div>
                        <div class="settings-box">
                            <h3>Création automatique des sessions</h3>
                            <p style="font-size:13px;color:var(--ukc-muted);margin-bottom:10px;line-height:1.45;">
                                Le système lit les lignes du programme de type « Session » / « Atelier » dont l’axe ou l’intitulé correspond
                                et génère les sessions (créneaux horaires) dans le planning. Les modérateurs et rapporteurs restent à compléter.
                            </p>
                            <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.evenements.edit', $event) }}?tab=programme">Ouvrir le programme de l’événement</a>
                        </div>
                        <p class="ukc-hint" style="font-size:12px;color:var(--ukc-light);margin-top:12px;">La gestion détaillée des salles (amphi, A/B/C, hall) se fait dans l’onglet <strong>Espaces</strong>.</p>
                    </div>
                @endif
            </div>

            {{-- ============================ SESSIONS ============================ --}}
            @php
                // Options de filtres (dérivées des sessions de l'événement).
                $sessDays = $sessions->map(fn ($s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d'))->filter()->unique()->sort()->values();
                $sessAteliers = $sessions->map(fn ($s) => $s->atelier?->title)->filter()->unique()->sort()->values();
                $sessRooms = $sessions->map(fn ($s) => $s->room)->filter()->unique()->sort()->values();
            @endphp
            <div @if ($tab !== 'sessions') style="display:none;" @endif data-ajax-root="prog-sessions">
                <div class="ukc-card">
                    <h2>
                        <span><x-ukc.icon name="layers" :size="18" /> Sessions</span>
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="openSession(null)"><x-ukc.icon name="plus" :size="14" /> Nouvelle session</button>
                    </h2>
                    <div class="desc">Chaque session a un modérateur et un rapporteur (fiches dédiées via Documents).</div>

                    {{-- Filtres serveur (GET) - même patron que admin/soumissions.
                         `tab` et le tri courant sont reportés pour rester sur l'onglet et
                         garder le tri quand on change un filtre.
                         ⚠️ Filtrage en AJAX (`data-filter`, cf. app.js) : la recherche
                         s'applique à la frappe, sans valider ni recharger la page. --}}
                    <form method="GET" action="{{ route('admin.program.index') }}" class="filter-bar filter-bar--inline" style="margin:4px 0 14px;" data-filter="prog-sessions">
                        <input type="hidden" name="event_id" value="{{ $event_id }}">
                        <input type="hidden" name="tab" value="sessions">
                        <input type="hidden" name="s_sort" value="{{ $sessSort }}">
                        <input type="hidden" name="s_dir" value="{{ $sessDir }}">
                        <select name="s_day" aria-label="Filtrer par jour">
                            <option value="">Tous les jours</option>
                            @foreach ($sessDays as $d)<option value="{{ $d }}" @selected($sessFilters['day'] === $d)>{{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('D d M') }}</option>@endforeach
                        </select>
                        <select name="s_type" aria-label="Filtrer par type">
                            <option value="">Tous les types</option>
                            <option value="plenary" @selected($sessFilters['type'] === 'plenary')>Plénière</option>
                            <option value="atelier" @selected($sessFilters['type'] === 'atelier')>Atelier</option>
                        </select>
                        <select name="s_atelier" aria-label="Filtrer par atelier">
                            <option value="">Tous les ateliers</option>
                            @foreach ($sessAteliers as $a)<option value="{{ $a }}" @selected($sessFilters['atelier'] === $a)>{{ Str::limit($a, 40) }}</option>@endforeach
                        </select>
                        <select name="s_room" aria-label="Filtrer par salle">
                            <option value="">Toutes les salles</option>
                            @foreach ($sessRooms as $r)<option value="{{ $r }}" @selected($sessFilters['room'] === $r)>{{ $r }}</option>@endforeach
                        </select>
                        <select name="s_mod" aria-label="Filtrer par modération">
                            <option value="">Modération : toutes</option>
                            <option value="assigned" @selected($sessFilters['mod'] === 'assigned')>Modérateur désigné</option>
                            <option value="pending" @selected($sessFilters['mod'] === 'pending')>À désigner</option>
                        </select>
                        <input type="search" name="s_q" value="{{ $sessFilters['q'] }}" placeholder="Session, modérateur, rapporteur…" style="min-width:190px;flex:1;">
                        {{-- Le lien dépend des filtres : sa propre région échangée. --}}
                        <span data-filter-part="prog-sessions-reset">
                            @if ($sessActive)
                                <a href="{{ route('admin.program.index', ['event_id' => $event_id, 'tab' => 'sessions']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
                            @endif
                        </span>
                    </form>
                    <div style="font-size:12.5px;color:var(--ukc-muted);margin-bottom:8px;" data-filter-part="prog-sessions-count">
                        {{ $sessionsPage->total() }} session(s)@if ($sessActive) filtrée(s) sur {{ $sessions->count() }}@endif.
                    </div>

                    <div class="ukc-table-wrap" data-filter-part="prog-sessions-table">
                        <table class="ukc-table">
                            <thead>
                                <tr>
                                    <x-ukc.th sort="day" :current="$sessSort" :dir="$sessDir" param="s_sort" dirParam="s_dir" pageParam="s_page">Jour</x-ukc.th>
                                    <x-ukc.th sort="atelier" :current="$sessSort" :dir="$sessDir" param="s_sort" dirParam="s_dir" pageParam="s_page">Atelier</x-ukc.th>
                                    <x-ukc.th sort="label" :current="$sessSort" :dir="$sessDir" param="s_sort" dirParam="s_dir" pageParam="s_page">Session</x-ukc.th>
                                    <x-ukc.th sort="time" :current="$sessSort" :dir="$sessDir" param="s_sort" dirParam="s_dir" pageParam="s_page">Horaire</x-ukc.th>
                                    <x-ukc.th sort="moderator" :current="$sessSort" :dir="$sessDir" param="s_sort" dirParam="s_dir" pageParam="s_page">Modérateur</x-ukc.th>
                                    <x-ukc.th sort="rapporteur" :current="$sessSort" :dir="$sessDir" param="s_sort" dirParam="s_dir" pageParam="s_page">Rapporteur</x-ukc.th>
                                    <th class="num">Créneaux</th><th class="num">Com.</th><th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            @forelse ($sessionsPage as $session)
                                @php
                                    $sessionSlots = $creneaux->where('session_id', $session->id);
                                    $sDay = optional($session->session_day ?? $session->starts_at);
                                @endphp
                                {{-- Un <tbody> par séance : il porte l'état d'ouverture de la
                                     visio, qui s'affiche sur une ligne à part. --}}
                                <tbody x-data="{ visio: false }">
                                    <tr>
                                        <td>{{ $sDay?->format('d/m') ?: '-' }}</td>
                                        <td>{{ $session->atelier?->title ?: '- (plénière)' }}</td>
                                        <td><strong>{{ $session->label }}</strong></td>
                                        <td>{{ $session->starts_at?->format('H:i') ?: '-' }}@if ($session->ends_at) - {{ $session->ends_at->format('H:i') }}@endif</td>
                                        <td>@if ($session->moderator_name)<span class="ukc-pill ukc-pill--mod">{{ $session->moderator_name }}</span>@else<span class="ukc-pill ukc-pill--muted">À désigner</span>@endif</td>
                                        <td>@if ($session->rapporteur_name)<span class="ukc-pill ukc-pill--rap">{{ $session->rapporteur_name }}</span>@else<span class="ukc-pill ukc-pill--muted">À désigner</span>@endif</td>
                                        <td class="num">{{ $sessionSlots->count() }}</td>
                                        <td class="num">{{ $sessionSlots->where('submission_id', '>', 0)->count() }}</td>
                                        <td style="text-align:right;">
                                            <div class="ukc-actions">
                                                <a href="{{ route('admin.documents.sessions', $event) }}" class="ukc-btn--icon" title="Fiches (modération / rapport)"><x-ukc.icon name="file" :size="15" /></a>
                                                <button type="button" class="ukc-btn--icon" title="Visioconférence"
                                                        @click="visio = ! visio" :class="{ 'active': visio }"><x-ukc.icon name="video" :size="15" /></button>
                                                <button type="button" class="ukc-btn--icon" title="Modifier"
                                                        @click="openSession({ id:{{ $session->id }}, type:@js($session->atelier_id ? 'atelier' : 'plenary'), atelier_id:@js((string) $session->atelier_id), label:@js($session->label), day:@js($sDay?->format('Y-m-d') ?? ''), start:@js($session->starts_at?->format('H:i') ?? ''), end:@js($session->ends_at?->format('H:i') ?? ''), room:@js($session->room ?? ''), moderator:@js($session->moderator_name ?? ''), moderator_email:@js($session->moderator_email ?? ''), rapporteur:@js($session->rapporteur_name ?? ''), rapporteur_email:@js($session->rapporteur_email ?? '') })"><x-ukc.icon name="pen" :size="15" /></button>
                                                <form method="POST" action="{{ route('admin.program.sessions.destroy', $session) }}" data-ajax data-ajax-target="prog-sessions" data-ajax-msg="Session supprimée." data-confirm="Supprimer cette session ?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="ukc-btn--icon danger" title="Supprimer"><x-ukc.icon name="trash" :size="15" /></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    {{-- Réglages de visioconférence de la séance (lien, état, accès). --}}
                                    <tr x-show="visio" x-cloak>
                                        <td colspan="9" style="background:#f8fafc;">
                                            @include('admin.program._meeting', ['s' => $session])
                                        </td>
                                    </tr>
                                </tbody>
                            @empty
                                <tbody>
                                    <tr><td colspan="9" style="text-align:center;color:var(--ukc-muted);padding:20px;">
                                        @if ($sessActive)Aucune session ne correspond aux filtres. <a href="{{ route('admin.program.index', ['event_id' => $event_id, 'tab' => 'sessions']) }}" class="ukc-link">Réinitialiser</a>@else Aucune session.@endif
                                    </td></tr>
                                </tbody>
                            @endforelse
                        </table>
                    </div>

                    <div style="margin-top:12px;" data-filter-part="prog-sessions-foot">{{ $sessionsPage->links() }}</div>
                </div>
            </div>

            {{-- ============================ CRÉNEAUX (liste) ============================ --}}
            @php
                // Options de filtres (dérivées des créneaux de l'événement).
                $slotDays = $creneaux->map(fn ($s) => $s->starts_at?->format('Y-m-d'))->filter()->unique()->sort()->values();
                $slotAteliers = $creneaux->map(fn ($s) => $s->session?->atelier?->title)->filter()->unique()->sort()->values();
                $slotRooms = $creneaux->map(fn ($s) => $s->room)->filter()->unique()->sort()->values();
            @endphp
            <div @if ($tab !== 'creneaux') style="display:none;" @endif data-ajax-root="prog-slots">
                <div class="ukc-card">
                    <h2>
                        <span><x-ukc.icon name="clock" :size="18" /> Créneaux</span>
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="openSlot(null)"><x-ukc.icon name="plus" :size="14" /> Nouveau créneau</button>
                    </h2>

                    {{-- Filtres serveur (GET) - même patron que admin/soumissions.
                         ⚠️ Filtrage en AJAX (`data-filter`, cf. app.js) : la recherche
                         s'applique à la frappe, sans valider ni recharger la page. --}}
                    <form method="GET" action="{{ route('admin.program.index') }}" class="filter-bar filter-bar--inline" style="margin:4px 0 14px;" data-filter="prog-creneaux">
                        <input type="hidden" name="event_id" value="{{ $event_id }}">
                        <input type="hidden" name="tab" value="creneaux">
                        <input type="hidden" name="c_sort" value="{{ $slotSort }}">
                        <input type="hidden" name="c_dir" value="{{ $slotDir }}">
                        <select name="c_day" aria-label="Filtrer par jour">
                            <option value="">Tous les jours</option>
                            @foreach ($slotDays as $d)<option value="{{ $d }}" @selected($slotFilters['day'] === $d)>{{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('D d M') }}</option>@endforeach
                        </select>
                        <select name="c_atelier" aria-label="Filtrer par atelier">
                            <option value="">Tous les ateliers</option>
                            @foreach ($slotAteliers as $a)<option value="{{ $a }}" @selected($slotFilters['atelier'] === $a)>{{ Str::limit($a, 40) }}</option>@endforeach
                        </select>
                        <select name="c_room" aria-label="Filtrer par salle">
                            <option value="">Toutes les salles</option>
                            @foreach ($slotRooms as $r)<option value="{{ $r }}" @selected($slotFilters['room'] === $r)>{{ $r }}</option>@endforeach
                        </select>
                        <select name="c_status" aria-label="Filtrer par statut">
                            <option value="">Assignés & libres</option>
                            <option value="assigned" @selected($slotFilters['status'] === 'assigned')>Assignés</option>
                            <option value="free" @selected($slotFilters['status'] === 'free')>Libres</option>
                        </select>
                        <select name="c_type" aria-label="Filtrer par type">
                            <option value="">Tous les types</option>
                            <option value="oral" @selected($slotFilters['type'] === 'oral')>Orale</option>
                            <option value="poster" @selected($slotFilters['type'] === 'poster')>Poster</option>
                        </select>
                        <select name="c_presentation_mode" aria-label="Filtrer par mode de présentation">
                            <option value="">Tous les modes</option>
                            <option value="online" @selected($slotFilters['presentation_mode'] === 'online')>En ligne</option>
                            <option value="onsite" @selected($slotFilters['presentation_mode'] === 'onsite')>En présentiel</option>
                        </select>
                        <input type="search" name="c_q" value="{{ $slotFilters['q'] }}" placeholder="Intitulé, titre, code…" style="min-width:190px;flex:1;">
                        {{-- Le lien dépend des filtres : sa propre région échangée. --}}
                        <span data-filter-part="prog-creneaux-reset">
                            @if ($slotActive)
                                <a href="{{ route('admin.program.index', ['event_id' => $event_id, 'tab' => 'creneaux']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
                            @endif
                        </span>
                    </form>
                    <div style="font-size:12.5px;color:var(--ukc-muted);margin-bottom:8px;" data-filter-part="prog-creneaux-count">
                        {{ $slotsPage->total() }} créneau(x)@if ($slotActive) filtré(s) sur {{ $creneaux->count() }}@endif.
                    </div>

                    <div class="ukc-table-wrap" data-filter-part="prog-creneaux-table">
                        <table class="ukc-table">
                            <thead>
                                <tr>
                                    <x-ukc.th sort="day" :current="$slotSort" :dir="$slotDir" param="c_sort" dirParam="c_dir" pageParam="c_page">Jour</x-ukc.th>
                                    <x-ukc.th sort="time" :current="$slotSort" :dir="$slotDir" param="c_sort" dirParam="c_dir" pageParam="c_page">Horaire</x-ukc.th>
                                    <x-ukc.th sort="room" :current="$slotSort" :dir="$slotDir" param="c_sort" dirParam="c_dir" pageParam="c_page">Salle</x-ukc.th>
                                    <x-ukc.th sort="atelier" :current="$slotSort" :dir="$slotDir" param="c_sort" dirParam="c_dir" pageParam="c_page">Atelier / Session</x-ukc.th>
                                    <x-ukc.th sort="title" :current="$slotSort" :dir="$slotDir" param="c_sort" dirParam="c_dir" pageParam="c_page">Intitulé</x-ukc.th>
                                    <x-ukc.th sort="type" :current="$slotSort" :dir="$slotDir" param="c_sort" dirParam="c_dir" pageParam="c_page">Type</x-ukc.th>
                                    <x-ukc.th sort="code" :current="$slotSort" :dir="$slotDir" param="c_sort" dirParam="c_dir" pageParam="c_page">Code</x-ukc.th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($slotsPage as $slot)
                                    @php
                                        // Heure de fin = heure de début + durée présumée (figée à la
                                        // création/modif) ; repli sur la durée orale pour d'anciens créneaux.
                                        $slotEnd = $slot->ends_at ?: ($slot->starts_at?->copy()->addMinutes($oralMin));
                                    @endphp
                                    <tr>
                                        <td>{{ $slot->starts_at?->format('d/m') ?: '-' }}</td>
                                        <td style="white-space:nowrap;">{{ $slot->starts_at?->format('H:i') ?: '-' }}@if ($slot->starts_at && $slotEnd) - {{ $slotEnd->format('H:i') }}@endif</td>
                                        <td>{{ $slot->room ?: '-' }}</td>
                                        {{-- Atelier et session sur deux niveaux : concaténés, ils
                                             répétaient le nom de l'atelier (les libellés de session
                                             le contiennent souvent) et gonflaient la colonne. --}}
                                        <td style="min-width:200px;">
                                            @if ($slot->session?->atelier?->title)
                                                <span class="axis-tag">{{ Str::limit($slot->session->atelier->title, 26) }}</span>
                                            @endif
                                            <div style="font-size:12.5px;color:var(--ukc-muted);margin-top:3px;">{{ Str::limit($slot->session_label ?: '-', 38) }}</div>
                                        </td>
                                        <td>@if ($slot->submission)<strong style="font-weight:600;">{{ Str::limit($slot->submission->title, 45) }}</strong>@else<span style="color:var(--ukc-light);">-</span>@endif</td>
                                        <td>
                                            @if ($slot->submission)<span class="ukc-pill ukc-pill--{{ $slot->submission->type === 'poster' ? 'poster' : 'oral' }}">{{ $slot->submission->type === 'poster' ? 'Poster' : 'Orale' }}</span>
                                                @if ($slot->submission->presentationModeLabel())
                                                    <div style="margin-top:5px;"><span class="ukc-pill ukc-pill--{{ $slot->submission->presentation_mode === 'online' ? 'info' : 'ok' }}">{{ $slot->submission->presentationModeLabel() }}</span></div>
                                                @endif
                                            @else<span class="ukc-pill ukc-pill--muted">Libre</span>@endif
                                        </td>
                                        <td>{{ $slot->submission?->ref_code ?: '-' }}</td>
                                        <td style="text-align:right;">
                                            <div class="ukc-actions">
                                                <button type="button" class="ukc-btn--icon" title="Modifier"
                                                        @click="openSlot({ id:{{ $slot->id }}, session_id:@js((string) $slot->session_id), day:@js($slot->starts_at?->format('Y-m-d') ?? ''), start:@js($slot->starts_at?->format('H:i') ?? ''), room:@js($slot->room ?? ''), submission_id:@js((string) $slot->submission_id), title:@js($slot->session_label ?? '') })"><x-ukc.icon name="pen" :size="15" /></button>
                                                <form method="POST" action="{{ route('admin.program.slots.destroy', $slot) }}" data-ajax data-ajax-target="prog-slots" data-ajax-msg="Créneau supprimé." data-confirm="Supprimer ce créneau ?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="ukc-btn--icon danger" title="Supprimer"><x-ukc.icon name="trash" :size="15" /></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" style="text-align:center;color:var(--ukc-muted);padding:20px;">
                                        @if ($slotActive)Aucun créneau ne correspond aux filtres. <a href="{{ route('admin.program.index', ['event_id' => $event_id, 'tab' => 'creneaux']) }}" class="ukc-link">Réinitialiser</a>@else Aucun créneau.@endif
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:12px;" data-filter-part="prog-creneaux-foot">{{ $slotsPage->links() }}</div>
                </div>
            </div>

            {{-- ============================ ESPACES ============================ --}}
            <div @if ($tab !== 'espaces') style="display:none;" @endif data-ajax-root="prog-spaces">
                <div class="ukc-card">
                    <h2>
                        <span><x-ukc.icon name="pin" :size="18" /> Gestion des espaces</span>
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="openSpace()"><x-ukc.icon name="plus" :size="14" /> Ajouter un espace</button>
                    </h2>
                    <div class="desc">Amphithéâtres, salles et hall posters. Un espace peut être associé à un atelier.</div>

                    @php($linkedRooms = collect($atelierRooms)->values()->all())
                    @forelse ($spaces as $i => $space)
                        @php($isLinked = in_array($space['name'], $linkedRooms, true))
                        <div class="entity-card">
                            <h3>
                                {{ $space['name'] }}
                                @if ($isLinked)<span class="ukc-pill ukc-pill--info">Liée</span>
                                @else<span class="ukc-pill ukc-pill--ok">Actif</span>@endif
                            </h3>
                            <div class="meta">
                                @php($bits = array_filter([
                                    ! empty($space['capacity']) ? 'Capacité '.$space['capacity'] : null,
                                    $space['building'] ?? null,
                                    $space['note'] ?? null,
                                ]))
                                {{ implode(' - ', $bits) ?: '-' }}
                            </div>
                            <div class="ukc-actions row-actions">
                                <form method="POST" action="{{ route('admin.program.spaces.destroy', $i) }}?event_id={{ $event_id }}" data-ajax data-ajax-target="prog-spaces" data-ajax-msg="Espace supprimé." data-confirm="Supprimer cet espace ?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ukc-btn--icon danger" title="Supprimer l’espace"><x-ukc.icon name="trash" :size="15" /></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:13px;color:var(--ukc-muted);">Aucun espace déclaré. Ajoutez-en, ou utilisez les salles saisies sur les sessions.</p>
                    @endforelse

                    @if ($usedRooms->isNotEmpty())
                        <div class="settings-box" style="margin-top:16px;">
                            <h3>Salles utilisées (dérivées du programme)</h3>
                            <div class="chip-list">
                                @foreach ($usedRooms as $room)<span class="ukc-pill ukc-pill--muted">{{ $room }}</span>@endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Communications acceptées restant à placer : la suite naturelle de
                 l'onglet Créneaux, plutôt qu'un onglet séparé. --}}
            <div style="margin-top:18px;{{ $tab !== 'creneaux' ? 'display:none;' : '' }}">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="inbox" :size="18" /> Communications acceptées non planifiées</span></h2>
                    <div class="desc">Priorité à l'affectation auto pour les communications <strong>payées</strong> dont l'axe correspond à un atelier avec créneau libre.</div>
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead>
                                <tr><th>Code</th><th>Titre</th><th>Auteur</th><th>Axe</th><th>Paiement</th><th style="text-align:right;">Actions</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($unplaced as $sub)
                                    <tr>
                                        <td>{{ $sub->ref_code }}</td>
                                        <td>{{ Str::limit($sub->title, 50) }}</td>
                                        <td>{{ $sub->participant?->full_name ?: '-' }}</td>
                                        <td>{{ $sub->track ?: '-' }}</td>
                                        <td>@if ($isPaid($sub))<span class="ukc-pill ukc-pill--ok">Payé</span>@else<span class="ukc-pill ukc-pill--warn">En attente</span>@endif</td>
                                        <td style="text-align:right;">
                                            <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="openSlot({ id:'', session_id:'', day:'', start:'', room:'', submission_id:'{{ $sub->id }}', title:'' })"><x-ukc.icon name="calendar-check" :size="14" /> Assigner</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="text-align:center;color:var(--ukc-green);padding:20px;"><span style="display:inline-flex;align-items:center;gap:6px;"><x-ukc.icon name="check-circle" :size="16" /> Toutes les communications acceptées sont planifiées.</span></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ============================ AFFECTATION AUTO ============================ --}}
            <div @if ($tab !== 'auto') style="display:none;" @endif>
                <div class="ukc-card" style="max-width:720px;">
                    <h2><span><x-ukc.icon name="refresh" :size="18" /> Affectation automatique des créneaux</span></h2>
                    <div class="desc">
                        Dès qu'une communication est <strong>acceptée</strong> et le <strong>paiement</strong> validé, le système peut la placer
                        sur un créneau libre de l'atelier correspondant à son axe.
                    </div>

                    <form method="POST" action="{{ route('admin.program.config') }}?event_id={{ $event_id }}">
                        @csrf
                        <div class="settings-box">
                            <h3>Règles</h3>
                            <label class="ukc-check"><input type="checkbox" name="auto_enabled" value="1" @checked($config['auto']['enabled'])> Activer l'affectation automatique après paiement</label>
                            <label class="ukc-check"><input type="checkbox" name="auto_only_paid" value="1" @checked($config['auto']['only_paid'])> Uniquement les communications dont le paiement est confirmé</label>
                            <label class="ukc-check"><input type="checkbox" name="auto_match_axis" value="1" @checked($config['auto']['match_axis'])> Respecter l'axe thématique → atelier</label>
                            <label class="ukc-check"><input type="checkbox" name="auto_notify" value="1" @checked($config['auto']['notify'])> Notifier l'auteur par e-mail (horaire, atelier, salle)</label>
                            <label class="ukc-check"><input type="checkbox" name="auto_use_ai" value="1" @checked($config['auto']['use_ai'])> Utiliser l'IA pour optimiser l'ordre / éviter les conflits d'auteurs</label>
                        </div>
                        <div class="settings-box">
                            <h3>Priorité de placement</h3>
                            <div class="ukc-form-row">
                                <label>Ordre de remplissage des créneaux libres</label>
                                <select name="auto_order">
                                    <option value="chrono" @selected($config['auto']['order']==='chrono')>Chronologique (premier créneau libre du jour)</option>
                                    <option value="balance" @selected($config['auto']['order']==='balance')>Équilibrer les sessions (répartition)</option>
                                    <option value="ai" @selected($config['auto']['order']==='ai')>Laisser l'IA proposer</option>
                                </select>
                            </div>
                            <div class="ukc-form-row">
                                <label>Si aucun créneau libre dans l'atelier de l'axe</label>
                                <select name="auto_fallback">
                                    <option value="wait" @selected($config['auto']['fallback']==='wait')>Laisser en « non planifiée »</option>
                                    <option value="other" @selected($config['auto']['fallback']==='other')>Proposer un autre atelier compatible</option>
                                    <option value="queue" @selected($config['auto']['fallback']==='queue')>File d'attente + alerte organisateur</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="ukc-btn">Enregistrer les règles</button>
                    </form>

                    <div class="settings-box" style="margin-top:16px;">
                        <h3>Générer les créneaux</h3>
                        <p style="font-size:13px;color:var(--ukc-muted);margin-bottom:10px;line-height:1.45;">
                            Crée des créneaux libres à partir des sessions horodatées (découpage par la durée d'une communication orale, réglée dans « Paramètres créneaux »). Les sessions déjà pourvues sont ignorées.
                        </p>
                        <form method="POST" action="{{ route('admin.program.slots.generate') }}?event_id={{ $event_id }}">
                            @csrf
                            <button type="submit" class="ukc-btn ukc-btn--sm">
                                <x-ukc.icon name="plus" :size="15" /> Générer les créneaux depuis les sessions
                            </button>
                        </form>
                    </div>

                    <div class="settings-box" style="margin-top:16px;">
                        <h3>Lancer une passe maintenant</h3>
                        <form method="POST" action="{{ route('admin.program.slots.auto') }}?event_id={{ $event_id }}">
                            @csrf
                            <input type="hidden" name="match_axis" value="1">
                            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                                <button type="submit" class="ukc-btn ukc-btn--green" @disabled($stats['toPlan'] === 0 || $creneaux->where('submission_id', 0)->isEmpty())>
                                    <x-ukc.icon name="check-circle" :size="16" /> Lancer une passe d'affectation
                                </button>
                                <span style="font-size:12.5px;color:var(--ukc-muted);">{{ $stats['toPlan'] }} à planifier - {{ $creneaux->where('submission_id', 0)->count() }} créneau(x) libre(s)</span>
                            </div>
                            @if ($creneaux->where('submission_id', 0)->isEmpty())
                                <p class="ukc-hint" style="font-size:12px;color:var(--ukc-light);margin-top:8px;">Aucun créneau libre : générez-les d'abord ci-dessus, ou ajoutez-en manuellement.</p>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            {{-- ============================ PARAMÈTRES ============================ --}}
            <div @if ($tab !== 'options') style="display:none;" @endif>
                <form method="POST" action="{{ route('admin.program.config') }}?event_id={{ $event_id }}" class="ukc-card" style="max-width:720px;">
                    @csrf
                    {{-- On renvoie aussi les règles auto pour ne pas les écraser --}}
                    <input type="hidden" name="auto_enabled" value="{{ $config['auto']['enabled'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_only_paid" value="{{ $config['auto']['only_paid'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_match_axis" value="{{ $config['auto']['match_axis'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_notify" value="{{ $config['auto']['notify'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_use_ai" value="{{ $config['auto']['use_ai'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_order" value="{{ $config['auto']['order'] }}">
                    <input type="hidden" name="auto_fallback" value="{{ $config['auto']['fallback'] }}">

                    <h2><span><x-ukc.icon name="settings" :size="18" /> Paramètres des créneaux</span></h2>
                    <div class="desc">Durées, pauses et règles de planification et d'affichage public.</div>

                    <div class="settings-box">
                        <h3>Durées par défaut</h3>
                        <div class="ukc-form-grid">
                            <div class="ukc-form-row"><label>Communication orale (min)</label><input type="number" name="oral_min" value="{{ $config['params']['oral_min'] }}" min="5" max="120" step="5"></div>
                            <div class="ukc-form-row"><label>Keynote / plénière (min)</label><input type="number" name="keynote_min" value="{{ $config['params']['keynote_min'] }}" min="15" max="120" step="5"></div>
                            <div class="ukc-form-row"><label>Session posters (min)</label><input type="number" name="poster_min" value="{{ $config['params']['poster_min'] }}" min="30" max="240" step="15"></div>
                            <div class="ukc-form-row"><label>Transition entre créneaux (min)</label><input type="number" name="gap_min" value="{{ $config['params']['gap_min'] }}" min="0" max="30" step="5"></div>
                        </div>
                    </div>

                    <div class="settings-box">
                        <h3>Pauses</h3>
                        <label class="ukc-check"><input type="checkbox" name="auto_break" value="1" @checked($config['params']['auto_break'])> Insérer une pause-café après N créneaux</label>
                        <div class="ukc-form-grid">
                            <div class="ukc-form-row"><label>Nombre de créneaux avant pause</label><input type="number" name="break_after" value="{{ $config['params']['break_after'] }}" min="1" max="10"></div>
                            <div class="ukc-form-row"><label>Durée de la pause (min)</label><input type="number" name="break_min" value="{{ $config['params']['break_min'] }}" min="5" max="60" step="5"></div>
                        </div>
                        <label class="ukc-check" style="margin-top:8px;"><input type="checkbox" name="lunch" value="1" @checked($config['params']['lunch'])> Prévoir une pause déjeuner</label>
                        <div class="ukc-form-grid">
                            <div class="ukc-form-row"><label>Début déjeuner</label><input type="time" name="lunch_start" value="{{ $config['params']['lunch_start'] }}"></div>
                            <div class="ukc-form-row"><label>Fin déjeuner</label><input type="time" name="lunch_end" value="{{ $config['params']['lunch_end'] }}"></div>
                        </div>
                    </div>

                    <div class="settings-box">
                        <h3>Horaires de journée</h3>
                        <div class="ukc-form-grid">
                            <div class="ukc-form-row"><label>Début (défaut)</label><input type="time" name="day_start" value="{{ $config['params']['day_start'] }}"></div>
                            <div class="ukc-form-row"><label>Fin (défaut)</label><input type="time" name="day_end" value="{{ $config['params']['day_end'] }}"></div>
                        </div>
                    </div>

                    <div class="settings-box">
                        <h3>Règles de validation</h3>
                        <label class="ukc-check"><input type="checkbox" name="no_overlap" value="1" @checked($config['params']['no_overlap'])> Interdire le chevauchement de créneaux dans le même espace</label>
                        <label class="ukc-check"><input type="checkbox" name="no_double_author" value="1" @checked($config['params']['no_double_author'])> Alerter si un auteur est planifié sur deux créneaux simultanés</label>
                        <label class="ukc-check"><input type="checkbox" name="params_match_axis" value="1" @checked($config['params']['match_axis'])> Vérifier la cohérence axe ↔ atelier</label>
                        <label class="ukc-check"><input type="checkbox" name="require_mod" value="1" @checked($config['params']['require_mod'])> Exiger modérateur et rapporteur avant publication</label>
                    </div>

                    <div class="settings-box">
                        <h3>Affichage public</h3>
                        <label class="ukc-check"><input type="checkbox" name="show_room" value="1" @checked($config['params']['show_room'])> Afficher la salle sur le programme public</label>
                        <label class="ukc-check"><input type="checkbox" name="show_mod" value="1" @checked($config['params']['show_mod'])> Afficher le nom du modérateur</label>
                        <label class="ukc-check"><input type="checkbox" name="show_empty" value="1" @checked($config['params']['show_empty'])> Afficher les créneaux libres sur le programme public</label>
                    </div>

                    <button type="submit" class="ukc-btn">Enregistrer les paramètres</button>
                </form>
            </div>
        @endif

        {{-- ============================ DISCUSSIONS (fils de visio) ============================ --}}
        @if ($tab === 'discussions')
            @include('admin.program._discussions')
        @endif

        {{-- ============================ MODALES ============================ --}}
        @if ($event)
            @include('admin.program._modals')
        @endif
    </div>
</x-layouts.admin>
