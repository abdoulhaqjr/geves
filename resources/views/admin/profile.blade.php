<x-layouts.admin title="Mon compte" subtitle="Profil, notifications & sécurité">
    @php($initialTab = ($pendingSecret !== '' || session('recovery_codes') || $errors->has('code') || $errors->has('current_password')) ? 'securite' : 'identite')
    <div class="admin-page admin-profile" x-data="{ tab: '{{ $initialTab }}' }">

        {{-- En-tête + onglets « Mon compte » — même présentation que l'espace participant. --}}
        <div class="account-head" style="display:flex;align-items:center;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:4px;">
            @if ($twoFactorEnabled)
                <span class="ukc-pill ukc-pill--ok account-badge">2FA activée · TOTP</span>
            @else
                <span class="ukc-pill ukc-pill--muted account-badge">2FA désactivée</span>
            @endif
        </div>
        <nav class="account-nav">
            <button type="button" :class="{ active: tab==='identite' }" @click="tab='identite'">Mon profil</button>
            <button type="button" :class="{ active: tab==='notifications' }" @click="tab='notifications'">Notifications</button>
            <button type="button" :class="{ active: tab==='securite' }" @click="tab='securite'">Sécurité</button>
        </nav>

        {{-- ===================== MON PROFIL ===================== --}}
        <div x-show="tab==='identite'">
            <div class="ukc-card">
                <h3>Mon profil</h3>

                <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    {{-- Photo de profil --}}
                    <div class="ukc-avatar-upload" x-data="{
                            removed: false,
                            hasPhoto: {{ $staff->photo_path ? 'true' : 'false' }},
                            preview: '{{ $staff->photo_path ? \Illuminate\Support\Facades\Storage::url($staff->photo_path) : '' }}',
                            pick(e) { const f = e.target.files[0]; if (!f) return; this.preview = URL.createObjectURL(f); this.hasPhoto = true; this.removed = false; },
                            clear() { this.preview = ''; this.hasPhoto = false; this.removed = true; this.$refs.photo.value = ''; }
                         }">
                        <div class="ukc-avatar-preview" aria-label="Aperçu photo de profil">
                            <img x-show="preview" :src="preview" alt="Photo de profil">
                            <span x-show="!preview">{{ strtoupper(mb_substr($staff->first_name ?: $staff->name ?: 'A', 0, 1)) }}</span>
                        </div>
                        <div class="ukc-avatar-actions">
                            <div style="font-size:14px;font-weight:700;color:var(--ukc-text);">Photo de profil</div>
                            <p class="hint">JPG, PNG ou WebP · max. 2 Mo · format carré recommandé</p>
                            <div class="row">
                                <input type="file" id="photo" name="photo" x-ref="photo" accept="image/jpeg,image/png,image/webp" @change="pick($event)" aria-label="Choisir une photo">
                                <label for="photo" class="ukc-btn ukc-btn--sm" style="cursor:pointer;">
                                    <x-ukc.icon name="upload" :size="16" /> Ajouter une photo
                                </label>
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" x-show="hasPhoto" @click="clear()">Supprimer</button>
                            </div>
                            <input type="hidden" name="remove_photo" :value="removed ? '1' : '0'">
                            @error('photo') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label for="civility">Civilité</label>
                            <select id="civility" name="civility">
                                <option value="">—</option>
                                @foreach ($civilities as $c)
                                    <option value="{{ $c }}" @selected(old('civility', $staff->civility) === $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="ukc-field">
                            <label for="grade">Grade / fonction</label>
                            <input type="text" id="grade" name="grade" value="{{ old('grade', $staff->grade) }}" placeholder="Ex. Secrétaire général">
                        </div>

                        <div class="ukc-field">
                            <label for="last_name">Nom <span class="req">*</span></label>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $staff->last_name) }}" required>
                            @error('last_name') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ukc-field">
                            <label for="first_name">Prénom(s) <span class="req">*</span></label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $staff->first_name) }}" required>
                            @error('first_name') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ukc-field">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" value="{{ $staff->email }}" readonly aria-readonly="true" style="background:var(--ukc-bg);cursor:not-allowed;">
                            <span class="hint">Identifiant de connexion — géré dans <strong>Utilisateurs</strong>.</span>
                        </div>

                        <div class="ukc-field">
                            <label for="phone">Téléphone</label>
                            <x-ukc.phone name="phone" :value="old('phone', $staff->phone)" />
                            @error('phone') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ukc-field full">
                            <label for="affiliation">Affiliation / Institution</label>
                            <input type="text" id="affiliation" name="affiliation" value="{{ old('affiliation', $staff->affiliation) }}" placeholder="Université de Kara">
                            @error('affiliation') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ukc-field full">
                            <label for="orcid">Identifiant ORCID <span class="hint">(facultatif)</span></label>
                            <input type="text" id="orcid" name="orcid" value="{{ old('orcid', $staff->orcid) }}" placeholder="0000-0002-1825-0097" inputmode="text" autocomplete="off">
                            @error('orcid') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ukc-field full">
                            <label for="bio">Biographie courte</label>
                            <textarea id="bio" name="bio" rows="4" placeholder="Quelques lignes sur votre parcours (optionnel).">{{ old('bio', $staff->bio) }}</textarea>
                            @error('bio') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="ukc-form-actions">
                        <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="16" /> Enregistrer</button>
                    </div>
                </form>
            </div>

            {{-- Compte (lecture seule) --}}
            <div class="ukc-card" style="margin-top:16px;">
                <h3>Compte</h3>
                <div class="detail-grid">
                    <div class="k">Adresse e-mail</div><div class="v">{{ $staff->email }}
                        @if ($staff->hasVerifiedEmail())<span class="ukc-pill ukc-pill--ok">Vérifiée</span>@else<span class="ukc-pill ukc-pill--warn">Non vérifiée</span>@endif
                    </div>
                    <div class="k">Niveau d’accès</div><div class="v">{{ \App\Models\User::accessLevels()[$staff->access_level] ?? $staff->access_level }}</div>
                </div>
                <p class="ukc-hint" style="margin-top:10px;font-size:12px;color:var(--ukc-light);">L’adresse e-mail (identifiant de connexion) et le niveau d’accès sont gérés dans <strong>Utilisateurs</strong>.</p>
            </div>
        </div>

        {{-- ===================== NOTIFICATIONS ===================== --}}
        <div x-show="tab==='notifications'" x-cloak>
            @if (! empty($notifPrefs))
                <form method="POST" action="{{ route('notifications.prefs') }}" class="ukc-card">
                    @csrf
                    <h3>Préférences de notification</h3>
                    <div class="desc" style="color:var(--ukc-muted);font-size:13px;margin-bottom:10px;">Choisissez, pour chaque type, où vous êtes notifié.</div>
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Type</th><th style="text-align:center;">Dans l’app</th><th style="text-align:center;">E-mail</th></tr></thead>
                            <tbody>
                                @foreach ($notifPrefs as $kind => $p)
                                    <tr>
                                        <td>{{ $p['label'] }}</td>
                                        <td style="text-align:center;"><input type="checkbox" name="prefs[{{ $kind }}][inapp]" value="1" @checked($p['inapp'])></td>
                                        <td style="text-align:center;"><input type="checkbox" name="prefs[{{ $kind }}][email]" value="1" @checked($p['email'])></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="ukc-form-actions" style="margin-top:12px;">
                        <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="bell" :size="16" /> Enregistrer les préférences</button>
                    </div>
                </form>
            @endif
        </div>

        {{-- ===================== SÉCURITÉ ===================== --}}
        <div x-show="tab==='securite'" x-cloak>
            {{-- Double authentification (TOTP) --}}
            <div class="ukc-card" id="securite">
                <h3>Double authentification</h3>
                <div class="desc" style="color:var(--ukc-muted);font-size:13px;margin-bottom:10px;">Protège l’accès au back-office avec une application d’authentification (Google Authenticator, Authy…).</div>

                @if (session('recovery_codes'))
                    <div class="ukc-note" style="background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;margin-bottom:14px;">
                        <strong>Codes de secours — notez-les maintenant</strong> (ils ne seront plus affichés).
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px 16px;font-family:var(--ukc-mono);margin-top:8px;">
                            @foreach (session('recovery_codes') as $code)<span>{{ $code }}</span>@endforeach
                        </div>
                    </div>
                @endif

                @if ($twoFactorEnabled)
                    <p><span class="ukc-pill ukc-pill--ok">Activée</span> · {{ $recoveryCount }} code(s) de secours restant(s).</p>
                    <form method="POST" action="{{ route('admin.profile.2fa.recovery') }}" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-top:10px;">
                        @csrf
                        <div class="ukc-field" style="margin:0;max-width:260px;"><label>Mot de passe actuel</label><input type="password" name="current_password" required></div>
                        <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Régénérer les codes</button>
                    </form>
                    @error('current_password')<div class="error" style="margin-top:6px;">{{ $message }}</div>@enderror
                    <form method="POST" action="{{ route('admin.profile.2fa.disable') }}" onsubmit="return confirm('Désactiver la double authentification ?')" style="margin-top:12px;display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
                        @csrf @method('DELETE')
                        <div class="ukc-field" style="margin:0;max-width:260px;"><label>Mot de passe actuel</label><input type="password" name="current_password" required></div>
                        <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">Désactiver</button>
                    </form>
                @elseif ($pendingSecret !== '')
                    <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;">
                        <div style="width:180px;">{!! $setupQr !!}</div>
                        <div style="flex:1;min-width:240px;">
                            <p style="font-size:13px;">1. Scannez le QR code avec votre application, ou saisissez la clé :</p>
                            <code style="display:block;background:#f1f5f9;padding:8px;border-radius:6px;font-size:13px;word-break:break-all;">{{ $setupSecretFormatted }}</code>
                            <form method="POST" action="{{ route('admin.profile.2fa.confirm') }}" style="margin-top:12px;">
                                @csrf
                                <div class="ukc-field" style="max-width:220px;"><label>2. Code à 6 chiffres généré par l’app</label>
                                    <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required style="letter-spacing:3px;">
                                    @error('code')<div class="error">{{ $message }}</div>@enderror
                                </div>
                                <div style="display:flex;gap:8px;">
                                    <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="16" /> Activer</button>
                                    <button type="submit" formaction="{{ route('admin.profile.2fa.cancel') }}" class="ukc-btn ukc-btn--ghost">Annuler</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <p><span class="ukc-pill ukc-pill--muted">Désactivée</span></p>
                    <form method="POST" action="{{ route('admin.profile.2fa.start') }}" style="margin-top:10px;">
                        @csrf
                        <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="shield" :size="16" /> Activer la double authentification</button>
                    </form>
                @endif
            </div>

            {{-- Mot de passe --}}
            <form method="POST" action="{{ route('admin.profile.password') }}" class="ukc-card" style="margin-top:16px;">
                @csrf @method('PUT')
                <h3>Mot de passe</h3>
                <div class="ukc-form-grid ukc-form-grid-3">
                    <div class="ukc-field"><label>Mot de passe actuel</label>
                        <input type="password" name="current_password" autocomplete="current-password" required>
                        @error('current_password')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="ukc-field"><label>Nouveau mot de passe</label>
                        <input type="password" name="password" autocomplete="new-password" required>
                        @error('password')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="ukc-field"><label>Confirmer</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="ukc-form-actions" style="margin-top:12px;">
                    <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="lock" :size="16" /> Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
