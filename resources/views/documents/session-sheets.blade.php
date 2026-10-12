<x-layouts.app :title="'Fiches de session - ' . $event->title">
    <div style="max-width:820px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;" class="no-print">
            <h1 style="font-size:1.2rem;font-weight:800;">Fiches de session - {{ $event->title }}</h1>
            <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        @forelse ($sessions as $session)
            @php($comms = $commsBySession[$session->id] ?? collect())
            <div style="border:1px solid var(--ukc-border);border-radius:14px;padding:24px;margin-bottom:20px;
                        break-inside:avoid;page-break-after:always;background:#fff;">
                <div style="height:5px;display:flex;margin:-24px -24px 18px;border-radius:14px 14px 0 0;overflow:hidden;">
                    <span style="flex:1;background:var(--ukc-blue);"></span>
                    <span style="flex:1;background:var(--ukc-gold);"></span>
                    <span style="flex:1;background:var(--ukc-green);"></span>
                </div>

                <div style="font-size:.78rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;">
                    {{ $session->type === 'plenary' ? 'Session plénière' : ('Atelier - ' . ($session->atelier?->title ?? '')) }}
                </div>
                <h2 style="font-size:1.4rem;font-weight:900;margin:4px 0 10px;">{{ $session->label }}</h2>

                @php($headerFields = \App\Support\SessionDocLayout::sheetHeader($event))
                @if ($headerFields)
                    <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:.9rem;color:var(--ukc-text);margin-bottom:18px;">
                        @foreach ($headerFields as $f)
                            <span><strong>{{ $f['label'] }} :</strong> {{ \App\Support\SessionDocLayout::headerValue($f['key'], $session) }}</span>
                        @endforeach
                    </div>
                @endif

                @php($cols = \App\Support\SessionDocLayout::sheetColumns($event))
                <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--ukc-border);">
                            @foreach ($cols as $c)<th style="text-align:left;padding:8px 6px;">{{ $c['label'] }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comms as $slot)
                            <tr style="border-bottom:1px solid var(--ukc-border);">
                                @foreach ($cols as $c)
                                    <td style="padding:10px 6px;vertical-align:top;@if ($c['key'] === 'emargement') min-width:130px;border-bottom:1px solid #94a3b8;@endif">{{ \App\Support\SessionDocLayout::cell($c['key'], $slot) }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ max(1, count($cols)) }}" style="padding:12px 6px;color:var(--ukc-muted);">Aucune communication programmée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @empty
            <div class="ukc-empty"><h3>Aucune session</h3><p>Créez des sessions dans le programme de l’événement.</p></div>
        @endforelse
    </div>

    @push('head')
        <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } }</style>
    @endpush
</x-layouts.app>
