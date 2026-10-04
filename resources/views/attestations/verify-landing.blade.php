{{-- Saisie manuelle d'un code d'attestation.
     ⚠️ Même famille visuelle que la page d'une attestation (`.ukc-cred`) : fond
     dégradé, barre de marque, pastille d'état, cartes à surtitre. C'est la même
     démarche - « ce document est-il authentique ? » - et l'une mène à l'autre.
     Elle portait l'en-tête et le pied de page du site, comme une page
     d'information quelconque, alors qu'on y arrive un diplôme à la main. --}}
<x-layouts.app
    :title="__('Vérifier une attestation - :app', ['app' => config('app.name')])"
    :description="__('Vérifiez l’authenticité d’une attestation délivrée par :app à partir de son code.', ['app' => config('app.name')])">

    <div class="ukc-cred">
        <header class="ukc-cred-top">
            <a href="{{ url('/') }}" class="ukc-cred-brand">
                <img src="{{ \App\Support\Branding::wide() }}" alt="{{ config('app.name') }}">
            </a>
            <span class="ukc-cred-state is-ok">
                <x-ukc.icon name="shield" :size="15" /> {{ __('Registre officiel') }}
            </span>
        </header>

        {{-- L'alerte vit DANS la colonne : hors d'elle, elle s'alignait sur la
             pleine largeur de la page d'attestation (1300px) au-dessus de cartes
             de 680, et ne paraissait plus leur appartenir. --}}
        <div class="ukc-cred-landing">
            @if (! empty($erreur))
                {{-- ⚠️ Un code inconnu n'est pas une page absente : il a été SAISI ici,
                     et la faute de frappe se corrige ici (cf. AttestationController). --}}
                <p class="ukc-cred-alert ukc-cred-landing-alert">
                    <x-ukc.icon name="alert-triangle" :size="18" /> {{ $erreur }}
                </p>
            @endif
            <div class="ukc-card ukc-cred-card">
                <div class="ukc-cred-kicker"><x-ukc.icon name="shield" :size="14" /> {{ __('Vérification') }}</div>
                <h1>{{ __('Vérifier une attestation') }}</h1>
                <p class="ukc-cred-text">
                    {{ __('Chaque attestation porte un identifiant unique, inscrit au registre de l’établissement. Saisissez-le pour ouvrir l’attestation et son contrôle d’authenticité.') }}
                </p>

                <form method="GET" action="{{ route('attestation.verify.landing') }}" class="ukc-cred-form">
                    <div class="ukc-field">
                        <label for="code">{{ __('Identifiant de l’attestation') }}</label>
                        <input type="text" id="code" name="code" required autofocus
                               autocomplete="off" spellcheck="false"
                               placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                               value="{{ request('code') }}">
                        <span class="ukc-hint">{{ __('L’adresse complète de l’attestation fonctionne aussi : collez-la telle quelle.') }}</span>
                    </div>
                    <button type="submit" class="ukc-btn ukc-btn--green ukc-cred-action">
                        <x-ukc.icon name="check-circle" :size="16" /> {{ __('Vérifier l’attestation') }}
                    </button>
                </form>
            </div>

            {{-- Ce que le visiteur a sous les yeux - le document - et ce qu'il en
                 obtiendra. Sans cela, la page n'est qu'un champ à remplir. --}}
            <div class="ukc-card ukc-cred-card ukc-cred-how">
                <div class="ukc-cred-kicker">{{ __('Comment ça marche') }}</div>
                <ol>
                    <li>
                        <span class="n">1</span>
                        <div>
                            <strong>{{ __('Scannez le QR du document') }}</strong>
                            <p>{{ __('Il ouvre directement l’attestation : vous n’avez rien à saisir.') }}</p>
                        </div>
                    </li>
                    <li>
                        <span class="n">2</span>
                        <div>
                            <strong>{{ __('Ou recopiez l’identifiant') }}</strong>
                            <p>{{ __('Il figure sous le QR, en caractères à chasse fixe.') }}</p>
                        </div>
                    </li>
                    <li>
                        <span class="n">3</span>
                        <div>
                            <strong>{{ __('Comparez avec l’original') }}</strong>
                            <p>{{ __('La page affiche le document tel qu’il a été délivré, qui l’a délivré et ce qu’il certifie - signature vérifiée à l’appui.') }}</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</x-layouts.app>
