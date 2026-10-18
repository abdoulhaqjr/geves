<x-layouts.app :title="'Badges - ' . $event->title">
    <div class="badge-page" style="max-width:900px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:4px;" class="no-print">
            <p class="ukc-muted" style="margin:0;font-size:13px;">
                Tous les badges prêts - {{ $totalBadges }} badge(s) - {{ count($groups) }} format(s)
            </p>
            <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        {{-- Un bloc de tuilage par FORMAT (saut de page entre groupes) : on ne mêle
             jamais deux tailles de badge sur une même feuille. --}}
        @forelse ($groups as $i => $g)
            @include('documents._badge-sheet', [
                'badges' => $g['badges'],
                'width' => $g['width'],
                'height' => $g['height'],
                'gi' => $i,
                'caption' => implode(', ', $g['labels']),
                'break' => $i > 0,
            ])
        @empty
            <div class="ukc-empty">
                <h3>Aucun badge prêt</h3>
                <p>Aucune catégorie n'a de destinataire pour cet événement.</p>
            </div>
        @endforelse
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
