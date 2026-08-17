<x-layouts.app :title="'Attestation — ' . $attestation->recipient_name">
    <div style="max-width:{{ $width + 80 }}px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px;" class="no-print">
            <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        <div style="display:flex;justify-content:center;border:1px solid var(--ukc-border);border-radius:14px;overflow:auto;background:#fff;box-shadow:var(--ukc-shadow);">
            {!! $canvas !!}
        </div>

        <p style="text-align:center;font-size:.75rem;color:var(--ukc-light);margin-top:12px;" class="no-print">
            Vérification : {{ $verifyUrl }}
        </p>
    </div>

    @push('head')
        <style>@media print { .no-print { display:none !important; } body { background:#fff !important; } }</style>
    @endpush
</x-layouts.app>
