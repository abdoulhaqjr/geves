<x-layouts.participant :title="__('Mon compte')" :subtitle="__('Préférences, notifications & sécurité')">
    @include('partials.account-nav')

    <form method="POST" action="{{ route('account.preferences.update') }}">
        @csrf
        @method('PUT')

        {{-- Préférences générales --}}
        <div class="ukc-card" style="margin-bottom:20px;">
            <h3>{{ __('Préférences générales') }}</h3>
            <p style="font-size:.9rem;color:var(--ukc-muted);margin:4px 0 14px;">{{ __('Langue de l’interface, fuseau et options d’affichage.') }}</p>
            <div class="ukc-form-grid">
                <div class="ukc-field">
                    <label for="lang">{{ __('Langue de l’interface') }}</label>
                    <select id="lang" name="lang">
                        @foreach ($langs as $val => $label)<option value="{{ $val }}" @selected($prefs['lang'] === $val)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="ukc-field">
                    <label for="tz">{{ __('Fuseau horaire') }}</label>
                    <select id="tz" name="tz">
                        @foreach ($timezones as $val => $label)<option value="{{ $val }}" @selected($prefs['tz'] === $val)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="ukc-field">
                    <label for="date_format">{{ __('Format de date') }}</label>
                    <select id="date_format" name="date_format">
                        @foreach ($dateFormats as $fmt)<option @selected($prefs['date_format'] === $fmt)>{{ $fmt }}</option>@endforeach
                    </select>
                </div>
                <div class="ukc-field">
                    <label for="theme">{{ __('Thème') }}</label>
                    <select id="theme" name="theme">
                        @foreach ($themes as $th)<option @selected($prefs['theme'] === $th)>{{ $th }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Communication --}}
        <div class="ukc-card">
            <h3>{{ __('Communication') }}</h3>
            <p style="font-size:.9rem;color:var(--ukc-muted);margin:4px 0 8px;">{{ __('Canaux par défaut pour les messages liés au colloque.') }}</p>

            <div class="switch-row">
                <div class="meta">
                    <strong>{{ __('E-mails transactionnels') }}</strong>
                    <span>{{ __('Accusés de soumission, décisions, reçus de paiement') }}</span>
                </div>
                <label class="switch"><input type="checkbox" name="comm_transactional" value="1" @checked($prefs['comm_transactional'])><span class="slider"></span></label>
            </div>
            <div class="switch-row">
                <div class="meta">
                    <strong>{{ __('SMS (alertes critiques)') }}</strong>
                    <span>{{ __('OTP, rappel de créneau, changements de dernière minute') }}</span>
                </div>
                <label class="switch"><input type="checkbox" name="comm_sms" value="1" @checked($prefs['comm_sms'])><span class="slider"></span></label>
            </div>
            <div class="switch-row">
                <div class="meta">
                    <strong>{{ __('Newsletter du colloque') }}</strong>
                    <span>{{ __('Annonces générales, programme, partenaires') }}</span>
                </div>
                <label class="switch"><input type="checkbox" name="comm_newsletter" value="1" @checked($prefs['comm_newsletter'])><span class="slider"></span></label>
            </div>

            <div style="margin-top:16px;">
                <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="16" /> {{ __('Enregistrer') }}</button>
            </div>
        </div>
    </form>

    {{-- Préférences de notification (par type / canal) --}}
    @php
        $kindDesc = [
            'submission_status' => __('Acceptation, refus, demande de révision'),
            'review_assigned' => __('Nouvelle relecture qui vous est confiée'),
            'attestation' => __('Attestation ou reçu prêt à télécharger'),
            'invitation' => __('Invitations et rappels d’inscription'),
            'payment' => __('Confirmation, échec, remboursement'),
        ];
    @endphp
    <div class="ukc-card" style="margin-top:20px;">
        <h3>{{ __('Préférences de notification') }}</h3>
        <p style="color:var(--ukc-muted);font-size:.9rem;margin:4px 0 8px;">{{ __('Activez ou désactivez chaque type d’alerte (in-app & e-mail).') }}</p>
        <form method="POST" action="{{ route('notifications.prefs') }}">
            @csrf
            @foreach ($notifKinds as $kind => $def)
                @php($on = ($notifPrefs[$kind]['inapp'] ?? false) || ($notifPrefs[$kind]['email'] ?? false))
                <div class="switch-row" x-data="{ on: {{ $on ? 'true' : 'false' }} }">
                    <div class="meta">
                        <strong>{{ $def['label'] }}</strong>
                        <span>{{ $kindDesc[$kind] ?? '' }}</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" x-model="on">
                        <span class="slider"></span>
                    </label>
                    <input type="hidden" name="prefs[{{ $kind }}][inapp]" :value="on ? '1' : '0'">
                    <input type="hidden" name="prefs[{{ $kind }}][email]" :value="on ? '1' : '0'">
                </div>
            @endforeach
            <div style="margin-top:16px;">
                <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="16" /> {{ __('Enregistrer') }}</button>
            </div>
        </form>
    </div>
</x-layouts.participant>
