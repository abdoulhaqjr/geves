<x-layouts.participant :title="__('Mes documents')" :subtitle="__('Badge, attestations & reçus')">
{{-- L'aperçu du badge est une mise en page du concepteur : il lui faut ses polices. --}}
@push('head')
    @include('documents._fonts')
@endpush
<div class="account-documents">
    @if (! $event)
        <div class="ukc-empty">
            <span class="ico"><x-ukc.icon name="folder" :size="28" /></span>
            <h3>{{ __('Aucun document pour le moment') }}</h3>
            <p>{{ __('Vos badge, attestations et reçus apparaîtront ici au fil de votre participation.') }}</p>
        </div>
    @else
        {{-- ============ Mes documents ============ --}}
        <div class="ukc-card">
            <h3>{{ __('Mes documents') }}</h3>
            <p style="font-size:13.5px;color:var(--ukc-muted);margin:-6px 0 14px;">
                {!! __('Une personne peut disposer d’<strong>une ou plusieurs attestations</strong> selon le nombre de communications. Le <strong>badge physique</strong> est imprimé par l’organisation (non téléchargeable ici).') !!}
            </p>

            {{-- ⚠️ Dit UNE FOIS, en tête, pourquoi aucune attestation ne se télécharge
                 sur cette page : sans cela, le participant croit à une panne et écrit
                 au secrétariat. --}}
            @if ($papier)
                <div class="ukc-note" style="margin-bottom:14px;padding:12px 14px;border:1px solid #f3d7a0;background:#fffaf0;border-radius:12px;font-size:13px;line-height:1.55;color:#7a5a12;">
                    {{ __('Cet événement délivre ses attestations sur papier : votre exemplaire officiel vous est remis par le secrétariat. Vous pouvez consulter et vérifier chacune d’elles ici - le document affiché en est un spécimen.') }}
                </div>
            @endif

            @if ($submissions->isNotEmpty())
                <div class="ukc-doc-section">{{ __('Par communication') }}</div>
                @foreach ($submissions as $i => $s)
                    <div class="ukc-doc-group">
                        <div class="g-title">{{ __('Communication :num', ['num' => $i + 1]) }} - {{ $s->type === 'poster' ? __('Poster') : __('Orale') }}@if ($s->track) - {{ $s->track }}@endif</div>
                        <div class="g-sub">{{ $s->title }}</div>
                        <ul class="ukc-doc-list">
                            {{-- ⚠️ Indexée par COMMUNICATION : par type, c'est celle de la
                                 dernière qui s'affichait en face de toutes les autres, et
                                 deux communications renvoyaient au même document. --}}
                            @include('account._doc-row', [
                                'name' => __('Attestation de communication'),
                                'sub' => __('Après validation du passage - QR DIDs'),
                                'att' => $attsParCommunication[$s->id] ?? null,
                                'status' => __('Après le passage'),
                            ])
                        </ul>
                    </div>
                @endforeach
            @endif

            {{-- ⚠️ Une section à part, et une ligne PAR formation : la page n'en
                 montrait qu'une seule, sans dire laquelle. Quelqu'un qui a suivi trois
                 ateliers n'en voyait qu'un, et pouvait croire les autres perdus.
                 L'animation d'une formation y figure aussi : le formateur est, lui
                 aussi, attestataire de cet événement. --}}
            @if ($attsFormation->isNotEmpty())
                <div class="ukc-doc-section">{{ __('Formations') }}</div>
                <ul class="ukc-doc-list">
                    @foreach ($attsFormation as $a)
                        @include('account._doc-row', [
                            'name' => $a->type === 'formation_animation'
                                ? __('Attestation d’animation de formation')
                                : __('Attestation de formation'),
                            'sub' => $a->formationTitle() ?: __('Atelier en marge du colloque'),
                            'att' => $a,
                        ])
                    @endforeach
                </ul>
            @endif

            <div class="ukc-doc-section">{{ __('Commun au participant') }}</div>
            <ul class="ukc-doc-list">
                @include('account._doc-row', [
                    'name' => __('Attestation de participation'),
                    'sub' => __('1 seule - présence aux sessions - QR DIDs'),
                    'att' => $atts['participation'] ?? null,
                    'status' => __('Après le colloque'),
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
                        {{-- ⚠️ Le PDF, et lui seul : le .docx est un gabarit du
                             back-office, pas le document qu'on remet. --}}
                        'links' => '<a href="'.route('account.documents.livret', $event).'" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">'.__('Livret (PDF)').'</a>',
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

        {{-- Ancre de la pastille du tableau de bord. --}}
        <div class="ukc-badge-wrap" id="mon-badge">
            <div class="ukc-card ukc-badge-card">
                <h3><x-ukc.icon name="shield" :size="20" /> {{ $badges->count() === 1 ? __('Mon badge') : __('Mes badges') }}</h3>
                <p class="badge-intro">
                    @if ($badges->count() > 1)
                        {{ __('Vos badges correspondent à vos fonctions pour cet événement.') }}
                        @unless ($paid)
                            {{ __('Le badge participant nécessite le règlement des frais d’inscription.') }}
                        @endunless
                    @elseif ($paid)
                        {{ __('Votre badge est généré. Présentez-le à l’accueil (papier ou mobile) ; le QR code sert au contrôle d’accès.') }}
                    @else
                        {{ __('L’aperçu de votre badge sera disponible dès confirmation du paiement d’inscription.') }}
                    @endif
                </p>
                <div class="ukc-badge-list">
                    @foreach ($badges as $badge)
                        @php
                            $badgeScale = $badge['w'] > 0 ? 280 / $badge['w'] : 1;
                            $qrPayload = 'verifier?ref='.urlencode($badge['ref']).'&pid='.$participant->id.'&ed='.$event->id.'&role='.\Illuminate\Support\Str::slug($badge['role']);
                        @endphp
                        <section class="ukc-badge-entry" id="badge-{{ $badge['type'] }}" aria-label="{{ __($badge['title']) }}">
                            <h4 class="ukc-badge-entry-title">{{ __($badge['title']) }}</h4>
                            <div class="ukc-badge-stage">
                                {{-- Modèle propre à ce type, avec les données du destinataire. --}}
                                <div class="ukc-badge-canvas {{ $badge['locked'] ? 'locked' : '' }}"
                                     style="width:280px;max-width:100%;height:{{ round($badge['h'] * $badgeScale) }}px;">
                                    <div class="scaler" style="width:{{ $badge['w'] }}px;height:{{ $badge['h'] }}px;transform:scale({{ $badgeScale }});">
                                        {!! $badge['html'] !!}
                                    </div>
                                    <div class="badge-lock-overlay">
                                        <x-ukc.icon name="lock" :size="28" />
                                        {{ __('Disponible après paiement') }}
                                    </div>
                                </div>

                                <div class="ukc-badge-actions">
                                    @if ($account->photo_path && $badge['supports_photo'])
                                        <form class="ukc-badge-photo-choice" method="POST" action="{{ route('account.documents.badge.photo', [$event, $badge['type']]) }}" data-badge-photo-form>
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="photo_mode" value="without">
                                            <span class="ukc-badge-photo-choice__title">{{ __('Photo sur ce badge') }}</span>
                                            <label class="ukc-badge-photo-choice__switch" for="badge-photo-{{ $badge['type'] }}">
                                                <input id="badge-photo-{{ $badge['type'] }}" type="checkbox" name="photo_mode" value="with" role="switch" aria-label="{{ __('Afficher la photo sur ce badge') }}" aria-checked="{{ $badge['photo_mode'] === 'with' ? 'true' : 'false' }}" data-saved="{{ $badge['photo_mode'] }}" data-badge-photo-toggle @checked($badge['photo_mode'] === 'with')>
                                                <span class="ukc-badge-photo-choice__track" aria-hidden="true"></span>
                                                <span class="ukc-badge-photo-choice__state" data-badge-photo-state>{{ $badge['photo_mode'] === 'with' ? __('Avec photo') : __('Sans photo') }}</span>
                                            </label>
                                            <noscript><button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm">{{ __('Enregistrer') }}</button></noscript>
                                        </form>
                                    @elseif ($badge['supports_photo'])
                                        <p class="ukc-badge-photo-hint">{{ __('Ce badge s’affiche sans photo.') }} <a href="{{ route('account.profile') }}">{{ __('Ajoutez une photo de profil') }}</a> {{ __('pour pouvoir choisir le modèle avec photo.') }}</p>
                                    @endif
                                    @if (! $badge['locked'])
                                        <div class="ukc-qr-details">
                                            <h4><x-ukc.icon name="grid" :size="18" /> {{ __('Détails du QR code') }}</h4>
                                            <dl>
                                                <dt>{{ __('Référence') }}</dt><dd><strong>{{ $badge['ref'] }}</strong></dd>
                                                <dt>{{ __('Participant') }}</dt><dd>{{ $account->first_name }} {{ $account->last_name }} - {{ __('ID compte') }} #{{ $participant->id }}</dd>
                                                <dt>{{ __('DID émetteur') }}</dt><dd style="font-family:var(--ukc-mono);font-size:12px;">{{ $didIssuer }}</dd>
                                                <dt>{{ __('Événement') }}</dt><dd>{{ $event->title ?: $event->year }}</dd>
                                                <dt>{{ __('Type d’accès') }}</dt><dd>{{ __($badge['role']) }} - {{ __('Sessions plénières + ateliers') }}</dd>
                                                <dt>{{ __('Validité') }}</dt><dd>{{ $event->date_start?->translatedFormat('j') }} - {{ $event->date_end?->translatedFormat('j F Y') ?? $event->year }}</dd>
                                                <dt>{{ __('Payload / URL scan') }}</dt><dd><div class="payload">{{ $qrPayload }}</div></dd>
                                            </dl>
                                        </div>
                                    @else
                                        <div class="locked-msg">
                                            {!! __('Finalisez votre <strong>inscription et paiement</strong> pour débloquer l’aperçu de votre badge et le QR code d’accès (impression gérée par l’organisation).') !!}
                                        </div>
                                        <a href="{{ route('account.payment', $event) }}" class="ukc-btn ukc-btn--green ukc-btn--sm">
                                            <x-ukc.icon name="card" :size="16" /> {{ __('Payer mes frais') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
</x-layouts.participant>
