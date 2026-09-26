@props(['gateways' => [], 'phone' => null, 'gatewayExpr' => null])
@php
    use App\Payments\Contracts\OnlineGateway;

    // Opérateurs proposables : ceux de la première passerelle en ligne qui sait
    // cibler un opérateur. Choisir ici évite au payeur de le chercher sur la page
    // du prestataire. Le code est revalidé côté passerelle.
    $operators = [];
    $operatorGateway = null;
    foreach ($gateways as $g) {
        if ($g instanceof OnlineGateway && $g->mobileOperators() !== []) {
            $operators = $g->mobileOperators();
            $operatorGateway = $g->key();
            break;
        }
    }

    // « Intelligent » : on met en tête les opérateurs du pays du numéro de la
    // personne (indicatif E.164), le reste passe dans un second groupe.
    $phone = preg_replace('/\D+/', '', (string) ($phone ?? auth('account')->user()?->phone ?? ''));
    $country = null;
    foreach (['228' => 'Togo', '229' => 'Bénin', '225' => 'Côte d’Ivoire', '221' => 'Sénégal', '227' => 'Niger'] as $prefix => $label) {
        if ($phone !== '' && str_starts_with($phone, (string) $prefix)) {
            $country = $label;
            break;
        }
    }

    $mine = $country ? array_filter($operators, fn ($l) => str_contains($l, $country)) : [];
    $others = array_diff_key($operators, $mine);
@endphp
@if ($operators !== [])
    {{-- Masqué quand le moyen choisi n'est pas la passerelle qui cible l'opérateur
         (ex. « sur place / virement ») : $gatewayExpr est une expression Alpine
         portant la clé de la passerelle sélectionnée. --}}
    <div @if ($gatewayExpr) x-show="{{ $gatewayExpr }} === '{{ $operatorGateway }}'" x-cloak @endif
         {{ $attributes->only('class') }} style="{{ $attributes->get('wrapper-style', '') }}">
        <select name="operator" aria-label="{{ __('Opérateur mobile money') }}"
                {{ $attributes->except(['class', 'wrapper-style']) }}>
            <option value="">{{ __('Choisir mon opérateur') }}</option>
            @if ($mine !== [])
                <optgroup label="{{ $country }}">
                    @foreach ($mine as $code => $label)
                        <option value="{{ $code }}" @selected(old('operator') === $code)>{{ $label }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="{{ __('Autres pays') }}">
                    @foreach ($others as $code => $label)
                        <option value="{{ $code }}" @selected(old('operator') === $code)>{{ $label }}</option>
                    @endforeach
                </optgroup>
            @else
                @foreach ($operators as $code => $label)
                    <option value="{{ $code }}" @selected(old('operator') === $code)>{{ $label }}</option>
                @endforeach
            @endif
        </select>
    </div>
@endif
