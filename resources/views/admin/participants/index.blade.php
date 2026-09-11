<x-layouts.admin title="Participants" subtitle="Inscrits, rôles, paiements & invitations">
    <x-slot:actions>
        @if ($edition_id !== '')
            <form method="POST" action="{{ route('admin.participants.invite-bulk') }}"
                  onsubmit="return confirm('Envoyer une invitation à tous les payés non encore invités ?');">
                @csrf
                <input type="hidden" name="edition_id" value="{{ $edition_id }}">
                <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                    <x-ukc.icon name="mail" :size="16" /> Inviter les payés non invités
                </button>
            </form>
        @endif
    </x-slot:actions>

    <div class="admin-page admin-participants">
        {{-- Filtres (GET) --}}
        <form method="GET" class="filter-bar">
            <select name="edition_id" onchange="this.form.submit()" data-tom data-placeholder="Édition…">
                <option value="">— Édition —</option>
                @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
            </select>
            <input type="search" name="search" value="{{ $search }}" placeholder="Nom ou e-mail…" style="min-width:180px;"
                   onkeydown="if(event.key==='Enter'){this.form.submit();}">
            <select name="role" onchange="this.form.submit()">
                <option value="">Tous les rôles</option>
                @foreach ($roles as $key => $label)<option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>@endforeach
            </select>
        </form>

        <div class="ukc-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Participants</div><div class="val blue">{{ $stats['total'] }}</div><x-ukc.trend :trend="$trendTotal" period="7 j" /></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="card" :size="20" /></div><div class="lbl">Ont payé</div><div class="val green">{{ $stats['paid'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Pointés (accueil)</div><div class="val">{{ $stats['checkedIn'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="pen" :size="20" /></div><div class="lbl">Auteurs</div><div class="val">{{ $stats['authors'] }}</div></div>
        </div>

        <div class="ukc-card" style="padding:0;">
            @if ($participants->count())
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead>
                            <tr>
                                <x-ukc.th sort="last_name" :current="$sort" :dir="$dir">Participant</x-ukc.th>
                                <x-ukc.th sort="role" :current="$sort" :dir="$dir">Rôle</x-ukc.th>
                                <th>Paiement</th><th>Invité</th><th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($participants as $p)
                                @php($paid = $p->payments->where('status', 'paid')->isNotEmpty())
                                <tr>
                                    <td>
                                        <div class="title">{{ $p->full_name }}</div>
                                        <div class="meta">{{ $p->email }}</div>
                                    </td>
                                    <td>
                                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                            <span class="ukc-pill ukc-pill--info">{{ $p->roleLabel() }}</span>
                                            @foreach (($p->user_id ? ($editionRoles[$p->user_id] ?? []) : []) as $r)
                                                <span class="ukc-pill ukc-pill--{{ \App\Support\StaffRoles::pill($r) }}">{{ \App\Support\StaffRoles::label($r) }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        @if ($paid)<span class="ukc-pill ukc-pill--ok">Réglé</span>
                                        @else<span class="ukc-pill ukc-pill--muted">Non</span>@endif
                                    </td>
                                    <td>
                                        @if ($p->isInvited())<span class="ukc-pill ukc-pill--ok">Oui</span>
                                        @else<span class="ukc-pill ukc-pill--muted">Non</span>@endif
                                    </td>
                                    <td style="text-align:right;white-space:nowrap;">
                                        <div class="ukc-actions">
                                            <a href="{{ route('admin.participants.preview', $p) }}"
                                               class="ukc-btn ukc-btn--ghost ukc-btn--sm" title="Aperçu du dossier, en lecture seule (vous restez admin)">
                                                <x-ukc.icon name="eye" :size="14" /> Sa page
                                            </a>
                                            @can('manage_colloques')
                                                @if ($p->user)
                                                    <form method="POST" action="{{ route('admin.participants.impersonate', $p) }}"
                                                          onsubmit="return confirm('Se connecter en tant que {{ addslashes($p->full_name) }} ?\n\nVous agirez réellement dans son espace participant jusqu’à « Quitter l’impersonation ».');">
                                                        @csrf
                                                        <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm"
                                                                title="Se connecter en tant que ce participant (impersonation)">
                                                            <x-ukc.icon name="login" :size="14" /> En tant que
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                            <form method="POST" action="{{ route('admin.participants.invite', $p) }}">
                                                @csrf
                                                <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm" @disabled(blank($p->email))>
                                                    <x-ukc.icon name="mail" :size="14" /> {{ $p->isInvited() ? 'Relancer' : 'Inviter' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:14px 18px;">{{ $participants->links() }}</div>
            @else
                <div class="ukc-empty"><h3>Aucun participant</h3><p>Sélectionnez une édition ou ajustez les filtres.</p></div>
            @endif
        </div>
    </div>
</x-layouts.admin>
