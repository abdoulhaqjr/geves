@php
    $step = $paid ? 4 : ($participant ? ($eligible ? 3 : 2) : 1);
    $steps = ['Participation', 'Décision', 'Paiement', 'Confirmé'];
    $status = $paid ? 'Inscription confirmée' : ($frozen ? 'Événement clôturé' : ($eligible ? 'Paiement à effectuer' : ($participant ? 'Décision en attente' : 'Participation à créer')));
@endphp

<x-layouts.participant title="Inscription & paiement" subtitle="Suivez votre participation et réglez vos frais">
<div class="account-payment">
    <section class="payment-overview" aria-labelledby="payment-overview-title">
        <div class="payment-overview__top">
            <div>
                <span class="payment-eyebrow">Votre parcours d’inscription</span>
                <h2 id="payment-overview-title">{{ $event->title ?: $event->year }}</h2>
            </div>
            <span @class(['payment-status', 'payment-status--success' => $paid, 'payment-status--ready' => $eligible && ! $paid && ! $frozen, 'payment-status--muted' => ! $paid && (! $eligible || $frozen)])>
                <span class="payment-status__dot" aria-hidden="true"></span>{{ $status }}
            </span>
        </div>
        <ol class="payment-progress" aria-label="Étapes de l’inscription">
            @foreach ($steps as $index => $label)
                @php($number = $index + 1)
                <li @class(['payment-progress__step', 'is-done' => $number < $step, 'is-current' => $number === $step]) @if ($number === $step) aria-current="step" @endif>
                    <span class="payment-progress__number">{{ $number < $step ? '✓' : $number }}</span>
                    <span>{{ $label }}</span>
                </li>
            @endforeach
        </ol>
    </section>

    @if (! $participant && ! $frozen)
        <section class="payment-state" aria-labelledby="payment-state-title">
            <div class="payment-state__icon"><x-ukc.icon name="file" :size="24" /></div>
            <div class="payment-state__body">
                <span class="payment-eyebrow">Prochaine étape</span>
                <h3 id="payment-state-title">Aucune participation liée à cet événement</h3>
                <p>Vos frais apparaîtront ici lorsque votre participation sera enregistrée. Si vous avez déposé un résumé, consultez son suivi dans vos communications. Pour assister sans présenter de communication, contactez l’organisation.</p>
                <div class="payment-state__actions">
                    <a href="{{ route('account.submissions.index') }}" class="ukc-btn ukc-btn--blue">Voir mes communications</a>
                    @if ($event->submission_open && $event->slug)
                        <a href="{{ route('account.submissions.create', $event->slug) }}" class="ukc-btn ukc-btn--outline">Déposer un résumé</a>
                    @endif
                </div>
            </div>
        </section>
    @elseif ($paid)
        <section class="payment-state payment-state--success" aria-labelledby="payment-state-title">
            <div class="payment-state__icon"><x-ukc.icon name="check" :size="24" /></div>
            <div class="payment-state__body">
                <span class="payment-eyebrow">Règlement confirmé</span>
                <h3 id="payment-state-title">Vos frais sont réglés</h3>
                <p>Votre inscription est à jour pour cet événement.@if ($paidReceipt) Reçu n° <strong>{{ $paidReceipt->receipt_no }}</strong>.@endif</p>
                <a href="{{ route('account.documents') }}" class="ukc-btn ukc-btn--blue">Voir mes documents</a>
            </div>
        </section>
    @elseif ($frozen)
        <section class="payment-state" aria-labelledby="payment-state-title">
            <div class="payment-state__icon"><x-ukc.icon name="lock" :size="24" /></div>
            <div class="payment-state__body">
                <span class="payment-eyebrow">Règlement clos</span>
                <h3 id="payment-state-title">Cet événement est archivé</h3>
                <p>Le paiement en ligne est fermé. Si votre dossier reste à régulariser, contactez le secrétariat de l’événement.</p>
            </div>
        </section>
    @elseif (! $eligible)
        <section class="payment-state" aria-labelledby="payment-state-title">
            <div class="payment-state__icon"><x-ukc.icon name="clock" :size="24" /></div>
            <div class="payment-state__body">
                <span class="payment-eyebrow">En attente de décision</span>
                <h3 id="payment-state-title">Le paiement n’est pas encore ouvert</h3>
                <p>Vous pourrez régler vos frais lorsqu’au moins une de vos communications aura été acceptée. Suivez l’avancement de votre dossier depuis votre espace.</p>
                <a href="{{ route('account.submissions.index') }}" class="ukc-btn ukc-btn--blue">Suivre mes communications</a>
            </div>
        </section>
    @elseif (session('pay_instructions'))
        <section class="payment-state payment-state--success" aria-labelledby="payment-state-title">
            <div class="payment-state__icon"><x-ukc.icon name="check-circle" :size="24" /></div>
            <div class="payment-state__body">
                <span class="payment-eyebrow">Demande enregistrée</span>
                <h3 id="payment-state-title">Poursuivez votre règlement</h3>
                <p>{{ session('pay_instructions') }}</p>
            </div>
        </section>
    @else
        <section class="payment-checkout" aria-labelledby="payment-checkout-title" x-data="{ gw: '{{ optional($gateways[0] ?? null)->key() }}' }">
            <div class="payment-checkout__heading">
                <div>
                    <span class="payment-eyebrow">Étape de paiement</span>
                    <h3 id="payment-checkout-title">Finaliser mon inscription</h3>
                    <p>Choisissez un moyen de paiement. Votre reçu sera disponible dans Documents après confirmation.</p>
                </div>
                <div class="payment-checkout__amount">
                    <span>Montant à régler</span>
                    <strong>{{ $amount ? number_format((float) $amount, 0, ',', ' ').' FCFA' : 'À préciser' }}</strong>
                    @if ($applicableLabel)<small>{{ $applicableLabel }}</small>@endif
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
                    <fieldset class="payment-methods">
                        <legend>Choisir un moyen de paiement</legend>
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
                    <div class="payment-checkout__footer">
                        <p><x-ukc.icon name="shield" :size="16" /> Vous serez guidé jusqu’à la confirmation du règlement.</p>
                        <button type="submit" class="ukc-btn ukc-btn--green">Continuer pour régler <strong>{{ number_format((float) $amount, 0, ',', ' ') }} FCFA</strong></button>
                    </div>
                </form>
            @endif
        </section>
    @endif

    @if (! empty($tiers))
        <details class="payment-tariffs" @if ($participant && ! $paid) open @endif>
            <summary>
                <span><span class="payment-eyebrow">Tarifs publiés</span><strong>Grille des frais d’inscription</strong></span>
                <span class="payment-tariffs__meta">{{ count($tiers) }} catégories <span class="payment-tariffs__chevron" aria-hidden="true"></span></span>
            </summary>
            <div class="payment-tariffs__grid">
                @foreach ($tiers as $tier)
                    @php($isApplicable = $participant && $applicableLabel !== null && ($tier['label'] ?? null) === $applicableLabel)
                    <div @class(['payment-tariff', 'is-applicable' => $isApplicable])>
                        <span class="payment-tariff__label">{{ $tier['label'] ?? 'Tarif' }}</span>
                        <span class="payment-tariff__price">{{ number_format((float) ($tier['amount'] ?? 0), 0, ',', ' ') }} <small>FCFA</small></span>
                        @if ($isApplicable)<span class="payment-tariff__tag">Votre catégorie</span>@endif
                    </div>
                @endforeach
            </div>
            <p class="payment-tariffs__hint">Le montant dépend de votre catégorie de participant. Vérifiez les informations de votre profil si le tarif affiché ne correspond pas à votre situation.</p>
        </details>
    @endif
</div>
</x-layouts.participant>
