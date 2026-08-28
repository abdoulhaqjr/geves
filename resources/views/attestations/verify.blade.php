<x-layouts.app title="Vérification d'attestation">
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 16px;
                background:radial-gradient(1000px 500px at 50% -10%, rgba(34,62,164,.10), transparent 60%), var(--ukc-bg);">
        <div style="width:100%;max-width:480px;">
            <div class="ukc-card" style="text-align:center;padding:32px 28px;">
                @if (! $attestation)
                    <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;background:#fee2e2;color:#991b1b;
                                display:flex;align-items:center;justify-content:center;">
                        <x-ukc.icon name="close" :size="30" />
                    </div>
                    <h1 style="font-size:1.3rem;font-weight:800;">Attestation introuvable</h1>
                    <p style="color:var(--ukc-muted);margin-top:6px;">Aucune attestation ne correspond à ce code.</p>
                @elseif ($attestation->revoked)
                    <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;background:#fee2e2;color:#991b1b;
                                display:flex;align-items:center;justify-content:center;">
                        <x-ukc.icon name="close" :size="30" />
                    </div>
                    <h1 style="font-size:1.3rem;font-weight:800;">Attestation révoquée</h1>
                    <p style="color:var(--ukc-muted);margin-top:6px;">Ce document n’est plus valide.</p>
                @else
                    <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;background:#dcfce7;color:#166534;
                                display:flex;align-items:center;justify-content:center;">
                        <x-ukc.icon name="check" :size="32" />
                    </div>
                    <h1 style="font-size:1.3rem;font-weight:800;color:#166534;">Attestation authentique</h1>
                    <div style="margin-top:20px;text-align:left;font-size:.95rem;">
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                            <span style="color:var(--ukc-muted);">Bénéficiaire</span><strong style="text-align:right;">{{ $attestation->recipient_name }}</strong></div>
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                            <span style="color:var(--ukc-muted);">Type</span><strong>{{ $attestation->typeLabel() }}</strong></div>
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                            <span style="color:var(--ukc-muted);">Événement</span><strong style="text-align:right;">{{ $attestation->edition?->title }}</strong></div>
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                            <span style="color:var(--ukc-muted);">Émetteur</span><strong style="text-align:right;">Université de Kara · Comité d’organisation</strong></div>
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;">
                            <span style="color:var(--ukc-muted);">Émise le</span><strong>{{ $attestation->issued_at?->format('d/m/Y') }}</strong></div>
                    </div>

                    <div style="margin-top:12px;text-align:left;">
                        <div style="font-size:.72rem;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:4px;">Identifiant</div>
                        <div style="font-size:.78rem;color:var(--ukc-text);font-family:ui-monospace,monospace;background:var(--ukc-bg);border:1px solid var(--ukc-border);border-radius:8px;padding:8px 10px;word-break:break-all;">
                            {{ $attestation->uuid }}
                        </div>
                    </div>

                    <a href="{{ route('attestation.document', $attestation->uuid) }}" target="_blank"
                       class="ukc-btn ukc-btn--blue" style="width:100%;margin-top:16px;">
                        <x-ukc.icon name="download" :size="16" /> Télécharger le document officiel
                    </a>
                @endif

                @unless ($attestation && ! $attestation->revoked)
                    <p style="font-size:.72rem;color:var(--ukc-light);margin-top:20px;font-family:ui-monospace,monospace;">
                        {{ request()->route('uuid') }}
                    </p>
                @endunless
            </div>
            <p style="text-align:center;font-size:.8rem;color:var(--ukc-muted);margin-top:14px;">
                {{ config('app.name') }} — Université de Kara
            </p>
        </div>
    </div>
</x-layouts.app>
