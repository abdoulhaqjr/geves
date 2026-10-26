<x-layouts.admin title="Utilisateurs" :subtitle="'Comptes, rôles '.config('app.name').' et permissions'">
    <x-slot:actions>
        <a href="{{ route('admin.users.create') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Associer un utilisateur
        </a>
    </x-slot:actions>

    {{-- ⚠️⚠️ LE CANAL DE PRÉSENCE NE DONNE QU'UN NOMBRE, JAMAIS DES IDENTITÉS :
         sa liste de membres est lue par tous les participants, donc on n'y publie
         que des empreintes. Il sert ici de SIGNAL - « quelqu'un vient d'arriver ou
         de partir, redemande au serveur » - et c'est le serveur qui dit la carte
         ET les pastilles, d'une seule requête. Afficher son compte à lui en face de
         pastilles calculées autrement ferait dire deux choses au même écran.
         ⚠️ Le signal est freiné : une arrivée groupée émet en rafale. --}}
    <div class="admin-page admin-users"
         x-data="{
            tab: new URLSearchParams(window.location.search).get('tab') || 'list',
            onlineCount: @js($stats['online']),
            sondage: null,
            frein: null,
            delOpen: false, delConfirm: '', delUser: { url: '', name: '' },
            async rafraichir() {
                try {
                    const r = await fetch(@js(route('admin.users.online')), { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' });
                    if (!r.ok) return;
                    const data = await r.json();
                    this.onlineCount = data.count;
                    this.peindre(data.ids);
                } catch {
                    // La page servie au chargement reste juste : on retentera.
                }
            },
            peindre(ids) {
                if (!Array.isArray(ids)) return;
                const presents = new Set(ids.map(Number));
                document.querySelectorAll('[data-online-for]').forEach(el => {
                    const la = presents.has(Number(el.dataset.onlineFor));
                    el.classList.toggle('ukc-pill--ok', la);
                    el.classList.toggle('ukc-pill--muted', !la);
                    el.textContent = la ? @js(__('En ligne')) : @js(__('Hors ligne'));
                });
            },
            init() {
                window.addEventListener('ukc-en-ligne', () => {
                    clearTimeout(this.frein);
                    this.frein = setTimeout(() => this.rafraichir(), 400);
                });
                this.sondage = setInterval(() => {
                    if (document.visibilityState !== 'visible' || this.tab !== 'list') return;
                    this.rafraichir();
                }, 30000);
            },
         }">
        <div class="ukc-nav-docs">
            <button type="button" @click="tab='list'" :class="{ active: tab==='list' }">Liste des utilisateurs</button>
            <button type="button" @click="tab='roles'" :class="{ active: tab==='roles' }">Rôles</button>
            @if (auth('web')->user()?->isColloqueSuperAdmin())
                <button type="button" @click="tab='access'" :class="{ active: tab==='access' }">Accès &amp; permissions</button>
            @endif
        </div>

        {{-- ===== LISTE ===== --}}
        <div x-show="tab==='list'">
            {{-- ⚠️ Région échangée : les chiffres ne bougent pas avec les filtres
                 (ce sont des repères sur tout le périmètre), mais le LIEN de la
                 carte « En ligne » les emporte. Hors région, il resterait pointé
                 sur les filtres précédents après un filtrage en AJAX. --}}
            <div class="ukc-stats" data-filter-part="users-kpis">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Utilisateurs</div><div class="val blue">{{ $stats['total'] }}</div></div>
                {{-- ⚠️ Ce nombre est la TAILLE de la liste qui peint les pastilles : même requête, même définition.
                     ⚠️ Et la carte est CLIQUABLE : avec 72 comptes sur quatre pages, « 1 en ligne » au-dessus
                     d'une page où tout le monde est hors ligne se lit comme une panne - le chiffre doit mener
                     aux personnes qu'il compte. Le sélecteur nomme l'élément (`a.ukc-stat-card.is-on`) : à
                     spécificité égale, Tailwise gagnerait et l'état allumé ne se verrait jamais. --}}
                <a href="{{ $lienPresence }}" class="ukc-stat-card tw-stat {{ $presence === 'online' ? 'is-on' : '' }}" title="{{ __('Comptes distincts ayant utilisé le site durant les 5 dernières minutes ; actualisé à chaque arrivée ou départ, et toutes les 30 secondes') }}"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">{{ __('En ligne (5 min)') }}</div><div class="val green" x-text="onlineCount === null ? '-' : onlineCount">{{ $stats['online'] ?? '-' }}</div></a>
                <div class="ukc-stat-card tw-stat" title="Comptes visibles ayant un niveau administrateur ou les droits effectifs de super administrateur"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="shield" :size="20" /></div><div class="lbl">Admins</div><div class="val">{{ $stats['admins'] }}</div></div>
                <div class="ukc-stat-card tw-stat" title="Personnes distinctes ayant une fiche évaluateur sur au moins un événement"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="mic" :size="20" /></div><div class="lbl">Évaluateurs</div><div class="val">{{ $stats['reviewers'] }}</div></div>
                <div class="ukc-stat-card tw-stat" title="Personnes distinctes affectées comme organisateur sur au moins un événement"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="calendar" :size="20" /></div><div class="lbl">Organisateurs</div><div class="val">{{ $stats['organizers'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="bell" :size="20" /></div><div class="lbl">Support</div><div class="val">{{ $stats['support'] }}</div></div>
            </div>

            {{-- ⚠️ Filtrage en AJAX (`data-filter`, cf. app.js) : la recherche
                 s'applique à la frappe, sans valider ni recharger la page. Les
                 régions qui en dépendent portent `data-filter-part`. --}}
            <form method="GET" class="filter-bar" data-filter="users">
                <select name="role">
                    <option value="">Toutes les fonctions</option>
                    @foreach ($roleOptions as $key => $label)<option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>@endforeach
                </select>
                <select name="status">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected($status === 'active')>Actif</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactif</option>
                </select>
                <input type="search" name="search" value="{{ $search }}" placeholder="Nom, e-mail, IP…" style="min-width:220px;">
                {{-- ⚠️ La barre poste TOUT le formulaire : sans ce champ caché,
                     taper une recherche reperdrait la présence posée par la carte
                     et la liste se réélargirait sous les doigts. --}}
                <input type="hidden" name="presence" value="{{ $presence }}">
                {{-- ⚠️ Le lien de réinitialisation apparaît selon les filtres : il
                     vit donc dans sa propre région échangée. Le formulaire, lui,
                     n'est jamais remplacé - il porte le curseur de qui tape. --}}
                <span data-filter-part="users-reset">
                    @if ($role !== '' || $status !== '' || $search !== '' || $presence !== '')
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
                                <x-ukc.th sort="email" :current="$sort" :dir="$dir">E-mail vérifié</x-ukc.th>
                                <x-ukc.th sort="last_login_at" :current="$sort" :dir="$dir">Dernière connexion</x-ukc.th>
                                {{-- ⚠️ « Présence » et non « État » : la colonne voisine s'appelle
                                     déjà « Statut » et dit tout autre chose (compte actif ou
                                     désactivé). Deux intitulés voisins pour deux notions
                                     différentes se confondent à la lecture. --}}
                                <th>Présence</th>
                                <th>IP</th>
                                <th>Pays (IP)</th>
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
                                        @if ($u->hasVerifiedEmail())
                                            <span class="ukc-pill ukc-pill--ok">Vérifié</span>
                                        @else
                                            <span class="ukc-pill ukc-pill--warn">Non vérifié</span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap;">{{ $u->last_login_at?->translatedFormat('d/m/Y - H:i') ?? 'Jamais' }}</td>
                                    {{-- ⚠️ Sans sessions en base on ne SAIT PAS : on se tait, au lieu
                                         d'annoncer tout le monde hors ligne. Le repeint en direct vise
                                         `data-online-for`. --}}
                                    <td>
                                        @if ($enLigneSet === null)
                                            <span class="ukc-pill ukc-pill--muted" title="{{ __('Les sessions ne sont pas enregistrées en base : la présence ne peut pas être connue.') }}">-</span>
                                        @else
                                            <span class="ukc-pill {{ isset($enLigneSet[$u->id]) ? 'ukc-pill--ok' : 'ukc-pill--muted' }}"
                                                  data-online-for="{{ $u->id }}"
                                                  title="{{ __('Activité sur le site durant les 5 dernières minutes') }}">{{ isset($enLigneSet[$u->id]) ? __('En ligne') : __('Hors ligne') }}</span>
                                        @endif
                                    </td>
                                    <td><span class="ip-cell">{{ $u->last_login_ip ?: '-' }}</span></td>
                                    {{-- Une IP privée n'a pas de pays, et ce n'est pas une panne : on le dit, plutôt que de rendre le même tiret que pour une plage inconnue. --}}
                                    <td>{{ $countriesByIp[$u->last_login_ip] ?? ($u->last_login_ip && ! \App\Support\IpCountry::isPublic($u->last_login_ip) ? __('Réseau local') : '-') }}</td>
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
                                            @if (auth('web')->user()?->canImpersonate() && auth('web')->id() !== $u->id)
                                                {{-- « Se connecter en tant que » : on emprunte un COMPTE, donc le
                                                     geste vit ici. Jamais sur sa propre ligne - cela déconnecterait
                                                     l'admin du back-office pour le poser dans son propre espace. --}}
                                                <form method="POST" action="{{ route('admin.users.impersonate', $u) }}"
                                                      data-confirm="Se connecter en tant que {{ $u->name }} ? Vous verrez et agirez dans son espace jusqu’à ce que vous quittiez l’impersonation.">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn--icon" title="Se connecter en tant que {{ $u->name }}">
                                                        <x-ukc.icon name="login" :size="16" />
                                                    </button>
                                                </form>
                                            @endif
                                            <button type="button" class="ukc-btn--icon danger" title="Supprimer"
                                                    @click="delUser = { url: @js(route('admin.users.destroy', $u)), name: @js($u->name) }; delConfirm = ''; delOpen = true">
                                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" style="padding:24px;text-align:center;color:var(--ukc-muted);">Aucun utilisateur ne correspond aux filtres.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="margin-top:12px;" data-filter-part="users-foot">{{ $users->links() }}</div>
            @unless ($geoip['resolvable'])
                {{-- L'échec était MUET : sans base installée, la colonne rendait des tirets et rien ne disait qu'il manquait un fichier. --}}
                <div class="ukc-alert ukc-alert--warn" style="margin-top:12px;">
                    <span class="ico"><x-ukc.icon name="alert-triangle" :size="18" /></span>
                    @if ($geoip['reader'])
                        <div>{{ __('La base de géolocalisation n’est pas installée : aucun pays ne peut être déduit, quelles que soient les adresses enregistrées. Sur le serveur :') }} <code>php artisan geoip:update</code></div>
                    @else
                        <div>{{ __('La bibliothèque de lecture GeoIP n’est pas installée : aucun pays ne peut être déduit. Sur le serveur :') }} <code>composer install --no-dev --optimize-autoloader</code></div>
                    @endif
                </div>
            @endunless
            <p class="ukc-hint" style="margin-top:12px;font-size:12px;color:var(--ukc-light);">La date et l’IP sont enregistrées après chaque connexion réussie, dans l’espace participant ou le back-office. « En ligne » désigne une activité durant les cinq dernières minutes. Le pays est estimé depuis l’IP publique ; une IP locale ou inconnue n’a pas de pays. <a href="https://db-ip.com" target="_blank" rel="noopener noreferrer">IP Geolocation by DB-IP</a>.</p>
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
            @if (auth('web')->user()?->isColloqueSuperAdmin())
            <form method="POST" action="{{ route('admin.users.access') }}">
                @csrf
                @method('PUT')
                <div class="ukc-card">
                    <h2>Matrice d’accès</h2>
                    <div class="desc">Cochez pour accorder une permission à une fonction sur ses événements. Les changements s’appliquent aux affectations existantes. Le super administrateur conserve toujours toutes les permissions.</div>
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
            @endif
        </div>
    </div>
</x-layouts.admin>
