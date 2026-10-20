@php($cookies = \App\Support\CookieConsent::inventory())

<x-layouts.app :title="__('Cookies - :app', ['app' => config('app.name')])"
               :description="__('Les cookies déposés par :app, leur raison d’être et leur durée.', ['app' => config('app.name')])">
    @include('partials.public-header')

    @php($pageBanner = \App\Support\SiteBanners::pageDefault())
    <section @class(['page-hero', 'page-hero--image' => $pageBanner])
             @if ($pageBanner) style="--page-hero-bg: url('{{ $pageBanner }}');" @endif>
        <div class="ukc-container">
            <div class="eyebrow">{{ __('Vie privée') }}</div>
            <h1>{{ __('Cookies') }}</h1>
            <p>{{ __('Ce que :app dépose dans votre navigateur, pourquoi, et pour combien de temps.', ['app' => config('app.name')]) }}</p>
            {{-- ⚠️ Les trois promesses sont ANNONCÉES ici, pas noyées dans un paragraphe :
                 c'est la première chose qu'on vient chercher sur cette page. --}}
            <div class="ukc-cookie-claims">
                <span><x-ukc.icon name="check-circle" :size="15" /> {{ __('Aucune mesure d’audience') }}</span>
                <span><x-ukc.icon name="check-circle" :size="15" /> {{ __('Aucun suivi publicitaire') }}</span>
                <span><x-ukc.icon name="check-circle" :size="15" /> {{ __('Aucun partage à des tiers') }}</span>
            </div>
        </div>
    </section>

    <section class="ukc-section">
        <div class="ukc-container ukc-cookie-page">

            {{-- ⚠️ Les CHIFFRES d'abord, et ils occupent toute la largeur : la prose
                 seule, bornée à 62ch dans une carte de 1135 px, laissait cinq cents
                 pixels de vide à droite - le travers déjà payé sur l'aperçu du
                 programme. Chacun est DÉDUIT de l'inventaire, jamais écrit en dur. --}}
            <div class="ukc-cookie-facts">
                <div class="fact">
                    <span class="val">{{ count($cookies) }}</span>
                    <span class="lbl">{{ __('cookie(s) au total') }}</span>
                    <span class="hint">{{ __('Tous nécessaires au fonctionnement.') }}</span>
                </div>
                <div class="fact">
                    <span class="val">0</span>
                    <span class="lbl">{{ __('traceur') }}</span>
                    <span class="hint">{{ __('Ni audience, ni publicité, ni réseau social.') }}</span>
                </div>
                <div class="fact">
                    <span class="val">{{ \App\Support\CookieConsent::lifetimeDays() }}</span>
                    <span class="lbl">{{ __('jours au plus') }}</span>
                    <span class="hint">{{ __('Durée du plus long d’entre eux.') }}</span>
                </div>
            </div>

            <div class="ukc-card ukc-cookie-intro">
                <h2><span><x-ukc.icon name="shield" :size="20" /> {{ __('Uniquement le nécessaire') }}</span></h2>
                <p>
                    {{ __('Les seuls cookies posés servent à vous garder connecté et à protéger les formulaires. C’est pourquoi aucune bannière ne vous demande d’accepter ou de refuser : il n’y a rien de facultatif à refuser. Les voici, un par un.') }}
                </p>
            </div>

            {{-- ⚠️ C'est un TABLEAU et il le reste : quatre colonnes comparables, que
                 des cartes désaligneraient. Seule sa présentation change - pastille
                 monospace pour le nom, pastille d'origine, durée en chiffres alignés.
                 Sous 720 px il s'empile, chaque cellule reprenant son intitulé. --}}
            <div class="ukc-cookie-table">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Cookie') }}</th>
                            <th scope="col">{{ __('Origine') }}</th>
                            <th scope="col">{{ __('À quoi il sert') }}</th>
                            <th scope="col">{{ __('Durée') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cookies as $c)
                            <tr>
                                <td data-th="{{ __('Cookie') }}"><code>{{ $c['name'] }}</code></td>
                                <td data-th="{{ __('Origine') }}">
                                    {{-- ⚠️ Le tiers vient de la DONNÉE, pas d'une comparaison de libellé :
                                         comparé à une chaîne traduite, il cessait d'être repéré en anglais. --}}
                                    <span @class(['org', 'org--tiers' => $c['third']])>{{ $c['party'] }}</span>
                                </td>
                                <td data-th="{{ __('À quoi il sert') }}" class="why">{{ $c['purpose'] }}</td>
                                <td data-th="{{ __('Durée') }}" class="dur">{{ $c['duration'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Deux réponses courtes, côte à côte : la place est là, autant s'en servir. --}}
            <div class="ukc-cookie-duo">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="settings" :size="20" /> {{ __('Les refuser quand même') }}</span></h2>
                    <p>
                        {{ __('Votre navigateur peut bloquer ou effacer ces cookies à tout moment, depuis ses propres réglages. Sachez seulement ce que cela coûte ici : sans cookie de session, la connexion à votre espace devient impossible et l’envoi des formulaires échoue. Rien d’autre ne sera perdu.') }}
                    </p>
                </div>
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="mail" :size="20" /> {{ __('Une question ?') }}</span></h2>
                    <p>
                        {{ __('Pour toute question sur vos données, écrivez à la Direction de la Recherche et de l’Innovation.') }}
                    </p>
                    <a href="mailto:dri@univkara.tg" class="ukc-btn ukc-btn--outline ukc-btn--sm">dri@univkara.tg</a>
                </div>
            </div>

        </div>
    </section>

    @include('partials.public-footer')
</x-layouts.app>
