<x-layouts.app :title="'Badges - ' . $event->title">
    <div class="badge-page" style="max-width:900px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:flex-end;align-items:center;gap:12px;margin-bottom:4px;" class="no-print">
            <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        {{-- Tuilage A4 d'un seul format : le partial calcule colonnes, gouttières et
             échelle (cf. _badge-sheet). La légende porte « … par page A4 ». --}}
        @include('documents._badge-sheet', [
            'badges' => $badges,
            'width' => $width,
            'height' => $height,
            'gi' => 0,
            'caption' => $badgeType,
        ])
    </div>

    @push('head')
        @include('documents._fonts')
        <style>
            /* Écran : galerie souple, chaque badge à sa taille native. */
            .badge-sheet { display:flex; flex-wrap:wrap; gap:14px; }
            .badge-cell { border:1px solid var(--ukc-border); border-radius:8px; overflow:hidden; break-inside:avoid; }

            @media print {
                .no-print { display:none !important; }
                html, body { background:#fff !important; }
                .badge-page { max-width:none !important; margin:0 !important; padding:0 !important; }
            }
        </style>
    @endpush
</x-layouts.app>
