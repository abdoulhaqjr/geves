<x-layouts.admin title="Mon profil" subtitle="Vos informations, notifications et sécurité">
    @php($initialTab = ($pendingSecret !== '' || session('recovery_codes') || $errors->has('code') || $errors->has('current_password')) ? 'securite' : 'identite')
    <div class="admin-page admin-profile" x-data="{ tab: '{{ $initialTab }}' }">

        {{-- Onglets --}}
        <div class="ukc-nav-docs" style="margin-bottom:16px;">
            <button type="button" :class="{ active: tab==='identite' }" @click="tab='identite'">Identité</button>
            <button type="button" :class="{ active: tab==='notifications' }" @click="tab='notifications'">Notifications</button>
            <button type="button" :class="{ active: tab==='securite' }" @click="tab='securite'">Sécurité</button>
        </div>

        {{-- ===================== IDENTITÉ ===================== --}}
        <div x-show="tab==='identite'">
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="ukc-card"
                  x-data="{ preview: '{{ $staff->photo_path ? \Illuminate\Support\Facades\Storage::url($staff->photo_path) : '' }}', remove: false }">
                @csrf @method('PUT')
                <h2>Identité</h2>

                <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
                    {{-- Photo --}}
                    <div style="text-align:center;">
                        <div class="ukc-avatar-lg">
                            <template x-if="preview && !remove"><img :src="preview" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"></template>
                            <template x-if="!preview || remove"><span>{{ strtoupper(mb_substr($staff->first_name ?: $staff->name ?: 'A', 0, 1)) }}</span></template>
                        </div>
                        <label class="ukc-btn ukc-btn--ghost ukc-btn--sm" style="margin-top:8px;cursor:pointer;">
                            <x-ukc.icon name="upload" :size="14" /> Photo
                            <input type="file" name="photo" accept="image/*" style="display:none;"
                                   @change="remove=false; const f=$event.target.files[0]; if(f){ preview=URL.createObjectURL(f); }">
                        </label>
                        @if ($staff->photo_path)
                            <label style="display:block;margin-top:6px;font-size:12px;color:var(--ukc-muted);cursor:pointer;">
                                <input type="checkbox" name="remove_photo" value="1" x-model="remove"> Retirer
                            </label>
                        @endif
                        @error('photo')<div class="error" style="font-size:12px;">{{ $message }}</div>@enderror
                    </div>

                    {{-- Champs --}}
                    <div style="flex:1;min-width:280px;">
                        <div style="display:grid;grid-template-columns:140px 1fr;gap:12px;">
                            <div class="ukc-form-row"><label>Civilité</label>
                                <select name="civility">
                                    <option value="">—</option>
                                    @foreach ($civilities as $c)<option value="{{ $c }}" @selected(old('civility', $staff->civility) === $c)>{{ $c }}</option>@endforeach
                                </select>
                            </div>
                            <div class="ukc-form-row"><label>Grade / fonction</label>
                                <input type="text" name="grade" value="{{ old('grade', $staff->grade) }}" placeholder="Ex. Secrétaire général">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="ukc-form-row"><label>Prénom</label>
                                <input type="text" name="first_name" value="{{ old('first_name', $staff->first_name) }}" required>
                                @error('first_name')<div class="error">{{ $message }}</div>@enderror
                            </div>
                            <div class="ukc-form-row"><label>Nom</label>
                                <input type="text" name="last_name" value="{{ old('last_name', $staff->last_name) }}" required>
                                @error('last_name')<div class="error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="ukc-form-row"><label>Téléphone</label>
                                <input type="text" name="phone" value="{{ old('phone', $staff->phone) }}">
                            </div>
                            <div class="ukc-form-row"><label>ORCID</label>
                                <input type="text" name="orcid" value="{{ old('orcid', $staff->orcid) }}" placeholder="0000-0002-1825-0097">
                                @error('orcid')<div class="error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="ukc-form-row"><label>Affiliation</label>
                            <input type="text" name="affiliation" value="{{ old('affiliation', $staff->affiliation) }}" placeholder="Université de Kara">
                        </div>
                        <div class="ukc-form-row"><label>Bio <span style="color:var(--ukc-muted);font-weight:500;">(optionnel)</span></label>
                            <textarea name="bio" rows="3">{{ old('bio', $staff->bio) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="ukc-form-actions" style="margin-top:14px;">
                    <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="16" /> Enregistrer</button>
                </div>
            </form>

            {{-- Compte (lecture seule) --}}
            <div class="ukc-card" style="margin-top:16px;">
                <h2>Compte</h2>
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
                    <h2>Préférences de notification</h2>
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
                <h2>Double authentification</h2>
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
                        <div class="ukc-form-row" style="margin:0;max-width:260px;"><label>Mot de passe actuel</label><input type="password" name="current_password" required></div>
                        <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Régénérer les codes</button>
                    </form>
                    @error('current_password')<div class="error" style="margin-top:6px;">{{ $message }}</div>@enderror
                    <form method="POST" action="{{ route('admin.profile.2fa.disable') }}" onsubmit="return confirm('Désactiver la double authentification ?')" style="margin-top:12px;display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
                        @csrf @method('DELETE')
                        <div class="ukc-form-row" style="margin:0;max-width:260px;"><label>Mot de passe actuel</label><input type="password" name="current_password" required></div>
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
                                <div class="ukc-form-row" style="max-width:220px;"><label>2. Code à 6 chiffres généré par l’app</label>
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
                <h2>Mot de passe</h2>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                    <div class="ukc-form-row"><label>Mot de passe actuel</label>
                        <input type="password" name="current_password" autocomplete="current-password" required>
                        @error('current_password')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="ukc-form-row"><label>Nouveau mot de passe</label>
                        <input type="password" name="password" autocomplete="new-password" required>
                        @error('password')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="ukc-form-row"><label>Confirmer</label>
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
