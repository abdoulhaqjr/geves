<x-layouts.admin title="Utilisateurs" subtitle="Comptes, rôles UK Colloques et permissions">
    <x-slot:actions>
        <a href="{{ route('admin.users.create') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Associer un utilisateur
        </a>
    </x-slot:actions>

    <div class="admin-page admin-users" x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'list' }">
        <div class="ukc-nav-docs">
            <button type="button" @click="tab='list'" :class="{ active: tab==='list' }">Liste des utilisateurs</button>
            <button type="button" @click="tab='roles'" :class="{ active: tab==='roles' }">Rôles</button>
            <button type="button" @click="tab='access'" :class="{ active: tab==='access' }">Accès &amp; permissions</button>
        </div>

        {{-- ===== LISTE ===== --}}
        <div x-show="tab==='list'">
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Utilisateurs</div><div class="val blue">{{ $stats['total'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="shield" :size="20" /></div><div class="lbl">Admins</div><div class="val">{{ $stats['admins'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="mic" :size="20" /></div><div class="lbl">Relecteurs</div><div class="val">{{ $stats['reviewers'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="calendar" :size="20" /></div><div class="lbl">Organisateurs</div><div class="val">{{ $stats['organizers'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="bell" :size="20" /></div><div class="lbl">Support</div><div class="val">{{ $stats['support'] }}</div></div>
            </div>

            <form method="GET" class="filter-bar">
                <select name="role" onchange="this.form.submit()">
                    <option value="">Tous les rôles</option>
                    @foreach ($roleOptions as $key => $label)<option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>@endforeach
                </select>
                <select name="status" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected($status === 'active')>Actif</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactif</option>
                </select>
                <input type="search" name="search" value="{{ $search }}" placeholder="Nom, e-mail, IP…" style="min-width:220px;"
                       onkeydown="if(event.key==='Enter'){this.form.submit();}">
                @if ($role !== '' || $status !== '' || $search !== '')
                    <a href="{{ route('admin.users.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
                @endif
            </form>

            <div class="ukc-card" style="padding:0;">
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead>
                            <tr>
                                <x-ukc.th sort="name" :current="$sort" :dir="$dir">Utilisateur</x-ukc.th>
                                <th>Rôle(s) UKC</th>
                                <x-ukc.th sort="email" :current="$sort" :dir="$dir">E-mail</x-ukc.th>
                                <x-ukc.th sort="last_login_at" :current="$sort" :dir="$dir">Dernière connexion</x-ukc.th>
                                <th>IP</th>
                                <x-ukc.th sort="is_active" :current="$sort" :dir="$dir">Statut</x-ukc.th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $u)
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="av">{{ \Illuminate\Support\Str::of($u->name)->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->take(2)->implode('') ?: 'U' }}</div>
                                            <div>
                                                <strong>{{ $u->name }}</strong>
                                                <span>{{ $u->email }}</span>
                                                @php($lvl = $u->access_level)
                                                <span class="ukc-pill ukc-pill--{{ $lvl === 'super_admin' ? 'ok' : ($lvl === 'colloque_admin' ? 'info' : 'muted') }}" style="margin-top:4px;">{{ \App\Models\User::accessLevels()[$lvl] ?? 'Utilisateur' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="role-pills">
                                            @forelse ($u->roles as $r)
                                                <span class="ukc-pill ukc-pill--{{ \App\Support\StaffRoles::pill($r->name) }}">{{ \App\Support\StaffRoles::label($r->name) }}</span>
                                            @empty
                                                <span class="ukc-pill ukc-pill--muted">—</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        @if ($u->hasVerifiedEmail())
                                            <span class="ukc-pill ukc-pill--ok">Vérifié</span>
                                        @else
                                            <span class="ukc-pill ukc-pill--warn">Non vérifié</span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap;">{{ $u->last_login_at?->translatedFormat('d/m/Y · H:i') ?? 'Jamais' }}</td>
                                    <td><span class="ip-cell">{{ $u->last_login_ip ?: '—' }}</span></td>
                                    <td>
                                        @if ($u->is_active)<span class="ukc-pill ukc-pill--ok">Actif</span>
                                        @else<span class="ukc-pill ukc-pill--muted">Inactif</span>@endif
                                    </td>
                                    <td>
                                        <div class="ukc-actions">
                                            <a href="{{ route('admin.users.edit', $u) }}" class="ukc-btn--icon" title="Modifier / Voir le détail">
                                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                            </a>
                                            @unless ($u->hasVerifiedEmail())
                                                <form method="POST" action="{{ route('admin.users.resend', $u) }}">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn--icon" title="Renvoyer le mail de vérification">
                                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                                    </button>
                                                </form>
                                            @endunless
                                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                                  onsubmit="return confirm('Supprimer cet utilisateur du back-office ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ukc-btn--icon danger" title="Supprimer">
                                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" style="padding:24px;text-align:center;color:var(--ukc-muted);">Aucun utilisateur ne correspond aux filtres.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="margin-top:12px;">{{ $users->links() }}</div>
            <p class="ukc-hint" style="margin-top:12px;font-size:12px;color:var(--ukc-light);">L’adresse IP et la date de dernière connexion sont enregistrées à chaque connexion au back-office. Les rôles UK Colloques déterminent l’accès aux fonctionnalités (matrice ci-dessous).</p>
        </div>

        {{-- ===== ROLES ===== --}}
        <div x-show="tab==='roles'" x-cloak>
            <div class="ukc-card">
                <h2>Rôles UK Colloques</h2>
                <div class="desc">Rôles métier du module, avec leurs permissions dans le back-office.</div>
                @foreach ($roles as $name => $meta)
                    <div class="role-card">
                        <h3>{{ $meta['label'] }} <span class="ukc-pill ukc-pill--{{ $meta['category'] === 'Système' ? 'ok' : $meta['pill'] }}">{{ $meta['category'] }}</span></h3>
                        <p>{{ $meta['description'] }}</p>
                        <div class="meta">{{ $countsByRole[$name] ?? 0 }} utilisateur(s) · {{ count(array_intersect($rolePermissions[$name] ?? [], array_keys($permissions))) }} permission(s)</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== ACCES ===== --}}
        <div x-show="tab==='access'" x-cloak>
            <form method="POST" action="{{ route('admin.users.access') }}">
                @csrf
                @method('PUT')
                <div class="ukc-card">
                    <h2>Matrice d’accès</h2>
                    <div class="desc">Cochez pour accorder une permission à un rôle. Enregistrez pour appliquer (spatie, guard « web »). L’administrateur conserve toujours toutes les permissions.</div>
                    <div style="overflow-x:auto;">
                        <div class="perm-grid" style="grid-template-columns: minmax(160px, 1.6fr) repeat({{ count($roles) }}, minmax(58px, 1fr)); min-width: {{ 160 + count($roles) * 64 }}px;">
                            <div class="cell head label">Permission</div>
                            @foreach ($roles as $meta)<div class="cell head">{{ $meta['abbr'] }}</div>@endforeach

                            @foreach ($permissions as $permKey => $permLabel)
                                <div class="cell label">{{ $permLabel }}</div>
                                @foreach ($roles as $roleName => $meta)
                                    <div class="cell">
                                        <input type="checkbox"
                                               name="matrix[{{ $roleName }}][]"
                                               value="{{ $permKey }}"
                                               @checked(in_array($permKey, $rolePermissions[$roleName] ?? [], true))
                                               @disabled($roleName === 'administrator')
                                               aria-label="{{ $permLabel }} — {{ $meta['label'] }}">
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    <p class="ukc-hint" style="margin-top:12px;font-size:12px;color:var(--ukc-light);">La porte d’entrée du back-office reste la capacité <code>manage_colloques</code> (rôles « Système ») — préservée automatiquement.</p>
                    <div class="ukc-form-actions" style="margin-top:14px;">
                        <button type="submit" class="ukc-btn ukc-btn--blue">
                            <x-ukc.icon name="check" :size="16" /> Enregistrer la matrice
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
