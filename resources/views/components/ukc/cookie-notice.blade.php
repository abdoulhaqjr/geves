{{-- Bandeau d'information sur les cookies (pages publiques).

     ⚠️⚠️ LE BALISAGE NE VARIE PAS D'UN VISITEUR À L'AUTRE, et c'est la décision
     qui tient tout le reste : les pages publiques sont faites pour être
     CACHEABLES EN HTTP (cf. l'en-tête de EventSeriesController). Rendre le
     bandeau seulement « si le cookie manque » ferait entrer l'état d'UN visiteur
     dans le cache de TOUS - le bandeau resterait collé à ceux qui l'ont écarté,
     ou disparaîtrait pour ceux qui ne l'ont jamais vu. Le serveur rend donc
     toujours la même page, et c'est le navigateur qui décide d'afficher.

     ⚠️ Conséquence : il part MASQUÉ (`hidden`) et le script le révèle. Dans
     l'autre sens - visible puis masqué - il clignoterait à chaque page pour qui
     l'a déjà lu. --}}
@if (\App\Support\CookieConsent::enabled())
    <div class="ukc-cookie" id="ukcCookieNotice" hidden
         role="region" aria-label="{{ __('Information sur les cookies') }}">
        <div class="ukc-cookie__inner">
            <p class="ukc-cookie__text">
                {{ __('Ce site dépose uniquement les cookies nécessaires à son fonctionnement : votre session et la sécurité des formulaires. Aucune mesure d’audience, aucun suivi publicitaire.') }}
            </p>
            <div class="ukc-cookie__acts">
                <a href="{{ route('public.cookies') }}" class="ukc-cookie__link">{{ __('En savoir plus') }}</a>
                <button type="button" id="ukcCookieOk" class="ukc-btn ukc-btn--gold ukc-btn--sm">{{ __('J’ai compris') }}</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const NOM = @json(\App\Support\CookieConsent::cookieName());
            const JOURS = @json(\App\Support\CookieConsent::lifetimeDays());
            const bandeau = document.getElementById('ukcCookieNotice');
            if (!bandeau) return;

            function dejaLu() {
                return document.cookie.split('; ').some((c) => c.split('=')[0] === NOM);
            }

            if (!dejaLu()) bandeau.hidden = false;

            document.getElementById('ukcCookieOk')?.addEventListener('click', function () {
                const fin = new Date();
                fin.setTime(fin.getTime() + JOURS * 864e5);
                // ⚠️ `SameSite` et `Secure` reprennent la session : un cookie posé
                // sans eux serait refusé par le navigateur là où la session, elle,
                // passe - le bandeau reviendrait à chaque page sans rien dire.
                document.cookie = NOM + '=1;expires=' + fin.toUTCString() + ';path=/'
                    + ';samesite={{ config('session.same_site') ?: 'lax' }}'
                    + '{{ config('session.secure') ? ';secure' : '' }}';
                bandeau.hidden = true;
            });
        })();
    </script>
@endif
