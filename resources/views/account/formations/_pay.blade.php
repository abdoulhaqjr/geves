@if (count($gateways) === 0)
    <p style="font-size:12px;color:var(--ukc-light);margin:0;">{{ __('Paiement momentanément indisponible.') }}</p>
@else
    <form method="POST" action="{{ route('account.formations.enroll', $f) }}" style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">
        @csrf
        <select name="gateway" style="min-width:160px;padding:6px 8px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;">
            @foreach ($gateways as $g)
                <option value="{{ $g->key() }}">{{ $g->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="card" :size="14" /> {{ $label }}</button>
    </form>
@endif
