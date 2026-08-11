<div>
    <div class="ukc-page-head" style="margin-bottom:18px;">
        <h2 style="font-size:1.4rem;font-weight:800;">Mes documents</h2>
        <p style="color:var(--ukc-muted);">Attestations, reçus de paiement et documents de vos éditions.</p>
    </div>

    @php($hasAnything = $attestations->isNotEmpty() || $payments->isNotEmpty() || $editionDocuments->isNotEmpty())

    @if (! $hasAnything)
        <div class="ukc-empty">
            <span class="ico"><x-ukc.icon name="folder" :size="28" /></span>
            <h3>Aucun document pour le moment</h3>
            <p>Vos attestations, reçus et livrets apparaîtront ici au fil de votre participation.</p>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;align-items:start;">
            {{-- Attestations --}}
            <div class="ukc-card">
                <h3><x-ukc.icon name="award" :size="20" /> Mes attestations</h3>
                @forelse ($attestations as $a)
                    <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--ukc-border);align-items:center;">
                        <span style="font-size:.88rem;">{{ $a->typeLabel() }}
                            <span style="color:var(--ukc-muted);"> · {{ $a->edition?->year }}</span>
                        </span>
                        <a href="{{ route('attestation.document', $a->uuid) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                            <x-ukc.icon name="diploma" :size="14" /> Télécharger
                        </a>
                    </div>
                @empty
                    <p style="font-size:.88rem;color:var(--ukc-muted);">Aucune attestation pour le moment.</p>
                @endforelse
            </div>

            {{-- Reçus --}}
            <div class="ukc-card">
                <h3><x-ukc.icon name="receipt" :size="20" /> Paiements & reçus</h3>
                @forelse ($payments as $p)
                    <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--ukc-border);align-items:center;">
                        <span style="font-size:.88rem;">
                            <strong>{{ number_format((float) $p->amount, 0, ',', ' ') }} {{ $p->currency }}</strong>
                            <span style="color:var(--ukc-muted);"> · {{ $p->edition?->year }}</span>
                        </span>
                        <a href="{{ route('account.receipt', $p) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                            <x-ukc.icon name="download" :size="14" /> Reçu
                        </a>
                    </div>
                @empty
                    <p style="font-size:.88rem;color:var(--ukc-muted);">Aucun paiement réglé.</p>
                @endforelse
            </div>
        </div>

        {{-- Documents des éditions --}}
        @if ($editionDocuments->isNotEmpty())
            <div class="ukc-card" style="margin-top:20px;">
                <h3><x-ukc.icon name="folder" :size="20" /> Documents de mes éditions</h3>
                @foreach ($editionDocuments as $doc)
                    <div style="padding:12px 0;border-top:1px solid var(--ukc-border);">
                        <div style="font-weight:700;margin-bottom:8px;">{{ $doc['edition']->title ?: $doc['edition']->year }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            @if ($doc['hasBooklet'])
                                <a href="{{ route('account.documents.livret', $doc['edition']) }}" target="_blank"
                                   class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                    <x-ukc.icon name="book" :size="14" /> Livret (imprimable)
                                </a>
                                <a href="{{ route('account.documents.livret', [$doc['edition'], 'format' => 'docx']) }}"
                                   class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                    <x-ukc.icon name="download" :size="14" /> Livret (Word)
                                </a>
                            @endif
                            @foreach ($doc['media'] as $m)
                                <a href="{{ $m->url }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                    <x-ukc.icon name="file" :size="14" /> {{ $m->title ?: $m->typeLabel() }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
