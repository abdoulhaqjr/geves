<?php

namespace App\Livewire\Admin\Settings;

use App\Support\Settings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Réglages du back-office : expéditeur, SMTP (avec e-mail de test) et paiement
 * (passerelle par défaut + identifiants). Écrits dans la table settings et
 * appliqués à la config au boot (voir AppServiceProvider).
 */
#[Title('Réglages')]
class Index extends Component
{
    // Général
    public string $from_name = '';

    public string $from_address = '';

    public string $currency = 'XOF';

    // SMTP
    public string $mail_host = '';

    public string $mail_port = '587';

    public string $mail_encryption = 'tls';

    public string $mail_username = '';

    public string $mail_password = '';

    public string $test_email = '';

    // Paiement
    public string $payments_default = 'manual';

    public string $cashpay_base_url = '';

    public string $cashpay_username = '';

    public string $cashpay_password = '';

    public string $cashpay_client_secret = '';

    public string $cashpay_api_key = '';

    public string $fedapay_secret = '';

    public string $fedapay_mode = 'test';

    public string $fedapay_webhook_secret = '';

    // Intelligence artificielle
    public string $ai_default = 'claude';

    public string $ai_openai_key = '';

    public string $ai_openai_model = '';

    public string $ai_anthropic_key = '';

    public string $ai_anthropic_model = '';

    public string $ai_gemini_key = '';

    public string $ai_gemini_model = '';

    public string $ai_grok_key = '';

    public string $ai_grok_model = '';

    // Visio (LiveKit)
    public string $meeting_default = 'livekit';

    public string $livekit_url = '';

    public string $livekit_api_key = '';

    public string $livekit_api_secret = '';

    public string $livekit_meet_url = '';

    // Visio — enregistrement (Egress → stockage S3)
    public string $livekit_egress_s3_bucket = '';

    public string $livekit_egress_s3_region = '';

    public string $livekit_egress_s3_access_key = '';

    public string $livekit_egress_s3_secret = '';

    public string $livekit_egress_s3_endpoint = '';

    // Interconnexion
    public bool $hal_enabled = false;

    public function mount(): void
    {
        $g = fn (string $k, $def = '') => (string) Settings::get($k, $def);

        $this->from_name = $g('mail.from_name', (string) config('mail.from.name'));
        $this->from_address = $g('mail.from_address', (string) config('mail.from.address'));
        $this->currency = $g('payments.currency', (string) config('payments.currency', 'XOF'));

        $this->mail_host = $g('mail.host');
        $this->mail_port = $g('mail.port', '587');
        $this->mail_encryption = $g('mail.encryption', 'tls');
        $this->mail_username = $g('mail.username');
        $this->mail_password = $g('mail.password');

        $this->payments_default = $g('payments.default', (string) config('payments.default', 'manual'));
        $this->cashpay_base_url = $g('cashpay.base_url', (string) config('services.cashpay.base_url'));
        $this->cashpay_username = $g('cashpay.username');
        $this->cashpay_password = $g('cashpay.password');
        $this->cashpay_client_secret = $g('cashpay.client_secret');
        $this->cashpay_api_key = $g('cashpay.api_key');
        $this->fedapay_secret = $g('fedapay.secret');
        $this->fedapay_mode = $g('fedapay.mode', 'test');
        $this->fedapay_webhook_secret = $g('fedapay.webhook_secret');

        $this->ai_default = $g('ai.default', (string) config('services.ai.default', 'claude'));
        $this->ai_openai_key = $g('ai.openai_key');
        $this->ai_openai_model = $g('ai.openai_model', (string) config('services.openai.model'));
        $this->ai_anthropic_key = $g('ai.anthropic_key');
        $this->ai_anthropic_model = $g('ai.anthropic_model', (string) config('services.anthropic.model'));
        $this->ai_gemini_key = $g('ai.gemini_key');
        $this->ai_gemini_model = $g('ai.gemini_model', (string) config('services.gemini.model'));
        $this->ai_grok_key = $g('ai.grok_key');
        $this->ai_grok_model = $g('ai.grok_model', (string) config('services.grok.model'));

        $this->meeting_default = $g('meetings.default', (string) config('meetings.default', 'livekit'));
        $this->livekit_url = $g('livekit.url', (string) config('meetings.livekit.url'));
        $this->livekit_api_key = $g('livekit.api_key', (string) config('meetings.livekit.api_key'));
        $this->livekit_api_secret = $g('livekit.api_secret', (string) config('meetings.livekit.api_secret'));
        $this->livekit_meet_url = $g('livekit.meet_url', (string) config('meetings.livekit.meet_url'));

        $this->livekit_egress_s3_bucket = $g('livekit_egress.s3_bucket', (string) config('meetings.livekit.egress.s3_bucket'));
        $this->livekit_egress_s3_region = $g('livekit_egress.s3_region', (string) config('meetings.livekit.egress.s3_region'));
        $this->livekit_egress_s3_access_key = $g('livekit_egress.s3_access_key', (string) config('meetings.livekit.egress.s3_access_key'));
        $this->livekit_egress_s3_secret = $g('livekit_egress.s3_secret', (string) config('meetings.livekit.egress.s3_secret'));
        $this->livekit_egress_s3_endpoint = $g('livekit_egress.s3_endpoint', (string) config('meetings.livekit.egress.s3_endpoint'));

        $this->hal_enabled = (bool) Settings::get('hal.enabled', (bool) config('services.hal.enabled', false));
    }

    public function save(): void
    {
        try {
            $this->validate([
                'from_address' => ['nullable', 'email'],
                'mail_port' => ['nullable', 'integer'],
                'payments_default' => ['required', 'in:manual,cashpay,fedapay'],
                'fedapay_mode' => ['required', 'in:test,live'],
                'ai_default' => ['required', 'in:openai,claude,gemini,grok'],
                'test_email' => ['nullable', 'email'],
                'meeting_default' => ['required', 'in:livekit,jitsi'],
                'livekit_meet_url' => ['nullable', 'url'],
                'livekit_egress_s3_endpoint' => ['nullable', 'url'],
            ]);
        } catch (ValidationException $e) {
            // Bascule l'UI vers l'onglet qui contient la première erreur (masquée sinon).
            $this->dispatch('settings-invalid', tab: $this->tabForField((string) array_key_first($e->errors())));

            throw $e;
        }

        Settings::setMany([
            'mail.from_name' => $this->from_name,
            'mail.from_address' => $this->from_address,
            'payments.currency' => $this->currency,
            'mail.host' => $this->mail_host,
            'mail.port' => $this->mail_port,
            'mail.encryption' => $this->mail_encryption,
            'mail.username' => $this->mail_username,
            'mail.password' => $this->mail_password,
            'payments.default' => $this->payments_default,
            'cashpay.base_url' => $this->cashpay_base_url,
            'cashpay.username' => $this->cashpay_username,
            'cashpay.password' => $this->cashpay_password,
            'cashpay.client_secret' => $this->cashpay_client_secret,
            'cashpay.api_key' => $this->cashpay_api_key,
            'fedapay.secret' => $this->fedapay_secret,
            'fedapay.mode' => $this->fedapay_mode,
            'fedapay.webhook_secret' => $this->fedapay_webhook_secret,
            'ai.default' => $this->ai_default,
            'ai.openai_key' => $this->ai_openai_key,
            'ai.openai_model' => $this->ai_openai_model,
            'ai.anthropic_key' => $this->ai_anthropic_key,
            'ai.anthropic_model' => $this->ai_anthropic_model,
            'ai.gemini_key' => $this->ai_gemini_key,
            'ai.gemini_model' => $this->ai_gemini_model,
            'ai.grok_key' => $this->ai_grok_key,
            'ai.grok_model' => $this->ai_grok_model,
            'meetings.default' => $this->meeting_default,
            'livekit.url' => $this->livekit_url,
            'livekit.api_key' => $this->livekit_api_key,
            'livekit.api_secret' => $this->livekit_api_secret,
            'livekit.meet_url' => $this->livekit_meet_url,
            'livekit_egress.s3_bucket' => $this->livekit_egress_s3_bucket,
            'livekit_egress.s3_region' => $this->livekit_egress_s3_region,
            'livekit_egress.s3_access_key' => $this->livekit_egress_s3_access_key,
            'livekit_egress.s3_secret' => $this->livekit_egress_s3_secret,
            'livekit_egress.s3_endpoint' => $this->livekit_egress_s3_endpoint,
            'hal.enabled' => $this->hal_enabled,
        ]);

        session()->flash('ukc_status', 'Réglages enregistrés.');
    }

    /** Associe un champ de formulaire à l'onglet qui le contient. */
    protected function tabForField(string $field): string
    {
        return match (true) {
            str_starts_with($field, 'payments_') || str_starts_with($field, 'cashpay_') || str_starts_with($field, 'fedapay_') => 'payments',
            str_starts_with($field, 'mail_') || $field === 'test_email' => 'email',
            str_starts_with($field, 'ai_') => 'ai',
            str_starts_with($field, 'meeting_') || str_starts_with($field, 'livekit_') => 'meetings',
            $field === 'hal_enabled' => 'integrations',
            default => 'general',
        };
    }

    public function sendTest(): void
    {
        $this->validate(['test_email' => ['required', 'email']]);

        // Applique les valeurs SMTP du formulaire pour l'envoi de test.
        if (filled($this->mail_host)) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $this->mail_host);
            Config::set('mail.mailers.smtp.port', (int) $this->mail_port);
            Config::set('mail.mailers.smtp.username', $this->mail_username);
            Config::set('mail.mailers.smtp.password', $this->mail_password);
            Config::set('mail.mailers.smtp.encryption', $this->mail_encryption ?: null);
        }
        Config::set('mail.from.name', $this->from_name ?: config('mail.from.name'));
        Config::set('mail.from.address', $this->from_address ?: config('mail.from.address'));

        try {
            Mail::raw('Ceci est un e-mail de test envoyé depuis GESCO.', function ($m) {
                $m->to($this->test_email)->subject('Test SMTP — GESCO');
            });
            session()->flash('ukc_status', 'E-mail de test envoyé à '.$this->test_email.'.');
        } catch (\Throwable $e) {
            session()->flash('ukc_error', 'Échec de l’envoi : '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.index')
            ->layout('components.layouts.admin', ['title' => 'Réglages']);
    }
}
