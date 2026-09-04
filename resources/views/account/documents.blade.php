<x-layouts.participant title="Mes documents" subtitle="Badge, attestations & reçus">
<div class="account-documents">
    @if (! $edition)
        <div class="ukc-empty">
            <span class="ico"><x-ukc.icon name="folder" :size="28" /></span>
            <h3>Aucun document pour le moment</h3>
            <p>Vos badge, attestations et reçus apparaîtront ici au fil de votre participation.</p>
        </div>
    @else
        @php
            $qrPayload = 'verifier?ref='.$badgeRef.'&pid='.$participant->id.'&ed='.$edition->id.'&role='.\Illuminate\Support\Str::slug($participant->roleLabel());
        @endphp

        {{-- ============ Mon badge participant ============ --}}
        <div class="ukc-badge-wrap">
            <div class="ukc-card ukc-badge-card">
                <h3><x-ukc.icon name="shield" :size="20" /> Mon badge participant</h3>
                <p class="badge-intro">
                    @if ($paid)
                        Votre badge est généré. Présentez-le à l’accueil (papier ou mobile) ; le QR code sert au contrôle d’accès.
                    @else
                        L’aperçu de votre badge sera disponible dès confirmation du paiement d’inscription.
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
                            Disponible après paiement
                        </div>
                    </div>

                    <div class="ukc-badge-actions">
                        @if ($paid)
                            <ul>
                                <li><x-ukc.icon name="check" :size="16" /> Paiement confirmé — badge généré</li>
                                <li><x-ukc.icon name="check" :size="16" /> Présentez-le à l’accueil (papier ou mobile)</li>
                                <li><x-ukc.icon name="check" :size="16" /> QR code pour contrôle d’accès (réf. signée)</li>
                            </ul>
                            <div class="locked-msg" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3;">
                                Badge <strong>non téléchargeable</strong> côté participant : génération et impression réservées à l’administration.
                            </div>

                            <div class="ukc-qr-details">
                                <h4><x-ukc.icon name="grid" :size="18" /> Détails du QR code</h4>
                                <dl>
                                    <dt>Référence</dt><dd><strong>{{ $badgeRef }}</strong></dd>
                                    <dt>Participant</dt><dd>{{ $account->first_name }} {{ $account->last_name }} · ID compte #{{ $participant->id }}</dd>
                                    <dt>DID émetteur</dt><dd style="font-family:ui-monospace,monospace;font-size:12px;">{{ $didIssuer }}</dd>
                                    <dt>Édition</dt><dd>{{ $edition->title ?: $edition->year }}</dd>
                                    <dt>Type d’accès</dt><dd>{{ $participant->roleLabel() }} · Sessions plénières + ateliers</dd>
                                    <dt>Validité</dt><dd>{{ $edition->date_start?->translatedFormat('j') }} – {{ $edition->date_end?->translatedFormat('j F Y') ?? $edition->year }}</dd>
                                    <dt>Payload / URL scan</dt><dd><div class="payload">{{ $qrPayload }}</div></dd>
                                </dl>
                                <p class="note">
                                    Le QR pointe vers la page de contrôle d’accès. La vérification s’appuie sur une <strong>référence signée</strong>
                                    et, en production, sur le modèle <strong>W3C DIDs / Verifiable Credentials</strong>. Ne le partagez pas publiquement.
                                </p>
                            </div>
                        @else
                            <div class="locked-msg">
                                Finalisez votre <strong>inscription et paiement</strong> pour débloquer l’aperçu de votre badge et le QR code d’accès
                                (impression gérée par l’organisation).
                            </div>
                            <a href="{{ route('account.payment', $edition) }}" class="ukc-btn ukc-btn--green ukc-btn--sm">
                                <x-ukc.icon name="card" :size="16" /> Payer mes frais
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Mes documents ============ --}}
        <div class="ukc-card">
            <h3>Mes documents</h3>
            <p style="font-size:13.5px;color:var(--ukc-muted);margin:-6px 0 14px;">
                Une personne peut disposer d’<strong>une ou plusieurs attestations</strong> selon le nombre de communications.
                Le <strong>badge physique</strong> est imprimé par l’organisation (non téléchargeable ici).
            </p>

            @if ($submissions->isNotEmpty())
                <div class="ukc-doc-section">Par communication</div>
                @foreach ($submissions as $i => $s)
                    <div class="ukc-doc-group">
                        <div class="g-title">Communication {{ $i + 1 }} — {{ $s->type === 'poster' ? 'Poster' : 'Orale' }}@if ($s->track) · {{ $s->track }}@endif</div>
                        <div class="g-sub">{{ $s->title }}</div>
                        <ul class="ukc-doc-list">
                            @include('account._doc-row', [
                                'name' => 'Attestation de soumission',
                                'sub' => $s->created_at ? 'Déposée le '.$s->created_at->format('d/m/Y') : null,
                                'att' => $atts['soumission'] ?? null,
                                'status' => 'En attente',
                            ])
                            @include('account._doc-row', [
                                'name' => 'Lettre d’acceptation',
                                'sub' => 'Liée à cette communication',
                                'att' => $atts['acceptation'] ?? null,
                                'status' => $s->status === 'accepted' ? 'Bientôt' : 'En attente',
                            ])
                            @include('account._doc-row', [
                                'name' => 'Attestation de communication',
                                'sub' => 'Après présentation · QR DIDs',
                                'att' => $atts['communication'] ?? null,
                                'status' => 'Après le colloque',
                            ])
                        </ul>
                    </div>
                @endforeach
            @endif

            <div class="ukc-doc-section">Commun au participant</div>
            <ul class="ukc-doc-list">
                @include('account._doc-row', [
                    'name' => 'Attestation de participation',
                    'sub' => '1 seule · présence aux sessions · QR DIDs',
                    'att' => $atts['participation'] ?? null,
                    'status' => 'Après le colloque',
                ])
                @include('account._doc-row', [
                    'name' => 'Attestation de formation',
                    'sub' => 'Ateliers en marge (si inscrit) · une par formation suivie',
                    'att' => $atts['formation'] ?? null,
                    'status' => 'Si éligible',
                ])
                @include('account._doc-row', [
                    'name' => 'Reçu de paiement',
                    'sub' => $paid ? 'Frais d’inscription réglés' : 'Après règlement des frais d’inscription',
                    'links' => $paid && $payment
                        ? '<a href="'.route('account.receipt', $payment).'" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">Reçu</a>'
                        : null,
                    'status' => 'Bientôt',
                ])
                @if ($hasBooklet)
                    @include('account._doc-row', [
                        'name' => 'Livret des résumés',
                        'sub' => 'Compilation des résumés acceptés',
                        'links' => '<a href="'.route('account.documents.livret', $edition).'" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">Livret (imprimable)</a>'
                            .'<a href="'.route('account.documents.livret', [$edition, 'format' => 'docx']).'" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Livret (Word)</a>',
                    ])
                @endif
                @foreach ($media as $m)
                    @include('account._doc-row', [
                        'name' => $m->title ?: $m->typeLabel(),
                        'sub' => 'Document officiel du colloque',
                        'links' => '<a href="'.e($m->url).'" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">Télécharger</a>',
                    ])
                @endforeach
            </ul>
        </div>
    @endif
</div>
</x-layouts.participant>
