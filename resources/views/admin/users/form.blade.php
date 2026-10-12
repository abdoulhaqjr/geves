@php($editing = $user->exists)
@php($initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->take(2)->implode('') ?: 'U')
@php($lvl = $user->access_level ?? \App\Models\User::ACCESS_USER)
<x-layouts.admin :title="$editing ? 'Modifier - '.$user->name : 'Associer un utilisateur'"
                 :subtitle="'Compte staff - rôles '.config('app.name').' & permissions'">
    <x-slot:actions>
        <a href="{{ route('admin.users.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Utilisateurs</a>
    </x-slot:actions>

    <div class="admin-page admin-users" style="max-width:860px;"
         x-data="{ delOpen: false, delConfirm: '', delUser: { url: @js($editing ? route('admin.users.destroy', $user) : ''), name: @js($user->name) } }">
        @if ($errors->any())
            <div class="ukc-alert ukc-alert--err">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        {{-- ===== Carte résumé (édition) ===== --}}
        @if ($editing)
            <div class="ukc-card" style="margin-bottom:16px;">
                <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                    <div class="av" style="width:52px;height:52px;font-size:17px;flex:0 0 auto;">{{ $initials }}</div>
                    <div style="flex:1;min-width:220px;">
                        <div style="font-size:18px;font-weight:800;line-height:1.2;">{{ $user->name }}</div>
                        <div style="color:var(--ukc-muted);font-size:14px;">{{ $user->email }}</div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:9px;">
                            <span class="ukc-pill ukc-pill--{{ $lvl === 'super_admin' ? 'ok' : ($lvl === 'colloque_admin' ? 'info' : 'muted') }}">{{ \App\Models\User::accessLevels()[$lvl] ?? 'Utilisateur' }}</span>
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
                            @foreach ($user->roles as $r)
                                <span class="ukc-pill ukc-pill--{{ \App\Support\StaffRoles::pill($r->name) }}">{{ \App\Support\StaffRoles::label($r->name) }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div style="text-align:right;font-size:12.5px;color:var(--ukc-muted);line-height:1.7;flex:0 0 auto;">
                        <div>Dernière connexion<br><strong style="color:var(--ukc-text);">{{ $user->last_login_at?->translatedFormat('d/m/Y - H:i') ?? 'Jamais' }}</strong></div>
                        @if ($user->created_at)
                            <div style="margin-top:6px;">Compte créé<br><strong style="color:var(--ukc-text);">{{ $user->created_at->translatedFormat('d/m/Y') }}</strong></div>
                        @endif
                    </div>
                </div>
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
                        <label for="access_level">Niveau d’accès</label>
                        <select id="access_level" name="access_level" required>
                            @foreach (\App\Models\User::accessLevels() as $key => $label)
                                <option value="{{ $key }}" @selected(old('access_level', $lvl) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="hint">Super admin : toute la plateforme - Admin de colloque : crée &amp; gère des colloques - Utilisateur : accès par colloque.</span>
                    </div>
                    <div class="ukc-field full">
                        <label for="notes">Notes internes</label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes', $user->notes) }}" placeholder="Optionnel">
                    </div>
                    <div class="ukc-field full">
                        <label class="ukc-check" style="font-weight:600;">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                            Compte actif pour {{ config('app.name') }}
                        </label>
                        @unless ($editing)
                            <label class="ukc-check" style="font-weight:600;">
                                <input type="checkbox" name="send_verification" value="1" checked>
                                Envoyer un e-mail de vérification à l’utilisateur
                            </label>
                        @endunless
                    </div>
                </div>
            </div>

            <div class="ukc-card">
                <h2>Rôles {{ config('app.name') }}</h2>
                <div class="desc">Un utilisateur peut cumuler plusieurs rôles. La matrice d’accès en découle.</div>
                <div class="ukc-form-grid">
                    @foreach ($roleOptions as $key => $label)
                        <label class="ukc-check" style="font-weight:600;">
                            <input type="checkbox" name="roles[]" value="{{ $key }}" @checked(in_array($key, old('roles', $assignedRoles), true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="ukc-form-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="check" :size="16" /> {{ $editing ? 'Enregistrer' : 'Créer l’utilisateur' }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="ukc-btn ukc-btn--ghost">Annuler</a>
            </div>
        </form>

        {{-- ===== Actions sur le compte (édition) ===== --}}
        @if ($editing)
            <div class="ukc-card" style="margin-top:16px;">
                <h2>Actions sur le compte</h2>

                {{-- Vérification de l'adresse --}}
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:10px 0;">
                    <span style="flex:0 0 auto;width:36px;height:36px;border-radius:9px;background:#eef2ff;color:#223ea4;display:inline-flex;align-items:center;justify-content:center;"><x-ukc.icon name="mail" :size="18" /></span>
                    <div style="flex:1;min-width:220px;">
                        <div style="font-weight:700;">Vérification de l’adresse</div>
                        <div style="color:var(--ukc-muted);font-size:13px;">{{ $user->hasVerifiedEmail() ? 'L’adresse est confirmée.' : 'L’adresse n’est pas confirmée : renvoyez le lien de vérification.' }}</div>
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
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:10px 0;border-top:1px solid var(--ukc-border);">
                    <span style="flex:0 0 auto;width:36px;height:36px;border-radius:9px;background:#eef2ff;color:#223ea4;display:inline-flex;align-items:center;justify-content:center;"><x-ukc.icon name="key" :size="18" /></span>
                    <div style="flex:1;min-width:220px;">
                        <div style="font-weight:700;">Mot de passe</div>
                        <div style="color:var(--ukc-muted);font-size:13px;">Envoie à l’utilisateur un lien pour définir un nouveau mot de passe.</div>
                    </div>
                    <form method="POST" action="{{ route('admin.users.password-reset', $user) }}"
                          data-confirm="Envoyer un e-mail de réinitialisation du mot de passe à {{ $user->email }} ?">
                        @csrf
                        <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="key" :size="15" /> Envoyer un lien de réinitialisation</button>
                    </form>
                </div>

                {{-- Suppression --}}
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:10px 0 2px;border-top:1px solid var(--ukc-border);">
                    <span style="flex:0 0 auto;width:36px;height:36px;border-radius:9px;background:#fef2f2;color:#dc2626;display:inline-flex;align-items:center;justify-content:center;"><x-ukc.icon name="trash" :size="18" /></span>
                    <div style="flex:1;min-width:220px;">
                        <div style="font-weight:700;">Supprimer le compte</div>
                        <div style="color:var(--ukc-muted);font-size:13px;">Retire l’accès au back-office. Suppression douce (réversible en base) ; vous confirmerez par mot de passe.</div>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="delOpen = true"><x-ukc.icon name="trash" :size="15" /> Supprimer</button>
                </div>
            </div>

            @include('admin.users._delete-modal')
        @endif
    </div>
</x-layouts.admin>
