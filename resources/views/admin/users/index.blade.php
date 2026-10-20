<x-layouts.admin title="Utilisateurs" :subtitle="'Comptes, rôles '.config('app.name').' et permissions'">
    <x-slot:actions>
        <a href="{{ route('admin.users.create') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Associer un utilisateur
        </a>
    </x-slot:actions>

    <div class="admin-page admin-users" x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'list', delOpen: false, delConfirm: '', delUser: { url: '', name: '' } }">
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
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="mic" :size="20" /></div><div class="lbl">Évaluateurs</div><div class="val">{{ $stats['reviewers'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="calendar" :size="20" /></div><div class="lbl">Organisateurs</div><div class="val">{{ $stats['organizers'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="bell" :size="20" /></div><div class="lbl">Support</div><div class="val">{{ $stats['support'] }}</div></div>
            </div>

            {{-- ⚠️ Filtrage en AJAX (`data-filter`, cf. app.js) : la recherche
                 s'applique à la frappe, sans valider ni recharger la page. Les
                 régions qui en dépendent portent `data-filter-part`. --}}
            <form method="GET" class="filter-bar" data-filter="users">
                <select name="role">
                    <option value="">Tous les rôles</option>
                    @foreach ($roleOptions as $key => $label)<option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>@endforeach
                </select>
                <select name="status">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected($status === 'active')>Actif</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactif</option>
                </select>
                <input type="search" name="search" value="{{ $search }}" placeholder="Nom, e-mail, IP…" style="min-width:220px;">
                {{-- ⚠️ Le lien de réinitialisation apparaît selon les filtres : il
                     vit donc dans sa propre région échangée. Le formulaire, lui,
                     n'est jamais remplacé - il porte le curseur de qui tape. --}}
                <span data-filter-part="users-reset">
                    @if ($role !== '' || $status !== '' || $search !== '')
                        <a href="{{ route('admin.users.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
                    @endif
                </span>
            </form>

            <div class="ukc-card" style="padding:0;" data-filter-part="users-table">
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
                                                <span class="ukc-pill ukc-pill--muted">-</span>
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
                                    <td style="white-space:nowrap;">{{ $u->last_login_at?->translatedFormat('d/m/Y - H:i') ?? 'Jamais' }}</td>
                                    <td><span class="ip-cell">{{ $u->last_login_ip ?: '-' }}</span></td>
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
                                                <form method="POST" action="{{ route('admin.users.resend', $u) }}"
                                                      data-confirm="Renvoyer l’e-mail de vérification à {{ $u->email }} ?">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn--icon" title="Renvoyer le mail de vérification">
                                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                                    </button>
                                                </form>
                                            @endunless
                                            <form method="POST" action="{{ route('admin.users.password-reset', $u) }}"
                                                  data-confirm="Envoyer un e-mail de réinitialisation du mot de passe à {{ $u->email }} ?">
                                                @csrf
                                                <button type="submit" class="ukc-btn--icon" title="Envoyer un e-mail de réinitialisation du mot de passe">
                                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.65 10A5.99 5.99 0 0 0 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6a5.99 5.99 0 0 0 5.65-4H17v4h4v-4h2v-4h-10.35zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg>
                                                </button>
                                            </form>
                                            <button type="button" class="ukc-btn--icon danger" title="Supprimer"
                                                    @click="delUser = { url: @js(route('admin.users.destroy', $u)), name: @js($u->name) }; delConfirm = ''; delOpen = true">
                                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                            </button>
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
            <div style="margin-top:12px;" data-filter-part="users-foot">{{ $users->links() }}</div>
            <p class="ukc-hint" style="margin-top:12px;font-size:12px;color:var(--ukc-light);">L’adresse IP et la date de dernière connexion sont enregistrées à chaque connexion au back-office. Les rôles {{ config('app.name') }} déterminent l’accès aux fonctionnalités (matrice ci-dessous).</p>
        </div>

        @include('admin.users._delete-modal')

        {{-- ===== ROLES ===== --}}
        <div x-show="tab==='roles'" x-cloak>
            <div class="ukc-card">
                <h2>Rôles {{ config('app.name') }}</h2>
                <div class="desc">
                    Rôles métier du module, avec leurs permissions dans le back-office.
                    Une fonction se confie <strong>par événement</strong> ; un rôle global, lui,
                    vaut sur toute la plateforme.
                </div>
                {{-- ⚠️ La ligne de compteurs est assemblée par le CONTRÔLEUR
                     (`UserController::roleUsageLines`). Un bloc PHP ouvert ici
                     casserait la compilation : Blade apparie l'ouverture du
                     PREMIER bloc à la PREMIÈRE fermeture, et ce gabarit emploie
                     déjà la forme en ligne plus haut (ligne 73) - tout ce qui
                     les sépare serait avalé comme du PHP brut.
                     ⚠️ Et ne pas écrire ces directives en toutes lettres, même
                     dans un commentaire : Blade les compile AVANT de retirer les
                     commentaires. C'est ainsi que la première version de cette
                     note a cassé le fichier qu'elle décrivait. --}}
                @foreach ($roles as $name => $meta)
                    <div class="role-card">
                        <h3>{{ $meta['label'] }} <span class="ukc-pill ukc-pill--{{ \App\Support\StaffRoles::categoryPill($name) }}">{{ $meta['category'] }}</span></h3>
                        <p>{{ $meta['description'] }}</p>
                        <div class="meta">{{ $roleUsage[$name] ?? '' }}</div>
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
                                               aria-label="{{ $permLabel }} - {{ $meta['label'] }}">
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    <p class="ukc-hint" style="margin-top:12px;font-size:12px;color:var(--ukc-light);">La porte d’entrée du back-office reste la capacité <code>manage_colloques</code>, portée par le seul rôle <strong>Administrateur (rôle hérité)</strong> - hors matrice, préservée automatiquement.</p>
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
