<x-layouts.app :title="'Livret - ' . $event->title">
    <div style="max-width:820px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;" class="no-print">
            <a href="{{ request()->fullUrlWithQuery(['format' => 'docx']) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Word (.docx)
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> PDF
            </a>
            <button onclick="window.print()" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer
            </button>
        </div>

        {{-- Page de garde --}}
        <div style="text-align:center;padding:60px 20px;border-bottom:2px solid var(--ukc-border);margin-bottom:32px;">
            <div style="font-weight:900;color:var(--ukc-blue);font-size:1.1rem;">{{ $event->series?->name }}</div>
            <h1 style="font-size:1.9rem;font-weight:900;margin:14px 0 8px;">Livret des résumés</h1>
            <p style="color:var(--ukc-muted);">{{ $event->title }}</p>
            @if ($event->date_start)
                <p style="color:var(--ukc-muted);font-size:.9rem;">{{ $event->date_start->format('d/m/Y') }}@if ($event->date_end) – {{ $event->date_end->format('d/m/Y') }}@endif</p>
            @endif
        </div>

        @forelse ($groups as $track => $submissions)
            <div style="break-inside:avoid;margin-bottom:28px;">
                <h2 style="font-size:1.25rem;font-weight:800;color:var(--ukc-blue);border-bottom:2px solid var(--ukc-gold);padding-bottom:6px;margin-bottom:16px;">
                    {{ $track }}
                </h2>
                @foreach ($submissions as $s)
                    <div style="margin-bottom:18px;break-inside:avoid;">
                        <div style="font-weight:700;font-size:1.02rem;">{{ $s->title }}</div>
                        <div style="font-size:.85rem;color:var(--ukc-muted);margin:2px 0 6px;">
                            {{ collect($s->authors ?? [])->pluck('name')->filter()->join(', ') }}
                        </div>
                        <div class="ukc-rte" style="font-size:.92rem;line-height:1.6;text-align:justify;">{!! \App\Support\Html::render($s->abstract) !!}</div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="ukc-empty"><h3>Aucune communication acceptée</h3></div>
        @endforelse
    </div>

    @push('head')
        <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } }</style>
    @endpush
</x-layouts.app>
