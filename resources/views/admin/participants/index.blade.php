<x-layouts.admin title="Participants" subtitle="Inscrits, rôles, paiements & invitations">
    <x-slot:actions>
        @if ($event_id !== '')
            <form method="POST" action="{{ route('admin.participants.invite-bulk') }}"
                  data-ajax data-ajax-target="participants" data-ajax-msg="Invitations envoyées."
                  data-confirm="Envoyer une invitation à tous les payés non encore invités ?">
                @csrf
                <input type="hidden" name="event_id" value="{{ $event_id }}">
                <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                    <x-ukc.icon name="mail" :size="16" /> Inviter les payés non invités
                </button>
            </form>
        @endif
    </x-slot:actions>

    <div class="admin-page admin-participants">
        {{-- Sélecteur d'événement en tête, AVANT les indicateurs (comme la page Projet). --}}
        <div style="max-width:340px;margin-bottom:16px;">
            <div class="ukc-field" style="margin:0;">
                <label>Événement</label>
                <select onchange="window.location='{{ route('admin.participants.index') }}?event_id='+this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($events as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        <div class="ukc-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Participants</div><div class="val blue">{{ $stats['total'] }}</div><x-ukc.trend :trend="$trendTotal" period="7 j" /></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="pen" :size="20" /></div><div class="lbl">Communicants</div><div class="val">{{ $stats['communicants'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="user" :size="20" /></div><div class="lbl">Participants simples</div><div class="val">{{ $stats['attendees'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="card" :size="20" /></div><div class="lbl">Ont payé</div><div class="val green">{{ $stats['paid'] }}</div></div>
        </div>

        {{-- Filtres (GET) - une seule ligne ; l'événement est reportée en champ caché
             pour qu'un filtre ne réélargisse pas le périmètre. --}}
        {{-- ⚠️ Filtrage en AJAX (`data-filter`, cf. app.js) : la recherche
             s'applique à la frappe, sans valider ni recharger la page. --}}
        <form method="GET" class="filter-bar filter-bar--inline" data-filter="participants">
            <input type="hidden" name="event_id" value="{{ $event_id }}">
            <select name="role">
                <option value="">Tous les rôles</option>
                @foreach ($roles as $key => $label)<option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="participation_mode" aria-label="Filtrer par mode de participation" style="min-width:250px;">
                <option value="">Tous les modes de participation</option>
                <option value="online" @selected($participationMode === 'online')>En ligne</option>
                <option value="onsite" @selected($participationMode === 'onsite')>En présentiel</option>
            </select>
            <select name="paid" title="Frais de participation">
                <option value="">Paiement : tous</option>
                <option value="1" @selected($paid === '1')>Ont payé</option>
                <option value="0" @selected($paid === '0')>N’ont pas payé</option>
            </select>
            <select name="invited" title="Invitation envoyée">
                <option value="">Invitation : tous</option>
                <option value="1" @selected($invited === '1')>Invités</option>
                <option value="0" @selected($invited === '0')>Non invités</option>
            </select>
            <input type="search" name="search" value="{{ $search }}" placeholder="Nom ou e-mail…" style="min-width:200px;flex:1;">
            {{-- Le lien dépend des filtres : sa propre région échangée. --}}
            <span data-filter-part="participants-reset">
                @if ($search !== '' || $role !== '' || $participationMode !== '' || $paid !== '' || $invited !== '')
                    <a href="{{ route('admin.participants.index', ['event_id' => $event_id]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
                @endif
            </span>
        </form>

        <div class="ukc-card" style="padding:0;" data-ajax-root="participants" data-filter-part="participants-table">
            @if ($participants->count())
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead>
                            <tr>
                                <x-ukc.th sort="last_name" :current="$sort" :dir="$dir">Participant</x-ukc.th>
                                <x-ukc.th sort="role" :current="$sort" :dir="$dir">Rôle</x-ukc.th>
                                <th>Mode de participation</th><th>Paiement</th><th>Invité</th><th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($participants as $person)
                                @php($p = $person['participant'])
                                <tr>
                                    <td>
                                        <div class="title">{{ $person['name'] }}</div>
                                        <div class="meta">{{ $person['email'] }}</div>
                                    </td>
                                    <td>
                                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                            <span class="ukc-pill ukc-pill--info">{{ $person['role'] === 'attendee' ? 'Participant simple' : 'Communicant' }}</span>
                                            @if ($person['role'] !== 'attendee')
                                                <span class="ukc-pill ukc-pill--muted">{{ $person['role'] === 'author' ? 'Auteur correspondant' : 'Co-auteur uniquement' }}</span>
                                            @endif
                                            @foreach (($person['userId'] ? ($editionRoles[$person['userId']] ?? []) : []) as $r)
                                                <span class="ukc-pill ukc-pill--{{ \App\Support\StaffRoles::pill($r) }}">{{ \App\Support\StaffRoles::label($r) }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td style="white-space:nowrap;">{{ $person['participationModeLabel'] ?? 'Non renseigné' }}</td>
                                    <td>
                                        @if ($person['paid'])<span class="ukc-pill ukc-pill--ok">Réglé</span>
                                        @else<span class="ukc-pill ukc-pill--muted">Non</span>@endif
                                    </td>
                                    <td>
                                        @if ($person['invited'])<span class="ukc-pill ukc-pill--ok">Oui</span>
                                        @else<span class="ukc-pill ukc-pill--muted">Non</span>@endif
                                    </td>
                                    <td style="text-align:right;white-space:nowrap;">
                                        <div class="ukc-actions">
                                            <a href="{{ route('admin.participants.preview', $p) }}"
                                               class="ukc-btn ukc-btn--ghost ukc-btn--sm" title="Aperçu du dossier, en lecture seule (vous restez admin)">
                                                <x-ukc.icon name="eye" :size="14" /> Aperçu
                                            </a>
                                            <form method="POST" action="{{ route('admin.participants.invite', $p) }}"
                                                  data-ajax data-ajax-target="participants" data-ajax-msg="Invitation envoyée.">
                                                @csrf
                                                <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm" @disabled(blank($person['email']))>
                                                    <x-ukc.icon name="mail" :size="14" /> {{ $person['invited'] ? 'Relancer' : 'Inviter' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:12.5px;color:var(--ukc-muted);">
                        {{ $participants->firstItem() }}-{{ $participants->lastItem() }} sur {{ $participants->total() }} participant(s)
                    </span>
                    @if ($participants->hasPages())<div>{{ $participants->links() }}</div>@endif
                </div>
            @else
                <div class="ukc-empty"><h3>Aucun participant</h3><p>Sélectionnez un événement ou ajustez les filtres.</p></div>
            @endif
        </div>
    </div>
</x-layouts.admin>
