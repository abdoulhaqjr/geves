@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    // Communication payée ? (colonne Paiement de « Non planifiées »)
    $isPaid = fn ($sub) => (bool) optional($sub->participant?->participationPayments)->firstWhere('status', 'paid');
    // Durée d'un créneau selon le type de communication (réglages).
    $slotDuration = fn ($sub) => (int) (($sub && $sub->type === 'poster') ? $config['params']['poster_min'] : $config['params']['oral_min']);
    $atelierRoomName = fn ($atelier) => $atelierRooms[(string) $atelier->id] ?? ($atelier->sessions->firstWhere('room', '!=', null)?->room);
@endphp

<x-layouts.admin title="Programme" subtitle="Ateliers, sessions, créneaux et salles de l’édition">
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
        <div class="page-head" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px;">
            <div class="ukc-field" style="min-width:320px;max-width:460px;margin:0;">
                <label for="edition_id">Édition</label>
                <select id="edition_id" name="edition_id"
                        onchange="window.location = '{{ route('admin.program.index') }}?edition_id=' + this.value + '&tab={{ $tab }}'">
                    <option value="">- Choisir -</option>
                    @foreach ($editions as $ed)
                        <option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($edition_id === '')
            <div class="ukc-empty"><h3>Choisissez une édition</h3></div>
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



            {{-- ============================ PLANNING ============================ --}}
            {{-- Agenda : le calendrier et la vue par jour décrivent la même chose,
                 ils sont donc côte à côte (dépliable) plutôt que sur deux pages. --}}
            <div x-data="{ agenda: false }" @if ($tab !== 'planning') style="display:none;" @endif>
                <div style="display:flex;justify-content:flex-end;margin-bottom:10px;">
                    <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="agenda = ! agenda; $nextTick(() => window.__programCal && window.__programCal.updateSize())">
                        <x-ukc.icon name="calendar" :size="15" />
                        <span x-text="agenda ? 'Masquer l’agenda' : 'Voir l’agenda'"></span>
                    </button>
                </div>
                <div class="ukc-card" x-show="agenda" x-cloak style="margin-bottom:18px;">
                    @if ($calendarEvents->isEmpty())
                        <p class="ukc-hint" style="margin-bottom:12px;">Aucune session horodatée pour l’instant - ajoutez des horaires aux sessions (jour, début, fin) pour les voir apparaître dans l’agenda.</p>
                    @endif
                    <script type="application/json" id="programEvents">{!! $calendarEvents->toJson() !!}</script>
                    <div id="programCalendar"></div>
                </div>
            </div>
            {{-- ============================ PLANNING ============================ --}}
            <div @if ($tab !== 'planning') style="display:none;" @endif>
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
                                <h2><span><x-ukc.icon name="inbox" :size="18" /> À planifier</span></h2>
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
                                        <p style="font-size:12.5px;color:var(--ukc-green);display:flex;align-items:center;gap:6px;"><x-ukc.icon name="check-circle" :size="15" /> Tout est planifié.</p>
                                    @endforelse
                                </div>
                                <p class="ukc-hint" style="margin-top:10px;font-size:12px;color:var(--ukc-light);">Cliquez une communication pour la placer sur un créneau.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ============================ ATELIERS ============================ --}}
            @php
                // Options de filtres (dérivées des ateliers de l'édition).
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
                                {{ $atelier->sessions->count() }} session(s) · {{ $creneaux->whereIn('session_id', $atelier->sessions->pluck('id'))->count() }} créneau(x)
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

                {{-- Lien avec le programme de l'édition (fidèle au mockup). --}}
                @if ($edition)
                    <div class="ukc-card">
                        <h2>Lien avec le programme de l’édition</h2>
                        <div class="desc">
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
            @php
                // Options de filtres (dérivées des sessions de l'édition).
                $sessDays = $sessions->map(fn ($s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d'))->filter()->unique()->sort()->values();
                $sessAteliers = $sessions->map(fn ($s) => $s->atelier?->title)->filter()->unique()->sort()->values();
                $sessRooms = $sessions->map(fn ($s) => $s->room)->filter()->unique()->sort()->values();
            @endphp
            <div @if ($tab !== 'sessions') style="display:none;" @endif data-ajax-root="prog-sessions">
                <div class="ukc-card" x-data="tableFilters(['day', 'atelier', 'room', 'type', 'moderation'])">
                    <h2>
                        <span><x-ukc.icon name="layers" :size="18" /> Sessions</span>
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="openSession(null)"><x-ukc.icon name="plus" :size="14" /> Nouvelle session</button>
                    </h2>
                    <div class="desc">Chaque session a un modérateur et un rapporteur (fiches dédiées via Documents).</div>

                    {{-- Filtres du tableau (client-side, instantanés). --}}
                    <div class="filter-bar filter-bar--inline" style="margin:4px 0 14px;">
                        <select x-model="f.day" aria-label="Filtrer par jour">
                            <option value="">Tous les jours</option>
                            @foreach ($sessDays as $d)<option value="{{ $d }}">{{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('D d M') }}</option>@endforeach
                        </select>
                        <select x-model="f.type" aria-label="Filtrer par type">
                            <option value="">Tous les types</option>
                            <option value="plenary">Plénière</option>
                            <option value="atelier">Atelier</option>
                        </select>
                        <select x-model="f.atelier" aria-label="Filtrer par atelier">
                            <option value="">Tous les ateliers</option>
                            @foreach ($sessAteliers as $a)<option value="{{ $a }}">{{ Str::limit($a, 40) }}</option>@endforeach
                        </select>
                        <select x-model="f.room" aria-label="Filtrer par salle">
                            <option value="">Toutes les salles</option>
                            @foreach ($sessRooms as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                        </select>
                        <select x-model="f.moderation" aria-label="Filtrer par modération">
                            <option value="">Modération : toutes</option>
                            <option value="assigned">Modérateur désigné</option>
                            <option value="pending">À désigner</option>
                        </select>
                        <input type="search" x-model="f.q" placeholder="Session, modérateur, rapporteur…" style="min-width:190px;flex:1;">
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" x-show="active" x-cloak @click="reset()">Réinitialiser</button>
                    </div>
                    <div style="font-size:12.5px;color:var(--ukc-muted);margin-bottom:8px;">
                        <span x-text="visibleIds.length"></span> session(s) affichée(s)<template x-if="active"> sur {{ $sessions->count() }}</template>.
                    </div>

                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead>
                                <tr><th>Jour</th><th>Atelier</th><th>Session</th><th>Horaire</th><th>Modérateur</th><th>Rapporteur</th><th class="num">Créneaux</th><th class="num">Com.</th><th style="text-align:right;">Actions</th></tr>
                            </thead>
                            @forelse ($sessions as $session)
                                @php
                                    $sessionSlots = $creneaux->where('session_id', $session->id);
                                    $sDay = optional($session->session_day ?? $session->starts_at);
                                    $sessType = $session->atelier_id ? 'atelier' : 'plenary';
                                    $sessSearch = Str::lower(collect([$session->label, $session->atelier?->title, $session->moderator_name, $session->rapporteur_name, $session->room])->filter()->implode(' '));
                                @endphp
                                {{-- Un <tbody> par séance : il porte l'état d'ouverture de la
                                     visio, qui s'affiche sur une ligne à part. --}}
                                <tbody x-data="{ visio: false }" x-show="visibleIds.includes({{ $session->id }})" data-filter-row
                                       data-id="{{ $session->id }}" data-day="{{ $sDay?->format('Y-m-d') }}"
                                       data-atelier="{{ $session->atelier?->title }}" data-room="{{ $session->room }}"
                                       data-type="{{ $sessType }}" data-moderation="{{ $session->moderator_name ? 'assigned' : 'pending' }}"
                                       data-text="{{ $sessSearch }}">
                                    <tr>
                                        <td>{{ $sDay?->format('d/m') ?: '-' }}</td>
                                        <td>{{ $session->atelier?->title ?: '- (plénière)' }}</td>
                                        <td><strong>{{ $session->label }}</strong></td>
                                        <td>{{ $session->starts_at?->format('H:i') ?: '-' }}@if ($session->ends_at) – {{ $session->ends_at->format('H:i') }}@endif</td>
                                        <td>@if ($session->moderator_name)<span class="ukc-pill ukc-pill--mod">{{ $session->moderator_name }}</span>@else<span class="ukc-pill ukc-pill--muted">À désigner</span>@endif</td>
                                        <td>@if ($session->rapporteur_name)<span class="ukc-pill ukc-pill--rap">{{ $session->rapporteur_name }}</span>@else<span class="ukc-pill ukc-pill--muted">À désigner</span>@endif</td>
                                        <td class="num">{{ $sessionSlots->count() }}</td>
                                        <td class="num">{{ $sessionSlots->where('submission_id', '>', 0)->count() }}</td>
                                        <td style="text-align:right;">
                                            <div class="ukc-actions">
                                                <a href="{{ route('admin.documents.sessions', $edition) }}" class="ukc-btn--icon" title="Fiches (modération / rapport)"><x-ukc.icon name="file" :size="15" /></a>
                                                <button type="button" class="ukc-btn--icon" title="Visioconférence"
                                                        @click="visio = ! visio" :class="{ 'active': visio }"><x-ukc.icon name="video" :size="15" /></button>
                                                <button type="button" class="ukc-btn--icon" title="Modifier"
                                                        @click="openSession({ id:{{ $session->id }}, type:@js($session->atelier_id ? 'atelier' : 'plenary'), atelier_id:@js((string) $session->atelier_id), label:@js($session->label), day:@js($sDay?->format('Y-m-d') ?? ''), start:@js($session->starts_at?->format('H:i') ?? ''), end:@js($session->ends_at?->format('H:i') ?? ''), room:@js($session->room ?? ''), moderator:@js($session->moderator_name ?? ''), rapporteur:@js($session->rapporteur_name ?? '') })"><x-ukc.icon name="pen" :size="15" /></button>
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
                                    <tr><td colspan="9" style="text-align:center;color:var(--ukc-muted);padding:20px;">Aucune session.</td></tr>
                                </tbody>
                            @endforelse
                            @if ($sessions->isNotEmpty())
                                <tbody x-show="visibleIds.length === 0">
                                    <tr><td colspan="9" style="text-align:center;color:var(--ukc-muted);padding:20px;">Aucune session ne correspond aux filtres. <button type="button" class="ukc-link" @click="reset()">Réinitialiser</button></td></tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            {{-- ============================ CRÉNEAUX (liste) ============================ --}}
            @php
                // Options de filtres (dérivées des créneaux de l'édition).
                $slotDays = $creneaux->map(fn ($s) => $s->starts_at?->format('Y-m-d'))->filter()->unique()->sort()->values();
                $slotAteliers = $creneaux->map(fn ($s) => $s->session?->atelier?->title)->filter()->unique()->sort()->values();
                $slotRooms = $creneaux->map(fn ($s) => $s->room)->filter()->unique()->sort()->values();
            @endphp
            <div @if ($tab !== 'creneaux') style="display:none;" @endif data-ajax-root="prog-slots">
                <div class="ukc-card" x-data="tableFilters(['day', 'atelier', 'room', 'status', 'type'])">
                    <h2>
                        <span><x-ukc.icon name="clock" :size="18" /> Créneaux</span>
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="openSlot(null)"><x-ukc.icon name="plus" :size="14" /> Nouveau créneau</button>
                    </h2>

                    {{-- Filtres du tableau (client-side, instantanés). --}}
                    <div class="filter-bar filter-bar--inline" style="margin:4px 0 14px;">
                        <select x-model="f.day" aria-label="Filtrer par jour">
                            <option value="">Tous les jours</option>
                            @foreach ($slotDays as $d)<option value="{{ $d }}">{{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('D d M') }}</option>@endforeach
                        </select>
                        <select x-model="f.atelier" aria-label="Filtrer par atelier">
                            <option value="">Tous les ateliers</option>
                            @foreach ($slotAteliers as $a)<option value="{{ $a }}">{{ Str::limit($a, 40) }}</option>@endforeach
                        </select>
                        <select x-model="f.room" aria-label="Filtrer par salle">
                            <option value="">Toutes les salles</option>
                            @foreach ($slotRooms as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                        </select>
                        <select x-model="f.status" aria-label="Filtrer par statut">
                            <option value="">Assignés & libres</option>
                            <option value="assigned">Assignés</option>
                            <option value="free">Libres</option>
                        </select>
                        <select x-model="f.type" aria-label="Filtrer par type">
                            <option value="">Tous les types</option>
                            <option value="oral">Orale</option>
                            <option value="poster">Poster</option>
                        </select>
                        <input type="search" x-model="f.q" placeholder="Intitulé, titre, code…" style="min-width:190px;flex:1;">
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" x-show="active" x-cloak @click="reset()">Réinitialiser</button>
                    </div>
                    <div style="font-size:12.5px;color:var(--ukc-muted);margin-bottom:8px;">
                        <span x-text="visibleIds.length"></span> créneau(x) affiché(s)<template x-if="active"> sur {{ $creneaux->count() }}</template>.
                    </div>

                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead>
                                <tr><th>Jour</th><th>Horaire</th><th>Salle</th><th>Atelier / Session</th><th>Intitulé</th><th>Type</th><th>Code</th><th style="text-align:right;">Actions</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($creneaux as $slot)
                                    @php
                                        $sType = $slot->submission?->type === 'poster' ? 'poster' : ($slot->submission ? 'oral' : '');
                                        $sStatus = $slot->submission ? 'assigned' : 'free';
                                        $sSearch = Str::lower(collect([$slot->session_label, $slot->session?->atelier?->title, $slot->submission?->title, $slot->submission?->ref_code, $slot->room])->filter()->implode(' '));
                                    @endphp
                                    <tr x-show="visibleIds.includes({{ $slot->id }})" data-filter-row
                                        data-id="{{ $slot->id }}"
                                        data-day="{{ $slot->starts_at?->format('Y-m-d') }}" data-atelier="{{ $slot->session?->atelier?->title }}"
                                        data-room="{{ $slot->room }}" data-status="{{ $sStatus }}" data-type="{{ $sType }}"
                                        data-text="{{ $sSearch }}">
                                        <td>{{ $slot->starts_at?->format('d/m') ?: '-' }}</td>
                                        <td>{{ $slot->starts_at?->format('H:i') ?: '-' }}</td>
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
                                    <tr><td colspan="8" style="text-align:center;color:var(--ukc-muted);padding:20px;">Aucun créneau.</td></tr>
                                @endforelse
                                @if ($creneaux->isNotEmpty())
                                    <tr x-show="visibleIds.length === 0"><td colspan="8" style="text-align:center;color:var(--ukc-muted);padding:20px;">Aucun créneau ne correspond aux filtres. <button type="button" class="ukc-link" @click="reset()">Réinitialiser</button></td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
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
                                {{ implode(' · ', $bits) ?: '-' }}
                            </div>
                            <div class="ukc-actions row-actions">
                                <form method="POST" action="{{ route('admin.program.spaces.destroy', $i) }}?edition_id={{ $edition_id }}" data-ajax data-ajax-target="prog-spaces" data-ajax-msg="Espace supprimé." data-confirm="Supprimer cet espace ?">
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
            <div @if ($tab !== 'options') style="display:none;" @endif>
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

        {{-- ============================ MODALES ============================ --}}
        @if ($edition)
            @include('admin.program._modals')
        @endif
    </div>
</x-layouts.admin>
