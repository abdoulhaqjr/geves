<x-layouts.participant title="Inscription" subtitle="Frais d’inscription au colloque">
<div>
    @php
        $step = $paid ? 4 : ($eligible ? 3 : 2);
    @endphp

    {{-- Statut d'inscription --}}
    <div class="ukc-card" style="margin-bottom:20px;">
        <h3>Statut d’inscription - {{ $event->title ?: $event->year }}</h3>
        <div class="ukc-steps">
            <div class="ukc-step done">Profil</div>
            <div @class(['ukc-step', 'done' => $step > 2, 'active' => $step === 2])>Choix du tarif</div>
            <div @class(['ukc-step', 'done' => $step > 3, 'active' => $step === 3])>Paiement</div>
            <div @class(['ukc-step', 'done' => $step === 4])>Confirmé</div>
        </div>
        <p style="font-size:14px;color:var(--ukc-muted);margin:0;">
            @if ($paid)
                Votre inscription est <strong>confirmée</strong>. Merci !
            @elseif ($eligible)
                Une de vos communications est acceptée : réglez vos frais ci-dessous pour finaliser votre inscription.
            @else
                Votre inscription s’ouvrira après <strong>acceptation</strong> de votre communication (ou pour les auditeurs).
            @endif
        </p>
    </div>

    {{-- Grille des tarifs --}}
    @if (! empty($tiers))
        <div class="ukc-grid-3" style="margin-bottom:20px;">
            @foreach ($tiers as $tier)
                @php($isApplicable = $applicableLabel !== null && ($tier['label'] ?? null) === $applicableLabel)
                <div class="ukc-card ukc-tariff-card {{ $isApplicable ? 'selected' : '' }}" style="text-align:center;">
                    @if ($isApplicable)<div style="font-size:11px;font-weight:700;color:var(--ukc-blue);margin-bottom:4px;">VOTRE TARIF</div>@endif
                    <div style="font-size:13px;font-weight:700;color:var(--ukc-muted);margin-bottom:8px;">{{ $tier['label'] ?? 'Tarif' }}</div>
                    <div style="font-size:1.6rem;font-weight:900;color:var(--ukc-blue);">{{ number_format((float) ($tier['amount'] ?? 0), 0, ',', ' ') }} <small style="font-size:.7em;">FCFA</small></div>
                    <div style="margin-top:14px;">
                        @if ($isApplicable)
                            <span class="ukc-pill ukc-pill--ok">Applicable</span>
                        @else
                            <span class="ukc-pill ukc-pill--muted">Selon profil</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <p class="ukc-hint" style="margin:-8px 0 20px;">Le tarif est déterminé par votre <strong>type de participant</strong> (modifiable dans Profil).</p>
    @endif

    {{-- Paiement --}}
    @if (! $participant)
        <div class="ukc-alert ukc-alert--info"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>Aucune participation trouvée pour cette événement.</div></div>
    @elseif ($paid)
        <div class="ukc-card" style="text-align:center;">
            <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;background:#dcfce7;color:#166534;display:flex;align-items:center;justify-content:center;">
                <x-ukc.icon name="check" :size="28" />
            </div>
            <h3 style="justify-content:center;">Frais déjà réglés</h3>
            <p style="color:var(--ukc-muted);">Merci, votre inscription est à jour.</p>
            @if ($paidReceipt)<p style="margin-top:8px;font-size:.9rem;">Reçu n° <strong style="font-family:var(--ukc-mono);">{{ $paidReceipt->receipt_no }}</strong> - disponible dans <a href="{{ route('account.documents') }}" style="color:var(--ukc-blue);font-weight:700;">Documents</a>.</p>@endif
        </div>
    @elseif ($frozen)
        {{-- Événement archivée : la page reste consultable (tarifs, reçu) mais le
             parcours de règlement est clos. --}}
        <div class="ukc-alert ukc-alert--info">
            <span class="ico"><x-ukc.icon name="lock" :size="18" /></span>
            <div>Cette événement est <strong>archivée</strong> : le règlement en ligne est clos. Rapprochez-vous du secrétariat si un paiement reste à régulariser.</div>
        </div>
    @elseif (! $eligible)
        <div class="ukc-alert ukc-alert--warn"><span class="ico"><x-ukc.icon name="lock" :size="18" /></span><div>Le paiement s’ouvrira dès qu’au moins une de vos communications sera <strong>acceptée</strong>.</div></div>
    @elseif (session('pay_instructions'))
        <div class="ukc-card"><div class="ukc-alert ukc-alert--ok" style="margin:0;"><span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span><div>{{ session('pay_instructions') }}</div></div></div>
    @else
        <div class="ukc-card" x-data="{ gw: '{{ optional($gateways[0] ?? null)->key() }}' }">
            <h3>Paiement Mobile Money</h3>
            <p style="font-size:14px;color:var(--ukc-muted);margin-bottom:16px;">
                Paiement sécurisé par <strong>Mobile Money</strong> ou <strong>carte bancaire</strong>.
                Le reçu PDF sera disponible dans <strong>Documents</strong> après confirmation.
            </p>

            <div class="ukc-pay-summary">
                <div>
                    <div style="font-size:12px;color:var(--ukc-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Montant à régler</div>
                    <div class="amount">{{ $amount ? number_format((float) $amount, 0, ',', ' ').' FCFA' : 'à préciser' }}</div>
                    @if ($applicableLabel)<div style="font-size:13px;color:var(--ukc-muted);">Tarif : {{ $applicableLabel }}</div>@endif
                </div>
                <span class="ukc-pill ukc-pill--warn">En attente de paiement</span>
            </div>

            @error('pay') <div class="ukc-alert ukc-alert--err" style="margin-bottom:14px;"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>{{ $message }}</div></div> @enderror

            @if (! $amount)
                <div class="ukc-alert ukc-alert--warn" style="margin:0;"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>Le montant n’a pas pu être déterminé automatiquement. Contactez l’organisation.</div></div>
            @else
                <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--ukc-text);">Choisir un moyen de paiement</div>
                <form method="POST" action="{{ route('account.payment.pay', $event) }}">
                    @csrf
                    <input type="hidden" name="gateway" :value="gw">
                    <div class="ukc-mm-methods">
                        @foreach ($gateways as $gateway)
                            @php($k = $gateway->key())
                            <label class="ukc-mm-method" :class="{ selected: gw === '{{ $k }}' }" @click="gw = '{{ $k }}'">
                                <input type="radio" name="mm" value="{{ $k }}" @checked($loop->first)>
                                <div class="mm-logo {{ $k === 'manual' ? 'manual' : 'card' }}">
                                    <x-ukc.icon :name="$k === 'manual' ? 'receipt' : 'card'" :size="18" />
                                </div>
                                <span class="mm-name">{{ $gateway->label() }}</span>
                                <span class="mm-sub">{{ $k === 'manual' ? 'Validation admin' : 'Paiement en ligne' }}</span>
                            </label>
                        @endforeach
                    </div>
                    {{-- N'apparaît que si le moyen choisi sait cibler un opérateur. --}}
                    <x-ukc.pay-operator :gateways="$gateways" gateway-expr="gw" id="operator"
                                        class="ukc-field" wrapper-style="max-width:340px;margin-top:14px;"
                                        style="width:100%;" />
                    <div class="ukc-form-actions">
                        <button type="submit" class="ukc-btn ukc-btn--green">
                            <x-ukc.icon name="card" :size="16" /> Payer {{ number_format((float) $amount, 0, ',', ' ') }} FCFA
                        </button>
                    </div>
                </form>

                <div class="ukc-pay-secure">
                    <x-ukc.icon name="shield" :size="16" /> Paiement chiffré · Reçu automatique après confirmation
                </div>
            @endif
        </div>
    @endif
</div>
</x-layouts.participant>
