@props(['embed' => null])
@php
    // Rien à afficher tant qu'un paiement n'a pas été engagé sur cette page.
    $embed = is_array($embed) ? $embed : null;

    // ⚠️ **Deux parcours, une seule fenêtre.** `push` : la demande est partie sur
    // le téléphone du payeur, il n'y a aucun formulaire à charger. `form` : le
    // formulaire du prestataire s'ouvre ici, et il lui faut la clé publique.
    $parcours = $embed['journey'] ?? 'form';
    $pret = $parcours === 'push'
        || (($embed['public_key'] ?? '') !== '' && ($embed['transaction_id'] ?? '') !== '');
@endphp
@if ($embed && $pret)
    {{-- ⚠️ **Le formulaire du prestataire s'ouvre SUR notre page.** Le payeur ne
         part plus : il reste dans son parcours, avec le montant et le colloque
         sous les yeux. Le voile est une surface modale, d'où le rang 100 de la
         charte - celui des modales, au-dessus du chatbot (55) et des
         slideovers (60). --}}
    <div class="ukc-pay-embed" data-journey="{{ $parcours }}" role="dialog" aria-modal="true" aria-labelledby="ukc-pay-embed-title"
         data-pay-embed="{{ json_encode($embed, JSON_THROW_ON_ERROR) }}">
        {{-- ⚠️ **L'attente a sa place à l'écran.** Le formulaire du prestataire
             met une à deux secondes à venir : la fenêtre s'ouvrait vide, et une
             page blanche à l'instant de payer se lit comme une panne de GEVES.
             `role="status"` le dit aussi aux lecteurs d'écran. --}}
        <div class="ukc-pay-embed__wait" role="status">
            <p>{{ __('Préparation du paiement sécurisé…') }}</p>
            <span class="ukc-pay-embed__bar" aria-hidden="true"><span></span></span>
        </div>

        <div class="ukc-pay-embed__panel">
            <div class="ukc-pay-embed__head">
                <div class="ukc-pay-embed__top">
                    <span class="ukc-pay-embed__eyebrow">
                        <x-ukc.icon name="shield" :size="14" /> {{ __('Paiement sécurisé') }}
                    </span>
                    {{-- ⚠️ Pas de croix qui ANNULE : la transaction existe déjà côté
                         prestataire, et elle se confirmera d'elle-même si le payeur la
                         termine ailleurs. On ferme la fenêtre, on n'annule rien. --}}
                    <button type="button" class="ukc-pay-embed__close" data-pay-embed-close
                            aria-label="{{ __('Fermer') }}">
                        <x-ukc.icon name="close" :size="18" />
                    </button>
                </div>
                {{-- ⚠️ **Ce qu'on règle, et combien.** Le bandeau ne disait que
                     « Régler en ligne » : le payeur arrivait devant un formulaire venu
                     d'ailleurs sans savoir ce qu'il engageait. C'est le moment du
                     parcours où l'on doute le plus. --}}
                <div class="ukc-pay-embed__what">
                    <h2 id="ukc-pay-embed-title">{{ $embed['title'] ?? __('Frais de participation') }}</h2>
                    <strong class="ukc-pay-embed__amount">{{ $embed['amount'] ?? '' }}</strong>
                </div>
            </div>

            @if ($parcours === 'push')
                {{-- ⚠️ **Aucun formulaire : la demande est sur le téléphone.** C'est
                     le seul parcours où l'opérateur choisi est réellement honoré par
                     le prestataire - sur sa page, le champ n'a aucun effet. Il
                     emporte avec lui les deux écrans que nous ne maîtrisions pas :
                     son sélecteur d'opérateur et sa case « reçu par mail ». --}}
                <div class="ukc-pay-embed__push">
                    <span class="ukc-pay-embed__phone" aria-hidden="true"><x-ukc.icon name="phone" :size="26" /></span>
                    <h3>{{ __('Validez sur votre téléphone') }}</h3>
                    <p class="ukc-pay-embed__to">
                        {{ __('Une demande de paiement vient d’être envoyée au :phone.', ['phone' => $embed['phone'] ?? '']) }}
                        @if (($embed['operator'] ?? '') !== '')
                            <span class="ukc-pay-embed__op">{{ $embed['operator'] }}</span>
                        @endif
                    </p>
                    <p>{{ __('Composez votre code secret pour confirmer le règlement.') }}</p>

                    {{-- ⚠️ **Le navigateur ne décide de rien** : cette ligne dit ce
                         qu'on ATTEND, pas ce qui est acquis. Le verdict vient du
                         serveur, qui interroge le prestataire. --}}
                    {{-- ⚠️ Les trois textes sont ÉCRITS DANS LA PAGE, donc traduits :
                         posés dans le script, ils échapperaient à `__()`. --}}
                    <p class="ukc-pay-embed__state" data-pay-embed-state role="status"
                       data-refuse="{{ __('La demande a été refusée, ou elle a expiré.') }}"
                       data-timeout="{{ __('Nous n’avons pas encore reçu la confirmation. Si vous avez validé, le règlement sera enregistré d’ici quelques minutes.') }}">{{ __('En attente de votre validation…') }}</p>
                    <span class="ukc-pay-embed__bar" aria-hidden="true"><span></span></span>
                </div>
            @else
                <div class="ukc-pay-embed__box" data-pay-embed-box></div>
                <p class="ukc-pay-embed__failed">{{ __('Le service de paiement ne répond pas pour le moment.') }}</p>
            @endif

            {{-- ⚠️ **Porte de sortie, toujours présente.** Si le script du
                 prestataire ne charge pas - réseau, blocage, coupure de son CDN -
                 le payeur doit pouvoir finir quand même. Un formulaire embarqué
                 sans issue, c'est un paiement perdu. --}}
            <div class="ukc-pay-embed__foot">
                <p>{{ __('Vous ne quittez pas :app : le règlement est traité par notre prestataire de paiement.', ['app' => config('app.name')]) }}</p>
                <p>
                    {{ $parcours === 'push' ? __('Vous n’avez rien reçu sur votre téléphone ?') : __('Le formulaire ne s’affiche pas ?') }}
                    @if (($embed['url'] ?? '') !== '')
                        <a href="{{ $embed['url'] }}">{{ __('Ouvrir la page de paiement') }}</a>
                    @else
                        {{ __('Fermez cette fenêtre et réessayez.') }}
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif
