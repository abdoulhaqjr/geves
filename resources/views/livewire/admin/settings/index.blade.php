<div style="max-width:760px;">
    <form wire:submit="save">
        {{-- Général --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="settings" :size="20" /> Général</h2>
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

        {{-- SMTP --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="mail" :size="20" /> Envoi des e-mails (SMTP)</h2>
            <p style="font-size:.88rem;color:var(--ukc-muted);margin-bottom:12px;">
                Laissez l’hôte vide pour utiliser le transport par défaut (log en dev).
            </p>
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

        {{-- Paiement --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="card" :size="20" /> Paiement</h2>
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

        <div class="ukc-form-actions" style="border-top:none;padding-top:0;margin-top:0;">
            <button type="submit" class="ukc-btn ukc-btn--primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Enregistrer les réglages</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </button>
        </div>
    </form>
</div>
