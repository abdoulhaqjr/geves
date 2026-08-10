<x-layouts.app :title="'Attestation — ' . $attestation->recipient_name">
    <div style="max-width:900px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px;" class="no-print">
            <button onclick="window.print()" class="ukc-btn ukc-btn--primary ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        <div class="attestation" style="background:#fff;border:1px solid var(--ukc-border);border-radius:16px;
             padding:48px;position:relative;overflow:hidden;box-shadow:var(--ukc-shadow);">
            <div style="position:absolute;top:0;left:0;right:0;height:6px;display:flex;">
                <span style="flex:1;background:var(--ukc-blue);"></span>
                <span style="flex:1;background:var(--ukc-gold);"></span>
                <span style="flex:1;background:var(--ukc-green);"></span>
            </div>

            <div style="text-align:center;">
                <div style="font-weight:900;color:var(--ukc-blue);font-size:1.1rem;letter-spacing:.02em;">
                    {{ $attestation->edition?->colloque?->name }}
                </div>
                <div style="font-size:.85rem;color:var(--ukc-muted);">Université de Kara</div>

                <h1 style="font-size:2rem;font-weight:900;color:var(--ukc-text);margin:28px 0 6px;letter-spacing:-.01em;">
                    Attestation de {{ mb_strtolower($attestation->typeLabel()) }}
                </h1>
                <div style="width:80px;height:3px;background:var(--ukc-gold);margin:0 auto 28px;border-radius:3px;"></div>

                <p style="font-size:1rem;color:var(--ukc-muted);">Décernée à</p>
                <p style="font-size:1.6rem;font-weight:800;color:var(--ukc-blue);margin:6px 0 22px;">
                    {{ $attestation->recipient_name }}
                </p>

                <p style="font-size:1rem;color:var(--ukc-text);line-height:1.7;max-width:60ch;margin:0 auto;">
                    pour sa {{ mb_strtolower($attestation->typeLabel()) }} à
                    <strong>{{ $attestation->edition?->title }}</strong>@if ($attestation->edition?->location), {{ $attestation->edition->location }}@endif.
                </p>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:40px;gap:24px;flex-wrap:wrap;">
                <div style="font-size:.85rem;color:var(--ukc-muted);">
                    <div>Délivrée le {{ $attestation->issued_at?->format('d/m/Y') }}</div>
                    <div style="margin-top:6px;font-family:ui-monospace,monospace;font-size:.75rem;">Réf. {{ $attestation->uuid }}</div>
                </div>
                <div style="text-align:center;">
                    <div style="width:120px;height:120px;">{!! $qr !!}</div>
                    <div style="font-size:.7rem;color:var(--ukc-light);margin-top:4px;">Scannez pour vérifier</div>
                </div>
            </div>
        </div>

        <p style="text-align:center;font-size:.75rem;color:var(--ukc-light);margin-top:12px;" class="no-print">
            Vérification : {{ $verifyUrl }}
        </p>
    </div>

    @push('head')
        <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } .attestation { border:none !important; box-shadow:none !important; } }</style>
    @endpush
</x-layouts.app>
