@use('App\Support\SessionDocLayout')
<x-layouts.app :title="'Procès-verbaux - ' . $edition->title">
    <div style="max-width:820px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;" class="no-print">
            <h1 style="font-size:1.2rem;font-weight:800;">Procès-verbaux (PV) - {{ $edition->title }}</h1>
            <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        @php($pvHeader = SessionDocLayout::pvHeader($edition))
        @php($pvCols = SessionDocLayout::pvColumns($edition))
        @php($pvSections = SessionDocLayout::pvSections($edition))

        @forelse ($sessions as $session)
            @php($comms = $commsBySession[$session->id] ?? collect())
            @php($report = $session->report)
            <div style="border:1px solid var(--ukc-border);border-radius:14px;padding:24px;margin-bottom:20px;
                        break-inside:avoid;page-break-after:always;background:#fff;">
                <div style="height:5px;display:flex;margin:-24px -24px 18px;border-radius:14px 14px 0 0;overflow:hidden;">
                    <span style="flex:1;background:var(--ukc-blue);"></span>
                    <span style="flex:1;background:var(--ukc-gold);"></span>
                    <span style="flex:1;background:var(--ukc-green);"></span>
                </div>

                <div style="font-size:.78rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;">
                    Procès-verbal · {{ $session->type === 'plenary' ? 'Session plénière' : ('Atelier · ' . ($session->atelier?->title ?? '')) }}
                </div>
                <h2 style="font-size:1.4rem;font-weight:900;margin:4px 0 10px;">{{ $session->label }}</h2>

                @if ($pvHeader)
                    <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:.9rem;color:var(--ukc-text);margin-bottom:18px;">
                        @foreach ($pvHeader as $f)
                            <span><strong>{{ $f['label'] }} :</strong> {{ SessionDocLayout::headerValue($f['key'], $session) }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($pvCols && $comms->isNotEmpty())
                    <div style="font-size:.8rem;font-weight:700;color:var(--ukc-muted);margin-bottom:6px;">Communications</div>
                    <table style="width:100%;border-collapse:collapse;font-size:.9rem;margin-bottom:18px;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--ukc-border);">
                                @foreach ($pvCols as $c)<th style="text-align:left;padding:8px 6px;">{{ $c['label'] }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comms as $slot)
                                <tr style="border-bottom:1px solid var(--ukc-border);">
                                    @foreach ($pvCols as $c)<td style="padding:9px 6px;vertical-align:top;">{{ SessionDocLayout::cell($c['key'], $slot) }}</td>@endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($report)
                    @foreach ($pvSections as $sec)
                        @if ($sec['key'] === 'synthese' && filled($report->summary))
                            <div style="margin-bottom:14px;">
                                <div style="font-size:.8rem;font-weight:700;color:var(--ukc-muted);margin-bottom:4px;">{{ $sec['label'] }}</div>
                                <div style="white-space:pre-line;line-height:1.55;">{{ $report->summary }}</div>
                            </div>
                        @elseif ($sec['key'] === 'points_saillants' && filled($report->highlights))
                            <div style="margin-bottom:14px;">
                                <div style="font-size:.8rem;font-weight:700;color:var(--ukc-muted);margin-bottom:4px;">{{ $sec['label'] }}</div>
                                <div style="white-space:pre-line;line-height:1.55;">{{ $report->highlights }}</div>
                            </div>
                        @elseif ($sec['key'] === 'presents' && $report->attendance !== null)
                            <div style="font-size:.9rem;margin-bottom:14px;">{{ $sec['label'] }} : <strong>{{ $report->attendance }}</strong></div>
                        @endif
                    @endforeach
                    @if ($report->isValidated())
                        <div style="font-size:.8rem;color:var(--ukc-muted);">Validé par le secrétariat @if ($report->validated_at)le {{ $report->validated_at->translatedFormat('d/m/Y') }}@endif.</div>
                    @endif
                @else
                    <div style="font-size:.9rem;color:var(--ukc-muted);font-style:italic;">Rapport de séance non encore rédigé.</div>
                @endif

                <div style="display:flex;gap:40px;margin-top:26px;font-size:.85rem;">
                    <div>Le modérateur<br><span style="color:var(--ukc-muted);">{{ $session->moderator_name ?: '……………………' }}</span></div>
                    <div>Le rapporteur<br><span style="color:var(--ukc-muted);">{{ $session->rapporteur_name ?: '……………………' }}</span></div>
                </div>
            </div>
        @empty
            <div class="ukc-empty"><h3>Aucune séance</h3><p>Créez des séances dans le programme de l'édition.</p></div>
        @endforelse
    </div>

    @push('head')
        <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } }</style>
    @endpush
</x-layouts.app>
