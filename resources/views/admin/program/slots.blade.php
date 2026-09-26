@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    // Communication payée ? (colonne Paiement de « Non planifiées »)
    $isPaid = fn ($sub) => (bool) optional($sub->participant?->participationPayments)->firstWhere('status', 'paid');
    // Durée d'un créneau selon le type de communication (réglages).
    $slotDuration = fn ($sub) => (int) (($sub && $sub->type === 'poster') ? $config['params']['poster_min'] : $config['params']['oral_min']);
    $atelierRoomName = fn ($atelier) => $atelierRooms[(string) $atelier->id] ?? ($atelier->sessions->firstWhere('room', '!=', null)?->room);
@endphp

<x-layouts.admin title="Créneaux de passage" subtitle="Ateliers · Sessions · Planning">
    @if ($edition)
        <x-slot:actions>
            <a href="{{ route('public.colloque', $edition->slug) }}#programme" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                <x-ukc.icon name="external" :size="15" /> Aperçu public
            </a>
            <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm"
                    onclick="window.dispatchEvent(new CustomEvent('ukc-new-slot'))">
                <x-ukc.icon name="plus" :size="15" /> Nouveau créneau
            </button>
        </x-slot:actions>
    @endif

    <div class="admin-page admin-program"
         x-data="{
            tab: 'planning',
            day: '{{ $days->first() }}',
            modal: null,
            form: {},
            openAtelier(a) { this.form = a ? {...a} : { id:'', title:'', tracks:[], room:'' }; this.modal='atelier'; },
            openSession(s) { this.form = s ? {...s} : { id:'', type:'atelier', atelier_id:'', label:'', day:'', start:'', end:'', room:'', moderator:'', rapporteur:'' }; this.modal='session'; },
            openSlot(s) { this.form = s ? {...s} : { id:'', session_id:'', day:'', start:'', room:'', submission_id:'', title:'' }; this.modal='slot'; },
            openSpace() { this.form = { name:'', capacity:'', building:'', note:'' }; this.modal='space'; },
            openRoom(a) { this.form = { ...a }; this.modal='room'; },
         }"
         @keydown.escape.window="modal=null"
         @ukc-new-slot.window="openSlot(null)">

        {{-- Filtres (GET) : sélecteur d'édition --}}
        <form method="GET" class="filter-bar">
            <select name="edition_id" onchange="this.form.submit()">
                <option value="">- Édition -</option>
                @foreach ($editions as $ed)
                    <option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>
                @endforeach
            </select>
        </form>

        @if ($edition_id === '')
            <div class="ukc-empty"><h3>Choisissez une édition</h3></div>
        @else
            {{-- KPI --}}
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="grid" :size="20" /></div><div class="lbl">Ateliers</div><div class="val blue">{{ $stats['ateliers'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="layers" :size="20" /></div><div class="lbl">Sessions</div><div class="val">{{ $stats['sessions'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">Créneaux</div><div class="val">{{ $stats['slots'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="calendar-check" :size="20" /></div><div class="lbl">Planifiées</div><div class="val green">{{ $stats['planned'] }}</div><div class="hint">orales</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="calendar" :size="20" /></div><div class="lbl">À planifier</div><div class="val warn">{{ $stats['toPlan'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="refresh" :size="20" /></div><div class="lbl">Affect. auto</div><div class="val" style="font-size:1.05rem;">{{ $stats['autoAssign'] ? 'Activée' : '-' }}</div><div class="hint">{{ $stats['autoAssign'] ? 'après paiement' : 'désactivée' }}</div></div>
            </div>

            <div class="hierarchy-note">
                <p>
                    Un <strong>atelier</strong> regroupe un ou plusieurs axes et contient des <strong>sessions</strong>
                    (modérateur &amp; rapporteur). Chaque <strong>créneau</strong> place une communication acceptée
                    dans une session, à une salle et une heure. La salle peut être associée plus tard.
                </p>
            </div>

            {{-- Onglets --}}
            <div class="ukc-nav-docs">
                <button type="button" @click="tab='planning'" :class="{ active: tab==='planning' }">Planning</button>
                <button type="button" @click="tab='ateliers'" :class="{ active: tab==='ateliers' }">Ateliers</button>
                <button type="button" @click="tab='sessions'" :class="{ active: tab==='sessions' }">Sessions</button>
                <button type="button" @click="tab='list'" :class="{ active: tab==='list' }">Créneaux</button>
                <button type="button" @click="tab='spaces'" :class="{ active: tab==='spaces' }">Espaces</button>
                <button type="button" @click="tab='unscheduled'" :class="{ active: tab==='unscheduled' }">Non planifiées ({{ $stats['toPlan'] }})</button>
                <button type="button" @click="tab='auto'" :class="{ active: tab==='auto' }">Affectation auto</button>
                <button type="button" @click="tab='params'" :class="{ active: tab==='params' }">Paramètres créneaux</button>
            </div>

            {{-- ============================ PLANNING ============================ --}}
            <div x-show="tab==='planning'" x-cloak>
                @if ($days->isEmpty())
                    <div class="ukc-empty"><h3>Aucun jour planifié</h3><p>Ajoutez des sessions datées et des créneaux pour bâtir le planning.</p></div>
                @else
                    <div class="day-tabs">
                        @foreach ($days as $i => $d)
                            @php $cd = Carbon::parse($d); @endphp
                            <button type="button" @click="day='{{ $d }}'" :class="{ active: day==='{{ $d }}' }">
                                Jour {{ $i + 1 }}<span class="d-sub">{{ $cd->translatedFormat('d M · D') }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="layout-plan">
                        <div>
                            @foreach ($days as $d)
                                <div x-show="day==='{{ $d }}'" x-cloak>
                                    @php
                                        $dayAteliers = $ateliers->filter(fn ($a) => $a->sessions->contains(fn ($s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d') === $d));
                                        $plenaryDay = $sessions->filter(fn ($s) => ! $s->atelier_id && optional($s->session_day ?? $s->starts_at)?->format('Y-m-d') === $d);
                                    @endphp

                                    {{-- Sessions plénières du jour --}}
                                    @foreach ($plenaryDay as $session)
                                        @include('admin.program._session-block', ['session' => $session, 'atelierName' => 'Plénière', 'roomBadge' => $session->room])
                                    @endforeach

                                    {{-- Ateliers du jour --}}
                                    @forelse ($dayAteliers as $atelier)
                                        @php $room = $atelierRoomName($atelier); @endphp
                                        <div class="atelier-block">
                                            <div class="atelier-head">
                                                <div>
                                                    <h3>{{ $atelier->title }}</h3>
                                                    <div class="meta">
                                                        Axes : {{ implode(', ', $atelier->tracks ?: ['-']) }}<br>
                                                        {{ $atelier->sessions->count() }} session(s)
                                                    </div>
                                                </div>
                                                <div class="room-badge {{ $room ? '' : 'pending' }}">{{ $room ?: 'Salle non assignée' }}</div>
                                            </div>
                                            @foreach ($atelier->sessions->filter(fn ($s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d') === $d) as $session)
                                                @include('admin.program._session-block', ['session' => $session, 'inAtelier' => true])
                                            @endforeach
                                        </div>
                                    @empty
                                        @if ($plenaryDay->isEmpty())
                                            <div class="ukc-card"><p style="margin:0;color:var(--ukc-muted);font-size:13.5px;">Aucune session ce jour.</p></div>
                                        @endif
                                    @endforelse
                                </div>
                            @endforeach
                        </div>

                        {{-- Colonne « À planifier » --}}
                        <div>
                            <div class="ukc-card" style="position:sticky;top:12px;">
                                <h2 style="font-size:1rem;font-weight:800;margin-bottom:6px;">À planifier</h2>
                                <div class="desc" style="font-size:12.5px;color:var(--ukc-muted);margin-bottom:12px;">Acceptées sans créneau.</div>
                                <div class="pool-list">
                                    @forelse ($unplaced as $sub)
                                        <div class="pool-item" @click="openSlot({ id:'', session_id:'', day:'', start:'', room:'', submission_id:'{{ $sub->id }}', title:'' })" title="Créer un créneau pour cette communication">
                                            <div class="t">{{ Str::limit($sub->title, 60) }}</div>
                                            <div class="m">
                                                {{ $sub->participant?->full_name ?: 'Auteur' }}
                                                @if ($sub->track) · {{ $sub->track }} @endif
                                                · {{ $isPaid($sub) ? 'Payé' : 'En attente' }}
                                            </div>
                                        </div>
                                    @empty
                                        <p style="font-size:12.5px;color:var(--ukc-green);">Tout est planifié. 🎉</p>
                                    @endforelse
                                </div>
                                <p class="ukc-hint" style="margin-top:10px;font-size:12px;color:var(--ukc-light);">Cliquez une communication pour la placer sur un créneau.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ============================ ATELIERS ============================ --}}
            <div x-show="tab==='ateliers'" x-cloak data-ajax-root="prog-ateliers">
                <div class="ukc-card">
                    <h2 style="display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:1rem;font-weight:800;margin-bottom:8px;">
                        Ateliers
                        <button type="button" class="ukc-btn ukc-btn--sm" @click="openAtelier(null)"><x-ukc.icon name="plus" :size="14" /> Nouvel atelier</button>
                    </h2>
                    <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">Un atelier regroupe un ou plusieurs axes et contient des sessions. La salle peut être associée plus tard.</div>

                    @forelse ($ateliers as $atelier)
                        @php $room = $atelierRoomName($atelier); @endphp
                        <div class="entity-card">
                            <h3>
                                {{ $atelier->title }}
                                @if ($room)<span class="ukc-pill ukc-pill--ok">{{ $room }}</span>
                                @else<span class="ukc-pill ukc-pill--warn">Salle à définir</span>@endif
                            </h3>
                            <div class="meta">
                                Axes : {{ implode(', ', $atelier->tracks ?: ['-']) }}<br>
                                {{ $atelier->sessions->count() }} session(s) · {{ $creneaux->whereIn('session_id', $atelier->sessions->pluck('id'))->count() }} créneau(x)
                            </div>
                            <div class="chip-list">
                                @foreach ($atelier->tracks ?: [] as $t)<span class="ukc-pill ukc-pill--info">{{ $t }}</span>@endforeach
                            </div>
                            <div class="row-actions">
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm"
                                        @click="openAtelier({ id:{{ $atelier->id }}, title:@js($atelier->title), tracks:@js($atelier->tracks ?: []), room:@js($room ?: '') })">Modifier</button>
                                <button type="button" class="{{ $room ? 'ukc-btn ukc-btn--ghost' : 'ukc-btn' }} ukc-btn--sm"
                                        @click="openRoom({ id:{{ $atelier->id }}, title:@js($atelier->title), room:@js($room ?: '') })">{{ $room ? 'Changer la salle' : 'Associer une salle' }}</button>
                                <form method="POST" action="{{ route('admin.program.ateliers.destroy', $atelier) }}" data-ajax data-ajax-target="prog-ateliers" data-ajax-msg="Atelier supprimé." data-confirm="Supprimer cet atelier ? Ses sessions deviendront plénières.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="ukc-empty"><h3>Aucun atelier</h3><p>Créez un premier atelier pour organiser les sessions.</p></div>
                    @endforelse
                </div>

                {{-- Lien avec le programme de l'édition (fidèle au mockup). --}}
                @if ($edition)
                    <div class="ukc-card">
                        <h2 style="font-size:1rem;font-weight:800;margin-bottom:8px;">Lien avec le programme de l’édition</h2>
                        <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">
                            Le planning et les sessions s’appuient sur le programme défini dans
                            <a href="{{ route('admin.editions.edit', $edition) }}?tab=programme" style="color:var(--ukc-blue);font-weight:600;">Édition → Programme</a>
                            (jours, horaires, type Session / Atelier, intitulés).
                        </div>
                        <div class="settings-box">
                            <h3>Création automatique des sessions</h3>
                            <p style="font-size:13px;color:var(--ukc-muted);margin-bottom:10px;line-height:1.45;">
                                Le système lit les lignes du programme de type « Session » / « Atelier » dont l’axe ou l’intitulé correspond
                                et génère les sessions (créneaux horaires) dans le planning. Les modérateurs et rapporteurs restent à compléter.
                            </p>
                            <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.editions.edit', $edition) }}?tab=programme">Ouvrir le programme de l’édition</a>
                        </div>
                        <p class="ukc-hint" style="font-size:12px;color:var(--ukc-light);margin-top:12px;">La gestion détaillée des salles (amphi, A/B/C, hall) se fait dans l’onglet <strong>Espaces</strong>.</p>
                    </div>
                @endif
            </div>

            {{-- ============================ SESSIONS ============================ --}}
            <div x-show="tab==='sessions'" x-cloak data-ajax-root="prog-sessions">
                <div class="ukc-card">
                    <h2 style="display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:1rem;font-weight:800;margin-bottom:8px;">
                        Sessions
                        <button type="button" class="ukc-btn ukc-btn--sm" @click="openSession(null)"><x-ukc.icon name="plus" :size="14" /> Nouvelle session</button>
                    </h2>
                    <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">Chaque session a un modérateur et un rapporteur (fiches dédiées via Documents).</div>

                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead>
                                <tr><th>Jour</th><th>Atelier</th><th>Session</th><th>Horaire</th><th>Modérateur</th><th>Rapporteur</th><th>Créneaux</th><th>Com.</th><th style="text-align:right;">Actions</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($sessions as $session)
                                    @php
                                        $sessionSlots = $creneaux->where('session_id', $session->id);
                                        $sDay = optional($session->session_day ?? $session->starts_at);
                                    @endphp
                                    <tr>
                                        <td>{{ $sDay?->format('d/m') ?: '-' }}</td>
                                        <td>{{ $session->atelier?->title ?: '- (plénière)' }}</td>
                                        <td><strong>{{ $session->label }}</strong></td>
                                        <td>{{ $session->starts_at?->format('H:i') ?: '-' }}@if ($session->ends_at) – {{ $session->ends_at->format('H:i') }}@endif</td>
                                        <td>@if ($session->moderator_name)<span class="ukc-pill ukc-pill--mod">{{ $session->moderator_name }}</span>@else<span class="ukc-pill ukc-pill--muted">À désigner</span>@endif</td>
                                        <td>@if ($session->rapporteur_name)<span class="ukc-pill ukc-pill--rap">{{ $session->rapporteur_name }}</span>@else<span class="ukc-pill ukc-pill--muted">À désigner</span>@endif</td>
                                        <td>{{ $sessionSlots->count() }}</td>
                                        <td>{{ $sessionSlots->where('submission_id', '>', 0)->count() }}</td>
                                        <td style="text-align:right;">
                                            <div class="ukc-actions">
                                                <a href="{{ route('admin.documents.sessions', $edition) }}" class="ukc-btn--icon" title="Fiches (modération / rapport)"><x-ukc.icon name="file" :size="15" /></a>
                                                <button type="button" class="ukc-btn--icon" title="Modifier"
                                                        @click="openSession({ id:{{ $session->id }}, type:@js($session->atelier_id ? 'atelier' : 'plenary'), atelier_id:@js((string) $session->atelier_id), label:@js($session->label), day:@js($sDay?->format('Y-m-d') ?? ''), start:@js($session->starts_at?->format('H:i') ?? ''), end:@js($session->ends_at?->format('H:i') ?? ''), room:@js($session->room ?? ''), moderator:@js($session->moderator_name ?? ''), rapporteur:@js($session->rapporteur_name ?? '') })"><x-ukc.icon name="pen" :size="15" /></button>
                                                <form method="POST" action="{{ route('admin.program.sessions.destroy', $session) }}" data-ajax data-ajax-target="prog-sessions" data-ajax-msg="Session supprimée." data-confirm="Supprimer cette session ?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="ukc-btn--icon" title="Supprimer"><x-ukc.icon name="trash" :size="15" /></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" style="text-align:center;color:var(--ukc-muted);padding:20px;">Aucune session.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ============================ CRÉNEAUX (liste) ============================ --}}
            <div x-show="tab==='list'" x-cloak data-ajax-root="prog-slots">
                <div class="ukc-card">
                    <h2 style="display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:1rem;font-weight:800;margin-bottom:14px;">
                        Créneaux
                        <button type="button" class="ukc-btn ukc-btn--sm" @click="openSlot(null)"><x-ukc.icon name="plus" :size="14" /> Nouveau créneau</button>
                    </h2>
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead>
                                <tr><th>Jour</th><th>Horaire</th><th>Atelier / Session</th><th>Intitulé</th><th>Type</th><th>Code</th><th style="text-align:right;">Actions</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($creneaux as $slot)
                                    <tr>
                                        <td>{{ $slot->starts_at?->format('d/m') ?: '-' }}</td>
                                        <td>{{ $slot->starts_at?->format('H:i') ?: '-' }}</td>
                                        <td>{{ $slot->session?->atelier?->title ? $slot->session->atelier->title.' · ' : '' }}{{ $slot->session_label ?: '-' }}</td>
                                        <td>@if ($slot->submission){{ Str::limit($slot->submission->title, 45) }}@else<em style="color:var(--ukc-light);">Créneau libre</em>@endif</td>
                                        <td>
                                            @if ($slot->submission)<span class="ukc-pill ukc-pill--{{ $slot->submission->type === 'poster' ? 'poster' : 'oral' }}">{{ $slot->submission->type === 'poster' ? 'Poster' : 'Orale' }}</span>
                                            @else<span class="ukc-pill ukc-pill--muted">Libre</span>@endif
                                        </td>
                                        <td>{{ $slot->submission?->ref_code ?: '-' }}</td>
                                        <td style="text-align:right;">
                                            <div class="ukc-actions">
                                                <button type="button" class="ukc-btn--icon" title="Modifier"
                                                        @click="openSlot({ id:{{ $slot->id }}, session_id:@js((string) $slot->session_id), day:@js($slot->starts_at?->format('Y-m-d') ?? ''), start:@js($slot->starts_at?->format('H:i') ?? ''), room:@js($slot->room ?? ''), submission_id:@js((string) $slot->submission_id), title:@js($slot->session_label ?? '') })"><x-ukc.icon name="pen" :size="15" /></button>
                                                <form method="POST" action="{{ route('admin.program.slots.destroy', $slot) }}" data-ajax data-ajax-target="prog-slots" data-ajax-msg="Créneau supprimé." data-confirm="Supprimer ce créneau ?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="ukc-btn--icon" title="Supprimer"><x-ukc.icon name="trash" :size="15" /></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" style="text-align:center;color:var(--ukc-muted);padding:20px;">Aucun créneau.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ============================ ESPACES ============================ --}}
            <div x-show="tab==='spaces'" x-cloak data-ajax-root="prog-spaces">
                <div class="ukc-card">
                    <h2 style="display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:1rem;font-weight:800;margin-bottom:8px;">
                        Gestion des espaces
                        <button type="button" class="ukc-btn ukc-btn--sm" @click="openSpace()"><x-ukc.icon name="plus" :size="14" /> Ajouter un espace</button>
                    </h2>
                    <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">Amphithéâtres, salles et hall posters. Un espace peut être associé à un atelier.</div>

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
                                {{ implode(' · ', $bits) ?: '-' }}
                            </div>
                            <div class="row-actions">
                                <form method="POST" action="{{ route('admin.program.spaces.destroy', $i) }}?edition_id={{ $edition_id }}" data-ajax data-ajax-target="prog-spaces" data-ajax-msg="Espace supprimé." data-confirm="Supprimer cet espace ?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">Supprimer</button>
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

            {{-- ============================ NON PLANIFIÉES ============================ --}}
            <div x-show="tab==='unscheduled'" x-cloak>
                <div class="ukc-card">
                    <h2 style="font-size:1rem;font-weight:800;margin-bottom:6px;">Communications acceptées non planifiées</h2>
                    <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">Priorité à l'affectation auto pour les communications <strong>payées</strong> dont l'axe correspond à un atelier avec créneau libre.</div>
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
                                            <button type="button" class="ukc-btn ukc-btn--sm" @click="openSlot({ id:'', session_id:'', day:'', start:'', room:'', submission_id:'{{ $sub->id }}', title:'' })">Assigner</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="text-align:center;color:var(--ukc-green);padding:20px;">Toutes les communications acceptées sont planifiées. 🎉</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ============================ AFFECTATION AUTO ============================ --}}
            <div x-show="tab==='auto'" x-cloak>
                <div class="ukc-card" style="max-width:720px;">
                    <h2 style="font-size:1rem;font-weight:800;margin-bottom:6px;">Affectation automatique des créneaux</h2>
                    <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">
                        Dès qu'une communication est <strong>acceptée</strong> et le <strong>paiement</strong> validé, le système peut la placer
                        sur un créneau libre de l'atelier correspondant à son axe.
                    </div>

                    <form method="POST" action="{{ route('admin.program.config') }}?edition_id={{ $edition_id }}">
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
                        <form method="POST" action="{{ route('admin.program.slots.generate') }}?edition_id={{ $edition_id }}">
                            @csrf
                            <button type="submit" class="ukc-btn ukc-btn--sm">
                                <x-ukc.icon name="plus" :size="15" /> Générer les créneaux depuis les sessions
                            </button>
                        </form>
                    </div>

                    <div class="settings-box" style="margin-top:16px;">
                        <h3>Lancer une passe maintenant</h3>
                        <form method="POST" action="{{ route('admin.program.slots.auto') }}?edition_id={{ $edition_id }}">
                            @csrf
                            <input type="hidden" name="match_axis" value="1">
                            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                                <button type="submit" class="ukc-btn ukc-btn--green" @disabled($stats['toPlan'] === 0 || $creneaux->where('submission_id', 0)->isEmpty())>
                                    <x-ukc.icon name="check-circle" :size="16" /> Lancer une passe d'affectation
                                </button>
                                <span style="font-size:12.5px;color:var(--ukc-muted);">{{ $stats['toPlan'] }} à planifier · {{ $creneaux->where('submission_id', 0)->count() }} créneau(x) libre(s)</span>
                            </div>
                            @if ($creneaux->where('submission_id', 0)->isEmpty())
                                <p class="ukc-hint" style="font-size:12px;color:var(--ukc-light);margin-top:8px;">Aucun créneau libre : générez-les d'abord ci-dessus, ou ajoutez-en manuellement.</p>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            {{-- ============================ PARAMÈTRES ============================ --}}
            <div x-show="tab==='params'" x-cloak>
                <form method="POST" action="{{ route('admin.program.config') }}?edition_id={{ $edition_id }}" class="ukc-card" style="max-width:720px;">
                    @csrf
                    {{-- On renvoie aussi les règles auto pour ne pas les écraser --}}
                    <input type="hidden" name="auto_enabled" value="{{ $config['auto']['enabled'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_only_paid" value="{{ $config['auto']['only_paid'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_match_axis" value="{{ $config['auto']['match_axis'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_notify" value="{{ $config['auto']['notify'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_use_ai" value="{{ $config['auto']['use_ai'] ? 1 : 0 }}">
                    <input type="hidden" name="auto_order" value="{{ $config['auto']['order'] }}">
                    <input type="hidden" name="auto_fallback" value="{{ $config['auto']['fallback'] }}">

                    <h2 style="font-size:1rem;font-weight:800;margin-bottom:6px;">Paramètres des créneaux</h2>
                    <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">Durées, pauses et règles de planification et d'affichage public.</div>

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

        {{-- ============================ MODALES ============================ --}}
        @if ($edition)
            @include('admin.program._modals')
        @endif
    </div>
</x-layouts.admin>
