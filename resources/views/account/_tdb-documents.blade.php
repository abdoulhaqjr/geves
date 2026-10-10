{{--
    « Mes documents » : UN SEUL rang de pastilles pour le reçu, le badge, les
    attestations et le livret.

    ⚠️ Ils vivaient sur trois cartes en fin de page (Paiements & reçus, Mes
    attestations, Documents de mes événements) : le participant qui vient chercher
    une pièce devait deviner laquelle des trois la porte. Chaque pastille fait
    44px de haut, donc atteignable au doigt.

    Reçoit : $documents (liste), $vue, et $grand (vrai = carte principale, après
    le colloque, où retirer ses pièces est le propos de la visite).
--}}
@php
    // ⚠️ `@include`, pas un composant : `@props` n'existe que pour les composants
    // anonymes (`<x-...>`) et compilerait une référence à `$attributes` absente ici.
    $grand = $grand ?? false;
@endphp
<section @class(['ukc-card', 'tdb-docs', 'tdb-docs--grand' => $grand])>
    <h3>
        <x-ukc.icon name="folder" :size="20" />
        {{ __('Mes documents') }}
        <a href="{{ route('account.documents') }}" class="tdb-link">{{ __('Tout voir') }}</a>
    </h3>

    @forelse ($documents as $doc)
        <a href="{{ $doc['url'] }}" target="_blank" rel="noopener" class="tdb-doc">
            <span class="i"><x-ukc.icon :name="$doc['icone']" :size="16" /></span>
            <span class="t">
                {{ $doc['libelle'] }}
                @if ($doc['detail'] !== '')<span class="d">{{ $doc['detail'] }}</span>@endif
            </span>
            <span class="f">{{ $doc['format'] }}</span>
        </a>
    @empty
        <p class="tdb-empty">
            @if ($vue === 'avant')
                {{ __('Vos reçus et attestations apparaîtront ici au fil de votre participation.') }}
            @else
                {{ __('Vos attestations seront disponibles ici dès que votre passage aura été constaté en séance.') }}
            @endif
        </p>
    @endforelse
</section>
