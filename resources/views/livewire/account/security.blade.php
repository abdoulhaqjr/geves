<div style="display:flex;flex-direction:column;gap:20px;max-width:640px;">
    <div class="ukc-page-head">
        <h2 style="font-size:1.4rem;font-weight:800;">Sécurité</h2>
        <p style="color:var(--ukc-muted);">Mot de passe et double authentification de votre compte.</p>
    </div>

    {{-- ============ Mot de passe ============ --}}
    <div class="ukc-card">
        <h3><x-ukc.icon name="lock" :size="20" /> Mot de passe</h3>

        <form wire:submit="updatePassword" class="ukc-form-grid" style="margin-top:8px;">
            <div class="ukc-field full">
                <label for="current_password">Mot de passe actuel <span class="req">*</span></label>
                <input type="password" id="current_password" wire:model="current_password" autocomplete="current-password">
                @error('current_password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="ukc-field">
                <label for="password">Nouveau mot de passe <span class="req">*</span></label>
                <input type="password" id="password" wire:model="password" autocomplete="new-password">
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="ukc-field">
                <label for="password_confirmation">Confirmation <span class="req">*</span></label>
                <input type="password" id="password_confirmation" wire:model="password_confirmation" autocomplete="new-password">
            </div>
            <div class="ukc-field full">
                <button type="submit" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="check" :size="16" /> Mettre à jour le mot de passe
                </button>
            </div>
        </form>
    </div>

    {{-- ============ Double authentification ============ --}}
    <div class="ukc-card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <h3 style="margin:0;"><x-ukc.icon name="shield" :size="20" /> Double authentification (2FA)</h3>
            @if ($enabled)
                <span class="ukc-pill ukc-pill--ok">Activée</span>
            @else
                <span class="ukc-pill ukc-pill--muted">Désactivée</span>
            @endif
        </div>
        <p style="color:var(--ukc-muted);font-size:.9rem;margin-top:6px;">
            Ajoutez une étape de vérification à la connexion via une application d’authentification
            (Google Authenticator, Authy, Microsoft Authenticator…).
        </p>

        {{-- Codes de récupération fraîchement générés (affichage unique) --}}
        @if (! empty($recoveryCodes))
            <div class="ukc-alert ukc-alert--warn" style="flex-direction:column;align-items:stretch;gap:10px;margin-top:12px;">
                <div style="display:flex;gap:8px;align-items:center;font-weight:700;">
                    <x-ukc.icon name="info" :size="18" /> Conservez vos codes de récupération
                </div>
                <p style="font-size:.85rem;margin:0;">
                    Chaque code ne fonctionne qu’une fois. Notez-les en lieu sûr : ils permettent de vous
                    connecter si vous perdez l’accès à votre application.
                </p>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px;font-family:monospace;
                            background:#fff;border:1px solid var(--ukc-border);border-radius:10px;padding:12px;">
                    @foreach ($recoveryCodes as $rc)
                        <span>{{ $rc }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if (! $enabled && ! $showingSetup)
            {{-- État désactivé : proposer l'activation --}}
            <div style="margin-top:14px;">
                <button type="button" wire:click="startSetup" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="lock" :size="16" /> Activer la 2FA
                </button>
            </div>
        @elseif ($showingSetup)
            {{-- Assistant d'activation : QR + clé + confirmation --}}
            <div style="margin-top:14px;border-top:1px solid var(--ukc-border);padding-top:16px;">
                <ol style="font-size:.9rem;color:var(--ukc-muted);padding-left:18px;margin:0 0 14px;line-height:1.7;">
                    <li>Ouvrez votre application d’authentification.</li>
                    <li>Scannez le QR code ci-dessous (ou saisissez la clé manuelle).</li>
                    <li>Entrez le code à 6 chiffres généré pour confirmer.</li>
                </ol>

                <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start;">
                    <div style="background:#fff;border:1px solid var(--ukc-border);border-radius:12px;padding:10px;width:200px;height:200px;">
                        {!! $setupQr !!}
                    </div>
                    <div style="flex:1;min-width:200px;">
                        <div style="font-size:.8rem;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">Clé manuelle</div>
                        <code style="display:block;font-size:.95rem;word-break:break-all;background:var(--ukc-bg-soft,#f4f6fb);
                                     border:1px solid var(--ukc-border);border-radius:8px;padding:8px 10px;margin:6px 0 14px;">{{ $setupSecretFormatted }}</code>

                        <form wire:submit="confirmTwoFactor" class="ukc-field" style="max-width:220px;">
                            <label for="code">Code de vérification <span class="req">*</span></label>
                            <input type="text" id="code" wire:model="code" inputmode="numeric" autocomplete="one-time-code"
                                   maxlength="6" placeholder="123456" style="letter-spacing:.3em;font-size:1.1rem;">
                            @error('code') <span class="error">{{ $message }}</span> @enderror
                            <div style="display:flex;gap:8px;margin-top:12px;">
                                <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                                    <x-ukc.icon name="check" :size="14" /> Confirmer
                                </button>
                                <button type="button" wire:click="cancelSetup" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            {{-- État activé : gérer codes / désactivation --}}
            <div style="margin-top:14px;border-top:1px solid var(--ukc-border);padding-top:16px;">
                <p style="font-size:.88rem;color:var(--ukc-muted);">
                    Codes de récupération restants : <strong>{{ $recoveryCount }}</strong>.
                </p>

                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:8px;">
                    <div class="ukc-field" style="max-width:260px;margin:0;">
                        <label for="disable_password">Mot de passe (pour confirmer) <span class="req">*</span></label>
                        <input type="password" id="disable_password" wire:model="disable_password" autocomplete="current-password">
                        @error('disable_password') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" wire:click="regenerateRecoveryCodes" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                            <x-ukc.icon name="download" :size="14" /> Régénérer les codes
                        </button>
                        <button type="button" wire:click="disableTwoFactor"
                                wire:confirm="Désactiver la double authentification ?"
                                class="ukc-btn ukc-btn--danger ukc-btn--sm">
                            <x-ukc.icon name="close" :size="14" /> Désactiver
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
