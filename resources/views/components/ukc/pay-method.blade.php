@props(['gateways' => [], 'phone' => null, 'gatewayExpr' => null])
@php
    use App\Payments\Contracts\OnlineGateway;

    // La première passerelle en ligne qui sait restreindre le moyen AVANT la
    // redirection. Choisir ici évite au payeur de le chercher sur la page suivante.
    $methods = [];
    $operators = [];
    $methodGateway = null;
    foreach ($gateways as $g) {
        if ($g instanceof OnlineGateway && $g->paymentMethods() !== []) {
            $methods = $g->paymentMethods();
            $operators = $g->mobileOperators();
            $methodGateway = $g->key();
            break;
        }
    }

    // ⚠️ Ce qui n'est pas un opérateur mobile money - la CARTE - se choisit chez
    // nous sans jamais être transmis au prestataire (cf. `FedaPayGateway::initiate`).
    $autres = array_diff_key($methods, $operators);

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
@if ($methods !== [])
    {{-- Masqué quand le moyen choisi n'est pas la passerelle qui cible le moyen
         (ex. « sur place / virement ») : $gatewayExpr est une expression Alpine
         portant la clé de la passerelle sélectionnée. --}}
    <div @if ($gatewayExpr) x-show="{{ $gatewayExpr }} === '{{ $methodGateway }}'" x-cloak @endif
         {{ $attributes->only('class') }} style="{{ $attributes->get('wrapper-style', '') }}">
        <select name="operator" aria-label="{{ __('Moyen de paiement') }}"
                {{ $attributes->except(['class', 'wrapper-style']) }}>
            {{-- ⚠️ Le choix N'EST PAS obligatoire : sans lui, la page du prestataire
                 propose tout ce qu'il accepte. L'intitulé le dit, là où « Choisir mon
                 opérateur » laissait croire qu'il fallait en prendre un - et donc que
                 la carte n'était pas acceptée. --}}
            <option value="">{{ __('Je choisis sur la page de paiement') }}</option>
            @foreach ($autres as $code => $label)
                <option value="{{ $code }}" @selected(old('operator') === $code)>{{ $label }}</option>
            @endforeach
            @if ($operators !== [])
                @if ($mine !== [])
                    <optgroup label="{{ __('Mobile money').' - '.$country }}">
                        @foreach ($mine as $code => $label)
                            <option value="{{ $code }}" @selected(old('operator') === $code)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="{{ __('Mobile money - autres pays') }}">
                        @foreach ($others as $code => $label)
                            <option value="{{ $code }}" @selected(old('operator') === $code)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @else
                    <optgroup label="{{ __('Mobile money') }}">
                        @foreach ($operators as $code => $label)
                            <option value="{{ $code }}" @selected(old('operator') === $code)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endif
            @endif
        </select>
    </div>
@endif
