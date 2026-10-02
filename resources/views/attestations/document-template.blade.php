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
    $monogram = fn (string $name) => collect(preg_split('/\s+/', trim($name)) ?: [])
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $initials = $monogram((string) $attestation->recipient_name);
    $issuerInitials = $monogram((string) ($issuer?->name ?? config('app.name')));

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

        {{-- Le document, tel qu'il a été délivré. Les gestes se posent par-dessus
             au survol (et au clavier, via :focus-within) : la colonne de gauche
             les porte aussi, pour le tactile et l'impression. --}}
        <section class="ukc-cred-stage">
            <div class="ukc-cred-doc" data-doc-width="{{ $width }}" style="--doc-w:{{ $width }}px">
                <div class="ukc-cred-doc-inner">{!! $canvas !!}</div>
                <div class="ukc-cred-overlay no-print">
                    <p class="ukc-cred-overlay-hint">{{ __('Partagez votre attestation ou vérifiez-la en un geste.') }}</p>
                    @include('attestations._actions', ['variant' => 'overlay'])
                </div>
                {{-- Réduit à la largeur de l'écran, le document se lit mal. --}}
                <button type="button" class="ukc-cred-zoom no-print" data-cred-zoom
                        title="{{ __('Voir en grand') }}" aria-label="{{ __('Voir en grand') }}">
                    <x-ukc.icon name="search" :size="16" />
                </button>
            </div>
            <p class="ukc-cred-caption no-print">
                {{ __('Identifiant de l’attestation') }} :
                <span class="ukc-cred-uuid">{{ $attestation->uuid }}</span>
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

                    @include('attestations._actions', ['variant' => 'side'])

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

                    {{-- Le contrôle est refait à la demande, et son résultat s'écrit ici. --}}
                    <button type="button" class="ukc-btn ukc-btn--green ukc-cred-action" data-cred-verify
                            data-url="{{ route('attestation.verify.json', $attestation->uuid) }}">
                        <x-ukc.icon name="check-circle" :size="16" /> {{ __('Vérifier l’attestation') }}
                    </button>
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
                        <div class="ukc-cred-issuer-head">
                            <span class="ukc-cred-avatar is-issuer">{{ $issuerInitials }}</span>
                            <h2 class="ukc-cred-issuer">{{ $issuer->name }}</h2>
                        </div>
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

    {{-- Résultat de la vérification, sur cette page. --}}
    {{-- Le document en grand : sur un téléphone, réduit à la largeur de l'écran,
         il n'est plus lisible. --}}
    <div class="ukc-cred-zoomview no-print" id="credZoom" hidden role="dialog" aria-modal="true">
        <button type="button" class="ukc-cred-check-close" data-cred-zoom-close aria-label="{{ __('Fermer') }}">
            <x-ukc.icon name="close" :size="18" />
        </button>
        <div class="ukc-cred-zoomview-inner" data-doc-width="{{ $width }}">{!! $canvas !!}</div>
    </div>
    <div class="ukc-cred-check no-print" id="credCheck" hidden role="dialog" aria-modal="true" aria-labelledby="credCheckTitle">
        <div class="ukc-cred-check-box">
            <button type="button" class="ukc-cred-check-close" data-cred-check-close aria-label="{{ __('Fermer') }}">
                <x-ukc.icon name="close" :size="18" />
            </button>
            <div class="ukc-cred-check-badge" data-check-badge><x-ukc.icon name="shield" :size="26" /></div>
            <h2 id="credCheckTitle" class="ukc-cred-check-title" data-check-label>{{ __('Vérification en cours…') }}</h2>
            <p class="ukc-cred-check-detail" data-check-detail></p>
            <dl class="ukc-cred-facts" data-check-facts></dl>
            <p class="ukc-cred-check-time" data-check-time></p>
            <a class="ukc-cred-link" data-check-credential hidden>{{ __('Attestation vérifiable (JSON signé)') }}</a>
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
                document.querySelectorAll('[data-cred-share]').forEach(function (share) {
                    share.addEventListener('click', function () {
                        var url = share.dataset.url;
                        if (navigator.share) navigator.share({ title: share.dataset.title, url: url }).catch(function () {});
                        else copy(url, share);
                    });
                });
                document.querySelectorAll('[data-cred-copy]').forEach(function (link) {
                    link.addEventListener('click', function () { copy(link.dataset.url, link); });
                });
            })();

            // Vérification SUR CETTE PAGE : le contrôle est refait côté serveur
            // (registre + signature) et son résultat s'écrit ici, sans quitter le
            // document.
            (function () {
                var box = document.getElementById('credCheck');
                if (!box) return;
                var label = box.querySelector('[data-check-label]');
                var detail = box.querySelector('[data-check-detail]');
                var facts = box.querySelector('[data-check-facts]');
                var time = box.querySelector('[data-check-time]');
                var badge = box.querySelector('[data-check-badge]');
                var credential = box.querySelector('[data-check-credential]');

                function open() { box.hidden = false; document.body.style.overflow = 'hidden'; }
                function close() { box.hidden = true; document.body.style.overflow = ''; }

                function render(data) {
                    badge.className = 'ukc-cred-check-badge is-' + (data.status || 'ok');
                    label.textContent = data.label || '';
                    detail.textContent = data.detail || '';
                    time.textContent = data.checked_at ? '{{ __('Vérifié le') }} ' + data.checked_at : '';
                    facts.innerHTML = '';
                    Object.keys(data.facts || {}).forEach(function (k) {
                        var row = document.createElement('div');
                        var dt = document.createElement('dt');
                        var dd = document.createElement('dd');
                        dt.textContent = k;
                        dd.textContent = data.facts[k];
                        row.appendChild(dt); row.appendChild(dd); facts.appendChild(row);
                    });
                    if (data.credential_url) { credential.href = data.credential_url; credential.hidden = false; }
                    else credential.hidden = true;
                }

                document.querySelectorAll('[data-cred-verify]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        badge.className = 'ukc-cred-check-badge';
                        label.textContent = '{{ __('Vérification en cours…') }}';
                        detail.textContent = '';
                        facts.innerHTML = '';
                        time.textContent = '';
                        credential.hidden = true;
                        open();
                        fetch(btn.dataset.url, { headers: { Accept: 'application/json' } })
                            .then(function (r) { return r.json(); })
                            .then(render)
                            .catch(function () {
                                badge.className = 'ukc-cred-check-badge is-warn';
                                label.textContent = '{{ __('Vérification impossible') }}';
                                detail.textContent = '{{ __('Le service n’a pas répondu. Réessayez dans un instant.') }}';
                            });
                    });
                });

                box.addEventListener('click', function (e) { if (e.target === box) close(); });
                box.querySelector('[data-cred-check-close]').addEventListener('click', close);
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !box.hidden) close(); });
            })();

            // Voir le document en grand, à l'échelle de l'écran.
            (function () {
                var view = document.getElementById('credZoom');
                if (!view) return;
                var inner = view.querySelector('.ukc-cred-zoomview-inner');
                var w = parseInt(inner.dataset.docWidth, 10) || 0;
                function fit() {
                    if (!w) return;
                    var scale = Math.min((window.innerWidth - 32) / w, (window.innerHeight - 96) / inner.scrollHeight);
                    inner.style.transform = 'scale(' + Math.max(scale, .2) + ')';
                }
                function open() { view.hidden = false; document.body.style.overflow = 'hidden'; fit(); }
                function close() { view.hidden = true; document.body.style.overflow = ''; }
                document.querySelectorAll('[data-cred-zoom]').forEach(function (b) { b.addEventListener('click', open); });
                view.querySelector('[data-cred-zoom-close]').addEventListener('click', close);
                view.addEventListener('click', function (e) { if (e.target === view) close(); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !view.hidden) close(); });
                window.addEventListener('resize', function () { if (!view.hidden) fit(); });
            })();
        </script>
    @endpush
</x-layouts.app>
