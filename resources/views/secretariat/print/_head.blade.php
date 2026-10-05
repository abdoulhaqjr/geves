{{--
    En-tête partagé des documents imprimables du secrétariat. Conçu une fois par
    événement (onglet Documents → « Personnaliser l'en-tête »), il coiffe tous les
    documents de type feuille (présence, émargement, ordre du jour).

    Variables attendues : $event, $header (org/subtitle/logo/align), $title, $sub (ligne contextuelle, optionnelle).
--}}
@php($align = ($header['align'] ?? 'left') === 'center' ? 'center' : 'left')
<div class="doc-head" style="text-align: {{ $align }};">
    @if (! empty($header['logo']))
        <img src="{{ \App\Support\Branding::mark() }}" alt="" style="height:46px;width:auto;display:block;margin:0 0 10px;{{ $align === 'center' ? 'margin-left:auto;margin-right:auto;' : '' }}">
    @endif
    @if (! empty($header['org']))<div class="app">{{ $header['org'] }}</div>@endif
    <h1>{{ $title }}</h1>
    <div class="ev">{{ $event->title ?: $event->year }}</div>
    @if (! empty($header['subtitle']))<div class="meta">{{ $header['subtitle'] }}</div>@endif
    @isset($sub)
        @if ($sub !== '')<div class="note-line">{{ $sub }}</div>@endif
    @endisset
</div>
