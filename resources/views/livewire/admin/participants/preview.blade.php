<div>
    {{-- Bandeau lecture seule --}}
    <div class="ukc-alert ukc-alert--info" style="align-items:center;">
        <span class="ico"><x-ukc.icon name="eye" :size="18" /></span>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;width:100%;">
            <div>Aperçu de l’espace de <strong>{{ $participant->full_name }}</strong> — lecture seule.</div>
            <a href="{{ route('admin.participants.index') }}" wire:navigate class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Participants</a>
        </div>
    </div>

    {{-- Identité --}}
    <div class="ukc-card" style="margin-bottom:18px;">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div class="av" style="width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                        color:#fff;font-weight:800;background:linear-gradient(135deg,var(--ukc-blue),var(--ukc-green));">
                {{ strtoupper(mb_substr($participant->first_name ?: $participant->last_name ?: '?', 0, 1)) }}
            </div>
            <div style="min-width:0;flex:1;">
                <div style="font-weight:800;font-size:1.1rem;">{{ $participant->full_name }}
                    <span class="ukc-pill ukc-pill--info" style="margin-left:6px;">{{ $participant->roleLabel() }}</span>
                    @if ($participant->account)
                        <span class="ukc-pill ukc-pill--ok" style="margin-left:2px;">Compte lié</span>
                    @endif
                </div>
                @if (count($derivedRoles))
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:6px;">
                        <span class="ukc-muted" style="font-size:.8rem;">Rôles (toutes éditions) :</span>
                        @foreach ($derivedRoles as $r)<span class="ukc-pill ukc-pill--muted">{{ $r }}</span>@endforeach
                    </div>
                @endif
                <div style="font-size:.88rem;color:var(--ukc-muted);">
                    {{ $participant->email }}
                    @if ($participant->title) — {{ $participant->title }} @endif
                    @if ($participant->institution) · {{ $participant->institution }} @endif
                </div>
            </div>
            <div style="font-size:.82rem;color:var(--ukc-muted);text-align:right;">
                {{ $participant->edition?->title }}
            </div>
        </div>
    </div>

    <div class="ukc-grid-2" style="grid-template-columns:1fr 1fr;gap:18px;align-items:start;">
        {{-- Communications --}}
        <div class="ukc-card">
            <h2 style="font-size:1rem;margin:0 0 12px;color:var(--ukc-blue);">Communications</h2>
            @forelse ($submissions as $s)
                <div style="padding:10px 0;border-top:1px solid var(--ukc-border);">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;">
                        <span style="font-family:ui-monospace,monospace;font-size:.82rem;color:var(--ukc-muted);">{{ $s->ref_code }}</span>
                        <span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span>
                    </div>
                    <div style="font-weight:600;margin-top:2px;">{{ $s->title }}</div>
                </div>
            @empty
                <p class="ukc-muted" style="margin:0;font-size:.9rem;">Aucune communication.</p>
            @endforelse
        </div>

        {{-- Paiements & reçus --}}
        <div class="ukc-card">
            <h2 style="font-size:1rem;margin:0 0 12px;color:var(--ukc-blue);">Paiements & reçus</h2>
            @forelse ($payments as $p)
                <div style="padding:10px 0;border-top:1px solid var(--ukc-border);display:flex;justify-content:space-between;gap:10px;align-items:center;">
                    <div>
                        <div style="font-weight:700;">{{ number_format((float) $p->amount, 0, ',', ' ') }} {{ $p->currency }}</div>
                        <div style="font-size:.82rem;color:var(--ukc-muted);">{{ $p->methodLabel() }} · {{ $p->paid_at?->format('d/m/Y') ?? '—' }}</div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span class="ukc-pill ukc-pill--{{ $p->statusPill() }}">{{ $p->statusLabel() }}</span>
                        @if ($p->status === 'paid')
                            <a href="{{ route('admin.payments.receipt', $p) }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                <x-ukc.icon name="receipt" :size="14" />
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="ukc-muted" style="margin:0;font-size:.9rem;">Aucun paiement.</p>
            @endforelse
        </div>
    </div>

    {{-- Attestations --}}
    <div class="ukc-card" style="margin-top:18px;">
        <h2 style="font-size:1rem;margin:0 0 12px;color:var(--ukc-blue);">Attestations</h2>
        @forelse ($attestations as $a)
            <div style="padding:10px 0;border-top:1px solid var(--ukc-border);display:flex;justify-content:space-between;gap:10px;align-items:center;">
                <span>{{ $a->typeLabel() }}</span>
                <a href="{{ route('attestation.document', $a->uuid) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                    <x-ukc.icon name="award" :size="14" /> Voir
                </a>
            </div>
        @empty
            <p class="ukc-muted" style="margin:0;font-size:.9rem;">Aucune attestation.</p>
        @endforelse
    </div>

    {{-- Rappel lecture seule --}}
    <p style="text-align:center;color:var(--ukc-light);font-size:.82rem;margin-top:18px;">
        <x-ukc.icon name="lock" :size="14" /> Vue en lecture seule — aucune action n’est possible depuis cet aperçu.
    </p>
</div>
