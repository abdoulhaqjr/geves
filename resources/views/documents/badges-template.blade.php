<x-layouts.app :title="'Badges - ' . $edition->title">
    <div style="max-width:900px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px;" class="no-print">
            <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:14px;">
            @forelse ($badges as $badge)
                <div style="border:1px solid var(--ukc-border);border-radius:8px;overflow:hidden;break-inside:avoid;">{!! $badge !!}</div>
            @empty
                <div class="ukc-empty" style="width:100%;"><h3>Aucun participant</h3></div>
            @endforelse
        </div>
    </div>

    @push('head')
        <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } }</style>
    @endpush
</x-layouts.app>
