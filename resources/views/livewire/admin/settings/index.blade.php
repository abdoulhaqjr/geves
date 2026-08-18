<div class="admin-page admin-settings" x-data="{ tab: 'general' }"
     @settings-invalid.window="tab = $event.detail.tab">
    <div class="ukc-tabs">
        <button type="button" @click="tab='general'" :class="{ active: tab==='general' }">Général</button>
        <button type="button" @click="tab='payments'" :class="{ active: tab==='payments' }">Paiements</button>
        <button type="button" @click="tab='email'" :class="{ active: tab==='email' }">E-mail</button>
        <button type="button" @click="tab='ai'" :class="{ active: tab==='ai' }">Intelligence artificielle</button>
        <button type="button" @click="tab='meetings'" :class="{ active: tab==='meetings' }">Visio</button>
        <button type="button" @click="tab='integrations'" :class="{ active: tab==='integrations' }">Intégrations</button>
        <a href="{{ route('admin.settings.agents') }}" wire:navigate style="padding:10px 16px;font-weight:700;font-size:13.5px;color:var(--ukc-muted);text-decoration:none;">Agents IA ↗</a>
    </div>

    <form wire:submit="save" style="max-width:820px;">
        {{-- Général --}}
        <div class="ukc-panel" :class="{ active: tab==='general' }">
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="settings" :size="20" /> Général</span></h2>
                <p class="desc">Identité de l’expéditeur des e-mails et devise utilisée pour les frais.</p>
                <div class="ukc-form-grid">
                    <div class="ukc-field">
                        <label for="from_name">Nom de l’expéditeur</label>
                        <input type="text" id="from_name" wire:model="from_name">
                        @error('from_name') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label for="from_address">E-mail de l’expéditeur</label>
                        <input type="email" id="from_address" wire:model="from_address">
                        @error('from_address') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label for="currency">Devise</label>
                        <input type="text" id="currency" wire:model="currency" maxlength="10">
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
                        <select id="payments_default" wire:model="payments_default">
                            <option value="manual">Paiement sur place / virement (manuel)</option>
                            <option value="cashpay">CashPay (SEMOA)</option>
                            <option value="fedapay">FedaPay</option>
                        </select>
                        <span class="hint">Une passerelle n’est proposée que si ses identifiants sont renseignés.</span>
                        @error('payments_default') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <h3 style="font-size:.95rem;margin:16px 0 8px;">CashPay (SEMOA)</h3>
                <div class="ukc-form-grid">
                    <div class="ukc-field full"><label for="cashpay_base_url">URL de base</label><input type="text" id="cashpay_base_url" wire:model="cashpay_base_url"></div>
                    <div class="ukc-field"><label for="cashpay_username">Identifiant</label><input type="text" id="cashpay_username" wire:model="cashpay_username" autocomplete="off"></div>
                    <div class="ukc-field"><label for="cashpay_password">Mot de passe</label><input type="password" id="cashpay_password" wire:model="cashpay_password" autocomplete="new-password"></div>
                    <div class="ukc-field"><label for="cashpay_client_secret">Client secret</label><input type="password" id="cashpay_client_secret" wire:model="cashpay_client_secret" autocomplete="new-password"></div>
                    <div class="ukc-field"><label for="cashpay_api_key">API key (callback)</label><input type="password" id="cashpay_api_key" wire:model="cashpay_api_key" autocomplete="new-password"></div>
                </div>

                <h3 style="font-size:.95rem;margin:16px 0 8px;">FedaPay</h3>
                <div class="ukc-form-grid">
                    <div class="ukc-field"><label for="fedapay_secret">Clé secrète</label><input type="password" id="fedapay_secret" wire:model="fedapay_secret" autocomplete="new-password"></div>
                    <div class="ukc-field"><label for="fedapay_mode">Mode</label>
                        <select id="fedapay_mode" wire:model="fedapay_mode"><option value="test">Sandbox (test)</option><option value="live">Production (live)</option></select>
                        @error('fedapay_mode') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field full"><label for="fedapay_webhook_secret">Secret de webhook</label><input type="password" id="fedapay_webhook_secret" wire:model="fedapay_webhook_secret" autocomplete="new-password"></div>
                </div>
            </div>
        </div>

        {{-- E-mail (SMTP) --}}
        <div class="ukc-panel" :class="{ active: tab==='email' }">
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="mail" :size="20" /> Envoi des e-mails (SMTP)</span></h2>
                <p class="desc">Laissez l’hôte vide pour utiliser le transport par défaut (log en dev).</p>
                <div class="ukc-form-grid">
                    <div class="ukc-field">
                        <label for="mail_host">Hôte</label>
                        <input type="text" id="mail_host" wire:model="mail_host" placeholder="smtp.exemple.tg">
                    </div>
                    <div class="ukc-field">
                        <label for="mail_port">Port</label>
                        <input type="number" id="mail_port" wire:model="mail_port">
                        @error('mail_port') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label for="mail_encryption">Chiffrement</label>
                        <select id="mail_encryption" wire:model="mail_encryption">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="">Aucun</option>
                        </select>
                    </div>
                    <div class="ukc-field">
                        <label for="mail_username">Identifiant</label>
                        <input type="text" id="mail_username" wire:model="mail_username" autocomplete="off">
                    </div>
                    <div class="ukc-field">
                        <label for="mail_password">Mot de passe</label>
                        <input type="password" id="mail_password" wire:model="mail_password" autocomplete="new-password">
                    </div>
                </div>

                <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid var(--ukc-border);">
                    <div class="ukc-field" style="flex:1;min-width:220px;">
                        <label for="test_email">Envoyer un e-mail de test à</label>
                        <input type="email" id="test_email" wire:model="test_email" placeholder="vous@exemple.tg">
                        @error('test_email') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" wire:click="sendTest" wire:loading.attr="disabled" wire:target="sendTest">
                        <span wire:loading.remove wire:target="sendTest">Envoyer le test</span>
                        <span wire:loading wire:target="sendTest">Envoi…</span>
                    </button>
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
                    <select id="ai_default" wire:model="ai_default">
                        <option value="claude">Claude (Anthropic)</option>
                        <option value="openai">OpenAI</option>
                        <option value="gemini">Gemini (Google)</option>
                        <option value="grok">Grok (xAI)</option>
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
                            <input type="password" wire:model="ai_{{ $prov }}_key" autocomplete="new-password" placeholder="Clé API"
                                   style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                            <input type="text" wire:model="ai_{{ $prov }}_model" placeholder="{{ $ph }}"
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
                    <select id="meeting_default" wire:model="meeting_default">
                        <option value="livekit">LiveKit</option>
                        <option value="jitsi">Jitsi (repli, sans config)</option>
                    </select>
                    @error('meeting_default') <span class="error">{{ $message }}</span> @enderror
                </div>

                <h3 style="font-size:.95rem;margin:16px 0 8px;">LiveKit</h3>
                <div class="ukc-form-grid">
                    <div class="ukc-field full">
                        <label for="livekit_url">URL du serveur <span class="hint">(wss://…)</span></label>
                        <input type="text" id="livekit_url" wire:model="livekit_url" placeholder="wss://ton-projet.livekit.cloud">
                    </div>
                    <div class="ukc-field">
                        <label for="livekit_api_key">Clé d’API</label>
                        <input type="password" id="livekit_api_key" wire:model="livekit_api_key" autocomplete="new-password" placeholder="API key">
                    </div>
                    <div class="ukc-field">
                        <label for="livekit_api_secret">Secret d’API</label>
                        <input type="password" id="livekit_api_secret" wire:model="livekit_api_secret" autocomplete="new-password" placeholder="API secret">
                    </div>
                    <div class="ukc-field full">
                        <label for="livekit_meet_url">App LiveKit Meet <span class="hint">(par défaut l’app publique)</span></label>
                        <input type="url" id="livekit_meet_url" wire:model="livekit_meet_url" placeholder="https://meet.livekit.io/custom">
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
                        <input type="text" id="livekit_egress_s3_bucket" wire:model="livekit_egress_s3_bucket" placeholder="gesco-recordings">
                    </div>
                    <div class="ukc-field">
                        <label for="livekit_egress_s3_region">Région</label>
                        <input type="text" id="livekit_egress_s3_region" wire:model="livekit_egress_s3_region" placeholder="eu-west-3">
                    </div>
                    <div class="ukc-field">
                        <label for="livekit_egress_s3_access_key">Access key</label>
                        <input type="password" id="livekit_egress_s3_access_key" wire:model="livekit_egress_s3_access_key" autocomplete="new-password" placeholder="AKIA…">
                    </div>
                    <div class="ukc-field">
                        <label for="livekit_egress_s3_secret">Secret key</label>
                        <input type="password" id="livekit_egress_s3_secret" wire:model="livekit_egress_s3_secret" autocomplete="new-password" placeholder="Secret">
                    </div>
                    <div class="ukc-field full">
                        <label for="livekit_egress_s3_endpoint">Endpoint <span class="hint">(optionnel — S3-compatible : MinIO…)</span></label>
                        <input type="url" id="livekit_egress_s3_endpoint" wire:model="livekit_egress_s3_endpoint" placeholder="https://s3.exemple.com">
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
                    <input type="checkbox" wire:model="hal_enabled"> Activer l’autocomplétion des affiliations (HAL)
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
                    <select id="sms_default" wire:model="sms_default">
                        <option value="log">Journal (développement)</option>
                        <option value="http">Passerelle HTTP (API)</option>
                    </select>
                    @error('sms_default') <span class="error">{{ $message }}</span> @enderror
                </div>

                <h3 style="font-size:.95rem;margin:16px 0 8px;">Passerelle HTTP</h3>
                <div class="ukc-form-grid">
                    <div class="ukc-field full">
                        <label for="sms_http_url">URL de l’API <span class="hint">(POST JSON : to, message, sender)</span></label>
                        <input type="url" id="sms_http_url" wire:model="sms_http_url" placeholder="https://api.passerelle-sms.tg/send">
                        @error('sms_http_url') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label for="sms_http_token">Jeton (Bearer)</label>
                        <input type="password" id="sms_http_token" wire:model="sms_http_token" autocomplete="new-password">
                    </div>
                    <div class="ukc-field">
                        <label for="sms_http_sender">Expéditeur (Sender ID)</label>
                        <input type="text" id="sms_http_sender" wire:model="sms_http_sender" placeholder="UKColloques" maxlength="20">
                    </div>
                </div>
            </div>
        </div>

        <div class="ukc-form-actions" style="border-top:none;padding-top:0;margin-top:4px;">
            <button type="submit" class="ukc-btn ukc-btn--blue" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Enregistrer les réglages</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </button>
        </div>
    </form>
</div>
