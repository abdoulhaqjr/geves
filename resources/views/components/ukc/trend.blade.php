@props([
    'trend' => null,          // sortie de App\Support\Trend::of()
    'period' => '7 j',         // libellé de période (affiché après la variation)
    'higherIsBad' => false,    // ex. « refusées » / « échecs » : hausse = mauvais signe
])
@php
    $dir = $trend['dir'] ?? 'flat';
    $delta = $trend['delta'] ?? 0;
    $pct = $trend['pct'] ?? null;
    // Couleur : par défaut hausse = positif (vert). Inversé si higherIsBad.
    $good = $dir === 'flat' ? null : (($dir === 'up') xor $higherIsBad);
    $tone = $good === null ? 'flat' : ($good ? 'up' : 'down');
    $arrow = $dir === 'up' ? '▲' : ($dir === 'down' ? '▼' : '•');
@endphp
<span class="tw-stat__trend tw-stat__trend--{{ $tone }}" title="{{ __('vs période précédente') }}">
    <span class="ar">{{ $arrow }}</span>
    <span>{{ $delta > 0 ? '+' : '' }}{{ $delta }}@if ($pct !== null) - {{ $pct }}%@endif</span>
    <span class="per">/ {{ $period }}</span>
</span>
