<li>
    <div>
        <div class="name">{{ $name }}</div>
        @if (! empty($sub))<div style="font-size:12px;color:var(--ukc-muted);">{{ $sub }}</div>@endif
    </div>
    @if (! empty($att))
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <a href="{{ route('attestation.document', $att->uuid) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">Télécharger</a>
            <a href="{{ route('attestation.verify', $att->uuid) }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Vérifier</a>
        </div>
    @elseif (! empty($links))
        <div style="display:flex;flex-wrap:wrap;gap:8px;">{!! $links !!}</div>
    @else
        <span class="ukc-pill ukc-pill--muted">{{ $status ?? 'Bientôt' }}</span>
    @endif
</li>
