@if (count($gateways) === 0)
    <span style="font-size:12px;color:var(--ukc-light);">{{ __('Paiement momentanément indisponible.') }}</span>
@else
    @php($ctl = 'padding:7px 10px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:9px;font-size:12.5px;background:#fff;max-width:220px;')
    <form method="POST" action="{{ route('account.formations.enroll', $f) }}"
          x-data="{ gw: '{{ $gateways[0]->key() }}' }"
          style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0;">
        @csrf
        @if (count($gateways) > 1)
            <select name="gateway" x-model="gw" aria-label="{{ __('Moyen de paiement') }}" style="{{ $ctl }}">
                @foreach ($gateways as $g)
                    <option value="{{ $g->key() }}">{{ $g->label() }}</option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="gateway" value="{{ $gateways[0]->key() }}">
        @endif
        <x-ukc.pay-method :gateways="$gateways" gateway-expr="gw" style="{{ $ctl }}" />
        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="white-space:nowrap;">
            <x-ukc.icon name="card" :size="14" /> {{ $label }}
        </button>
    </form>
@endif
