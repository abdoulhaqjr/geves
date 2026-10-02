<x-layouts.app :title="__('Vérifier une attestation - :app', ['app' => config('app.name')])">
    @include('partials.public-header')

    <section class="page-hero">
        <div class="ukc-container">
            <div class="eyebrow">{{ __('Authenticité des documents') }}</div>
            <h1>{{ __('Vérifier une attestation') }}</h1>
            <p>{{ __('Chaque attestation délivrée par la DRI porte un QR code et un code unique. Scannez le QR (il ouvre directement l’attestation) ou saisissez le code ci-dessous.') }}</p>
        </div>
    </section>

    <section class="ukc-section">
        <div class="ukc-container" style="max-width:620px;">
            <div class="ukc-card">
                <h2><x-ukc.icon name="shield" :size="20" /> {{ __('Saisir un code de vérification') }}</h2>
                @if (! empty($erreur))
                    <div class="ukc-alert ukc-alert--err" style="margin-bottom:14px;">
                        <span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>{{ $erreur }}</div>
                    </div>
                @endif
                <form method="GET" action="{{ route('attestation.verify.landing') }}">
                    <div class="ukc-field">
                        <label for="code">{{ __('Code d’attestation') }} <span class="hint">{{ __('(ou collez l’URL de l’attestation)') }}</span></label>
                        <input type="text" id="code" name="code" required autofocus
                               placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                               value="{{ request('code') }}">
                    </div>
                    <div class="ukc-form-actions">
                        <button type="submit" class="ukc-btn ukc-btn--blue">
                            <x-ukc.icon name="check" :size="18" /> {{ __('Vérifier') }}
                        </button>
                    </div>
                </form>
                <p class="ukc-muted" style="font-size:.85rem;margin:14px 0 0;">
                    {{ __('Le code figure sous le QR de l’attestation. Il ouvre la page de l’attestation, où le document se vérifie.') }}
                </p>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</x-layouts.app>
