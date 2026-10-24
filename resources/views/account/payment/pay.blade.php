@php
    $step = $paid ? 4 : ($participant ? ($eligible ? 3 : 2) : 1);
    $steps = ['Participation', 'Décision', 'Paiement', 'Confirmé'];
    $status = $paid ? 'Inscription confirmée' : ($frozen ? 'Événement clôturé' : ($eligible ? 'Paiement à effectuer' : ($participant ? 'Décision en attente' : 'Participation à créer')));
    $statusPill = $paid ? 'ok' : (($eligible && ! $frozen) ? 'info' : 'muted');
@endphp

<x-layouts.participant title="Inscription & paiement" subtitle="Suivez votre participation et réglez vos frais">
<div class="account-payment">
    {{-- ⚠️ UNE SEULE fenêtre par page : la poser dans une boucle en afficherait
         autant que de lignes, toutes empilées sur la même transaction. --}}
    <x-ukc.pay-embed :embed="session('ukc_pay_embed')" />

    {{-- ===== Parcours d'inscription ===== --}}
    <div class="payment-overview">
        <div class="payment-overview__top">
            <div class="payment-overview__heading">
                <span class="payment-overview__eyebrow">Votre parcours d’inscription</span>
                <h2>{{ $event->title ?: $event->year }}</h2>
            </div>
            <span class="payment-status payment-status--{{ ['ok' => 'success', 'info' => 'ready', 'muted' => 'muted'][$statusPill] }}"><span class="payment-status__dot" aria-hidden="true"></span>{{ $status }}</span>
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

    @if ($participant)
        <section class="payment-mode" id="mode-participation" aria-labelledby="payment-mode-title">
            <div class="payment-mode__intro">
                <h2 id="payment-mode-title"><x-ukc.icon name="users" :size="20" /> {{ __('Mode de participation au colloque') }}</h2>
                <p>{{ __('Choisissez comment vous assisterez au colloque. Le mode de présentation se choisit séparément pour chaque communication.') }}</p>
            </div>
            <form method="POST" action="{{ route('account.participation.mode.update', $event) }}" class="payment-mode__form">
                @csrf
                @method('PUT')
                <div class="ukc-field">
                    <label for="participation_mode">{{ __('Mon mode de participation') }}</label>
                    <div class="payment-mode__controls">
                        <select id="participation_mode" name="participation_mode" required>
                            @if (count($participationModes) > 1 && ! $participant->participation_mode)
                                <option value="">{{ __('Choisir un mode') }}</option>
                            @endif
                            @foreach ($participationModes as $value => $label)
                                <option value="{{ $value }}" @selected(old('participation_mode', $participant->participation_mode) === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="ukc-btn ukc-btn--blue">{{ __('Enregistrer') }}</button>
                    </div>
                    @error('participation_mode') <span class="error">{{ $message }}</span> @enderror
                </div>
            </form>
        </section>
    @endif

    {{-- ===== Étape courante ===== --}}
    {{-- ⚠️⚠️ **ICI, ON S'INSCRIT.** Ce bloc était une impasse : « Pour assister
         sans présenter de communication, contactez l'organisation. » On ne
         devenait participant qu'en déposant un résumé, alors que le rôle
         « Participant simple » existait déjà et que la page publique promettait
         de pouvoir s'inscrire ainsi. --}}
    @if (! $participant && $canRegister)
        <section class="ukc-card payment-register" id="inscription" aria-labelledby="payment-register-title">
            <h2 id="payment-register-title"><x-ukc.icon name="user-plus" :size="20" /> {{ __('M’inscrire à cet événement') }}</h2>
            <p class="payment-register__intro">
                {{ __('Inscrivez-vous pour assister à l’événement, que vous présentiez une communication ou non.') }}
                {{-- ⚠️ On annonce la SUITE telle qu'elle sera : promettre un
                     paiement « ensuite » alors qu'il n'ouvre qu'après la clôture
                     des dépôts, c'est laisser quelqu'un revenir chaque jour. --}}
                @if ($attendeePaymentOpen)
                    {{ __('Vos frais s’afficheront ensuite ici.') }}
                @else
                    {{ __('Le règlement des frais ouvrira plus tard, en général après la clôture des dépôts.') }}
                @endif
            </p>

            <form method="POST" action="{{ route('account.registration.store', $event) }}" class="ukc-form-grid">
                @csrf
                {{-- ⚠️ Le GRADE est le seul champ qui décide de quelque chose :
                     c'est lui qui résout le tarif (Fees::resolveAmount). --}}
                <div class="ukc-field full">
                    <label for="grade">{{ __('Ma catégorie') }} <span class="req">*</span></label>
                    <select id="grade" name="grade" required>
                        <option value="">{{ __('Choisir ma catégorie…') }}</option>
                        @foreach ($grades as $g)
                            <option value="{{ $g }}" @selected(old('grade', $accountGrade) === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                    <span class="hint">{{ __('C’est elle qui détermine le montant de vos frais.') }}</span>
                    @error('grade') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="reg-affiliation">{{ __('Affiliation') }}</label>
                    <input type="text" id="reg-affiliation" name="affiliation" list="ukc-affiliations"
                           value="{{ old('affiliation', $accountAffiliation) }}" autocomplete="off">
                    @error('affiliation') <span class="error">{{ $message }}</span> @enderror
                </div>
                <datalist id="ukc-affiliations"></datalist>

                @if (count($participationModes) > 1)
                    <div class="ukc-field full">
                        <label for="reg-mode">{{ __('Mode de participation') }}</label>
                        <select id="reg-mode" name="participation_mode">
                            <option value="">{{ __('Choisir un mode') }}</option>
                            @foreach ($participationModes as $value => $label)
                                <option value="{{ $value }}" @selected(old('participation_mode') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        @error('participation_mode') <span class="error">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="ukc-field full">
                    <button type="submit" class="ukc-btn ukc-btn--blue">
                        <x-ukc.icon name="check" :size="16" /> {{ __('M’inscrire') }}
                    </button>
                    @if ($event->submissionsAreOpen() && $event->slug)
                        <a href="{{ route('account.submissions.create', $event->slug) }}" class="ukc-btn ukc-btn--ghost">{{ __('Déposer un résumé') }}</a>
                    @endif
                </div>
            </form>
        </section>
    @elseif (! $participant && ! $frozen)
        {{-- Inscriptions fermées : on le DIT, au lieu de laisser croire à un oubli. --}}
        <div class="payment-state">
            <span class="payment-state__icon" aria-hidden="true"><x-ukc.icon name="clock" :size="24" /></span>
            <div class="payment-state__body">
                <h2>{{ __('Les inscriptions sont fermées') }}</h2>
                <p>{{ __('Vous n’êtes pas inscrit à cet événement et les inscriptions ne sont plus ouvertes. Si votre dossier doit être régularisé, contactez le secrétariat de l’événement.') }}</p>
                <div class="payment-state__actions">
                    <a href="{{ route('account.submissions.index') }}" class="ukc-btn ukc-btn--blue">{{ __('Voir mes communications') }}</a>
                </div>
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
    @elseif ($attendRegleMentPourSimple)
        {{-- ⚠️ Message PROPRE au participant simple : le générique lui parlait
             d'une communication à faire accepter, ce qui ne le concerne pas. --}}
        <div class="ukc-card">
            <h2><x-ukc.icon name="check-circle" :size="20" /> {{ __('Votre inscription est enregistrée') }}</h2>
            <p style="color:var(--ukc-muted);margin:0 0 16px;">
                {{ __('Le règlement des frais n’est pas encore ouvert aux participants sans communication : il l’est en général après la clôture des dépôts. Vous serez prévenu(e) et pourrez régler depuis cette page.') }}
            </p>
            <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--blue">{{ __('Mon tableau de bord') }}</a>
        </div>
    @elseif (! $eligible)
        <div class="ukc-card">
            <h2><x-ukc.icon name="clock" :size="20" /> Le paiement n’est pas encore ouvert</h2>
            <p style="color:var(--ukc-muted);margin:0 0 16px;">Vous pourrez régler vos frais lorsqu’au moins une de vos communications aura été acceptée. Suivez l’avancement de votre dossier depuis votre espace.</p>
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
                    {{-- ⚠️ **AUCUN choix d'opérateur ici.** La passerelle choisie
                         ci-dessus EST le moyen de paiement : qui prend « en ligne »
                         finit sur la page du prestataire, qui propose tout ce que le
                         compte marchand accepte - opérateurs mobile money et carte.
                         Le lui faire choisir chez nous d'abord, c'était un écran de
                         plus pour un choix qu'il refait ensuite. --}}
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
        <details class="payment-tariffs" @if ($participant && ! $paid) open @endif>
            <summary>
                <strong><x-ukc.icon name="receipt" :size="20" /> Grille des frais d’inscription</strong>
                <span class="payment-tariffs__meta">{{ count($tiers) }} catégories <span class="payment-tariffs__chevron" aria-hidden="true"></span></span>
            </summary>
            <div class="payment-tariffs__grid">
                @foreach ($tiers as $tier)
                    @php($isApplicable = $participant && $applicableLabel !== null && ($tier['label'] ?? null) === $applicableLabel)
                    <div @class(['payment-tariff', 'is-applicable' => $isApplicable])>
                        <span class="payment-tariff__label">{{ $tier['label'] ?? 'Tarif' }}</span>
                        <strong class="payment-tariff__price">{{ number_format((float) ($tier['amount'] ?? 0), 0, ',', ' ') }} <small>FCFA</small></strong>
                        @if ($isApplicable)<span class="payment-tariff__tag">Votre catégorie</span>@endif
                    </div>
                @endforeach
            </div>
            <p class="payment-tariffs__hint">Le montant dépend de votre catégorie de participant. Vérifiez les informations de votre profil si le tarif affiché ne correspond pas à votre situation.</p>
        </details>
    @endif
</div>
</x-layouts.participant>
