{{-- Porte d'entrée des co-auteurs. Elle ORIENTE vers l'espace participant et
     n'encaisse plus : payer ici créait un SECOND participant pour la même
     personne, et l'attestation partait sur celui-là, que son titulaire ne
     pouvait pas consulter faute de compte (cf. CoauthorController).
     ⚠️ Elle emprunte la grammaire des autres pages publiques - en-tête commun,
     `page-hero`, `ukc-section`, pied de page du colloque - au lieu du bloc
     centré sans contexte qu'elle était : on y arrive par un lien reçu par
     e-mail, sans savoir de quel colloque il s'agit ni comment en sortir. --}}
@php
    // Où en est le visiteur : la barre d'étapes du projet (`ukc-steps`) le dit.
    $etapeRetrouve = $searched && ! empty($foundSubmissions);
    $etapes = [
        ['n' => 1, 'label' => __('Vous retrouver'), 'etat' => $etapeRetrouve ? 'done' : 'active'],
        ['n' => 2, 'label' => __('Votre compte'), 'etat' => $hasPaid ? 'done' : ($etapeRetrouve ? 'active' : '')],
        ['n' => 3, 'label' => __('Régler vos frais'), 'etat' => $hasPaid ? 'done' : ''],
    ];
@endphp

<x-layouts.app :title="__('Frais de co-auteur').' - '.($event->tr('title') ?: config('app.name'))">
    @include('partials.public-header')

    <section class="page-hero">
        <div class="ukc-container">
            <div class="eyebrow">{{ $event->tr('title') ?: $event->year }}</div>
            <h1>{{ __('Frais de co-auteur') }}</h1>
            <p>{{ __('Vous êtes cité(e) comme co-auteur d’une communication acceptée ? Retrouvez-la par votre e-mail, puis réglez vos frais depuis votre espace participant, au tarif de votre grade.') }}</p>
            <a class="note" href="{{ route('public.colloque', $event->slug) }}">
                <x-ukc.icon name="chevron" :size="14" /> {{ __('Revenir à la page du colloque') }}
            </a>
        </div>
    </section>

    <section class="ukc-section ukc-section--tight">
        <div class="ukc-container coauthor-wrap">
            <div class="ukc-steps">
                @foreach ($etapes as $etape)
                    <div @class(['ukc-step', $etape['etat']])>
                        <span class="n">{{ $etape['n'] }}</span> {{ $etape['label'] }}
                    </div>
                @endforeach
            </div>

            {{-- Recherche par e-mail (GET) --}}
            <div class="ukc-card">
                <h2><x-ukc.icon name="search" :size="20" /> {{ __('Retrouver mes communications') }}</h2>
                <form method="GET" action="{{ route('public.coauthor', $event->slug) }}" class="coauthor-search">
                    <div class="ukc-field">
                        <label for="email">{{ __('Votre e-mail de co-auteur') }}</label>
                        <input type="email" id="email" name="email" value="{{ $email }}"
                               placeholder="prenom.nom@example.org" autocomplete="email" required
                               @unless ($searched) autofocus @endunless>
                    </div>
                    <button type="submit" class="ukc-btn ukc-btn--blue">
                        <x-ukc.icon name="search" :size="18" /> {{ __('Rechercher') }}
                    </button>
                </form>
                <p class="ukc-muted" style="font-size:.85rem;margin:12px 0 0;">
                    {{ __('Indiquez l’adresse que l’auteur correspondant a saisie en vous ajoutant à la communication.') }}
                </p>
            </div>

            @if ($searched && empty($foundSubmissions))
                <div class="ukc-alert ukc-alert--warn" style="margin-top:18px;">
                    <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                    <div>{!! __('Aucune communication <strong>acceptée</strong> ne vous associe comme co-auteur avec cet e-mail. Vérifiez l’adresse utilisée lors de la soumission, ou demandez-la à l’auteur correspondant.') !!}</div>
                </div>
            @elseif ($hasPaid)
                {{-- ⚠️ Conservé pour les règlements co-auteur ANCIENS : le retour de
                     passerelle d'un paiement `meta.flow = coauthor` atterrit encore ici. --}}
                <div class="ukc-card coauthor-done">
                    <div class="seal"><x-ukc.icon name="check" :size="28" /></div>
                    <h2>{{ __('Frais déjà réglés') }}</h2>
                    <p>{{ __('Merci, votre participation est à jour. Votre attestation de communication vous sera délivrée.') }}</p>
                    @if ($paidReceipt)
                        <p class="receipt">{{ __('Reçu n°') }} <strong>{{ $paidReceipt->receipt_no }}</strong></p>
                    @endif
                </div>
            @elseif (! empty($foundSubmissions))
                {{-- ⚠️ On ORIENTE, on n'encaisse plus. Le règlement se fait dans l'espace
                     participant, où la communication est déjà rattachée
                     (`LinkSubmissionAuthors` à la soumission, `LinkPendingAuthorships` à
                     l'inscription). --}}
                <div class="ukc-card coauthor-found">
                    <h2><x-ukc.icon name="file" :size="20" /> {{ __('Vos communications acceptées') }}</h2>
                    <ul class="comms">
                        @foreach ($foundSubmissions as $s)
                            <li>
                                <span class="ukc-pill ukc-pill--ok">{{ $s['ref'] }}</span>
                                <span>{{ $s['title'] }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="coauthor-next">
                        <p class="lead">{!! __('Vos frais se règlent depuis <strong>votre espace participant</strong>, au tarif de votre grade.') !!}</p>
                        <ul>
                            <li><x-ukc.icon name="check" :size="16" /> {{ __('Vos communications y sont déjà rattachées.') }}</li>
                            <li><x-ukc.icon name="check" :size="16" /> {{ __('Votre attestation de communication s’y trouvera après le colloque.') }}</li>
                            <li><x-ukc.icon name="check" :size="16" /> {{ __('Votre reçu et vos documents y restent accessibles.') }}</li>
                        </ul>
                        <div class="acts">
                            <a href="{{ $registerUrl }}" class="ukc-btn ukc-btn--primary">
                                <x-ukc.icon name="user-plus" :size="16" /> {{ __('Créer mon compte') }}
                            </a>
                            <a href="{{ $loginUrl }}" class="ukc-btn ukc-btn--outline">
                                <x-ukc.icon name="login" :size="16" /> {{ __('J’ai déjà un compte') }}
                            </a>
                        </div>
                        <p class="hint">{{ __('Utilisez l’adresse ci-dessus : c’est elle qui rattache vos communications à votre compte.') }}</p>
                    </div>
                </div>
            @else
                {{-- Première visite : la page expliquait son formulaire et rien d'autre.
                     On y arrive par un lien reçu par e-mail - dire à quoi elle sert
                     évite de la refermer. --}}
                <div class="coauthor-why">
                    <div>
                        <span class="ico"><x-ukc.icon name="search" :size="18" /></span>
                        <strong>{{ __('Vérifier que vous êtes concerné(e)') }}</strong>
                        <p>{{ __('Seules les communications acceptées qui vous citent comme co-auteur ouvrent droit au règlement.') }}</p>
                    </div>
                    <div>
                        <span class="ico"><x-ukc.icon name="user-plus" :size="18" /></span>
                        <strong>{{ __('Un compte, un seul dossier') }}</strong>
                        <p>{{ __('Vos communications, votre reçu et vos attestations se retrouvent au même endroit, d’un événement à l’autre.') }}</p>
                    </div>
                    <div>
                        <span class="ico"><x-ukc.icon name="diploma" :size="18" /></span>
                        <strong>{{ __('Votre attestation vous suit') }}</strong>
                        <p>{{ __('Le règlement ouvre droit à l’attestation de communication, délivrée dans votre espace après le colloque.') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @include('partials.colloque-footer', ['e' => $event])
</x-layouts.app>
