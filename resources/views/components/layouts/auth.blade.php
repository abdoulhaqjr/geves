@props(['title' => null, 'width' => '460px'])
<x-layouts.app :title="$title">
    {{-- Pages d'authentification - mise en page deux colonnes fidèle au mockup
         connexion.html / inscription.html : panneau de marque à gauche, carte à droite. --}}
    <div class="auth-shell">
        <aside class="auth-brand">
            <div>
                <a href="{{ url('/') }}" class="logo">
                    <img src="{{ \App\Support\Branding::wideWhite() }}" alt="Université de Kara" class="auth-logo">
                </a>
                @isset($brandBody)
                    {{ $brandBody }}
                @else
                    <h1>Bienvenue sur la plateforme <em>scientifique</em></h1>
                    <p>
                        Accédez à votre espace pour suivre vos soumissions, inscriptions, badges
                        et attestations des colloques de l’Université de Kara.
                    </p>
                    <ul>
                        <li>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            Suivi des résumés et évaluations
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            Badges et attestations avec QR
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            Assistance IA Gesco
                        </li>
                    </ul>
                @endisset
            </div>
            <div class="foot">
                <a href="{{ url('/') }}">← Retour à l’accueil</a> · Service institutionnel non commercial
            </div>
        </aside>

        <main class="auth-panel">
            <div class="auth-card" style="max-width:{{ $width }};">
                @isset($heading)
                    <h2 class="auth-card__title">{{ $heading }}</h2>
                    @isset($subheading)
                        <p class="auth-card__sub">{{ $subheading }}</p>
                    @endisset
                @endisset

                {{ $slot }}
            </div>
        </main>
    </div>
</x-layouts.app>
