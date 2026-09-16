<x-layouts.app :title="'Fiches de session — ' . $edition->title">
    <div style="max-width:820px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;" class="no-print">
            <h1 style="font-size:1.2rem;font-weight:800;">Fiches de session — {{ $edition->title }}</h1>
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
                    {{ $session->type === 'plenary' ? 'Session plénière' : ('Atelier · ' . ($session->atelier?->title ?? '')) }}
                </div>
                <h2 style="font-size:1.4rem;font-weight:900;margin:4px 0 10px;">{{ $session->label }}</h2>

                <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:.9rem;color:var(--ukc-text);margin-bottom:16px;">
                    <span><strong>Date :</strong> {{ $session->session_day?->translatedFormat('l d F Y') ?: '—' }}</span>
                    <span><strong>Horaire :</strong>
                        @if ($session->starts_at){{ $session->starts_at->format('H:i') }}@if ($session->ends_at)–{{ $session->ends_at->format('H:i') }}@endif @else — @endif
                    </span>
                    <span><strong>Salle :</strong> {{ $session->room ?: '—' }}</span>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:.9rem;color:var(--ukc-text);margin-bottom:18px;">
                    <span><strong>Modérateur :</strong> {{ $session->moderator_name ?: '—' }}</span>
                    <span><strong>Rapporteur :</strong> {{ $session->rapporteur_name ?: '—' }}</span>
                </div>

                <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--ukc-border);">
                            <th style="text-align:left;padding:8px 6px;width:70px;">Horaire</th>
                            <th style="text-align:left;padding:8px 6px;">Communication</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comms as $slot)
                            @php($sub = $slot->submission)
                            <tr style="border-bottom:1px solid var(--ukc-border);">
                                <td style="padding:10px 6px;font-family:var(--ukc-mono);vertical-align:top;">
                                    {{ $slot->starts_at?->format('H:i') ?: '—' }}
                                </td>
                                <td style="padding:10px 6px;">
                                    <div style="font-weight:700;">{{ $sub?->title }}</div>
                                    <div style="color:var(--ukc-muted);font-size:.85rem;">
                                        {{ collect($sub?->authors ?? [])->pluck('name')->filter()->join(', ') }}
                                        @if ($sub?->ref_code) · réf. {{ $sub->ref_code }} @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" style="padding:12px 6px;color:var(--ukc-muted);">Aucune communication programmée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @empty
            <div class="ukc-empty"><h3>Aucune session</h3><p>Créez des sessions dans le programme de l’édition.</p></div>
        @endforelse
    </div>

    @push('head')
        <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } }</style>
    @endpush
</x-layouts.app>
