<x-layouts.app :title="'Reçu ' . $payment->receipt_no">
    <div style="max-width:720px;margin:0 auto;padding:32px 20px;">
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;" class="no-print">
            <a href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Télécharger le PDF
            </a>
            <button onclick="window.print()" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                <x-ukc.icon name="file" :size="16" /> Imprimer
            </button>
        </div>

        <div class="ukc-card" style="padding:32px;">
            <div style="height:5px;display:flex;margin:-32px -32px 24px;border-radius:0;">
                <span style="flex:1;background:var(--ukc-blue);"></span>
                <span style="flex:1;background:var(--ukc-gold);"></span>
                <span style="flex:1;background:var(--ukc-green);"></span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:900;color:var(--ukc-blue);font-size:1.1rem;">{{ $payment->edition?->colloque?->name }}</div>
                    <div style="font-size:.85rem;color:var(--ukc-muted);">Université de Kara</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:.8rem;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;">Reçu de paiement</div>
                    <div style="font-family:var(--ukc-mono);font-weight:800;font-size:1.05rem;">{{ $payment->receipt_no }}</div>
                </div>
            </div>

            <hr style="border:none;border-top:1px solid var(--ukc-border);margin:24px 0;">

            <table style="width:100%;font-size:.95rem;border-collapse:collapse;">
                <tr><td style="padding:8px 0;color:var(--ukc-muted);">Reçu de</td>
                    <td style="padding:8px 0;text-align:right;font-weight:700;">{{ $payment->participant?->full_name }}</td></tr>
                <tr><td style="padding:8px 0;color:var(--ukc-muted);">E-mail</td>
                    <td style="padding:8px 0;text-align:right;">{{ $payment->participant?->email }}</td></tr>
                <tr><td style="padding:8px 0;color:var(--ukc-muted);">Édition</td>
                    <td style="padding:8px 0;text-align:right;">{{ $payment->edition?->title }}</td></tr>
                <tr><td style="padding:8px 0;color:var(--ukc-muted);">Moyen de paiement</td>
                    <td style="padding:8px 0;text-align:right;">{{ $payment->methodLabel() }}</td></tr>
                <tr><td style="padding:8px 0;color:var(--ukc-muted);">Date</td>
                    <td style="padding:8px 0;text-align:right;">{{ $payment->paid_at?->format('d/m/Y') }}</td></tr>
            </table>

            <div style="margin-top:24px;padding:18px 20px;background:var(--ukc-bg);border-radius:12px;
                        display:flex;justify-content:space-between;align-items:center;">
                <span style="font-weight:700;">Montant réglé</span>
                <span style="font-size:1.5rem;font-weight:900;color:var(--ukc-blue);">
                    {{ number_format((float) $payment->amount, 0, ',', ' ') }} {{ $payment->currency }}
                </span>
            </div>

            <p style="margin-top:24px;font-size:.8rem;color:var(--ukc-light);text-align:center;">
                Reçu généré le {{ now()->format('d/m/Y') }} — {{ $payment->edition?->colloque?->name }}.
            </p>
        </div>
    </div>

    @push('head')
        <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } }</style>
    @endpush
</x-layouts.app>
