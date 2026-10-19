@php($editing = $user->exists)
@php($initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->take(2)->implode('') ?: 'U')
@php($lvl = old('access_level', $user->access_level ?? \App\Models\User::ACCESS_USER))
<x-layouts.admin :title="$editing ? 'Modifier - '.$user->name : 'Associer un utilisateur'"
                 :subtitle="'Compte staff - niveau d’accès & affectations'">
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
        @endif

        {{-- ⚠️ DEUX COLONNES : ce qui se MODIFIE à gauche, ce qui se CONSTATE et
             s'actionne à droite. Les actions du compte (vérification, mot de
             passe, suppression) vivaient SOUS le bouton « Enregistrer », donc
             après la fin apparente de la page : on les prenait pour une suite du
             formulaire, et le bouton ne terminait plus rien. Elles ne pourront
             jamais être DANS ce formulaire - chacune a le sien, et deux
             formulaires ne s'imbriquent pas - alors elles passent à côté. --}}
        <div class="uf-grid {{ $editing ? '' : 'uf-grid--solo' }}">
            <form class="uf-main" method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}">
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
                     ⚠️⚠️ C'EST LE SEUL RÔLE GLOBAL DU SYSTÈME. La fiche portait en
                     plus douze cases « Rôles GEVES » valables sur toute la
                     plateforme : elles contredisaient le modèle du projet, où tout
                     ce qui n'est pas ces trois niveaux se confie PAR ÉVÉNEMENT. Le
                     niveau, lui, était relégué en liste déroulante entre le mot de
                     passe et les notes internes - alors qu'il décide de tout. --}}
                <div class="ukc-card">
                    <h2>Niveau d’accès</h2>
                    <div class="desc">
                        Le seul rôle global du système. Tout le reste - gestionnaire, comité,
                        secrétariat, accueil - se confie <strong>par colloque</strong> : l’administrateur
                        crée l’événement et y nomme son gestionnaire, qui forme ensuite son équipe.
                    </div>
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

                <div class="ukc-form-actions uf-actions">
                    <button type="submit" class="ukc-btn ukc-btn--blue">
                        <x-ukc.icon name="check" :size="16" /> {{ $editing ? 'Enregistrer' : 'Créer l’utilisateur' }}
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="ukc-btn ukc-btn--ghost">Annuler</a>
                </div>
            </form>

            @if ($editing)
                <aside class="uf-side">
                    {{-- ===== Ce que le compte ouvre VRAIMENT =====
                         ⚠️ En lecture seule : une affectation se confie depuis
                         l'onglet Équipe de l'événement, qui est aussi l'écran où
                         l'on voit qui d'autre s'y trouve. La fiche ne montrait
                         rien de cela - on y réglait des accès sans jamais voir
                         ceux dont le compte dispose. --}}
                    <div class="ukc-card">
                        <h2>Accès aux colloques</h2>

                        @if ($user->access_level === \App\Models\User::ACCESS_SUPER_ADMIN)
                            <div class="uf-note uf-note--all">
                                <x-ukc.icon name="shield" :size="18" />
                                <div><strong>Tous les colloques.</strong> Super administrateur : la plateforme entière, affectation ou pas.</div>
                            </div>
                        @endif

                        @forelse ($affectations as $a)
                            <div class="uf-row">
                                <span class="uf-row__ico"><x-ukc.icon name="calendar" :size="18" /></span>
                                <div class="uf-row__body">
                                    <div class="uf-row__title">{{ $a['title'] }}@if ($a['year']) <span class="uf-row__year">{{ $a['year'] }}</span>@endif</div>
                                    <div class="uf-row__sub">{{ $a['label'] }} - {{ trans_choice(':count capacité|:count capacités', $a['caps'], ['count' => $a['caps']]) }}</div>
                                </div>
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

                    {{-- ===== Actions sur le compte ===== --}}
                    <div class="ukc-card">
                        <h2>Actions sur le compte</h2>

                        <div class="uf-row">
                            <span class="uf-row__ico"><x-ukc.icon name="mail" :size="18" /></span>
                            <div class="uf-row__body">
                                <div class="uf-row__title">Vérification de l’adresse</div>
                                <div class="uf-row__sub">{{ $user->hasVerifiedEmail() ? 'L’adresse est confirmée.' : 'L’adresse n’est pas confirmée : renvoyez le lien.' }}</div>
                            </div>
                            @if ($user->hasVerifiedEmail())
                                <span class="ukc-pill ukc-pill--ok">Vérifiée</span>
                            @else
                                <form method="POST" action="{{ route('admin.users.resend', $user) }}"
                                      data-confirm="Renvoyer l’e-mail de vérification à {{ $user->email }} ?">
                                    @csrf
                                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="mail" :size="15" /> Renvoyer le lien</button>
                                </form>
                            @endif
                        </div>

                        <div class="uf-row uf-row--sep">
                            <span class="uf-row__ico"><x-ukc.icon name="key" :size="18" /></span>
                            <div class="uf-row__body">
                                <div class="uf-row__title">Mot de passe</div>
                                <div class="uf-row__sub">Envoie un lien pour définir un nouveau mot de passe.</div>
                            </div>
                            <form method="POST" action="{{ route('admin.users.password-reset', $user) }}"
                                  data-confirm="Envoyer un e-mail de réinitialisation du mot de passe à {{ $user->email }} ?">
                                @csrf
                                <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="key" :size="15" /> Envoyer le lien</button>
                            </form>
                        </div>

                        <div class="uf-row uf-row--sep uf-row--danger">
                            <span class="uf-row__ico"><x-ukc.icon name="trash" :size="18" /></span>
                            <div class="uf-row__body">
                                <div class="uf-row__title">Supprimer le compte</div>
                                <div class="uf-row__sub">Retire l’accès au back-office. Suppression douce, réversible en base ; vous confirmerez par mot de passe.</div>
                            </div>
                            <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="delOpen = true"><x-ukc.icon name="trash" :size="15" /> Supprimer</button>
                        </div>
                    </div>
                </aside>
            @endif
        </div>

        @if ($editing)
            @include('admin.users._delete-modal')
        @endif
    </div>
</x-layouts.admin>
