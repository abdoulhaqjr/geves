<x-layouts.participant :title="__('Mes documents')" :subtitle="__('Badge, attestations & reçus')">
<div class="account-documents">
    @if (! $edition)
        <div class="ukc-empty">
            <span class="ico"><x-ukc.icon name="folder" :size="28" /></span>
            <h3>{{ __('Aucun document pour le moment') }}</h3>
            <p>{{ __('Vos badge, attestations et reçus apparaîtront ici au fil de votre participation.') }}</p>
        </div>
    @else
        @php
            $qrPayload = 'verifier?ref='.$badgeRef.'&pid='.$participant->id.'&ed='.$edition->id.'&role='.\Illuminate\Support\Str::slug($participant->roleLabel());
        @endphp

        {{-- ============ Mon badge participant ============ --}}
        <div class="ukc-badge-wrap">
            <div class="ukc-card ukc-badge-card">
                <h3><x-ukc.icon name="shield" :size="20" /> {{ __('Mon badge participant') }}</h3>
                <p class="badge-intro">
                    @if ($paid)
                        {{ __('Votre badge est généré. Présentez-le à l’accueil (papier ou mobile) ; le QR code sert au contrôle d’accès.') }}
                    @else
                        {{ __('L’aperçu de votre badge sera disponible dès confirmation du paiement d’inscription.') }}
                    @endif
                </p>
                @php($badgeScale = $badgeW > 0 ? 280 / $badgeW : 1)
                <div class="ukc-badge-stage">
                    {{-- Aperçu = MODÈLE de badge conçu dans l'admin, peuplé des données réelles, mis à l'échelle. --}}
                    <div class="ukc-badge-canvas {{ $paid ? '' : 'locked' }}"
                         style="width:280px;max-width:100%;height:{{ round($badgeH * $badgeScale) }}px;">
                        <div class="scaler" style="width:{{ $badgeW }}px;height:{{ $badgeH }}px;transform:scale({{ $badgeScale }});">
                            {!! $badgeCanvas !!}
                        </div>
                        <div class="badge-lock-overlay">
                            <x-ukc.icon name="lock" :size="28" />
                            {{ __('Disponible après paiement') }}
                        </div>
                    </div>

                    <div class="ukc-badge-actions">
                        @if ($paid)
                            <ul>
                                <li><x-ukc.icon name="check" :size="16" /> {{ __('Paiement confirmé — badge généré') }}</li>
                                <li><x-ukc.icon name="check" :size="16" /> {{ __('Présentez-le à l’accueil (papier ou mobile)') }}</li>
                                <li><x-ukc.icon name="check" :size="16" /> {{ __('QR code pour contrôle d’accès (réf. signée)') }}</li>
                            </ul>
                            <div class="locked-msg" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3;">
                                {!! __('Badge <strong>non téléchargeable</strong> côté participant : génération et impression réservées à l’administration.') !!}
                            </div>

                            <div class="ukc-qr-details">
                                <h4><x-ukc.icon name="grid" :size="18" /> {{ __('Détails du QR code') }}</h4>
                                <dl>
                                    <dt>{{ __('Référence') }}</dt><dd><strong>{{ $badgeRef }}</strong></dd>
                                    <dt>{{ __('Participant') }}</dt><dd>{{ $account->first_name }} {{ $account->last_name }} · {{ __('ID compte') }} #{{ $participant->id }}</dd>
                                    <dt>{{ __('DID émetteur') }}</dt><dd style="font-family:var(--ukc-mono);font-size:12px;">{{ $didIssuer }}</dd>
                                    <dt>{{ __('Édition') }}</dt><dd>{{ $edition->title ?: $edition->year }}</dd>
                                    <dt>{{ __('Type d’accès') }}</dt><dd>{{ $participant->roleLabel() }} · {{ __('Sessions plénières + ateliers') }}</dd>
                                    <dt>{{ __('Validité') }}</dt><dd>{{ $edition->date_start?->translatedFormat('j') }} – {{ $edition->date_end?->translatedFormat('j F Y') ?? $edition->year }}</dd>
                                    <dt>{{ __('Payload / URL scan') }}</dt><dd><div class="payload">{{ $qrPayload }}</div></dd>
                                </dl>
                                <p class="note">
                                    {!! __('Le QR pointe vers la page de contrôle d’accès. La vérification s’appuie sur une <strong>référence signée</strong> et, en production, sur le modèle <strong>W3C DIDs / Verifiable Credentials</strong>. Ne le partagez pas publiquement.') !!}
                                </p>
                            </div>
                        @else
                            <div class="locked-msg">
                                {!! __('Finalisez votre <strong>inscription et paiement</strong> pour débloquer l’aperçu de votre badge et le QR code d’accès (impression gérée par l’organisation).') !!}
                            </div>
                            <a href="{{ route('account.payment', $edition) }}" class="ukc-btn ukc-btn--green ukc-btn--sm">
                                <x-ukc.icon name="card" :size="16" /> {{ __('Payer mes frais') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Mes documents ============ --}}
        <div class="ukc-card">
            <h3>{{ __('Mes documents') }}</h3>
            <p style="font-size:13.5px;color:var(--ukc-muted);margin:-6px 0 14px;">
                {!! __('Une personne peut disposer d’<strong>une ou plusieurs attestations</strong> selon le nombre de communications. Le <strong>badge physique</strong> est imprimé par l’organisation (non téléchargeable ici).') !!}
            </p>

            @if ($submissions->isNotEmpty())
                <div class="ukc-doc-section">{{ __('Par communication') }}</div>
                @foreach ($submissions as $i => $s)
                    <div class="ukc-doc-group">
                        <div class="g-title">{{ __('Communication :num', ['num' => $i + 1]) }} — {{ $s->type === 'poster' ? __('Poster') : __('Orale') }}@if ($s->track) · {{ $s->track }}@endif</div>
                        <div class="g-sub">{{ $s->title }}</div>
                        <ul class="ukc-doc-list">
                            @include('account._doc-row', [
                                'name' => __('Attestation de soumission'),
                                'sub' => $s->created_at ? __('Déposée le').' '.$s->created_at->format('d/m/Y') : null,
                                'att' => $atts['soumission'] ?? null,
                                'status' => __('En attente'),
                            ])
                            @include('account._doc-row', [
                                'name' => __('Lettre d’acceptation'),
                                'sub' => __('Liée à cette communication'),
                                'att' => $atts['acceptation'] ?? null,
                                'status' => $s->status === 'accepted' ? __('Bientôt') : __('En attente'),
                            ])
                            @include('account._doc-row', [
                                'name' => __('Attestation de communication'),
                                'sub' => __('Après présentation · QR DIDs'),
                                'att' => $atts['communication'] ?? null,
                                'status' => __('Après le colloque'),
                            ])
                        </ul>
                    </div>
                @endforeach
            @endif

            <div class="ukc-doc-section">{{ __('Commun au participant') }}</div>
            <ul class="ukc-doc-list">
                @include('account._doc-row', [
                    'name' => __('Attestation de participation'),
                    'sub' => __('1 seule · présence aux sessions · QR DIDs'),
                    'att' => $atts['participation'] ?? null,
                    'status' => __('Après le colloque'),
                ])
                @include('account._doc-row', [
                    'name' => __('Attestation de formation'),
                    'sub' => __('Ateliers en marge (si inscrit) · une par formation suivie'),
                    'att' => $atts['formation'] ?? null,
                    'status' => __('Si éligible'),
                ])
                @include('account._doc-row', [
                    'name' => __('Reçu de paiement'),
                    'sub' => $paid ? __('Frais d’inscription réglés') : __('Après règlement des frais d’inscription'),
                    'links' => $paid && $payment
                        ? '<a href="'.route('account.receipt', $payment).'" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">'.__('Reçu').'</a>'
                        : null,
                    'status' => __('Bientôt'),
                ])
                @if ($hasBooklet)
                    @include('account._doc-row', [
                        'name' => __('Livret des résumés'),
                        'sub' => __('Compilation des résumés acceptés'),
                        'links' => '<a href="'.route('account.documents.livret', $edition).'" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">'.__('Livret (imprimable)').'</a>'
                            .'<a href="'.route('account.documents.livret', [$edition, 'format' => 'docx']).'" class="ukc-btn ukc-btn--ghost ukc-btn--sm">'.__('Livret (Word)').'</a>',
                    ])
                @endif
                @foreach ($media as $m)
                    @include('account._doc-row', [
                        'name' => $m->title ?: $m->typeLabel(),
                        'sub' => __('Document officiel du colloque'),
                        'links' => '<a href="'.e($m->url).'" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">'.__('Télécharger').'</a>',
                    ])
                @endforeach
            </ul>
        </div>
    @endif
</div>
</x-layouts.participant>
