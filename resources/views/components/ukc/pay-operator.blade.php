@props(['gateways' => []])
@php
    // Opérateurs proposables : ceux de la première passerelle en ligne qui sait
    // cibler un opérateur. Choisir ici évite au payeur de chercher son opérateur
    // dans la liste du prestataire. Le code est revalidé côté passerelle.
    $operators = [];
    foreach ($gateways as $g) {
        if ($g instanceof \App\Payments\Contracts\OnlineGateway && $g->mobileOperators() !== []) {
            $operators = $g->mobileOperators();
            break;
        }
    }
@endphp
@if ($operators !== [])
    <select name="operator" aria-label="{{ __('Opérateur mobile money') }}"
            {{ $attributes->merge(['class' => 'ukc-pay-operator']) }}>
        <option value="">{{ __('Choisir mon opérateur') }}</option>
        @foreach ($operators as $code => $label)
            <option value="{{ $code }}" @selected(old('operator') === $code)>{{ $label }}</option>
        @endforeach
    </select>
@endif
