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
        </div>
    </section>

    <section class="ukc-section">
        <div class="ukc-container ukc-cookie-page">
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="shield" :size="20" /> {{ __('Uniquement le nécessaire') }}</span></h2>
                <p class="ukc-cookie-page__lede">
                    {{ __('La plateforme ne dépose aucun cookie de mesure d’audience, de régie publicitaire ou de réseau social. Les seuls cookies posés servent à vous garder connecté et à protéger les formulaires. C’est pourquoi aucune bannière ne vous demande d’accepter ou de refuser : il n’y a rien de facultatif à refuser.') }}
                </p>
                <p class="ukc-cookie-page__lede">
                    {{ __('Les voici, un par un.') }}
                </p>
            </div>

            <div class="ukc-card" style="padding:0;">
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead>
                            <tr>
                                <th>{{ __('Cookie') }}</th>
                                <th>{{ __('Origine') }}</th>
                                <th>{{ __('À quoi il sert') }}</th>
                                <th>{{ __('Durée') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Support\CookieConsent::inventory() as $c)
                                <tr>
                                    <td><code>{{ $c['name'] }}</code></td>
                                    <td>{{ $c['party'] }}</td>
                                    <td>{{ $c['purpose'] }}</td>
                                    <td style="white-space:nowrap;">{{ $c['duration'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ukc-card">
                <h2><span><x-ukc.icon name="settings" :size="20" /> {{ __('Les refuser quand même') }}</span></h2>
                <p class="ukc-cookie-page__lede">
                    {{ __('Votre navigateur peut bloquer ou effacer ces cookies à tout moment, depuis ses propres réglages. Sachez seulement ce que cela coûte ici : sans cookie de session, la connexion à votre espace devient impossible et l’envoi des formulaires échoue. Rien d’autre ne sera perdu.') }}
                </p>
                <p class="ukc-cookie-page__lede">
                    {{ __('Pour une question sur vos données, écrivez à :mail.', ['mail' => 'dri@univkara.tg']) }}
                </p>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</x-layouts.app>
