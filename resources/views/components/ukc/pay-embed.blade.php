@props(['embed' => null])
@php
    // Rien à afficher tant qu'un paiement n'a pas été engagé sur cette page.
    $embed = is_array($embed) ? $embed : null;

@endphp
@if ($embed && ($embed['public_key'] ?? '') !== '' && ($embed['transaction_id'] ?? '') !== '')
    {{-- ⚠️ **Le formulaire du prestataire s'ouvre SUR notre page.** Le payeur ne
         part plus : il reste dans son parcours, avec le montant et le colloque
         sous les yeux. Le voile est une surface modale, d'où le rang 100 de la
         charte - celui des modales, au-dessus du chatbot (55) et des
         slideovers (60). --}}
    <div class="ukc-pay-embed" role="dialog" aria-modal="true" aria-label="{{ __('Paiement sécurisé') }}"
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
            </div>

            <div class="ukc-pay-embed__box" data-pay-embed-box></div>
            <p class="ukc-pay-embed__failed">{{ __('Le service de paiement ne répond pas pour le moment.') }}</p>

            {{-- ⚠️ **Porte de sortie, toujours présente.** Si le script du
                 prestataire ne charge pas - réseau, blocage, coupure de son CDN -
                 le payeur doit pouvoir finir quand même. Un formulaire embarqué
                 sans issue, c'est un paiement perdu. --}}
            <div class="ukc-pay-embed__foot">
                <p>{{ __('Vous ne quittez pas :app : le règlement est traité par notre prestataire de paiement.', ['app' => config('app.name')]) }}</p>
                <p>
                    {{ __('Le formulaire ne s’affiche pas ?') }}
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
