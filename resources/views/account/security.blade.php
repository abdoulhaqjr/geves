<x-layouts.participant :title="__('Mon compte')" :subtitle="__('Préférences, notifications & sécurité')">
    @include('partials.account-nav')

    @php($tab = (request('tab') === '2fa' || $showingSetup || $showingSmsSetup || ! empty($recoveryCodes)) ? '2fa' : 'securite')

    <div style="display:flex;flex-direction:column;gap:16px;">
    @if ($tab === 'securite')
    {{-- ============ Mot de passe ============ --}}
    <div class="ukc-card">
        <h3>{{ __('Mot de passe') }}</h3>

        <form id="pwForm" method="POST" action="{{ route('account.security.password') }}" class="ukc-form-grid" style="margin-top:8px;">
            @csrf
            <div class="ukc-field full">
                <label for="current_password">{{ __('Mot de passe actuel') }} <span class="req">*</span></label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                @error('current_password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="ukc-field">
                <label for="password">{{ __('Nouveau mot de passe') }} <span class="req">*</span></label>
                <input type="password" id="password" name="password" autocomplete="new-password">
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="ukc-field">
                <label for="password_confirmation">{{ __('Confirmation') }} <span class="req">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
            </div>
        </form>
        <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;">
            <button type="submit" form="pwForm" class="ukc-btn ukc-btn--blue">{{ __('Mettre à jour le mot de passe') }}</button>
        </div>
    </div>

    {{-- ============ Adresse e-mail de connexion ============ --}}
    <div class="ukc-card">
        <h3>{{ __('Adresse e-mail de connexion') }}</h3>
        <div class="ukc-form-grid" style="margin-top:8px;">
            <div class="ukc-field full">
                <label for="login_email">{{ __('E-mail') }}</label>
                <input type="email" id="login_email" value="{{ $account->email }}" readonly aria-readonly="true"
                       style="background:var(--ukc-bg);cursor:not-allowed;">
                <span class="hint">
                    @if ($account->hasVerifiedEmail())
                        {{ __('Adresse vérifiée. La modification de l’e-mail de connexion se fait auprès de l’organisation.') }}
                    @else
                        {{ __('Adresse non vérifiée -') }}
                        <a href="{{ route('account.verification.notice') }}" style="color:var(--ukc-blue);font-weight:700;">{{ __('renvoyer le lien de confirmation') }}</a>.
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- ============ Sessions actives ============ --}}
    <div class="ukc-card">
        <h3>{{ __('Sessions actives') }}</h3>
        <p style="color:var(--ukc-muted);font-size:.9rem;margin:4px 0 0;">{{ __('Appareils récemment connectés à votre compte.') }}</p>
        <div style="overflow-x:auto;">
            <table class="sessions-table">
                <thead>
                    <tr><th>{{ __('Appareil') }}</th><th>{{ __('Lieu / IP') }}</th><th>{{ __('Dernière activité') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $s)
                        <tr>
                            <td>
                                <strong>{{ $s['device'] }}</strong>
                                @if ($s['current'])<br><span style="color:var(--ukc-muted);font-size:12px;">{{ __('Session actuelle') }}</span>@endif
                            </td>
                            <td>{{ $s['ip'] }}</td>
                            <td>{{ $s['last'] ?: '-' }}</td>
                            <td style="text-align:right;white-space:nowrap;">
                                @if ($s['current'])
                                    <span class="ukc-pill ukc-pill--ok">{{ __('Active') }}</span>
                                @else
                                    <form method="POST" action="{{ route('account.security.sessions.revoke', $s['id']) }}"
                                          onsubmit="return confirm('{{ __('Révoquer cette session ?') }}')" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ __('Révoquer') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--ukc-muted);">{{ __('Aucune session active.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (count($sessions) > 1)
            <form method="POST" action="{{ route('account.security.sessions.revoke-others') }}"
                  onsubmit="return confirm('{{ __('Déconnecter tous les autres appareils ?') }}')"
                  style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:14px;padding-top:14px;border-top:1px solid var(--ukc-border);">
                @csrf
                <div class="ukc-field" style="max-width:260px;margin:0;">
                    <label for="revoke_password">{{ __('Mot de passe (pour confirmer)') }} <span class="req">*</span></label>
                    <input type="password" id="revoke_password" name="disable_password" autocomplete="current-password">
                    @error('disable_password') <span class="error">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">{{ __('Déconnecter les autres sessions') }}</button>
            </form>
        @endif
    </div>

    @endif {{-- fin panneau Sécurité --}}

    {{-- ============ Double authentification (fidèle au mockup compte.html) ============ --}}
    @if ($tab === '2fa')
        @php($smsActive = $enabled && $method === 'sms')
        @php($totpActive = $enabled && $method === 'totp')

        {{-- Activer la 2FA --}}
        <div class="ukc-card">
            <h3>{{ __('Double authentification (2FA)') }}</h3>
            <p style="color:var(--ukc-muted);font-size:.9rem;margin:4px 0 6px;">
                {!! __('Renforcez la sécurité de votre compte. Vous pouvez activer <strong>soit l’OTP par SMS</strong>, <strong>soit le TOTP</strong> (application d’authentification). Un seul mode est actif à la fois.') !!}
            </p>
            <div class="switch-row" style="border-bottom:none;padding-bottom:0;">
                <div class="meta">
                    <strong>{{ __('Activer la 2FA') }}</strong>
                    <span>{{ __('Exigée à chaque connexion depuis un nouvel appareil') }}</span>
                </div>
                <label class="switch" title="{{ $enabled ? __('Activée') : __('Désactivée') }}">
                    <input type="checkbox" @checked($enabled) onclick="return false;" aria-readonly="true">
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        {{-- Codes fraîchement générés (affichage unique) --}}
        @if (! empty($recoveryCodes))
            <div class="ukc-alert ukc-alert--warn" style="flex-direction:column;align-items:stretch;gap:10px;">
                <div style="display:flex;gap:8px;align-items:center;font-weight:700;">
                    <x-ukc.icon name="info" :size="18" /> {{ __('Conservez vos codes de secours') }}
                </div>
                <p style="font-size:.85rem;margin:0;">{{ __('Chaque code ne fonctionne qu’une fois. Notez-les en lieu sûr.') }}</p>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;font-family:var(--ukc-mono);font-weight:700;
                            background:#fff;border:1px solid var(--ukc-border);border-radius:10px;padding:12px;">
                    @foreach ($recoveryCodes as $rc)<span>{{ $rc }}</span>@endforeach
                </div>
            </div>
        @endif

        {{-- Méthode active --}}
        <div class="ukc-card">
            <h3>{{ __('Méthode active') }}</h3>
            <p style="color:var(--ukc-muted);font-size:.9rem;margin:4px 0 0;">{{ __('Choisissez une seule méthode. Le basculement désactive l’autre.') }}</p>

            <div class="method-cards">
                <form method="POST" action="{{ route('account.security.sms.start') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="method-card {{ ($smsActive || $showingSmsSetup) ? 'selected' : '' }}"
                            @disabled($enabled || $showingSetup || $showingSmsSetup)>
                        <strong>{{ __('OTP par SMS') }}</strong>
                        <span>{{ __('Code à usage unique envoyé sur votre numéro de téléphone à chaque connexion.') }}</span>
                        <span class="badge {{ $smsActive ? 'on' : 'off' }}">{{ $smsActive ? __('Actif') : __('Inactif') }}</span>
                    </button>
                </form>
                <form method="POST" action="{{ route('account.security.totp.start') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="method-card {{ ($totpActive || $showingSetup) ? 'selected' : '' }}"
                            @disabled($enabled || $showingSetup || $showingSmsSetup)>
                        <strong>{{ __('TOTP (application)') }}</strong>
                        <span>{{ __('Google Authenticator, Microsoft Authenticator, Authy… code renouvelé toutes les 30 s.') }}</span>
                        <span class="badge {{ $totpActive ? 'on' : 'off' }}">{{ $totpActive ? __('Actif') : __('Inactif') }}</span>
                    </button>
                </form>
            </div>

            @if ($showingSmsSetup)
                {{-- Configuration OTP SMS --}}
                <h3 style="font-size:14px;">{{ __('Configuration OTP SMS') }}</h3>
                @if (! $smsCodeSent)
                    <form method="POST" action="{{ route('account.security.sms.send') }}">
                        @csrf
                        <div class="ukc-field" style="max-width:280px;">
                            <label for="phone">{{ __('Numéro de téléphone') }} <span class="req">*</span></label>
                            <x-ukc.phone name="phone" :value="$phone" required />
                            <span class="hint">{{ __('Indicatif pays inclus · utilisé pour l’envoi des codes') }}</span>
                            @error('phone') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="mail" :size="14" /> {{ __('Envoyer le code') }}</button>
                            <button type="submit" formaction="{{ route('account.security.sms.cancel') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ __('Annuler') }}</button>
                        </div>
                    </form>
                @else
                    <div class="ukc-field" style="max-width:280px;">
                        <label>{{ __('Numéro de téléphone') }}</label>
                        <input type="tel" value="{{ $account->phone }}" disabled>
                    </div>
                    <form method="POST" action="{{ route('account.security.sms.confirm') }}" class="ukc-field" style="max-width:220px;margin-top:12px;">
                        @csrf
                        <label for="sms_code">{{ __('Code reçu par SMS') }} <span class="req">*</span></label>
                        <input type="text" id="sms_code" name="code" inputmode="numeric" autocomplete="one-time-code"
                               maxlength="6" placeholder="123456" style="letter-spacing:.3em;font-size:1.1rem;">
                        @error('code') <span class="error">{{ $message }}</span> @enderror
                        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                            <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="14" /> {{ __('Vérifier') }}</button>
                            <button type="submit" formaction="{{ route('account.security.sms.send') }}" name="phone" value="{{ $account->phone }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ __('Renvoyer') }}</button>
                            <button type="submit" formaction="{{ route('account.security.sms.cancel') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ __('Annuler') }}</button>
                        </div>
                    </form>
                @endif
            @elseif ($showingSetup)
                {{-- Configuration TOTP --}}
                <h3 style="font-size:14px;">{{ __('Configuration TOTP') }}</h3>
                <p style="color:var(--ukc-muted);font-size:.9rem;margin:0 0 8px;">{{ __('Scannez le QR code avec votre application, ou saisissez la clé secrète manuellement.') }}</p>
                <div class="qr-box">
                    <div style="background:#fff;border:1px solid var(--ukc-border);border-radius:8px;padding:8px;width:156px;height:156px;">
                        {!! $setupQr !!}
                    </div>
                    <div style="flex:1;min-width:220px;">
                        <div style="font-size:13px;font-weight:600;margin-bottom:4px;">{{ __('Clé secrète') }}</div>
                        <div class="secret-code">{{ $setupSecretFormatted }}</div>
                        <p class="hint" style="max-width:320px;">{{ __('Après scan, saisissez un code généré par l’application pour confirmer l’activation.') }}</p>
                        <form method="POST" action="{{ route('account.security.totp.confirm') }}" class="ukc-field" style="max-width:220px;margin-top:8px;">
                            @csrf
                            <label for="code">{{ __('Code de vérification') }} <span class="req">*</span></label>
                            <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                                   maxlength="6" placeholder="123456" style="letter-spacing:.3em;font-size:1.1rem;">
                            @error('code') <span class="error">{{ $message }}</span> @enderror
                            <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                                <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="14" /> {{ __('Activer le TOTP') }}</button>
                                <button type="submit" formaction="{{ route('account.security.totp.cancel') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ __('Annuler') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif ($enabled)
                <p class="hint" style="margin-top:12px;">{{ __('Méthode active :') }} <strong>{{ $smsActive ? __('OTP par SMS') : __('TOTP (application)') }}</strong>. {{ __('Désactivez la 2FA ci-dessous pour changer de méthode.') }}</p>
            @else
                <p class="hint" style="margin-top:12px;">{{ __('Choisissez une méthode ci-dessus pour activer la double authentification.') }}</p>
            @endif
        </div>

        @if ($enabled)
            {{-- Codes de secours --}}
            <div class="ukc-card">
                <h3>{{ __('Codes de secours') }}</h3>
                <p style="color:var(--ukc-muted);font-size:.9rem;margin:4px 0 8px;">{{ __('À conserver hors ligne. Chaque code ne peut être utilisé qu’une seule fois si vous perdez l’accès à l’OTP/TOTP.') }}</p>
                <p style="font-size:.88rem;color:var(--ukc-muted);">{{ __('Codes restants :') }} <strong>{{ $recoveryCount }}</strong>.</p>
                <form method="POST" action="{{ route('account.security.recovery') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:8px;">
                    @csrf
                    <div class="ukc-field" style="max-width:260px;margin:0;">
                        <label for="recovery_password">{{ __('Mot de passe (pour confirmer)') }} <span class="req">*</span></label>
                        <input type="password" id="recovery_password" name="disable_password" autocomplete="current-password">
                    </div>
                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="refresh" :size="14" /> {{ __('Régénérer') }}</button>
                </form>
            </div>

            {{-- Désactiver la 2FA --}}
            <div class="ukc-card">
                <h3>{{ __('Désactiver la 2FA') }}</h3>
                <p style="color:var(--ukc-muted);font-size:.9rem;margin:4px 0 8px;">{{ __('Non recommandé. Vous devrez confirmer avec votre mot de passe.') }}</p>
                <form method="POST" action="{{ route('account.security.disable') }}"
                      style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;"
                      onsubmit="return confirm('{{ __('Désactiver la double authentification ?') }}')">
                    @csrf
                    <div class="ukc-field" style="max-width:260px;margin:0;">
                        <label for="disable_password">{{ __('Mot de passe (pour confirmer)') }} <span class="req">*</span></label>
                        <input type="password" id="disable_password" name="disable_password" autocomplete="current-password">
                        @error('disable_password') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm"><x-ukc.icon name="close" :size="14" /> {{ __('Désactiver la double authentification') }}</button>
                </form>
            </div>
        @endif
    @endif {{-- fin panneau 2FA --}}
    </div>
</x-layouts.participant>
