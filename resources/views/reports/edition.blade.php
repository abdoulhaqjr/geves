<x-layouts.app :title="'Rapport - ' . ($edition->title ?: $edition->year)">
    <div style="max-width:1000px;margin:0 auto;padding:28px 20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;" class="no-print">
            <a href="{{ route('admin.reports.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Rapports</a>
            <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        <div class="ukc-card" style="padding:32px;">
            <div style="height:5px;display:flex;margin:-32px -32px 24px;">
                <span style="flex:1;background:var(--ukc-blue);"></span>
                <span style="flex:1;background:var(--ukc-gold);"></span>
                <span style="flex:1;background:var(--ukc-green);"></span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:900;color:var(--ukc-blue);font-size:1.2rem;">{{ $edition->title ?: $edition->year }}</div>
                    <div style="font-size:.85rem;color:var(--ukc-muted);">Université de Kara - Rapport d’édition</div>
                </div>
                <div style="text-align:right;font-size:.8rem;color:var(--ukc-muted);">
                    Généré le {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>

            <hr style="border:none;border-top:1px solid var(--ukc-border);margin:22px 0;">

            {{-- Synthèse --}}
            <h2 style="font-size:1rem;color:var(--ukc-blue);margin:0 0 12px;">Synthèse</h2>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:8px;">
                <div class="ukc-stat-card"><div class="num">{{ $summary['participants'] }}</div><div class="lbl">Participants</div></div>
                <div class="ukc-stat-card"><div class="num">{{ $summary['submissions'] }}</div><div class="lbl">Communications</div></div>
                <div class="ukc-stat-card"><div class="num">{{ $summary['accepted'] }}</div><div class="lbl">Acceptées</div></div>
                <div class="ukc-stat-card"><div class="num">{{ $summary['paidCount'] }}</div><div class="lbl">Ont payé</div></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:8px;">
                <div class="ukc-stat-card"><div class="num" style="color:var(--ukc-green);">{{ number_format($summary['collected'], 0, ',', ' ') }} {{ $summary['currency'] }}</div><div class="lbl">Encaissé</div></div>
                <div class="ukc-stat-card"><div class="num">{{ number_format($summary['pending'], 0, ',', ' ') }} {{ $summary['currency'] }}</div><div class="lbl">En attente</div></div>
            </div>
            <p style="font-size:.85rem;color:var(--ukc-muted);margin:6px 0 0;">
                Communications par statut :
                @foreach ($statuses as $key => $label){{ $label }} ({{ $summary['submissionsByStatus'][$key] ?? 0 }}){{ ! $loop->last ? ' · ' : '' }}@endforeach
            </p>

            @php
                $tables = [
                    ['Participants', $participantHeaders, $participantRows],
                    ['Communications', $submissionHeaders, $submissionRows],
                    ['Paiements', $paymentHeaders, $paymentRows],
                ];
            @endphp

            @foreach ($tables as [$heading, $headers, $rows])
                <h2 style="font-size:1rem;color:var(--ukc-blue);margin:26px 0 10px;">{{ $heading }} <span style="color:var(--ukc-muted);font-weight:400;">({{ count($rows) }})</span></h2>
                @if (count($rows))
                    <div style="overflow-x:auto;">
                        <table class="ukc-table" style="width:100%;font-size:.82rem;">
                            <thead><tr>@foreach ($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>@foreach ($row as $cell)<td>{{ is_float($cell) ? number_format($cell, 0, ',', ' ') : $cell }}</td>@endforeach</tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="font-size:.85rem;color:var(--ukc-muted);">Aucune donnée.</p>
                @endif
            @endforeach
        </div>
    </div>

    @push('head')
        <style>
            @media print {
                .no-print { display:none !important; }
                body { background:#fff !important; }
                .ukc-card { box-shadow:none !important; border:none !important; }
                table { page-break-inside:auto; }
                tr { page-break-inside:avoid; }
            }
        </style>
    @endpush
</x-layouts.app>
