<x-layouts.app :title="__('Vérification d\'attestation')">
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 16px;
                background:radial-gradient(1000px 500px at 50% -10%, rgba(34,62,164,.10), transparent 60%), var(--ukc-bg);">
        <div style="width:100%;max-width:480px;">
            <div class="ukc-card" style="text-align:center;padding:32px 28px;">
                @if (! $attestation)
                    <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;background:#fee2e2;color:#991b1b;
                                display:flex;align-items:center;justify-content:center;">
                        <x-ukc.icon name="close" :size="30" />
                    </div>
                    <h1 style="font-size:1.3rem;font-weight:800;">{{ __('Attestation introuvable') }}</h1>
                    <p style="color:var(--ukc-muted);margin-top:6px;">{{ __('Aucune attestation ne correspond à ce code.') }}</p>
                @elseif ($attestation->revoked)
                    <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;background:#fee2e2;color:#991b1b;
                                display:flex;align-items:center;justify-content:center;">
                        <x-ukc.icon name="close" :size="30" />
                    </div>
                    <h1 style="font-size:1.3rem;font-weight:800;">{{ __('Attestation révoquée') }}</h1>
                    <p style="color:var(--ukc-muted);margin-top:6px;">{{ __('Ce document n’est plus valide.') }}</p>
                @elseif ($proof && $proof['signed'] && ! $proof['valid'])
                    {{-- Attestation présente mais signature illisible/altérée : alerte d'intégrité. --}}
                    <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;background:#fef3c7;color:#92400e;
                                display:flex;align-items:center;justify-content:center;">
                        <x-ukc.icon name="info" :size="30" />
                    </div>
                    <h1 style="font-size:1.3rem;font-weight:800;color:#92400e;">{{ __('Signature non vérifiée') }}</h1>
                    <p style="color:var(--ukc-muted);margin-top:6px;">{{ __('Ce document existe au registre mais sa signature cryptographique n’a pas pu être validée. Ne le considérez pas comme authentique.') }}</p>
                    <div style="margin-top:12px;text-align:left;">
                        <div style="font-size:.72rem;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:4px;">{{ __('Identifiant') }}</div>
                        <div style="font-size:.78rem;color:var(--ukc-text);font-family:var(--ukc-mono);background:var(--ukc-bg);border:1px solid var(--ukc-border);border-radius:8px;padding:8px 10px;word-break:break-all;">
                            {{ $attestation->uuid }}
                        </div>
                    </div>
                @else
                    <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;background:#dcfce7;color:#166534;
                                display:flex;align-items:center;justify-content:center;">
                        <x-ukc.icon name="check" :size="32" />
                    </div>
                    <h1 style="font-size:1.3rem;font-weight:800;color:#166534;">{{ __('Attestation authentique') }}</h1>
                    @if ($proof && $proof['valid'])
                        <div class="ukc-pill--ok" style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:.78rem;">
                            <x-ukc.icon name="shield" :size="14" /> {{ __('Signature cryptographique vérifiée') }}
                        </div>
                    @else
                        <div class="ukc-pill--muted" style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:.78rem;">
                            <x-ukc.icon name="info" :size="14" /> {{ __('Vérifié par le registre officiel') }}
                        </div>
                    @endif
                    <div style="margin-top:20px;text-align:left;font-size:.95rem;">
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                            <span style="color:var(--ukc-muted);">{{ __('Bénéficiaire') }}</span><strong style="text-align:right;">{{ $attestation->recipient_name }}</strong></div>
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                            <span style="color:var(--ukc-muted);">{{ __('Type') }}</span><strong>{{ $attestation->typeLabel() }}</strong></div>
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                            <span style="color:var(--ukc-muted);">{{ __('Événement') }}</span><strong style="text-align:right;">{{ $attestation->edition?->title }}</strong></div>
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                            <span style="color:var(--ukc-muted);">{{ __('Émetteur') }}</span><strong style="text-align:right;">{{ __('Université de Kara · Comité d’organisation') }}</strong></div>
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;">
                            <span style="color:var(--ukc-muted);">{{ __('Émise le') }}</span><strong>{{ $attestation->issued_at?->format('d/m/Y') }}</strong></div>
                    </div>

                    <div style="margin-top:12px;text-align:left;">
                        <div style="font-size:.72rem;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:4px;">{{ __('Identifiant') }}</div>
                        <div style="font-size:.78rem;color:var(--ukc-text);font-family:var(--ukc-mono);background:var(--ukc-bg);border:1px solid var(--ukc-border);border-radius:8px;padding:8px 10px;word-break:break-all;">
                            {{ $attestation->uuid }}
                        </div>
                    </div>

                    <a href="{{ route('attestation.document', $attestation->uuid) }}" target="_blank"
                       class="ukc-btn ukc-btn--blue" style="width:100%;margin-top:16px;">
                        <x-ukc.icon name="download" :size="16" /> {{ __('Télécharger le document officiel') }}
                    </a>

                    @if ($proof && $proof['valid'])
                        <a href="{{ route('attestation.credential', $attestation->uuid) }}"
                           class="ukc-btn ukc-btn--outline" style="width:100%;margin-top:8px;">
                            <x-ukc.icon name="shield" :size="16" /> {{ __('Attestation vérifiable (JSON signé)') }}
                        </a>
                        <p style="font-size:.72rem;color:var(--ukc-muted);margin-top:10px;">
                            {{ __('Signée par') }} <span style="font-family:var(--ukc-mono);">{{ $proof['payload']['iss'] ?? '' }}</span>.
                            {{ __('Vérifiable de façon indépendante via la clé publique de l’émetteur (did:web / JWKS).') }}
                        </p>
                    @endif
                @endif

                @unless ($attestation && ! $attestation->revoked)
                    <p style="font-size:.72rem;color:var(--ukc-light);margin-top:20px;font-family:var(--ukc-mono);">
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
