@if (count($gateways) === 0)
    <span style="font-size:12px;color:var(--ukc-light);">{{ __('Paiement momentanément indisponible.') }}</span>
@else
    @php($ctl = 'padding:7px 10px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:9px;font-size:12.5px;background:#fff;max-width:220px;')
    <form method="POST" action="{{ route('account.formations.enroll', $f) }}"
          style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0;">
        @csrf
        @if (count($gateways) > 1)
            <select name="gateway" aria-label="{{ __('Moyen de paiement') }}" style="{{ $ctl }}">
                @foreach ($gateways as $g)
                    <option value="{{ $g->key() }}">{{ $g->label() }}</option>
                @endforeach
            </select>
        @else
            {{-- ⚠️ **On NOMME le moyen, même quand il n'y a pas à choisir.** Un
                 champ caché seul envoyait le participant chez le prestataire sans
                 qu'un mot de la page le lui ait annoncé : « S'inscrire » ne dit
                 pas qu'on va régler en ligne. --}}
            <input type="hidden" name="gateway" value="{{ $gateways[0]->key() }}">
            <span style="font-size:12.5px;color:var(--ukc-muted);">{{ $gateways[0]->label() }}</span>
        @endif
        {{-- ⚠️ Pas de choix d'opérateur : le moyen se choisit sur la page du
             prestataire (même règle que la page de paiement des frais). --}}
        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="white-space:nowrap;">
            <x-ukc.icon name="card" :size="14" /> {{ $label }}
        </button>
    </form>
@endif
