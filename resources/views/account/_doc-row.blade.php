<li>
    <div>
        <div class="name">{{ $name }}</div>
        @if (! empty($sub))<div style="font-size:12px;color:var(--ukc-muted);">{{ $sub }}</div>@endif
    </div>
    @if (! empty($att))
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            {{-- ⚠️ Délivrance PHYSIQUE : l'attestation existe et se vérifie, mais
                 l'exemplaire qui fait foi est remis en main propre. Pas de
                 téléchargement ici - deux documents circuleraient, dont un que
                 l'émetteur n'a jamais délivré. Ce qui s'affiche est un spécimen. --}}
            @if ($papier ?? false)
                <span class="ukc-pill ukc-pill--muted">
                    @if ($att->delivered_at)
                        {{ __('Remise le') }} {{ $att->delivered_at->format('d/m/Y') }}
                    @else
                        {{ __('À retirer au secrétariat') }}
                    @endif
                </span>
                <a href="{{ route('attestation.document', $att->uuid) }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ __('Vérifier (spécimen)') }}</a>
            @else
                <a href="{{ route('attestation.document', $att->uuid) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">Télécharger</a>
                <a href="{{ route('attestation.document', $att->uuid) }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Vérifier</a>
            @endif
        </div>
    @elseif (! empty($links))
        <div style="display:flex;flex-wrap:wrap;gap:8px;">{!! $links !!}</div>
    @else
        <span class="ukc-pill ukc-pill--muted">{{ $status ?? 'Bientôt' }}</span>
    @endif
</li>
