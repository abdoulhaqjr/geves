<x-layouts.app :title="'Badges — ' . $edition->title">
    @php
        $roleColors = [
            'author' => '#223ea4', 'coauthor' => '#3b5bc9', 'reviewer' => '#7c3aed',
            'committee' => '#b45309', 'moderator' => '#009933', 'rapporteur' => '#0d9488', 'attendee' => '#64748b',
        ];
    @endphp
    <div style="max-width:820px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px;" class="no-print">
            <button onclick="window.print()" class="ukc-btn ukc-btn--primary ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            @forelse ($participants as $p)
                @php($color = $roleColors[$p->role] ?? '#223ea4')
                <div style="border:1px solid var(--ukc-border);border-radius:12px;overflow:hidden;break-inside:avoid;background:#fff;">
                    <div style="height:8px;background:{{ $color }};"></div>
                    <div style="padding:16px 18px;">
                        <div style="font-size:.72rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;">
                            {{ $edition->colloque?->name }}
                        </div>
                        <div style="font-size:1.15rem;font-weight:800;margin-top:8px;line-height:1.2;">{{ $p->full_name }}</div>
                        @if ($p->institution)
                            <div style="font-size:.82rem;color:var(--ukc-muted);margin-top:2px;">{{ $p->institution }}</div>
                        @endif
                        <div style="display:inline-block;margin-top:10px;font-size:.72rem;font-weight:700;color:#fff;
                                    background:{{ $color }};padding:3px 10px;border-radius:50px;">
                            {{ $p->roleLabel() }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="ukc-empty" style="grid-column:1/-1;"><h3>Aucun participant</h3></div>
            @endforelse
        </div>
    </div>

    @push('head')
        <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } }</style>
    @endpush
</x-layouts.app>
