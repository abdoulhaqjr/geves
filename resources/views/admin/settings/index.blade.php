@php($v = fn ($k) => old($k, $values[$k]))
<x-layouts.admin title="Réglages" subtitle="Configuration globale de la plateforme">
    {{-- Onglets pilotés par l'URL : `?tab=` décide de l'onglet ouvert, et chaque
         clic le réécrit (`history.pushState`), donc l'onglet se partage par lien
         et les flèches du navigateur fonctionnent.

         ⚠️ On réécrit l'URL plutôt que de naviguer : les six premiers panneaux
         vivent dans UN SEUL formulaire, une vraie navigation perdrait les
         modifications non enregistrées des autres onglets.
         ⚠️ Valeur filtrée sur les onglets connus : un `?tab=` fantaisiste
         n'afficherait aucun panneau, et la page paraîtrait vide. --}}
    @php($openTab = in_array($t = request('tab', session('settings_tab', 'general')), \App\Http\Controllers\Admin\SettingController::TABS, true) ? $t : 'general')
    <div class="admin-page admin-settings" x-data="ukcUrlTabs(@js($openTab))">
        @include('admin.settings._nav')

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="active_tab" :value="tab">
            {{-- Général --}}
            <div class="ukc-panel" :class="{ active: tab==='general' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="settings" :size="20" /> Général</span></h2>
                    <p class="desc">Identité de l’expéditeur des e-mails et devise utilisée pour les frais.</p>
                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label for="from_name">Nom de l’expéditeur</label>
                            <input type="text" id="from_name" name="from_name" value="{{ $v('from_name') }}">
                            @error('from_name') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="from_address">E-mail de l’expéditeur</label>
                            <input type="email" id="from_address" name="from_address" value="{{ $v('from_address') }}">
                            @error('from_address') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="currency">Devise</label>
                            <input type="text" id="currency" name="currency" value="{{ $v('currency') }}" maxlength="10">
                            {{-- ⚠️ Le CODE ISO, pas le mot : il part tel quel au prestataire.
                                 « XOF » s'écrit « FCFA » partout où une somme est affichée. --}}
                            <span class="hint">Code ISO (XOF). Les montants s'affichent en FCFA.</span>
                        </div>
                        {{-- Une carte par forme (large, compacte). Chaque carte montre
                             le logo sur SES DEUX fonds et accepte une version claire
                             facultative : sans elle, on blanchit en CSS, ce qui sauve
                             la lisibilité mais écrase les évidements - l'aperçu sombre
                             le rend visible avant d'enregistrer. --}}
                        <div class="ukc-field full">
                            <label>Logos de la plateforme</label>
                            <span class="hint" style="font-size:12px;color:var(--ukc-muted);margin-bottom:12px;display:block;">
                                Le <strong>logo large</strong> s’affiche là où la place le permet : pied de page, pages de
                                connexion et de création de compte, bandeau des pages d’événement. Le <strong>logo compact</strong>
                                sert à l’en-tête public et aux menus latéraux, y compris réduits. PNG, JPG, WEBP ou SVG,
                                1 Mo maximum, de préférence sur fond transparent.
                            </span>

                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:14px;">
                                @foreach ([
                                    [
                                        'titre' => 'Logo large',
                                        'aide' => 'Marque complète, nom compris. Rapport conseillé 3:1 environ.',
                                        'clair' => ['key' => \App\Support\Branding::WIDE, 'url' => \App\Support\Branding::wide()],
                                        'sombre' => ['key' => \App\Support\Branding::WIDE_WHITE, 'url' => \App\Support\Branding::wideWhite()],
                                        'whiten' => \App\Support\Branding::whitensOnDark(\App\Support\Branding::WIDE),
                                    ],
                                    [
                                        'titre' => 'Logo compact',
                                        'aide' => 'Monogramme carré. Sert aussi d’icône d’accueil sur mobile.',
                                        'clair' => ['key' => \App\Support\Branding::MARK, 'url' => \App\Support\Branding::mark()],
                                        'sombre' => ['key' => \App\Support\Branding::MARK_WHITE, 'url' => \App\Support\Branding::markWhite()],
                                        'whiten' => \App\Support\Branding::whitensOnDark(\App\Support\Branding::MARK),
                                    ],
                                ] as $logo)
                                    <div class="ukc-logo-card">
                                        <div class="head">
                                            {{ $logo['titre'] }}
                                            <span class="ukc-pill {{ \App\Support\Branding::url($logo['clair']['key']) ? 'ukc-pill--info' : 'ukc-pill--muted' }}">
                                                {{ \App\Support\Branding::url($logo['clair']['key']) ? 'Personnalisé' : 'Logo livré' }}
                                            </span>
                                        </div>
                                        <p class="hint">{{ $logo['aide'] }}</p>

                                        @foreach ([
                                            ['v' => $logo['clair'], 'fond' => 'light', 'label' => 'Fond clair', 'note' => null],
                                            ['v' => $logo['sombre'], 'fond' => 'dark', 'label' => 'Fond sombre', 'note' => 'Facultatif. Sans fichier, le logo clair est mis en blanc automatiquement - les évidements (lettres détourées) sont alors perdus.'],
                                        ] as $slot)
                                            <div class="ukc-logo-upload" x-data="{
                                                    preview: '{{ $slot['v']['url'] }}',
                                                    livre: '{{ \App\Support\Branding::shipped($slot['v']['key']) }}',
                                                    custom: {{ \App\Support\Branding::url($slot['v']['key']) ? 'true' : 'false' }},
                                                    whiten: {{ ($slot['fond'] === 'dark' && $logo['whiten']) ? 'true' : 'false' }},
                                                    removed: false,
                                                    pick(e) {
                                                        const f = e.target.files[0]; if (! f) return;
                                                        this.preview = URL.createObjectURL(f);
                                                        this.custom = true; this.whiten = false; this.removed = false;
                                                    },
                                                    reset() {
                                                        this.preview = this.livre; this.custom = false;
                                                        this.whiten = false; this.removed = true; this.$refs.f.value = '';
                                                    }
                                                 }">
                                                <div class="shot shot--{{ $slot['fond'] }}">
                                                    <img :src="preview" :class="{ 'logo-auto-white': whiten }" alt="{{ $logo['titre'] }} - {{ $slot['label'] }}">
                                                </div>
                                                <div class="body">
                                                    <div class="name">{{ $slot['label'] }}</div>
                                                    @if ($slot['note'])
                                                        <p class="hint">{{ $slot['note'] }}</p>
                                                    @endif
                                                    <div class="row">
                                                        <input type="file" id="{{ $slot['v']['key'] }}" name="{{ $slot['v']['key'] }}" x-ref="f"
                                                               accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="pick($event)">
                                                        <label for="{{ $slot['v']['key'] }}" class="ukc-btn ukc-btn--sm" style="cursor:pointer;">
                                                            <x-ukc.icon name="upload" :size="16" /> <span x-text="custom ? 'Remplacer' : 'Téléverser'">Téléverser</span>
                                                        </label>
                                                        {{-- Supprimer = rendre la main au logo livré : il n'y a
                                                             jamais « pas de logo ». --}}
                                                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" x-show="custom" x-cloak @click="reset()">
                                                            Supprimer
                                                        </button>
                                                    </div>
                                                    {{-- Champ caché plutôt qu'une case : la remise à zéro se décide
                                                         par le bouton, qui met aussi l'aperçu à jour. --}}
                                                    <input type="hidden" name="{{ $slot['v']['key'] }}_remove" :value="removed ? '1' : '0'">
                                                    @error($slot['v']['key']) <span class="error">{{ $message }}</span> @enderror
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="ukc-field">
                            <label for="app_name">Nom de la plateforme</label>
                            <input type="text" id="app_name" name="app_name" value="{{ $v('app_name') }}" maxlength="60" placeholder="GEVES">
                            <span class="hint" style="font-size:12px;color:var(--ukc-muted);">Marque affichée partout (en-têtes, titres, e-mails, code TOTP). Laisser vide pour utiliser APP_NAME du .env.</span>
                        </div>
                        <div class="ukc-field full">
                            <label for="participant_types">Types / grades de participant</label>
                            <textarea id="participant_types" name="participant_types" rows="6"
                                      placeholder="Un grade par ligne">{{ $v('participant_types') }}</textarea>
                            <span class="hint" style="font-size:12px;color:var(--ukc-muted);">
                                Un grade par ligne. C’est la <strong>liste unique</strong> proposée à l’inscription, au profil
                                et pour les co-auteurs ; c’est aussi elle qu’on rattache aux catégories tarifaires d’un événement.
                                Renommer un grade déjà utilisé ne met pas à jour les comptes existants : ajoutez plutôt une ligne.
                            </span>
                        </div>
                        <div class="ukc-field">
                            <label for="app_url">URL du site (APP_URL)</label>
                            <input type="url" id="app_url" name="app_url" value="{{ $v('app_url') }}" placeholder="https://gesco.test">
                            <span class="hint" style="font-size:12px;color:var(--ukc-muted);">Base des liens dans les e-mails et notifications (utile pour les envois en file d’attente).</span>
                            @error('app_url') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="ukc-card" style="margin-top:18px;">
                    <h2><span><x-ukc.icon name="globe" :size="20" /> Réseaux sociaux du site</span></h2>
                    <p class="desc">Utilisés comme repli dans le pied de page d’un colloque qui n’a pas renseigné les siens.</p>
                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label for="site_facebook">Facebook</label>
                            <input type="url" id="site_facebook" name="site_facebook" value="{{ $v('site_facebook') }}" placeholder="https://facebook.com/…">
                            @error('site_facebook') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="site_x">X (Twitter)</label>
                            <input type="url" id="site_x" name="site_x" value="{{ $v('site_x') }}" placeholder="https://x.com/…">
                            @error('site_x') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="site_youtube">YouTube</label>
                            <input type="url" id="site_youtube" name="site_youtube" value="{{ $v('site_youtube') }}" placeholder="https://youtube.com/…">
                            @error('site_youtube') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="site_linkedin">LinkedIn</label>
                            <input type="url" id="site_linkedin" name="site_linkedin" value="{{ $v('site_linkedin') }}" placeholder="https://linkedin.com/…">
                            @error('site_linkedin') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bannières --}}
            <div class="ukc-panel" :class="{ active: tab==='banners' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="image" :size="20" /> Bannières</span></h2>
                    <p class="desc">
                        Les grandes images de bandeau du site. Format paysage très large conseillé
                        (21:9, par exemple 1920 x 820). Un voile bleu est posé par-dessus pour que
                        le texte blanc reste lisible : évitez les visuels déjà très chargés au centre.
                    </p>
                    <div class="ukc-banner-grid">
                        @foreach ([
                            [
                                'key' => \App\Support\SiteBanners::HOME,
                                'titre' => 'Bandeau de la page d’accueil',
                                'aide' => 'Première image du site, derrière le titre de la plateforme. Sans fichier, le dégradé de la charte est utilisé.',
                            ],
                            [
                                'key' => \App\Support\SiteBanners::EVENT_DEFAULT,
                                'titre' => 'Bandeau par défaut des pages',
                                'aide' => 'Habille les bandeaux des pages publiques hors accueil et ceux des événements sans visuel propre. Un événement qui a choisi son bandeau garde le sien.',
                            ],
                        ] as $banniere)
                            @php($posee = \App\Support\SiteBanners::url($banniere['key']))
                            <div class="ukc-banner-card" x-data="{
                                    preview: '{{ $posee }}',
                                    custom: {{ $posee ? 'true' : 'false' }},
                                    removed: false,
                                    pick(e) {
                                        const f = e.target.files[0]; if (! f) return;
                                        this.preview = URL.createObjectURL(f);
                                        this.custom = true; this.removed = false;
                                    },
                                    reset() {
                                        this.preview = ''; this.custom = false;
                                        this.removed = true; this.$refs.f.value = '';
                                    }
                                 }">
                                <div class="head">
                                    {{ $banniere['titre'] }}
                                    <span class="ukc-pill" :class="custom ? 'ukc-pill--info' : 'ukc-pill--muted'"
                                          x-text="custom ? 'Image posée' : 'Dégradé de la charte'">Dégradé de la charte</span>
                                </div>
                                <p class="hint">{{ $banniere['aide'] }}</p>
                                {{-- L'aperçu vide n'est pas vide : il montre le dégradé que les
                                     visiteurs voient aujourd'hui, c'est-à-dire ce que remplacera
                                     le fichier téléversé. --}}
                                <div class="shot" :style="preview ? `background-image:url(&quot;${preview}&quot;)` : ''">
                                    <span x-show="! preview" x-cloak>Aucune image : dégradé de la charte</span>
                                </div>
                                <div class="row">
                                    <input type="file" id="{{ $banniere['key'] }}" name="{{ $banniere['key'] }}" x-ref="f"
                                           accept="image/png,image/jpeg,image/webp,image/avif" @change="pick($event)">
                                    <label for="{{ $banniere['key'] }}" class="ukc-btn ukc-btn--sm" style="cursor:pointer;">
                                        <x-ukc.icon name="upload" :size="16" /> <span x-text="custom ? 'Remplacer' : 'Téléverser'">Téléverser</span>
                                    </label>
                                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" x-show="custom" x-cloak @click="reset()">
                                        Supprimer
                                    </button>
                                    <span class="hint">{{ number_format(\App\Http\Controllers\Admin\SettingController::bannerMaxKb() / 1024, 1, ',', ' ') }} Mo max - JPG, PNG, WebP ou AVIF</span>
                                </div>
                                {{-- Champ caché plutôt qu'une case : la remise à zéro se décide par
                                     le bouton, qui met aussi l'aperçu à jour. --}}
                                <input type="hidden" name="{{ $banniere['key'] }}_remove" :value="removed ? '1' : '0'">
                                @error($banniere['key']) <span class="error">{{ $message }}</span> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- Paiement --}}
            <div class="ukc-panel" :class="{ active: tab==='payments' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="card" :size="20" /> Paiement</span></h2>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label for="payments_default">Passerelle de paiement</label>
                            <select id="payments_default" name="payments_default">
                                <option value="manual" @selected($v('payments_default') === 'manual')>Paiement sur place / virement (manuel)</option>
                                <option value="fedapay" @selected($v('payments_default') === 'fedapay')>FedaPay</option>
                            </select>
                            <span class="hint">C’est la <strong>seule</strong> que le participant verra : choisir FedaPay, c’est n’encaisser qu’en ligne ; choisir le manuel, c’est n’encaisser qu’au guichet. Un événement peut la remplacer par la sienne. ⚠️ Sans identifiants renseignés, FedaPay retombe sur le manuel - une clé oubliée ne doit pas fermer la caisse.</span>
                            @error('payments_default') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <h3 style="font-size:.95rem;margin:16px 0 8px;">FedaPay</h3>
                    <div class="ukc-form-grid">
                        <div class="ukc-field"><label for="fedapay_secret">Clé secrète</label><input type="password" id="fedapay_secret" name="fedapay_secret" value="{{ $v('fedapay_secret') }}" autocomplete="new-password"></div>
                        <div class="ukc-field"><label for="fedapay_mode">Mode</label>
                            <select id="fedapay_mode" name="fedapay_mode">
                                <option value="test" @selected($v('fedapay_mode') === 'test')>Sandbox (test)</option>
                                <option value="live" @selected($v('fedapay_mode') === 'live')>Production (live)</option>
                            </select>
                            @error('fedapay_mode') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full"><label for="fedapay_webhook_secret">Secret de webhook</label><input type="password" id="fedapay_webhook_secret" name="fedapay_webhook_secret" value="{{ $v('fedapay_webhook_secret') }}" autocomplete="new-password"></div>
                        <div class="ukc-field full">
                            <label for="fedapay_public_key">Clé publique (formulaire sur notre page)</label>
                            <input type="text" id="fedapay_public_key" name="fedapay_public_key" value="{{ $v('fedapay_public_key') }}" autocomplete="off" placeholder="pk_live_… / pk_sandbox_…">
                            <span class="hint">Renseignée, le formulaire de paiement s’ouvre <strong>sur la page du participant</strong> au lieu de l’envoyer ailleurs. Laissée vide, le règlement se fait par redirection. ⚠️ C’est la clé <strong>publique</strong> : elle part dans le navigateur, et ce n’est pas la clé secrète ci-dessus.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- E-mail (SMTP) --}}
            <div class="ukc-panel" :class="{ active: tab==='email' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="mail" :size="20" /> Envoi des e-mails (SMTP)</span></h2>
                    <p class="desc">« Journal » écrit les e-mails dans les logs sans les envoyer (dev) ; « SMTP » les expédie via les paramètres ci-dessous.</p>
                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label for="mail_mailer">Transport</label>
                            <select id="mail_mailer" name="mail_mailer">
                                <option value="log" @selected($v('mail_mailer') === 'log')>Journal (log - n’envoie rien)</option>
                                <option value="smtp" @selected($v('mail_mailer') === 'smtp')>SMTP (envoi réel)</option>
                            </select>
                            @error('mail_mailer') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="mail_host">Hôte</label>
                            <input type="text" id="mail_host" name="mail_host" value="{{ $v('mail_host') }}" placeholder="smtp.exemple.tg">
                        </div>
                        <div class="ukc-field">
                            <label for="mail_port">Port</label>
                            <input type="number" id="mail_port" name="mail_port" value="{{ $v('mail_port') }}">
                            @error('mail_port') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="mail_encryption">Chiffrement</label>
                            <select id="mail_encryption" name="mail_encryption">
                                <option value="tls" @selected($v('mail_encryption') === 'tls')>TLS</option>
                                <option value="ssl" @selected($v('mail_encryption') === 'ssl')>SSL</option>
                                <option value="" @selected($v('mail_encryption') === '')>Aucun</option>
                            </select>
                        </div>
                        <div class="ukc-field">
                            <label for="mail_username">Identifiant</label>
                            <input type="text" id="mail_username" name="mail_username" value="{{ $v('mail_username') }}" autocomplete="off">
                        </div>
                        <div class="ukc-field">
                            <label for="mail_password">Mot de passe</label>
                            <input type="password" id="mail_password" name="mail_password" value="{{ $v('mail_password') }}" autocomplete="new-password">
                        </div>
                        <div class="ukc-field full">
                            <input type="hidden" name="mail_pilot_copy_enabled" value="0">
                            <label style="display:inline-flex;align-items:center;gap:8px;font-size:.95rem;font-weight:500;">
                                <input type="checkbox" name="mail_pilot_copy_enabled" value="1" style="width:auto;" @checked(old('mail_pilot_copy_enabled', $values['mail_pilot_copy_enabled']))>
                                Envoyer une copie de chaque e-mail
                            </label>
                        </div>
                        <div class="ukc-field full">
                            <label for="mail_pilot_copy_address">Adresse de réception des copies</label>
                            <input type="email" id="mail_pilot_copy_address" name="mail_pilot_copy_address" value="{{ $v('mail_pilot_copy_address') }}" autocomplete="off" style="max-width:420px;">
                            @error('mail_pilot_copy_address') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid var(--ukc-border);">
                        <div class="ukc-field" style="flex:1;min-width:220px;">
                            <label for="test_email">Envoyer un e-mail de test à</label>
                            <input type="email" id="test_email" name="test_email" value="{{ old('test_email') }}" placeholder="vous@exemple.tg">
                            @error('test_email') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" formaction="{{ route('admin.settings.test') }}"
                                class="ukc-btn ukc-btn--outline ukc-btn--sm">Envoyer le test</button>
                    </div>
                </div>
            </div>

            {{-- Sécurité des formulaires publics --}}
            <div class="ukc-panel" :class="{ active: tab==='security' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="shield" :size="20" /> Sécurité</span></h2>
                    <p class="desc">Cloudflare Turnstile protège l’inscription des participants et le formulaire de contact public. Créez un widget Turnstile pour les domaines de la plateforme, puis renseignez ses deux clés.</p>
                    <p class="ukc-pill {{ \App\Support\Turnstile::configured() ? 'ukc-pill--open' : 'ukc-pill--muted' }}" style="margin-bottom:16px;">
                        {{ \App\Support\Turnstile::configured() ? 'Clés configurées' : 'Clés à renseigner : formulaires temporairement indisponibles' }}
                    </p>
                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label for="turnstile_site_key">Clé du site <span class="req">*</span></label>
                            <input type="text" id="turnstile_site_key" name="turnstile_site_key" value="{{ $v('turnstile_site_key') }}" autocomplete="off" placeholder="Clé publique Turnstile">
                            <span class="hint">Cette clé est affichée dans le navigateur pour charger le défi.</span>
                            @error('turnstile_site_key') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="turnstile_secret_key">Clé secrète <span class="req">*</span></label>
                            <input type="password" id="turnstile_secret_key" name="turnstile_secret_key" value="" autocomplete="new-password" placeholder="{{ filled(config('services.turnstile.secret_key')) ? 'Clé enregistrée - laisser vide pour conserver' : 'Clé secrète Turnstile' }}">
                            <span class="hint">Enregistrée chiffrée ; jamais réaffichée. Laissez vide pour conserver la clé actuelle.</span>
                            @error('turnstile_secret_key') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                                <input type="checkbox" name="turnstile_secret_key_remove" value="1" style="width:auto;">
                                Supprimer la clé secrète enregistrée
                            </label>
                            <span class="hint">Après suppression, les deux formulaires seront bloqués jusqu’à la saisie d’une nouvelle clé.</span>
                        </div>
                    </div>
                </div>

                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="settings" :size="20" /> Cookies</span></h2>
                    <p class="desc">
                        La plateforme ne dépose que les cookies nécessaires à son fonctionnement : la session et la
                        sécurité des formulaires. Le bandeau l’annonce au visiteur et renvoie vers
                        <a href="{{ route('public.cookies') }}" target="_blank" rel="noopener">la page qui les liste</a>,
                        laquelle reste atteignable depuis le pied de page même bandeau masqué.
                    </p>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                                <input type="checkbox" name="cookies_notice_enabled" value="1" style="width:auto;" @checked($v('cookies_notice_enabled'))>
                                Afficher le bandeau d’information sur les pages publiques
                            </label>
                            <span class="hint">Le visiteur l’écarte d’un clic, pour {{ \App\Support\CookieConsent::lifetimeDays() }} jours.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Intelligence artificielle --}}
            <div class="ukc-panel" :class="{ active: tab==='ai' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="sparkles" :size="20" /> Intelligence artificielle</span></h2>
                    <p class="desc">
                        Utilisée dans les chats, les rapports, l’analyse et la génération de documents.
                        Les clés sont facultatives ; renseignez au moins un fournisseur.
                    </p>
                    <div class="ukc-field" style="max-width:360px;">
                        <label for="ai_default">Fournisseur par défaut</label>
                        <select id="ai_default" name="ai_default">
                            <option value="claude" @selected($v('ai_default') === 'claude')>Claude (Anthropic)</option>
                            <option value="openai" @selected($v('ai_default') === 'openai')>OpenAI</option>
                            <option value="gemini" @selected($v('ai_default') === 'gemini')>Gemini (Google)</option>
                            <option value="grok" @selected($v('ai_default') === 'grok')>Grok (xAI)</option>
                            <option value="openrouter" @selected($v('ai_default') === 'openrouter')>OpenRouter (Gemma)</option>
                        </select>
                        @error('ai_default') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field" style="margin-top:6px;">
                        <span class="hint">Repli automatique sur un autre fournisseur configuré si nécessaire.</span>
                    </div>

                    @php($aiProviders = [
                        ['anthropic', 'Claude (Anthropic)', 'ex. claude-sonnet-4-5'],
                        ['openai', 'OpenAI', 'ex. gpt-4o'],
                        ['gemini', 'Gemini (Google)', 'ex. gemini-2.5-flash'],
                        ['grok', 'Grok (xAI)', 'ex. grok-4'],
                    ])
                    <div style="margin-top:14px;display:flex;flex-direction:column;gap:12px;">
                        <div style="display:grid;grid-template-columns:150px 1fr 1fr;gap:10px;font-size:.75rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                            <span>Fournisseur</span><span>Clé API</span><span>Modèle par défaut</span>
                        </div>
                        @foreach ($aiProviders as [$prov, $label, $ph])
                            <div style="display:grid;grid-template-columns:150px 1fr 1fr;gap:10px;align-items:center;">
                                <span style="font-weight:600;font-size:.88rem;">{{ $label }}</span>
                                <input type="password" name="ai_{{ $prov }}_key" value="{{ $v('ai_'.$prov.'_key') }}" autocomplete="new-password" placeholder="Clé API"
                                       style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                                <input type="text" name="ai_{{ $prov }}_model" value="{{ $v('ai_'.$prov.'_model') }}" placeholder="{{ $ph }}"
                                       style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                            </div>
                        @endforeach
                        <div style="display:grid;grid-template-columns:150px 1fr 1fr;gap:10px;align-items:center;">
                            <span style="font-weight:600;font-size:.88rem;">OpenRouter (Gemma)</span>
                            <input type="password" name="ai_openrouter_key" autocomplete="new-password" placeholder="Clé API"
                                   style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                            <input type="text" name="ai_openrouter_model" value="{{ $v('ai_openrouter_model') }}" placeholder="google/gemma-4-31b-it:free"
                                   style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                        </div>
                        @error('ai_openrouter_key') <span class="error">{{ $message }}</span> @enderror
                        @error('ai_openrouter_model') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Visio (LiveKit) --}}
            <div class="ukc-panel" :class="{ active: tab==='meetings' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="video" :size="20" /> Visio des sessions</span></h2>
                    <p class="desc">
                        Plateforme de visio interactive pour les présentations en ligne. LiveKit nécessite un serveur
                        (LiveKit Cloud ou self-host) et ses identifiants d’API. Sans identifiants, le système bascule
                        automatiquement sur Jitsi (mode public, sans configuration).
                    </p>

                    <div class="ukc-field" style="max-width:360px;">
                        <label for="meeting_default">Fournisseur par défaut</label>
                        <select id="meeting_default" name="meeting_default">
                            <option value="livekit" @selected($v('meeting_default') === 'livekit')>LiveKit</option>
                            <option value="jitsi" @selected($v('meeting_default') === 'jitsi')>Jitsi (repli, sans config)</option>
                        </select>
                        @error('meeting_default') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ukc-field full" style="margin-top:12px;">
                        <label for="meetings_backgrounds">Arrière-plans virtuels <span class="hint">(une URL d’image par ligne)</span></label>
                        <textarea id="meetings_backgrounds" name="meetings_backgrounds" rows="4"
                                  placeholder="https://exemple.tg/fonds/campus.jpg&#10;https://exemple.tg/fonds/amphi.jpg">{{ $v('meetings_backgrounds') }}</textarea>
                        <span class="hint">Proposés dans la salle visio (en plus de « Aucun » et « Flou »). Les participants peuvent aussi charger leur propre image. Les images doivent être accessibles en HTTPS et autoriser le cross-origin (CORS).</span>
                    </div>

                    <h3 style="font-size:.95rem;margin:16px 0 8px;">LiveKit</h3>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label for="livekit_url">URL du serveur <span class="hint">(wss://…)</span></label>
                            <input type="text" id="livekit_url" name="livekit_url" value="{{ $v('livekit_url') }}" placeholder="wss://ton-projet.livekit.cloud">
                        </div>
                        <div class="ukc-field">
                            <label for="livekit_api_key">Clé d’API</label>
                            <input type="password" id="livekit_api_key" name="livekit_api_key" value="{{ $v('livekit_api_key') }}" autocomplete="new-password" placeholder="API key">
                        </div>
                        <div class="ukc-field">
                            <label for="livekit_api_secret">Secret d’API</label>
                            <input type="password" id="livekit_api_secret" name="livekit_api_secret" value="{{ $v('livekit_api_secret') }}" autocomplete="new-password" placeholder="API secret">
                        </div>
                        <div class="ukc-field full">
                            <label for="livekit_meet_url">App LiveKit Meet <span class="hint">(par défaut l’app publique)</span></label>
                            <input type="url" id="livekit_meet_url" name="livekit_meet_url" value="{{ $v('livekit_meet_url') }}" placeholder="https://meet.livekit.io/custom">
                            @error('livekit_meet_url') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <h3 style="font-size:.95rem;margin:16px 0 8px;">Enregistrement <span class="hint">(stockage S3)</span></h3>
                    <p style="font-size:.84rem;color:var(--ukc-muted);margin:0 0 10px;">
                        Les sessions peuvent être enregistrées (Egress RoomComposite → MP4). LiveKit dépose le fichier
                        dans un bucket objet compatible S3. Sans ces valeurs, le bouton d’enregistrement reste indisponible.
                    </p>
                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label for="livekit_egress_s3_bucket">Bucket</label>
                            <input type="text" id="livekit_egress_s3_bucket" name="livekit_egress_s3_bucket" value="{{ $v('livekit_egress_s3_bucket') }}" placeholder="gesco-recordings">
                        </div>
                        <div class="ukc-field">
                            <label for="livekit_egress_s3_region">Région</label>
                            <input type="text" id="livekit_egress_s3_region" name="livekit_egress_s3_region" value="{{ $v('livekit_egress_s3_region') }}" placeholder="eu-west-3">
                        </div>
                        <div class="ukc-field">
                            <label for="livekit_egress_s3_access_key">Access key</label>
                            <input type="password" id="livekit_egress_s3_access_key" name="livekit_egress_s3_access_key" value="{{ $v('livekit_egress_s3_access_key') }}" autocomplete="new-password" placeholder="AKIA…">
                        </div>
                        <div class="ukc-field">
                            <label for="livekit_egress_s3_secret">Secret key</label>
                            <input type="password" id="livekit_egress_s3_secret" name="livekit_egress_s3_secret" value="{{ $v('livekit_egress_s3_secret') }}" autocomplete="new-password" placeholder="Secret">
                        </div>
                        <div class="ukc-field full">
                            <label for="livekit_egress_s3_endpoint">Endpoint <span class="hint">(optionnel - S3-compatible : MinIO…)</span></label>
                            <input type="url" id="livekit_egress_s3_endpoint" name="livekit_egress_s3_endpoint" value="{{ $v('livekit_egress_s3_endpoint') }}" placeholder="https://s3.exemple.com">
                            @error('livekit_egress_s3_endpoint') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Intégrations --}}
            <div class="ukc-panel" :class="{ active: tab==='integrations' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="file" :size="20" /> Documents de travail DocSpace</span></h2>
                    <p class="desc">Connexion utilisée pour créer, modifier et télécharger les documents des tâches. Les valeurs laissées vides utilisent la configuration du serveur. La salle peut aussi être définie pour chaque événement.</p>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label for="docspace_url">Adresse DocSpace (HTTPS)</label>
                            <input type="url" id="docspace_url" name="docspace_url" value="{{ $v('docspace_url') }}" placeholder="https://votre-docspace.example">
                            @error('docspace_url') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="docspace_room_id">Identifiant de la salle par défaut</label>
                            <input type="text" inputmode="numeric" id="docspace_room_id" name="docspace_room_id" value="{{ $v('docspace_room_id') }}" placeholder="Identifiant numérique">
                            @error('docspace_room_id') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="docspace_api_key">Clé API @if($values['docspace_api_key_configured']) <span class="hint">(déjà configurée)</span> @endif</label>
                            <input type="password" id="docspace_api_key" name="docspace_api_key" value="" autocomplete="new-password" placeholder="Laisser vide pour conserver la clé actuelle">
                            @error('docspace_api_key') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <input type="hidden" name="docspace_file_links_enabled" value="0">
                            <label style="display:inline-flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="docspace_file_links_enabled" value="1" @checked(old('docspace_file_links_enabled', $values['docspace_file_links_enabled']))>
                                Activer les liens d’édition externes des fichiers
                            </label>
                            <p class="ukc-hint">Un lien donne accès à l’édition du fichier à toute personne qui le possède. GEVES ne le remet qu’aux collaborateurs autorisés.</p>
                        </div>
                    </div>
                </div>

                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="globe" :size="20" /> Interconnexion HAL</span></h2>
                    <p class="desc">
                        Active l’autocomplétion des affiliations depuis le référentiel des structures HAL (AuréHAL),
                        sur le profil des participants. Aucun compte HAL requis pour cette étape.
                    </p>
                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:.95rem;">
                        <input type="checkbox" name="hal_enabled" value="1" @checked(old('hal_enabled', $values['hal_enabled']))> Activer l’autocomplétion des affiliations (HAL)
                    </label>
                </div>

                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="phone" :size="20" /> SMS (2FA par code)</span></h2>
                    <p class="desc">
                        Passerelle d’envoi des codes de vérification (OTP) pour la double authentification par SMS des
                        comptes participants. « Journal » n’envoie rien (journalise, développement) ; « Passerelle HTTP »
                        appelle une API d’envoi de SMS.
                    </p>
                    <div class="ukc-field" style="max-width:360px;">
                        <label for="sms_default">Fournisseur par défaut</label>
                        <select id="sms_default" name="sms_default">
                            <option value="log" @selected($v('sms_default') === 'log')>Journal (développement)</option>
                            <option value="http" @selected($v('sms_default') === 'http')>Passerelle HTTP (API)</option>
                        </select>
                        @error('sms_default') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <h3 style="font-size:.95rem;margin:16px 0 8px;">Passerelle HTTP</h3>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label for="sms_http_url">URL de l’API <span class="hint">(POST JSON : to, message, sender)</span></label>
                            <input type="url" id="sms_http_url" name="sms_http_url" value="{{ $v('sms_http_url') }}" placeholder="https://api.passerelle-sms.tg/send">
                            @error('sms_http_url') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="sms_http_token">Jeton (Bearer)</label>
                            <input type="password" id="sms_http_token" name="sms_http_token" value="{{ $v('sms_http_token') }}" autocomplete="new-password">
                        </div>
                        <div class="ukc-field">
                            <label for="sms_http_sender">Expéditeur (Sender ID)</label>
                            <input type="text" id="sms_http_sender" name="sms_http_sender" value="{{ $v('sms_http_sender') }}" placeholder="UKColloques" maxlength="20">
                        </div>
                    </div>
                </div>

                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="shield" :size="20" /> Anti-plagiat (vérification web)</span></h2>
                    <p class="desc">
                        Complément à la <strong>similarité interne</strong> (toujours active, gratuite) : une vérification
                        contre le web via un fournisseur externe, lancée à la demande depuis la fiche d’une soumission.
                        « Aucun » désactive la vérification web.
                    </p>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label for="plagiarism_provider">Fournisseur</label>
                            <select id="plagiarism_provider" name="plagiarism_provider" style="max-width:360px;">
                                <option value="none" @selected($v('plagiarism_provider') === 'none' || $v('plagiarism_provider') === '')>Aucun (similarité interne seule)</option>
                                <option value="prepostseo" @selected($v('plagiarism_provider') === 'prepostseo')>Prepostseo (payant)</option>
                            </select>
                            @error('plagiarism_provider') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label for="plagiarism_prepostseo_key">Clé API Prepostseo <span class="hint">(2 500 mots max/vérif, quota selon votre offre)</span></label>
                            <input type="password" id="plagiarism_prepostseo_key" name="plagiarism_prepostseo_key" value="{{ $v('plagiarism_prepostseo_key') }}" autocomplete="new-password" placeholder="clé fournie par prepostseo.com">
                        </div>
                    </div>
                </div>

                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="check-circle" :size="20" /> Connexion ORCID</span></h2>
                    <p class="desc">
                        Permet aux auteurs de <strong>prouver l’appartenance</strong> de leur identifiant ORCID (bouton « Connecter avec ORCID » sur leur profil).
                        API publique gratuite : créez une application sur orcid.org (Developer tools) et enregistrez l’URL de redirection ci-dessous.
                    </p>
                    <div class="ukc-field full">
                        <label>URL de redirection à enregistrer chez ORCID</label>
                        <input type="text" value="{{ route('account.orcid.callback') }}" readonly onclick="this.select()" style="background:var(--ukc-bg);">
                    </div>
                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label for="orcid_environment">Environnement</label>
                            <select id="orcid_environment" name="orcid_environment">
                                <option value="sandbox" @selected($v('orcid_environment') !== 'production')>Sandbox (test)</option>
                                <option value="production" @selected($v('orcid_environment') === 'production')>Production</option>
                            </select>
                        </div>
                        <div class="ukc-field">
                            <label for="orcid_client_id">Client ID</label>
                            <input type="text" id="orcid_client_id" name="orcid_client_id" value="{{ $v('orcid_client_id') }}" autocomplete="off" placeholder="APP-XXXXXXXXXXXXXXXX">
                        </div>
                        <div class="ukc-field full">
                            <label for="orcid_client_secret">Client secret</label>
                            <input type="password" id="orcid_client_secret" name="orcid_client_secret" value="{{ $v('orcid_client_secret') }}" autocomplete="new-password">
                        </div>
                    </div>
                </div>

                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="bell" :size="20" /> Notifications instantanées (WebSocket)</span></h2>
                    <p class="desc">
                        Actualise la cloche dès qu’une notification est enregistrée, dans l’espace participant et le back-office.
                        La connexion utilise un service WebSocket compatible Pusher. Renseignez son hôte, ou laissez-le vide
                        pour utiliser les serveurs de Pusher avec le cluster de votre application.
                    </p>
                    <p class="desc">
                        Sans connexion WebSocket, les notifications restent disponibles dans la cloche après rechargement de la page.
                    </p>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                                <input type="checkbox" name="realtime_enabled" value="1" style="width:auto;" @checked($v('realtime_enabled'))>
                                Activer les notifications instantanées
                            </label>
                        </div>
                        <div class="ukc-field">
                            <label for="realtime_app_id">App ID</label>
                            <input type="text" id="realtime_app_id" name="realtime_app_id" value="{{ $v('realtime_app_id') }}" autocomplete="off">
                            @error('realtime_app_id') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="realtime_key">Clé publique</label>
                            <input type="text" id="realtime_key" name="realtime_key" value="{{ $v('realtime_key') }}" autocomplete="off">
                            <span class="hint">Celle-ci est affichée dans le navigateur : c’est son rôle.</span>
                            @error('realtime_key') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="realtime_secret">Clé secrète</label>
                            <input type="password" id="realtime_secret" name="realtime_secret" value="" autocomplete="new-password" placeholder="{{ filled(config('broadcasting.connections.pusher.secret')) ? 'Clé enregistrée - laisser vide pour conserver' : 'Clé secrète du service' }}">
                            <span class="hint">Enregistrée chiffrée, jamais réaffichée et jamais envoyée au navigateur.</span>
                            @error('realtime_secret') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="realtime_cluster">Cluster</label>
                            <input type="text" id="realtime_cluster" name="realtime_cluster" value="{{ $v('realtime_cluster') }}" autocomplete="off" placeholder="eu, mt1…">
                            @error('realtime_cluster') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="realtime_host">Hôte du service</label>
                            <input type="text" id="realtime_host" name="realtime_host" value="{{ $v('realtime_host') }}" autocomplete="off" placeholder="exemple.piesocket.com">
                            <span class="hint">Un nom de domaine SEUL, sans « https:// » ni chemin.</span>
                            @error('realtime_host') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="realtime_port">Port</label>
                            <input type="number" id="realtime_port" name="realtime_port" value="{{ $v('realtime_port') }}" min="1" max="65535">
                            @error('realtime_port') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="realtime_scheme">Schéma</label>
                            <select id="realtime_scheme" name="realtime_scheme">
                                <option value="https" @selected($v('realtime_scheme') !== 'http')>https (wss)</option>
                                <option value="http" @selected($v('realtime_scheme') === 'http')>http (ws)</option>
                            </select>
                            <span class="hint">Le chiffrement du WebSocket suit ce choix.</span>
                        </div>
                        <div class="ukc-field full">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                                <input type="checkbox" name="realtime_secret_remove" value="1" style="width:auto;">
                                Supprimer la clé secrète enregistrée
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ⚠️ Masqué sur l'onglet des agents : ce bouton enregistre les RÉGLAGES,
                 pas les agents, qui ont le leur. Les deux côte à côte faisaient
                 cliquer sur le mauvais. --}}
            <div class="ukc-form-actions" style="border-top:none;padding-top:0;margin-top:4px;"
                 x-show="tab !== 'agents-ia'">
                <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer les réglages</button>
            </div>
        </form>

        {{-- Agents IA : septième onglet, mais formulaire DISTINCT (il s'enregistre
             en PUT sur sa propre route) - d'où sa place hors du formulaire
             ci-dessus, deux formulaires imbriqués étant impossibles. --}}
        <div class="ukc-panel" :class="{ active: tab==='agents-ia' }" x-data="agentsEditor(@js($agents), @js(\App\Support\AiAgents::galleryForJs()))">
            @include('admin.settings._agents')
        </div>
    </div>
</x-layouts.admin>
