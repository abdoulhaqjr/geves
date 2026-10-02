@php
    // Page publique de l'attestation : c'est l'adresse que porte le QR code et
    // que le bénéficiaire partage. Elle doit tenir debout seule devant un tiers
    // (recruteur, institution) - d'où le document, puis qui l'a délivré, ce
    // qu'il certifie et comment le vérifier.
    $revoked = (bool) $attestation->revoked;
    $signed = (bool) ($proof['signed'] ?? false);
    $signatureOk = (bool) ($proof['valid'] ?? false);
    $issuer = $attestation->edition?->colloque;
    $pageUrl = route('attestation.document', $attestation->uuid);
    $initials = collect(preg_split('/\s+/', trim((string) $attestation->recipient_name)) ?: [])
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    // Signaler une erreur : l'adresse officielle de l'établissement, avec l'objet
    // et l'identifiant déjà renseignés - le visiteur n'a pas à les retrouver.
    $issuerMail = (string) \App\Support\Settings::get('mail.from_address', (string) config('mail.from.address'));
    $contactUrl = $issuerMail !== ''
        ? 'mailto:'.$issuerMail.'?'.http_build_query([
            'subject' => __('Attestation').' '.$attestation->uuid,
            'body' => __('Bonjour,')."\n\n".__('Je signale une erreur sur l’attestation suivante :')."\n".$pageUrl."\n\n",
        ])
        : null;

    // « Ajouter au profil » de LinkedIn : paramètres publics documentés.
    $linkedIn = 'https://www.linkedin.com/profile/add?'.http_build_query(array_filter([
        'startTask' => 'CERTIFICATION_NAME',
        'name' => $attestation->typeLabel().' - '.($attestation->edition?->title ?? ''),
        'organizationName' => $issuer?->name ?? config('app.name'),
        'issueYear' => $attestation->issued_at?->format('Y'),
        'issueMonth' => $attestation->issued_at?->format('n'),
        'certId' => $attestation->uuid,
        'certUrl' => $pageUrl,
    ]));
@endphp

