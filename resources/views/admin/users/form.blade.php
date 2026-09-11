@php($editing = $user->exists)
<x-layouts.admin :title="$editing ? 'Modifier — '.$user->name : 'Associer un utilisateur'"
                 subtitle="Compte staff · rôles UK Colloques & permissions">
    <x-slot:actions>
        <a href="{{ route('admin.users.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Utilisateurs</a>
    </x-slot:actions>

    <div class="admin-page admin-users" style="max-width:760px;">
        @if ($errors->any())
            <div class="ukc-alert ukc-alert--err">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>{{ $errors->first() }}</div>
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
                        <label for="password">{{ $editing ? 'Nouveau mot de passe' : 'Mot de passe' }} <span class="hint">({{ $editing ? 'laisser vide pour ne pas changer' : 'optionnel — sinon généré' }})</span></label>
                        <input type="password" id="password" name="password" autocomplete="new-password">
                    </div>
                    <div class="ukc-field">
                        <label for="access_level">Niveau d’accès</label>
                        <select id="access_level" name="access_level" required>
                            @foreach (\App\Models\User::accessLevels() as $key => $label)
                                <option value="{{ $key }}" @selected(old('access_level', $user->access_level ?? \App\Models\User::ACCESS_USER) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="hint">Super admin : toute la plateforme · Admin de colloque : crée &amp; gère des colloques · Utilisateur : accès par colloque.</span>
                    </div>
                    <div class="ukc-field">
                        <label for="notes">Notes internes</label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes', $user->notes) }}" placeholder="Optionnel">
                    </div>
                    <div class="ukc-field full">
                        <label class="ukc-check" style="font-weight:600;">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                            Compte actif pour UK Colloques
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
                <h2>Rôles UK Colloques</h2>
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

        @if ($editing)
            <div class="ukc-card" style="margin-top:16px;">
                <h2>Actions</h2>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    @unless ($user->hasVerifiedEmail())
                        <form method="POST" action="{{ route('admin.users.resend', $user) }}">
                            @csrf
                            <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                Renvoyer le mail de vérification
                            </button>
                        </form>
                        <span class="ukc-pill ukc-pill--warn">Adresse non vérifiée</span>
                    @else
                        <span class="ukc-pill ukc-pill--ok">Adresse vérifiée</span>
                    @endunless

                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="margin-left:auto;"
                          onsubmit="return confirm('Supprimer définitivement cet utilisateur du back-office ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
