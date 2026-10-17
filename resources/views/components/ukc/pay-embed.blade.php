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
    <div class="ukc-pay-embed" role="dialog" aria-modal="true" aria-labelledby="ukc-pay-embed-title"
         data-pay-embed="{{ json_encode($embed, JSON_THROW_ON_ERROR) }}">
        <div class="ukc-pay-embed__panel">
            <div class="ukc-pay-embed__head">
                <h2 id="ukc-pay-embed-title">{{ __('Régler en ligne') }}</h2>
                {{-- ⚠️ Pas de croix qui ANNULE : la transaction existe déjà côté
                     prestataire, et elle se confirmera d'elle-même si le payeur la
                     termine ailleurs. On ferme la fenêtre, on n'annule rien. --}}
                <button type="button" class="ukc-pay-embed__close" data-pay-embed-close
                        aria-label="{{ __('Fermer') }}">
                    <x-ukc.icon name="close" :size="18" />
                </button>
            </div>

            <div class="ukc-pay-embed__box" data-pay-embed-box></div>
            <p class="ukc-pay-embed__failed">{{ __('Le service de paiement ne répond pas pour le moment.') }}</p>

            {{-- ⚠️ **Porte de sortie, toujours présente.** Si le script du
                 prestataire ne charge pas - réseau, blocage, coupure de son CDN -
                 le payeur doit pouvoir finir quand même. Un formulaire embarqué
                 sans issue, c'est un paiement perdu. --}}
            <p class="ukc-pay-embed__fallback">
                <x-ukc.icon name="shield" :size="15" />
                {{ __('Le formulaire ne s’affiche pas ?') }}
                @if (($embed['url'] ?? '') !== '')
                    <a href="{{ $embed['url'] }}">{{ __('Ouvrir la page de paiement sécurisée') }}</a>
                @else
                    {{ __('Fermez cette fenêtre et réessayez.') }}
                @endif
            </p>
        </div>
    </div>
@endif
