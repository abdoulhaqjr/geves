@php
    $step = $paid ? 4 : ($participant ? ($eligible ? 3 : 2) : 1);
    $steps = ['Participation', 'Décision', 'Paiement', 'Confirmé'];
    $status = $paid ? 'Inscription confirmée' : ($frozen ? 'Événement clôturé' : ($eligible ? 'Paiement à effectuer' : ($participant ? 'Décision en attente' : 'Participation à créer')));
    $statusPill = $paid ? 'ok' : (($eligible && ! $frozen) ? 'info' : 'muted');
    // Petit surtitre (eyebrow) réutilisé, au bleu de la charte.
    $eye = 'display:block;font-weight:800;font-size:11px;letter-spacing:.09em;text-transform:uppercase;color:var(--ukc-blue);margin-bottom:3px;';
@endphp

<x-layouts.participant title="Inscription & paiement" subtitle="Suivez votre participation et réglez vos frais">
<div class="account-payment">
    {{-- ===== Parcours d'inscription ===== --}}
    <div class="ukc-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
            <div style="min-width:0;">
                <span style="{{ $eye }}">Votre parcours d’inscription</span>
                <h2 style="margin:0;font-size:clamp(18px,2vw,23px);line-height:1.3;">{{ $event->title ?: $event->year }}</h2>
            </div>
            <span class="ukc-pill ukc-pill--{{ $statusPill }}" style="flex:none;">{{ $status }}</span>
        </div>
        <div class="ukc-steps" style="margin-bottom:0;" aria-label="Étapes de l’inscription">
            @foreach ($steps as $index => $label)
                @php($number = $index + 1)
                <div @class(['ukc-step', 'done' => $number < $step, 'active' => $number === $step]) @if ($number === $step) aria-current="step" @endif>
                    <span class="n">{{ $number < $step ? '✓' : $number }}</span> {{ $label }}
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== Étape courante ===== --}}
    @if (! $participant && ! $frozen)
        <div class="ukc-card">
            <h2><x-ukc.icon name="file" :size="20" /> Aucune participation liée à cet événement</h2>
            <p style="color:var(--ukc-muted);margin:0 0 16px;max-width:760px;">Vos frais apparaîtront ici lorsque votre participation sera enregistrée. Si vous avez déposé un résumé, consultez son suivi dans vos communications. Pour assister sans présenter de communication, contactez l’organisation.</p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="{{ route('account.submissions.index') }}" class="ukc-btn ukc-btn--blue">Voir mes communications</a>
                @if ($event->submission_open && $event->slug)
                    <a href="{{ route('account.submissions.create', $event->slug) }}" class="ukc-btn ukc-btn--outline">Déposer un résumé</a>
                @endif
            </div>
        </div>
    @elseif ($paid)
        <div class="ukc-card">
            <h2><x-ukc.icon name="check-circle" :size="20" /> Vos frais sont réglés</h2>
            <p style="color:var(--ukc-muted);margin:0 0 16px;">Votre inscription est à jour pour cet événement.@if ($paidReceipt) Reçu n° <strong>{{ $paidReceipt->receipt_no }}</strong>.@endif</p>
            <a href="{{ route('account.documents') }}" class="ukc-btn ukc-btn--blue">Voir mes documents</a>
        </div>
    @elseif ($frozen)
        <div class="ukc-card">
            <h2><x-ukc.icon name="lock" :size="20" /> Cet événement est archivé</h2>
            <p style="color:var(--ukc-muted);margin:0;">Le paiement en ligne est fermé. Si votre dossier reste à régulariser, contactez le secrétariat de l’événement.</p>
        </div>
    @elseif (! $eligible)
        <div class="ukc-card">
            <h2><x-ukc.icon name="clock" :size="20" /> Le paiement n’est pas encore ouvert</h2>
            <p style="color:var(--ukc-muted);margin:0 0 16px;max-width:760px;">Vous pourrez régler vos frais lorsqu’au moins une de vos communications aura été acceptée. Suivez l’avancement de votre dossier depuis votre espace.</p>
            <a href="{{ route('account.submissions.index') }}" class="ukc-btn ukc-btn--blue">Suivre mes communications</a>
        </div>
    @elseif (session('pay_instructions'))
        <div class="ukc-card">
            <h2><x-ukc.icon name="check-circle" :size="20" /> Poursuivez votre règlement</h2>
            <p style="color:var(--ukc-muted);margin:0;">{{ session('pay_instructions') }}</p>
        </div>
    @else
        <div class="ukc-card" x-data="{ gw: '{{ optional($gateways[0] ?? null)->key() }}' }">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap;margin-bottom:18px;">
                <div style="min-width:0;">
                    <h2 style="margin:0 0 6px;">Finaliser mon inscription</h2>
                    <p style="color:var(--ukc-muted);margin:0;max-width:520px;">Choisissez un moyen de paiement. Votre reçu sera disponible dans Documents après confirmation.</p>
                </div>
                <div style="flex:none;min-width:210px;padding:14px 18px;border-radius:12px;background:#f1f4ff;border:1px solid #dbe3ff;">
                    <span style="display:block;color:var(--ukc-muted);font-size:12px;">Montant à régler</span>
                    <strong style="display:block;color:var(--ukc-blue);font-size:22px;line-height:1.25;">{{ $amount ? number_format((float) $amount, 0, ',', ' ').' FCFA' : 'À préciser' }}</strong>
                    @if ($applicableLabel)<small style="display:block;color:var(--ukc-muted);font-size:12px;">{{ $applicableLabel }}</small>@endif
                </div>
            </div>

            @error('pay') <div class="ukc-alert ukc-alert--err"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>{{ $message }}</div></div> @enderror
            @if (! $amount)
                <div class="ukc-alert ukc-alert--warn"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>Le montant n’a pas pu être déterminé. Contactez l’organisation avant de régler.</div></div>
            @elseif (count($gateways) === 0)
                <div class="ukc-alert ukc-alert--warn"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>Aucun moyen de paiement n’est disponible actuellement. Réessayez plus tard ou contactez l’organisation.</div></div>
            @else
                <form method="POST" action="{{ route('account.payment.pay', $event) }}">
                    @csrf
                    <fieldset style="border:0;padding:0;margin:0;">
                        <legend style="font-weight:700;font-size:14px;margin-bottom:10px;padding:0;">Choisir un moyen de paiement</legend>
                        <div class="ukc-mm-methods">
                            @foreach ($gateways as $gateway)
                                @php($key = $gateway->key())
                                <label class="ukc-mm-method" :class="{ selected: gw === '{{ $key }}' }">
                                    <input type="radio" name="gateway" value="{{ $key }}" x-model="gw" @checked($loop->first)>
                                    <span class="mm-logo {{ $key === 'manual' ? 'manual' : 'card' }}"><x-ukc.icon :name="$key === 'manual' ? 'receipt' : 'card'" :size="18" /></span>
                                    <span class="mm-name">{{ $gateway->label() }}</span>
                                    <span class="mm-sub">{{ $key === 'manual' ? 'Validation par le secrétariat' : 'Paiement en ligne' }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <x-ukc.pay-operator :gateways="$gateways" gateway-expr="gw" id="operator" class="ukc-field" wrapper-style="max-width:340px;margin-top:14px;" style="width:100%;" />
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;border-top:1px solid var(--ukc-border);padding-top:18px;margin-top:20px;">
                        <p style="display:inline-flex;align-items:center;gap:7px;margin:0;color:var(--ukc-muted);font-size:12px;"><x-ukc.icon name="shield" :size="16" /> Vous serez guidé jusqu’à la confirmation du règlement.</p>
                        <button type="submit" class="ukc-btn ukc-btn--green">Continuer pour régler <strong>&nbsp;{{ number_format((float) $amount, 0, ',', ' ') }} FCFA</strong></button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    {{-- ===== Tarifs publiés ===== --}}
    @if (! empty($tiers))
        <div class="ukc-card" style="padding:0;">
            <details @if ($participant && ! $paid) open @endif>
                <summary style="display:flex;justify-content:space-between;align-items:center;gap:15px;padding:18px 24px;cursor:pointer;list-style:none;">
                    <span><span style="{{ $eye }}">Tarifs publiés</span><strong style="font-size:16px;">Grille des frais d’inscription</strong></span>
                    <span style="color:var(--ukc-muted);font-size:12px;font-weight:650;flex:none;">{{ count($tiers) }} catégories</span>
                </summary>
                <div style="padding:0 24px 20px;">
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        @foreach ($tiers as $tier)
                            @php($isApplicable = $participant && $applicableLabel !== null && ($tier['label'] ?? null) === $applicableLabel)
                            <div @class(['pay-tier']) style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-radius:10px;border:1px solid {{ $isApplicable ? '#9cafff' : 'var(--ukc-border)' }};background:{{ $isApplicable ? '#f0f3ff' : '#fff' }};">
                                <span style="font-weight:650;">{{ $tier['label'] ?? 'Tarif' }}@if ($isApplicable) <span class="ukc-pill ukc-pill--info" style="margin-left:6px;">Votre catégorie</span>@endif</span>
                                <span style="font-weight:800;font-variant-numeric:tabular-nums;white-space:nowrap;">{{ number_format((float) ($tier['amount'] ?? 0), 0, ',', ' ') }} <small style="font-weight:600;color:var(--ukc-muted);">FCFA</small></span>
                            </div>
                        @endforeach
                    </div>
                    <p style="color:var(--ukc-muted);font-size:12.5px;margin:14px 0 0;">Le montant dépend de votre catégorie de participant. Vérifiez les informations de votre profil si le tarif affiché ne correspond pas à votre situation.</p>
                </div>
            </details>
        </div>
    @endif
</div>
</x-layouts.participant>
