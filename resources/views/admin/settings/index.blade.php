@php($v = fn ($k) => old($k, $values[$k]))
<x-layouts.admin title="Réglages" subtitle="Configuration globale de la plateforme">
    <div class="admin-page admin-settings" x-data="{ tab: '{{ session('settings_tab', 'general') }}' }">
        <div class="ukc-tabs">
            <button type="button" @click="tab='general'" :class="{ active: tab==='general' }">Général</button>
            <button type="button" @click="tab='payments'" :class="{ active: tab==='payments' }">Paiements</button>
            <button type="button" @click="tab='email'" :class="{ active: tab==='email' }">E-mail</button>
            <button type="button" @click="tab='ai'" :class="{ active: tab==='ai' }">Intelligence artificielle</button>
            <button type="button" @click="tab='meetings'" :class="{ active: tab==='meetings' }">Visio</button>
            <button type="button" @click="tab='integrations'" :class="{ active: tab==='integrations' }">Intégrations</button>
            <a href="{{ route('admin.settings.agents') }}" style="padding:10px 16px;font-weight:700;font-size:13.5px;color:var(--ukc-muted);text-decoration:none;">Agents IA ↗</a>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf
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
                        </div>
                        {{-- Deux logos à fournir (large, compact), présentés comme la
                             photo de profil : vignettes d'aperçu, intitulé, aide, puis
                             les actions. Chaque logo est montré sur SES DEUX fonds :
                             la version sombre n'est pas téléversée, elle est blanchie
                             en CSS, et l'admin doit pouvoir vérifier ce que ça donne. --}}
                        <div class="ukc-field full">
                            <label>Logos de la plateforme</label>
                            <span class="hint" style="font-size:12px;color:var(--ukc-muted);margin-bottom:12px;display:block;">
                                Le <strong>logo large</strong> s’affiche là où la place le permet : pied de page, pages de
                                connexion et de création de compte, bandeau des pages d’événement. Le <strong>logo compact</strong>
                                sert à l’en-tête public et aux menus latéraux, y compris réduits. Sur les fonds bleus, le logo
                                est <strong>mis en blanc automatiquement</strong> : un seul fichier à fournir par forme,
                                de préférence sur <strong>fond transparent</strong>. PNG, JPG, WEBP ou SVG, 1 Mo maximum.
                            </span>

                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:14px;">
                                @foreach ([
                                    [
                                        'key' => \App\Support\Branding::WIDE,
                                        'titre' => 'Logo large',
                                        'clair' => \App\Support\Branding::wide(),
                                        'sombre' => \App\Support\Branding::wideWhite(),
                                        'livreClair' => \App\Support\Branding::shipped(\App\Support\Branding::WIDE),
                                        'livreSombre' => \App\Support\Branding::shipped(\App\Support\Branding::WIDE, true),
                                        'aide' => 'Marque complète, nom compris. Rapport conseillé 3:1 environ.',
                                    ],
                                    [
                                        'key' => \App\Support\Branding::MARK,
                                        'titre' => 'Logo compact',
                                        'clair' => \App\Support\Branding::mark(),
                                        'sombre' => \App\Support\Branding::markWhite(),
                                        'livreClair' => \App\Support\Branding::shipped(\App\Support\Branding::MARK),
                                        'livreSombre' => \App\Support\Branding::shipped(\App\Support\Branding::MARK, true),
                                        'aide' => 'Monogramme carré. Sert aussi d’icône d’accueil sur mobile.',
                                    ],
                                ] as $logo)
                                    <div class="ukc-logo-upload" x-data="{
                                            clair: '{{ $logo['clair'] }}',
                                            sombre: '{{ $logo['sombre'] }}',
                                            livreClair: '{{ $logo['livreClair'] }}',
                                            livreSombre: '{{ $logo['livreSombre'] }}',
                                            custom: {{ \App\Support\Branding::url($logo['key']) ? 'true' : 'false' }},
                                            whiten: {{ \App\Support\Branding::whitensOnDark($logo['key']) ? 'true' : 'false' }},
                                            removed: false,
                                            pick(e) {
                                                const f = e.target.files[0]; if (! f) return;
                                                const u = URL.createObjectURL(f);
                                                this.clair = u; this.sombre = u;
                                                this.custom = true; this.whiten = true; this.removed = false;
                                            },
                                            reset() {
                                                this.clair = this.livreClair; this.sombre = this.livreSombre;
                                                this.custom = false; this.whiten = false; this.removed = true;
                                                this.$refs.f.value = '';
                                            }
                                         }">
                                        <div class="shots">
                                            <div class="shot shot--light"><img :src="clair" alt="{{ $logo['titre'] }} sur fond clair"></div>
                                            <div class="shot shot--dark"><img :src="sombre" :class="{ 'logo-auto-white': whiten }" alt="{{ $logo['titre'] }} sur fond sombre"></div>
                                        </div>
                                        <div class="body">
                                            <div class="name">
                                                {{ $logo['titre'] }}
                                                <span class="ukc-pill ukc-pill--info" x-show="custom" x-cloak>Personnalisé</span>
                                                <span class="ukc-pill ukc-pill--muted" x-show="!custom" x-cloak>Logo livré</span>
                                            </div>
                                            <p class="hint">{{ $logo['aide'] }}</p>
                                            <div class="row">
                                                <input type="file" id="{{ $logo['key'] }}" name="{{ $logo['key'] }}" x-ref="f"
                                                       accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="pick($event)">
                                                <label for="{{ $logo['key'] }}" class="ukc-btn ukc-btn--sm" style="cursor:pointer;">
                                                    <x-ukc.icon name="upload" :size="16" /> <span x-text="custom ? 'Remplacer' : 'Téléverser'">Téléverser</span>
                                                </label>
                                                {{-- Supprimer = revenir au logo livré : il n'y a jamais
                                                     « pas de logo », le repli reprend la main. --}}
                                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" x-show="custom" x-cloak @click="reset()">
                                                    Supprimer
                                                </button>
                                            </div>
                                            {{-- Champ caché plutôt qu'une case : la remise à zéro se décide
                                                 par le bouton, qui met aussi les aperçus à jour. --}}
                                            <input type="hidden" name="{{ $logo['key'] }}_remove" :value="removed ? '1' : '0'">
                                            @error($logo['key']) <span class="error">{{ $message }}</span> @enderror
                                        </div>
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
                                et pour les co-auteurs ; c’est aussi elle qu’on rattache aux catégories tarifaires d’une édition.
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

            {{-- Paiement --}}
            <div class="ukc-panel" :class="{ active: tab==='payments' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="card" :size="20" /> Paiement</span></h2>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label for="payments_default">Passerelle par défaut</label>
                            <select id="payments_default" name="payments_default">
                                <option value="manual" @selected($v('payments_default') === 'manual')>Paiement sur place / virement (manuel)</option>
                                <option value="cashpay" @selected($v('payments_default') === 'cashpay')>CashPay (SEMOA)</option>
                                <option value="fedapay" @selected($v('payments_default') === 'fedapay')>FedaPay</option>
                            </select>
                            <span class="hint">Une passerelle n’est proposée que si ses identifiants sont renseignés.</span>
                            @error('payments_default') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <h3 style="font-size:.95rem;margin:16px 0 8px;">CashPay (SEMOA)</h3>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full"><label for="cashpay_base_url">URL de base</label><input type="text" id="cashpay_base_url" name="cashpay_base_url" value="{{ $v('cashpay_base_url') }}"></div>
                        <div class="ukc-field"><label for="cashpay_username">Identifiant</label><input type="text" id="cashpay_username" name="cashpay_username" value="{{ $v('cashpay_username') }}" autocomplete="off"></div>
                        <div class="ukc-field"><label for="cashpay_password">Mot de passe</label><input type="password" id="cashpay_password" name="cashpay_password" value="{{ $v('cashpay_password') }}" autocomplete="new-password"></div>
                        <div class="ukc-field"><label for="cashpay_client_secret">Client secret</label><input type="password" id="cashpay_client_secret" name="cashpay_client_secret" value="{{ $v('cashpay_client_secret') }}" autocomplete="new-password"></div>
                        <div class="ukc-field"><label for="cashpay_api_key">API key (callback)</label><input type="password" id="cashpay_api_key" name="cashpay_api_key" value="{{ $v('cashpay_api_key') }}" autocomplete="new-password"></div>
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

            {{-- Intelligence artificielle --}}
            <div class="ukc-panel" :class="{ active: tab==='ai' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="sparkles" :size="20" /> Intelligence artificielle</span></h2>
                    <p class="desc">
                        Utilisée pour le paramétrage assisté d’une édition et la génération de modèles (badges, attestations).
                        Les clés sont facultatives ; renseignez au moins le fournisseur par défaut.
                    </p>
                    <div class="ukc-field" style="max-width:360px;">
                        <label for="ai_default">Fournisseur par défaut</label>
                        <select id="ai_default" name="ai_default">
                            <option value="claude" @selected($v('ai_default') === 'claude')>Claude (Anthropic)</option>
                            <option value="openai" @selected($v('ai_default') === 'openai')>OpenAI</option>
                            <option value="gemini" @selected($v('ai_default') === 'gemini')>Gemini (Google)</option>
                            <option value="grok" @selected($v('ai_default') === 'grok')>Grok (xAI)</option>
                        </select>
                        <span class="hint">Repli automatique sur un autre fournisseur configuré si nécessaire.</span>
                        @error('ai_default') <span class="error">{{ $message }}</span> @enderror
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
            </div>

            <div class="ukc-form-actions" style="border-top:none;padding-top:0;margin-top:4px;">
                <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer les réglages</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
