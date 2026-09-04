<x-layouts.participant title="Mon compte" subtitle="Préférences, notifications & sécurité">
    @include('partials.account-nav')

    <form method="POST" action="{{ route('account.preferences.update') }}">
        @csrf
        @method('PUT')

        {{-- Préférences générales --}}
        <div class="ukc-card" style="margin-bottom:20px;">
            <h3>Préférences générales</h3>
            <p style="font-size:.9rem;color:var(--ukc-muted);margin:4px 0 14px;">Langue de l’interface, fuseau et options d’affichage.</p>
            <div class="ukc-form-grid">
                <div class="ukc-field">
                    <label for="lang">Langue de l’interface</label>
                    <select id="lang" name="lang">
                        @foreach ($langs as $val => $label)<option value="{{ $val }}" @selected($prefs['lang'] === $val)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="ukc-field">
                    <label for="tz">Fuseau horaire</label>
                    <select id="tz" name="tz">
                        @foreach ($timezones as $val => $label)<option value="{{ $val }}" @selected($prefs['tz'] === $val)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="ukc-field">
                    <label for="date_format">Format de date</label>
                    <select id="date_format" name="date_format">
                        @foreach ($dateFormats as $fmt)<option @selected($prefs['date_format'] === $fmt)>{{ $fmt }}</option>@endforeach
                    </select>
                </div>
                <div class="ukc-field">
                    <label for="theme">Thème</label>
                    <select id="theme" name="theme">
                        @foreach ($themes as $th)<option @selected($prefs['theme'] === $th)>{{ $th }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Communication --}}
        <div class="ukc-card">
            <h3>Communication</h3>
            <p style="font-size:.9rem;color:var(--ukc-muted);margin:4px 0 8px;">Canaux par défaut pour les messages liés au colloque.</p>

            <div class="switch-row">
                <div class="meta">
                    <strong>E-mails transactionnels</strong>
                    <span>Accusés de soumission, décisions, reçus de paiement</span>
                </div>
                <label class="switch"><input type="checkbox" name="comm_transactional" value="1" @checked($prefs['comm_transactional'])><span class="slider"></span></label>
            </div>
            <div class="switch-row">
                <div class="meta">
                    <strong>SMS (alertes critiques)</strong>
                    <span>OTP, rappel de créneau, changements de dernière minute</span>
                </div>
                <label class="switch"><input type="checkbox" name="comm_sms" value="1" @checked($prefs['comm_sms'])><span class="slider"></span></label>
            </div>
            <div class="switch-row">
                <div class="meta">
                    <strong>Newsletter du colloque</strong>
                    <span>Annonces générales, programme, partenaires</span>
                </div>
                <label class="switch"><input type="checkbox" name="comm_newsletter" value="1" @checked($prefs['comm_newsletter'])><span class="slider"></span></label>
            </div>

            <div style="margin-top:16px;">
                <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="16" /> Enregistrer</button>
            </div>
        </div>
    </form>
</x-layouts.participant>
