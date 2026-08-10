<div>
    <div class="ukc-card" style="margin-bottom:18px;">
        <div style="font-size:.8rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
            {{ $edition->edition_label }}
        </div>
        <div style="font-weight:800;font-size:1.1rem;margin-top:2px;">{{ $edition->title }}</div>
    </div>

    @if (! $participant)
        <div class="ukc-alert ukc-alert--info">
            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
            <div>Aucune participation trouvée pour cette édition.</div>
        </div>
    @elseif ($this->hasPaid())
        <div class="ukc-card" style="text-align:center;">
            <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;background:#dcfce7;color:#166534;
                        display:flex;align-items:center;justify-content:center;">
                <x-ukc.icon name="check" :size="28" />
            </div>
            <h2 style="justify-content:center;">Frais déjà réglés</h2>
            <p style="color:var(--ukc-muted);">Merci, votre inscription est à jour.</p>
            @if ($receipt = $this->paidReceipt())
                <p style="margin-top:8px;font-size:.9rem;">Reçu n° <strong style="font-family:ui-monospace,monospace;">{{ $receipt->receipt_no }}</strong></p>
            @endif
        </div>
    @elseif (! $this->isEligible())
        <div class="ukc-alert ukc-alert--warn">
            <span class="ico"><x-ukc.icon name="lock" :size="18" /></span>
            <div>Le paiement s’ouvrira dès qu’au moins une de vos communications sera <strong>acceptée</strong>.</div>
        </div>
    @elseif ($instructions)
        <div class="ukc-card">
            <div class="ukc-alert ukc-alert--ok" style="margin-bottom:0;">
                <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
                <div>{{ $instructions }}</div>
            </div>
        </div>
    @else
        <div class="ukc-card">
            <div class="ukc-pay-summary" style="display:flex;justify-content:space-between;align-items:center;gap:12px;
                        padding:14px 16px;border-radius:12px;background:var(--ukc-bg);border:1px solid var(--ukc-border);margin-bottom:18px;">
                <span style="font-weight:600;">Frais d’inscription</span>
                <span style="font-size:1.35rem;font-weight:900;color:var(--ukc-blue);">
                    {{ $amount ? number_format((float) $amount, 0, ',', ' ') . ' FCFA' : 'à préciser' }}
                </span>
            </div>

            @error('pay') <div class="ukc-alert ukc-alert--err"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>{{ $message }}</div></div> @enderror

            @if (! $amount)
                <div class="ukc-alert ukc-alert--warn" style="margin-bottom:0;">
                    <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                    <div>Le montant n’a pas pu être déterminé automatiquement. Contactez l’organisation.</div>
                </div>
            @else
                <p style="font-size:.9rem;color:var(--ukc-muted);margin-bottom:12px;">Choisissez un moyen de paiement :</p>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    @foreach ($gateways as $gateway)
                        <button type="button" class="ukc-btn ukc-btn--outline"
                                style="justify-content:space-between;"
                                wire:click="pay('{{ $gateway->key() }}')" wire:loading.attr="disabled">
                            <span style="display:inline-flex;align-items:center;gap:8px;">
                                <x-ukc.icon name="card" :size="18" /> {{ $gateway->label() }}
                            </span>
                            <x-ukc.icon name="chevron" :size="16" />
                        </button>
                    @endforeach
                </div>
                <div class="ukc-pay-secure" style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--ukc-muted);margin-top:14px;">
                    <x-ukc.icon name="lock" :size="14" /> Paiement sécurisé — un reçu vous sera envoyé par e-mail.
                </div>
            @endif
        </div>
    @endif
</div>
