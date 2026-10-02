<x-layouts.app :title="'Rapport IA - ' . ($entry['title'] ?? 'Rapport')">
    <div style="max-width:1000px;margin:0 auto;padding:28px 20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;" class="no-print">
            <a href="{{ route('admin.reports.index', ['edition_id' => $edition->id]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Rapports</a>
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
                    <div style="font-size:.85rem;color:var(--ukc-muted);">Université de Kara - Rapport personnalisé (assisté par IA)</div>
                </div>
                <div style="text-align:right;font-size:.8rem;color:var(--ukc-muted);">
                    {{ $entry['created_at'] ?? now()->format('d/m/Y · H:i') }}
                </div>
            </div>

            <hr style="border:none;border-top:1px solid var(--ukc-border);margin:22px 0;">

            <h1 style="font-size:1.35rem;font-weight:900;margin:0 0 16px;">{{ $entry['title'] ?? 'Rapport' }}</h1>

            @foreach (($entry['sections'] ?? []) as $section)
                @if (!empty($section['heading']))
                    <h2 style="font-size:1rem;color:var(--ukc-blue);margin:18px 0 8px;">{{ $section['heading'] }}</h2>
                @endif
                <div style="white-space:pre-line;line-height:1.7;color:var(--ukc-text);">{{ $section['body'] ?? '' }}</div>
            @endforeach

            {{-- Le tableau demandé, quand la réponse en comporte un : c'est la
                 réponse elle-même, pas une annexe. --}}
            @if (! empty($table))
                <div style="overflow:auto;margin-top:18px;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead>
                            <tr>
                                @foreach ($table['columns'] as $col)
                                    <th style="text-align:left;padding:6px 8px;background:#f8fafc;border-bottom:1px solid var(--ukc-border);white-space:nowrap;">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($table['rows'] as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td style="padding:6px 8px;border-bottom:1px solid var(--ukc-border);">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p style="font-size:.78rem;color:var(--ukc-muted);margin-top:6px;">{{ count($table['rows']) }} ligne(s).</p>
            @endif

            {{-- Données réelles jointes selon le périmètre choisi --}}
            @foreach ($datasets as [$name, $headers, $rows])
                <h2 style="font-size:1rem;color:var(--ukc-blue);margin:24px 0 8px;">{{ $name }} <span style="font-size:.8rem;color:var(--ukc-muted);font-weight:600;">({{ count($rows) }} lignes)</span></h2>
                <div style="overflow:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead>
                            <tr>
                                @foreach ($headers as $h)
                                    <th style="text-align:left;padding:6px 8px;background:#f8fafc;border-bottom:1px solid var(--ukc-border);white-space:nowrap;">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($rows, 0, 200) as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td style="padding:6px 8px;border-bottom:1px solid var(--ukc-border);">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($rows) > 200)
                    <p style="font-size:12px;color:var(--ukc-muted);margin-top:6px;">… {{ count($rows) - 200 }} lignes supplémentaires (export Excel/CSV pour la liste complète).</p>
                @endif
            @endforeach

            <hr style="border:none;border-top:1px solid var(--ukc-border);margin:24px 0 12px;">
            <p style="font-size:11.5px;color:var(--ukc-muted);">
                Brouillon généré par IA à partir des données réelles de l’édition - à relire et valider avant toute diffusion officielle.
            </p>
        </div>
    </div>
</x-layouts.app>
