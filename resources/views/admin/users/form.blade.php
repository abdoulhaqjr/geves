@php($editing = $user->exists)
@php($initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->take(2)->implode('') ?: 'U')
@php($lvl = old('access_level', $user->access_level ?? \App\Models\User::ACCESS_USER))
@php($coche = old('roles', $assignedRoles))
<x-layouts.admin :title="$editing ? 'Modifier - '.$user->name : 'Associer un utilisateur'"
                 :subtitle="'Compte staff - rôles '.config('app.name').' & permissions'">
    <x-slot:actions>
        <a href="{{ route('admin.users.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Utilisateurs</a>
    </x-slot:actions>

    <div class="admin-page admin-users admin-user-form"
         x-data="{ delOpen: false, delConfirm: '', delUser: { url: @js($editing ? route('admin.users.destroy', $user) : ''), name: @js($user->name) } }">
        @if ($errors->any())
            <div class="ukc-alert ukc-alert--err">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        {{-- ===== Carte résumé (édition) ===== --}}
        @if ($editing)
            <div class="ukc-card uf-head">
                <div class="av">{{ $initials }}</div>
                <div class="uf-head__id">
                    <div class="uf-head__name">{{ $user->name }}</div>
                    <div class="uf-head__mail">{{ $user->email }}</div>
                    <div class="uf-head__pills">
                        <span class="ukc-pill ukc-pill--{{ $user->access_level === 'super_admin' ? 'ok' : ($user->access_level === 'colloque_admin' ? 'info' : 'muted') }}">{{ \App\Models\User::accessLevels()[$user->access_level] ?? 'Utilisateur' }}</span>
                        @if ($user->hasVerifiedEmail())
                            <span class="ukc-pill ukc-pill--ok">Adresse vérifiée</span>
                        @else
                            <span class="ukc-pill ukc-pill--warn">Adresse non vérifiée</span>
                        @endif
                        @if ($user->is_active)
                            <span class="ukc-pill ukc-pill--ok">Actif</span>
                        @else
                            <span class="ukc-pill ukc-pill--muted">Inactif</span>
                        @endif
                    </div>
                </div>
                <div class="uf-head__meta">
                    <div>Dernière connexion<br><strong>{{ $user->last_login_at?->translatedFormat('d/m/Y - H:i') ?? 'Jamais' }}</strong></div>
                    @if ($user->created_at)
                        <div>Compte créé<br><strong>{{ $user->created_at->translatedFormat('d/m/Y') }}</strong></div>
                    @endif
                </div>
            </div>

            {{-- ===== Ce que le compte ouvre VRAIMENT =====
                 ⚠️ En lecture seule, et volontairement AVANT le formulaire : on
                 règle plus bas des rôles de plateforme, et la question qu'on se
                 pose en arrivant sur cette fiche est « à quoi cette personne
                 a-t-elle accès ? ». La réponse est ici, et elle ne venait de
                 nulle part. --}}
            <div class="ukc-card">
                <h2>Accès aux colloques</h2>
                <div class="desc">Les fonctions d’équipe se confient <strong>par colloque</strong>, depuis l’onglet Équipe de l’événement. Ce sont elles qui ouvrent les pages du back-office.</div>

                @if ($user->access_level === \App\Models\User::ACCESS_SUPER_ADMIN)
                    <div class="uf-note uf-note--all">
                        <x-ukc.icon name="shield" :size="18" />
                        <div><strong>Tous les colloques.</strong> Ce compte est super administrateur : il ouvre la plateforme entière, affectation ou pas.</div>
                    </div>
                @endif

                @forelse ($affectations as $a)
                    <div class="uf-row">
                        <span class="uf-row__ico"><x-ukc.icon name="calendar" :size="18" /></span>
                        <div class="uf-row__body">
                            <div class="uf-row__title">{{ $a['title'] }}@if ($a['year']) <span class="uf-row__year">{{ $a['year'] }}</span>@endif</div>
                            <div class="uf-row__sub">{{ trans_choice(':count capacité|:count capacités', $a['caps'], ['count' => $a['caps']]) }} sur ce colloque</div>
                        </div>
                        <span class="ukc-pill ukc-pill--{{ $a['pill'] }}">{{ $a['label'] }}</span>
                        <a href="{{ route('admin.evenements.edit', ['event' => $a['event_id'], 'tab' => 'team']) }}"
                           class="ukc-btn ukc-btn--outline ukc-btn--sm">Équipe</a>
                    </div>
                @empty
                    @if ($user->access_level !== \App\Models\User::ACCESS_SUPER_ADMIN)
                        <div class="uf-note uf-note--none">
                            <x-ukc.icon name="info" :size="18" />
                            <div>Aucune affectation : ce compte n’ouvre <strong>aucun colloque</strong>. Ajoutez-le à l’équipe d’un événement pour lui confier une fonction.</div>
                        </div>
                    @endif
                @endforelse
            </div>
        @endif

        <form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if ($editing)@method('PUT')@endif

            <div class="ukc-card">
                <h2>Identité</h2>
                <div class="ukc-form-grid">
                    <div class="ukc-field">
                        <label for="name">Nom complet <span class="req">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="ukc-field">
                        <label for="email">Adresse e-mail <span class="req">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="ukc-field">
                        <label for="password">{{ $editing ? 'Nouveau mot de passe' : 'Mot de passe' }} <span class="hint">({{ $editing ? 'laisser vide pour ne pas changer' : 'optionnel - sinon généré' }})</span></label>
                        <input type="password" id="password" name="password" autocomplete="new-password">
                    </div>
                    <div class="ukc-field">
                        <label for="notes">Notes internes</label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes', $user->notes) }}" placeholder="Optionnel">
                    </div>
                    <div class="ukc-field full">
                        <label class="ukc-check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                            Compte actif pour {{ config('app.name') }}
                        </label>
                        @unless ($editing)
                            <label class="ukc-check">
                                <input type="checkbox" name="send_verification" value="1" checked>
                                Envoyer un e-mail de vérification à l’utilisateur
                            </label>
                        @endunless
                    </div>
                </div>
            </div>

            {{-- ===== Niveau d'accès =====
                 ⚠️ Sorti des champs d'identité, où il était une liste déroulante
                 voisine des « Notes internes ». C'est le réglage le plus lourd de
                 la fiche : il décide de ce que le compte ouvre sur la plateforme
                 entière, et chaque choix dit désormais ce qu'il ouvre. --}}
            <div class="ukc-card">
                <h2>Niveau d’accès</h2>
                <div class="desc">Ce que le compte ouvre sur la plateforme, indépendamment des colloques.</div>
                <div class="uf-levels">
                    @foreach (\App\Models\User::accessLevels() as $key => $label)
                        <label class="uf-choice">
                            <input type="radio" name="access_level" value="{{ $key }}" @checked($lvl === $key) required>
                            <span class="uf-choice__body">
                                <span class="uf-choice__label">{{ $label }}</span>
                                <span class="uf-choice__hint">{{ $accessHints[$key] ?? '' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- ===== Rôles globaux =====
                 ⚠️⚠️ ONZE DE CES DOUZE CASES N'OUVRENT RIEN, et l'écran le dit
                 désormais au lieu de promettre « la matrice d'accès en découle ».
                 Les capacités d'une fonction sont lues par `edition.cap:` →
                 `canOnEdition()`, donc depuis `event_user` - une affectation à un
                 événement. Les trois gardes `can:` du back-office portent sur des
                 permissions que seul « Administrateur colloque » détient. --}}
            <div class="ukc-card">
                <h2>Rôles globaux</h2>
                <div class="desc">
                    Rôles de <strong>plateforme</strong>, valables sur tous les colloques à la fois.
                    Seul <strong>Administrateur colloque</strong> change ce que le compte peut faire ;
                    les autres sont des étiquettes, qui classent le compte dans la liste et ses filtres
                    sans lui ouvrir de page. Pour donner un accès, affectez la personne à l’équipe d’un colloque.
                </div>

                @foreach ($roleGroups as $famille => $fonctions)
                    <div class="uf-family">{{ $famille }}</div>
                    <div class="uf-roles">
                        @foreach ($fonctions as $key => $meta)
                            <label class="uf-choice {{ $key === 'administrator' ? 'uf-choice--strong' : '' }}">
                                <input type="checkbox" name="roles[]" value="{{ $key }}" @checked(in_array($key, $coche, true))>
                                <span class="uf-choice__body">
                                    <span class="uf-choice__label">
                                        {{ $meta['label'] }}
                                        @if ($key === 'administrator')
                                            <span class="ukc-pill ukc-pill--warn">Accès complet</span>
                                        @endif
                                    </span>
                                    <span class="uf-choice__hint">{{ $meta['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="ukc-form-actions uf-actions">
                <button type="submit" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="check" :size="16" /> {{ $editing ? 'Enregistrer' : 'Créer l’utilisateur' }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="ukc-btn ukc-btn--ghost">Annuler</a>
            </div>
        </form>

        {{-- ===== Actions sur le compte (édition) ===== --}}
        @if ($editing)
            <div class="ukc-card">
                <h2>Actions sur le compte</h2>

                {{-- Vérification de l'adresse --}}
                <div class="uf-row">
                    <span class="uf-row__ico"><x-ukc.icon name="mail" :size="18" /></span>
                    <div class="uf-row__body">
                        <div class="uf-row__title">Vérification de l’adresse</div>
                        <div class="uf-row__sub">{{ $user->hasVerifiedEmail() ? 'L’adresse est confirmée.' : 'L’adresse n’est pas confirmée : renvoyez le lien de vérification.' }}</div>
                    </div>
                    @if ($user->hasVerifiedEmail())
                        <span class="ukc-pill ukc-pill--ok">Vérifiée</span>
                    @else
                        <form method="POST" action="{{ route('admin.users.resend', $user) }}"
                              data-confirm="Renvoyer l’e-mail de vérification à {{ $user->email }} ?">
                            @csrf
                            <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="mail" :size="15" /> Renvoyer le mail de vérification</button>
                        </form>
                    @endif
                </div>

                {{-- Réinitialisation du mot de passe --}}
                <div class="uf-row uf-row--sep">
                    <span class="uf-row__ico"><x-ukc.icon name="key" :size="18" /></span>
                    <div class="uf-row__body">
                        <div class="uf-row__title">Mot de passe</div>
                        <div class="uf-row__sub">Envoie à l’utilisateur un lien pour définir un nouveau mot de passe.</div>
                    </div>
                    <form method="POST" action="{{ route('admin.users.password-reset', $user) }}"
                          data-confirm="Envoyer un e-mail de réinitialisation du mot de passe à {{ $user->email }} ?">
                        @csrf
                        <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="key" :size="15" /> Envoyer un lien de réinitialisation</button>
                    </form>
                </div>

                {{-- Suppression --}}
                <div class="uf-row uf-row--sep uf-row--danger">
                    <span class="uf-row__ico"><x-ukc.icon name="trash" :size="18" /></span>
                    <div class="uf-row__body">
                        <div class="uf-row__title">Supprimer le compte</div>
                        <div class="uf-row__sub">Retire l’accès au back-office. Suppression douce (réversible en base) ; vous confirmerez par mot de passe.</div>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="delOpen = true"><x-ukc.icon name="trash" :size="15" /> Supprimer</button>
                </div>
            </div>

            @include('admin.users._delete-modal')
        @endif
    </div>
</x-layouts.admin>
