<x-layouts.app :title="'Reçu ' . $payment->receipt_no">
    @php($isPreview = ($sample ?? false))

    @if ($isPreview)
        {{-- Aperçu verrouillé : cadre sombre, barre « Fermer », valeurs d'exemple (même grammaire que l'aperçu attestation/badge). --}}
        <div x-data="{ back() { window.location.href = @js($backUrl ?? url('/admin/paiements')); } }"
             x-init="window.addEventListener('keydown', e => { if (e.key === 'Escape') back(); })"
             @click.self="back()"
             style="position:fixed;inset:0;z-index:200;background:#1e293b;overflow:auto;">
            <div style="position:sticky;top:0;display:flex;justify-content:space-between;align-items:center;gap:12px;
                        padding:12px 18px;background:rgba(15,23,42,.9);backdrop-filter:blur(4px);">
                <span style="display:inline-flex;align-items:center;gap:8px;color:#e2e8f0;font-size:.85rem;font-weight:600;">
                    <x-ukc.icon name="receipt" :size="16" />
                    Aperçu du reçu - valeurs d'exemple
                </span>
                <button type="button" @click="back()"
                        style="display:inline-flex;align-items:center;gap:6px;border:0;border-radius:8px;cursor:pointer;
                               padding:8px 14px;background:#f8fafc;color:#0f172a;font-weight:700;font-size:.85rem;">
                    <x-ukc.icon name="close" :size="16" /> Fermer
                </button>
            </div>

            <div style="padding:32px 16px;">
                @include('receipts._document', ['payment' => $payment])
            </div>
        </div>

        @push('head')
            <style>body { background:#1e293b !important; }</style>
        @endpush
    @else
        <div style="max-width:720px;margin:0 auto;padding:32px 20px;">
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;" class="no-print">
                <a href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                    <x-ukc.icon name="download" :size="16" /> Télécharger le PDF
                </a>
                <button onclick="window.print()" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                    <x-ukc.icon name="file" :size="16" /> Imprimer
                </button>
            </div>

            @include('receipts._document', ['payment' => $payment])
        </div>

        @push('head')
            <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } }</style>
        @endpush
    @endif
</x-layouts.app>
