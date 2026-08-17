<x-layouts.app title="Vérifier une attestation — UK Colloques">
    @include('partials.public-header')

    <section class="page-hero">
        <div class="ukc-container">
            <div class="eyebrow">Authenticité des documents</div>
            <h1>Vérifier une attestation</h1>
            <p>Chaque attestation délivrée par la DRI porte un QR code et un code unique. Scannez le QR
                (il ouvre directement le résultat) ou saisissez le code ci-dessous.</p>
        </div>
    </section>

    <section class="ukc-section">
        <div class="ukc-container" style="max-width:620px;">
            <div class="ukc-card">
                <h2><x-ukc.icon name="shield" :size="20" /> Saisir un code de vérification</h2>
                <form method="GET" action="{{ route('attestation.verify.landing') }}">
                    <div class="ukc-field">
                        <label for="code">Code d’attestation <span class="hint">(ou collez l’URL de vérification)</span></label>
                        <input type="text" id="code" name="code" required autofocus
                               placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                               value="{{ request('code') }}">
                    </div>
                    <div class="ukc-form-actions">
                        <button type="submit" class="ukc-btn ukc-btn--blue">
                            <x-ukc.icon name="check" :size="18" /> Vérifier
                        </button>
                    </div>
                </form>
                <p class="ukc-muted" style="font-size:.85rem;margin:14px 0 0;">
                    Le code figure sous le QR de l’attestation. La vérification indique le nom, le type
                    d’attestation et l’édition concernée.
                </p>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</x-layouts.app>
