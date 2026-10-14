<x-layouts.app :title="'Badges - ' . $event->title">
    @php
        // ⚠️ Impression : on tuile AUTANT de badges DE LA DIMENSION CHOISIE que l'A4
        // peut en contenir (comme l'ancien template.docx), sans déformer le badge.
        // Les px CSS s'impriment à 96 dpi : un badge de L px fait L/96 pouce. On calcule
        // combien de colonnes/lignes tiennent sur une A4 portrait, avec une petite
        // tolérance (le badge A6 fait 397 px ≈ 105,03 mm, soit 2×210,06 mm, à peine au
        // delà de l'A4 : sans tolérance il ne passerait qu'à une colonne) et une marge
        // de sécurité pour ne jamais rogner au bord de la feuille.
        $w = (int) ($width ?? 397);
        $h = (int) ($height ?? 560);
        $pageW = 793.7;   // 210 mm @ 96 dpi
        $pageH = 1122.52; // 297 mm @ 96 dpi
        $tol = 1.012;     // ~1,2 % : rattrape le quasi-débordement de l'A6 2-up
        $cols = max(1, (int) floor($pageW * $tol / $w));
        $rows = max(1, (int) floor($pageH * $tol / $h));
        $fit = min($pageW / ($cols * $w), $pageH / ($rows * $h), 1.0);
        $scale = round($fit * 0.995, 4); // marge anti-rognage
        $cellW = (int) floor($w * $scale);
        $cellH = (int) floor($h * $scale);
        $perPage = $cols * $rows;
    @endphp

    <div class="badge-page" style="max-width:900px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;" class="no-print">
            <p class="ukc-muted" style="margin:0;font-size:13px;">
                {{ $badgeType }} · {{ count($badges) }} badge(s) · {{ $cols }}&times;{{ $rows }} = {{ $perPage }} par page A4
            </p>
            <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        <div class="badge-sheet">
            @forelse ($badges as $badge)
                <div class="badge-cell">{!! $badge !!}</div>
            @empty
                <div class="ukc-empty" style="width:100%;"><h3>Aucun destinataire pour ce badge</h3></div>
            @endforelse
        </div>
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
                @page { size: A4 portrait; margin: 0; }

                /* Tuilage : N colonnes de cellules exactement dimensionnées, centrées,
                   le badge réduit par transform pour que {{ $cols }} tiennent en largeur. */
                .badge-sheet {
                    gap:0;
                    width:{{ $cols * $cellW }}px;
                    margin:0 auto;
                    justify-content:flex-start;
                }
                .badge-cell {
                    border:0 !important; border-radius:0 !important;
                    width:{{ $cellW }}px; height:{{ $cellH }}px;
                    overflow:hidden; break-inside:avoid;
                    outline:0.2mm dashed #c8c8c8; outline-offset:-0.1mm; /* repère de découpe */
                }
                .badge-cell .ukc-doc-canvas {
                    transform: scale({{ $scale }});
                    transform-origin: top left;
                }
            }
        </style>
    @endpush
</x-layouts.app>