<x-layouts.app
    :title="$attestation->recipient_name.' - '.$attestation->typeLabel()"
    :description="$attestation->recipient_name.' - '.$attestation->typeLabel().' délivrée par '.($issuer?->name ?? config('app.name')).'.'">

    <div class="ukc-cred">
        <header class="ukc-cred-top no-print">
            <a href="{{ url('/') }}" class="ukc-cred-brand">
                <img src="{{ \App\Support\Branding::wide() }}" alt="{{ config('app.name') }}">
            </a>
            @if ($revoked)
                <span class="ukc-cred-state is-revoked"><x-ukc.icon name="close" :size="15" /> {{ __('Attestation révoquée') }}</span>
            @elseif ($signed && ! $signatureOk)
                <span class="ukc-cred-state is-warn"><x-ukc.icon name="alert-triangle" :size="15" /> {{ __('Signature non vérifiée') }}</span>
            @else
                <span class="ukc-cred-state is-ok"><x-ukc.icon name="shield" :size="15" /> {{ __('Attestation authentique') }}</span>
            @endif
        </header>

        @if ($revoked)
            <p class="ukc-cred-alert no-print">
                <x-ukc.icon name="alert-triangle" :size="18" />
                {{ __('Cette attestation a été révoquée par l’émetteur : elle ne vaut plus comme preuve.') }}
            </p>
        @endif

        {{-- Le document, tel qu'il a été délivré. --}}
        <section class="ukc-cred-stage">
            <div class="ukc-cred-doc" data-doc-width="{{ $width }}" style="--doc-w:{{ $width }}px">
                <div class="ukc-cred-doc-inner">{!! $canvas !!}</div>
            </div>
            <p class="ukc-cred-caption no-print">
                {{ __('Identifiant de l’attestation') }} :
                <span class="ukc-cred-uuid">{{ $attestation->uuid }}</span>
                @if ($issuer)
                    <span class="ukc-cred-sep">·</span> {{ __('Délivré par') }} <strong>{{ $issuer->name }}</strong>
                @endif
            </p>
        </section>

        <div class="ukc-cred-grid no-print">
            <aside class="ukc-cred-side">
                <div class="ukc-card ukc-cred-card">
                    <div class="ukc-cred-kicker">{{ __('Bénéficiaire') }}</div>
                    <div class="ukc-cred-person">
                        <span class="ukc-cred-avatar">{{ $initials }}</span>
                        <strong>{{ $attestation->recipient_name }}</strong>
                    </div>

                    <button type="button" class="ukc-btn ukc-btn--blue ukc-cred-action" data-cred-share data-url="{{ $pageUrl }}"
                            data-title="{{ $attestation->typeLabel() }} - {{ $attestation->recipient_name }}">
                        <x-ukc.icon name="share" :size="16" /> {{ __('Partager l’attestation') }}
                    </button>
                    <a href="{{ $linkedIn }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--outline ukc-cred-action">
                        <x-ukc.icon name="linkedin" :size="16" /> {{ __('Ajouter au profil LinkedIn') }}
                    </a>
                    <div class="ukc-cred-row">
                        <a href="{{ route('attestation.pdf', $attestation->uuid) }}" class="ukc-btn ukc-btn--outline ukc-cred-action">
                            <x-ukc.icon name="download" :size="16" /> {{ __('Télécharger (PDF)') }}
                        </a>
                        <button type="button" class="ukc-btn ukc-btn--outline ukc-cred-icon" data-cred-copy data-url="{{ $pageUrl }}"
                                title="{{ __('Copier le lien') }}" aria-label="{{ __('Copier le lien') }}">
                            <x-ukc.icon name="link" :size="16" />
                        </button>
                    </div>

                    @if ($contactUrl)
                        <p class="ukc-cred-help">
                            {{ __('Une erreur dans ce document ?') }}
                            <a href="{{ $contactUrl }}">{{ __('Contacter l’émetteur') }}</a>
                        </p>
                    @endif
                </div>

                <div class="ukc-card ukc-cred-card ukc-cred-verify">
                    <div class="ukc-cred-kicker {{ $revoked ? 'is-revoked' : ($signed && ! $signatureOk ? 'is-warn' : 'is-ok') }}">
                        <x-ukc.icon name="shield" :size="14" /> {{ __('Vérification') }}
                    </div>

                    @if ($revoked)
                        <p class="ukc-cred-verdict is-revoked">{{ __('Révoquée par l’émetteur') }}</p>
                    @elseif ($signed && ! $signatureOk)
                        <p class="ukc-cred-verdict is-warn">{{ __('Signature non validée') }}</p>
                    @elseif ($signatureOk)
                        <p class="ukc-cred-verdict is-ok">{{ __('Signature cryptographique vérifiée') }}</p>
                    @else
                        <p class="ukc-cred-verdict is-ok">{{ __('Inscrite au registre officiel') }}</p>
                    @endif

                    @if ($attestation->issued_at)
                        <p class="ukc-cred-meta">{{ __('Émise le') }} {{ $attestation->issued_at->translatedFormat('j F Y') }}</p>
                    @endif

                    <a href="{{ $verifyUrl }}" class="ukc-btn ukc-btn--green ukc-cred-action">
                        <x-ukc.icon name="check-circle" :size="16" /> {{ __('Vérifier l’attestation') }}
                    </a>
                    <p class="ukc-cred-id">{{ __('Identifiant') }} : <span class="ukc-cred-uuid">{{ $attestation->uuid }}</span></p>
                    @if ($signatureOk)
                        <a href="{{ route('attestation.credential', $attestation->uuid) }}" class="ukc-cred-link">
                            {{ __('Attestation vérifiable (JSON signé)') }}
                        </a>
                    @endif
                </div>
            </aside>

            <main class="ukc-cred-main">
                @if ($issuer)
                    <div class="ukc-card ukc-cred-card">
                        <div class="ukc-cred-kicker">{{ __('Délivré par') }}</div>
                        <h2 class="ukc-cred-issuer">{{ $issuer->name }}</h2>
                        @if (filled($issuer->description))
                            <p class="ukc-cred-text">{{ $issuer->description }}</p>
                        @endif
                    </div>
                @endif

                <div class="ukc-card ukc-cred-card">
                    <div class="ukc-cred-kicker">{{ __('Ce que certifie ce document') }}</div>
                    <dl class="ukc-cred-facts">
                        @foreach ($facts as $label => $value)
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </main>
        </div>
    </div>

    @push('head')
        @include('documents._fonts')
    @endpush

    @push('scripts')
        <script>
            // Le document est dessiné à une largeur fixe : on le met à l'échelle de
            // son cadre plutôt que d'imposer un défilement horizontal au visiteur.
            (function () {
                var box = document.querySelector('.ukc-cred-doc');
                if (!box) return;
                var inner = box.querySelector('.ukc-cred-doc-inner');
                var w = parseInt(box.dataset.docWidth, 10) || 0;
                function fit() {
                    if (!w) return;
                    var scale = Math.min(1, box.clientWidth / w);
                    inner.style.transform = 'scale(' + scale + ')';
                    box.style.height = (inner.scrollHeight * scale) + 'px';
                }
                fit();
                window.addEventListener('resize', fit);
                if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
            })();

            // Partage : la feuille de partage du système quand elle existe, sinon
            // le lien copié - jamais une impasse.
            (function () {
                function copy(url, el) {
                    navigator.clipboard.writeText(url).then(function () {
                        if (window.ukcToast) window.ukcToast('{{ __('Lien copié') }}');
                        else if (el) el.setAttribute('title', '{{ __('Lien copié') }}');
                    });
                }
                var share = document.querySelector('[data-cred-share]');
                if (share) share.addEventListener('click', function () {
                    var url = share.dataset.url;
                    if (navigator.share) navigator.share({ title: share.dataset.title, url: url }).catch(function () {});
                    else copy(url, share);
                });
                var link = document.querySelector('[data-cred-copy]');
                if (link) link.addEventListener('click', function () { copy(link.dataset.url, link); });
            })();
        </script>
    @endpush
</x-layouts.app>
