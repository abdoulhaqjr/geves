@php
    $page = match ($status) {
        400 => [
            'title' => __('Demande incorrecte'),
            'message' => __('La demande envoyée ne peut pas être traitée.'),
            'hint' => __('Vérifiez le lien ou les informations saisies, puis recommencez.'),
        ],
        403 => [
            'title' => __('Accès non autorisé'),
            'message' => __('Vous n’avez pas accès à cette page avec votre compte actuel.'),
            'hint' => __('Si cet accès vous semble nécessaire, contactez l’équipe organisatrice.'),
        ],
        404 => [
            'title' => __('Page introuvable'),
            'message' => __('La page que vous recherchez est introuvable.'),
            'hint' => __('Le lien est peut-être incorrect ou la page a été déplacée.'),
        ],
        500 => [
            'title' => __('Une erreur est survenue'),
            'message' => __('Nous n’avons pas pu terminer votre demande.'),
            'hint' => __('Réessayez dans quelques instants. Si le problème persiste, contactez la DRI.'),
        ],
        503 => [
            'title' => __('Service temporairement indisponible'),
            'message' => __('GEVES ne peut pas répondre pour le moment.'),
            'hint' => __('Réessayez un peu plus tard. Merci de votre patience.'),
        ],
    };
    $showRetry = in_array($status, [500, 503], true) && request()->isMethod('GET');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $status }} — {{ $page['title'] }} · {{ config('app.name', 'GEVES') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('error-pages.css') }}">
</head>
<body>
    <header class="error-header">
        <div class="error-container error-header__inner">
            <a class="error-brand" href="{{ url('/') }}" aria-label="{{ __('Retour à l’accueil de GEVES') }}">
                <img src="{{ asset('img/apple-touch-icon.png') }}" alt="" width="44" height="44">
                <span class="error-brand__rule" aria-hidden="true"></span>
                <span class="error-brand__text"><strong>{{ config('app.name', 'GEVES') }}</strong><small>{{ __('Gestion des événements scientifiques') }}</small></span>
            </a>
            <span class="error-header__institution">{{ __('Université de Kara') }}</span>
        </div>
    </header>

    <main class="error-main error-container">
        <section class="error-panel" aria-labelledby="error-title">
            <div class="error-panel__content">
                <p class="error-eyebrow"><span aria-hidden="true"></span>{{ __('Erreur :status', ['status' => $status]) }}</p>
                <h1 id="error-title">{{ $page['title'] }}</h1>
                <p class="error-lead">{{ $page['message'] }}</p>
                <p class="error-hint">{{ $page['hint'] }}</p>
                <div class="error-actions">
                    <a class="error-button error-button--primary" href="{{ url('/') }}">
                        {{ __('Retour à l’accueil') }}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                    @if ($showRetry)
                        <a class="error-button error-button--secondary" href="{{ request()->fullUrl() }}">{{ __('Réessayer') }}</a>
                    @else
                        <a class="error-button error-button--secondary" href="mailto:dri@univkara.tg">{{ __('Contacter la DRI') }}</a>
                    @endif
                </div>
            </div>
            <div class="error-panel__visual" aria-hidden="true">
                <span class="error-panel__orbit error-panel__orbit--outer"></span>
                <span class="error-panel__orbit error-panel__orbit--inner"></span>
                <span class="error-panel__number">{{ $status }}</span>
                <span class="error-panel__dot error-panel__dot--gold"></span>
                <span class="error-panel__dot error-panel__dot--green"></span>
            </div>
        </section>
        <p class="error-support">{{ __('Besoin d’aide ?') }} <a href="mailto:dri@univkara.tg">dri@univkara.tg</a></p>
    </main>

    <footer class="error-footer">
        <div class="error-container error-footer__inner">
            <span>© {{ date('Y') }} {{ __('Université de Kara - Direction de la Recherche et de l’Innovation') }}</span>
            <span>{{ __('Kara, Togo') }}</span>
        </div>
    </footer>
</body>
</html>
