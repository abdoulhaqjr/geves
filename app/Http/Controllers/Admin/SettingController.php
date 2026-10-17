<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Providers\AppServiceProvider;
use App\Support\AiAgents;
use App\Support\Branding;
use App\Support\EventArtwork;
use App\Support\ParticipantTypes;
use App\Support\Settings;
use App\Support\SiteBanners;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Réglages du back-office (contrôleur + Blade) : expéditeur, SMTP (avec e-mail
 * de test), paiement, IA, visio (LiveKit + Egress), intégrations (HAL, SMS).
 * Écrits dans la table settings et appliqués à la config au boot
 * (AppServiceProvider). Onglets pilotés par Alpine ; en cas d'erreur de
 * validation, l'onglet fautif est réactivé via un flash `settings_tab`.
 */
class SettingController extends Controller
{
    /**
     * Onglets de la page, dans l'ordre d'affichage. Source unique : la barre de
     * navigation les parcourt, et `?tab=` est validé contre cette liste.
     *
     * ⚠️ « Agents IA » est un onglet mais garde **son propre formulaire** (PUT
     * sur `admin.settings.agents.update`) : il vit hors du formulaire général.
     */
    public const TAB_LABELS = [
        'general' => 'Général',
        'banners' => 'Bannières',
        'payments' => 'Paiements',
        'email' => 'E-mail',
        'security' => 'Sécurité',
        'ai' => 'Intelligence artificielle',
        'agents-ia' => 'Agents IA',
        'meetings' => 'Visio',
        'integrations' => 'Intégrations',
    ];

    /** @var list<string> */
    public const TABS = ['general', 'banners', 'payments', 'email', 'security', 'ai', 'agents-ia', 'meetings', 'integrations'];

    /** Correspondance champ de formulaire → clé de réglage. */
    private const MAP = [
        'app_name' => 'app.name',
        'participant_types' => 'participant_types',
        'from_name' => 'mail.from_name',
        'from_address' => 'mail.from_address',
        'currency' => 'payments.currency',
        'app_url' => 'app.url',
        'mail_mailer' => 'mail.mailer',
        'mail_host' => 'mail.host',
        'mail_port' => 'mail.port',
        'mail_encryption' => 'mail.encryption',
        'mail_username' => 'mail.username',
        'mail_password' => 'mail.password',
        'payments_default' => 'payments.default',
        'fedapay_secret' => 'fedapay.secret',
        'fedapay_mode' => 'fedapay.mode',
        'fedapay_webhook_secret' => 'fedapay.webhook_secret',
        'turnstile_site_key' => 'turnstile.site_key',
        'ai_default' => 'ai.default',
        'ai_openai_key' => 'ai.openai_key',
        'ai_openai_model' => 'ai.openai_model',
        'ai_anthropic_key' => 'ai.anthropic_key',
        'ai_anthropic_model' => 'ai.anthropic_model',
        'ai_gemini_key' => 'ai.gemini_key',
        'ai_gemini_model' => 'ai.gemini_model',
        'ai_grok_key' => 'ai.grok_key',
        'ai_grok_model' => 'ai.grok_model',
        'meeting_default' => 'meetings.default',
        'meetings_backgrounds' => 'meetings.backgrounds',
        'livekit_url' => 'livekit.url',
        'livekit_api_key' => 'livekit.api_key',
        'livekit_api_secret' => 'livekit.api_secret',
        'livekit_meet_url' => 'livekit.meet_url',
        'livekit_egress_s3_bucket' => 'livekit_egress.s3_bucket',
        'livekit_egress_s3_region' => 'livekit_egress.s3_region',
        'livekit_egress_s3_access_key' => 'livekit_egress.s3_access_key',
        'livekit_egress_s3_secret' => 'livekit_egress.s3_secret',
        'livekit_egress_s3_endpoint' => 'livekit_egress.s3_endpoint',
        'sms_default' => 'sms.default',
        'sms_http_url' => 'sms.http_url',
        'sms_http_token' => 'sms.http_token',
        'sms_http_sender' => 'sms.http_sender',
        'site_facebook' => 'site.facebook',
        'site_x' => 'site.x',
        'site_youtube' => 'site.youtube',
        'site_linkedin' => 'site.linkedin',
        'plagiarism_provider' => 'plagiarism.provider',
        'plagiarism_prepostseo_key' => 'plagiarism.prepostseo_key',
        'orcid_environment' => 'orcid.environment',
        'orcid_client_id' => 'orcid.client_id',
        'orcid_client_secret' => 'orcid.client_secret',
    ];

    public function edit(): View
    {
        $g = fn (string $k, $def = '') => (string) Settings::get($k, $def);

        $values = [
            'app_name' => $g('app.name', (string) config('app.name')),
            // Grades de participant : liste effective, un par ligne.
            'participant_types' => implode('
', ParticipantTypes::all()),
            'from_name' => $g('mail.from_name', (string) config('mail.from.name')),
            'from_address' => $g('mail.from_address', (string) config('mail.from.address')),
            'currency' => $g('payments.currency', (string) config('payments.currency', 'XOF')),
            'app_url' => $g('app.url', (string) config('app.url')),
            'mail_mailer' => $g('mail.mailer', (string) config('mail.default', 'log')),
            'mail_host' => $g('mail.host'),
            'mail_port' => $g('mail.port', '587'),
            'mail_encryption' => $g('mail.encryption', 'tls'),
            'mail_username' => $g('mail.username'),
            'mail_password' => $g('mail.password'),
            'payments_default' => $g('payments.default', (string) config('payments.default', 'manual')),
            'fedapay_secret' => $g('fedapay.secret'),
            'fedapay_mode' => $g('fedapay.mode', 'test'),
            'fedapay_card' => (bool) Settings::get('fedapay.card', (bool) config('services.fedapay.card', true)),
            'fedapay_webhook_secret' => $g('fedapay.webhook_secret'),
            'turnstile_site_key' => $g('turnstile.site_key', (string) config('services.turnstile.site_key')),
            'ai_default' => $g('ai.default', (string) config('services.ai.default', 'claude')),
            'ai_openai_key' => $g('ai.openai_key'),
            'ai_openai_model' => $g('ai.openai_model', (string) config('services.openai.model')),
            'ai_anthropic_key' => $g('ai.anthropic_key'),
            'ai_anthropic_model' => $g('ai.anthropic_model', (string) config('services.anthropic.model')),
            'ai_gemini_key' => $g('ai.gemini_key'),
            'ai_gemini_model' => $g('ai.gemini_model', (string) config('services.gemini.model')),
            'ai_grok_key' => $g('ai.grok_key'),
            'ai_grok_model' => $g('ai.grok_model', (string) config('services.grok.model')),
            'meeting_default' => $g('meetings.default', (string) config('meetings.default', 'livekit')),
            'meetings_backgrounds' => $g('meetings.backgrounds'),
            'livekit_url' => $g('livekit.url', (string) config('meetings.livekit.url')),
            'livekit_api_key' => $g('livekit.api_key', (string) config('meetings.livekit.api_key')),
            'livekit_api_secret' => $g('livekit.api_secret', (string) config('meetings.livekit.api_secret')),
            'livekit_meet_url' => $g('livekit.meet_url', (string) config('meetings.livekit.meet_url')),
            'livekit_egress_s3_bucket' => $g('livekit_egress.s3_bucket', (string) config('meetings.livekit.egress.s3_bucket')),
            'livekit_egress_s3_region' => $g('livekit_egress.s3_region', (string) config('meetings.livekit.egress.s3_region')),
            'livekit_egress_s3_access_key' => $g('livekit_egress.s3_access_key', (string) config('meetings.livekit.egress.s3_access_key')),
            'livekit_egress_s3_secret' => $g('livekit_egress.s3_secret', (string) config('meetings.livekit.egress.s3_secret')),
            'livekit_egress_s3_endpoint' => $g('livekit_egress.s3_endpoint', (string) config('meetings.livekit.egress.s3_endpoint')),
            'hal_enabled' => (bool) Settings::get('hal.enabled', (bool) config('services.hal.enabled', false)),
            'sms_default' => $g('sms.default', (string) config('sms.default', 'log')),
            'sms_http_url' => $g('sms.http_url', (string) config('sms.http.url')),
            'sms_http_token' => $g('sms.http_token', (string) config('sms.http.token')),
            'sms_http_sender' => $g('sms.http_sender', (string) config('sms.http.sender', 'UKColloques')),
            'site_facebook' => $g('site.facebook'),
            'site_x' => $g('site.x'),
            'site_youtube' => $g('site.youtube'),
            'site_linkedin' => $g('site.linkedin'),
            'plagiarism_provider' => $g('plagiarism.provider', 'none'),
            'plagiarism_prepostseo_key' => $g('plagiarism.prepostseo_key'),
            'orcid_environment' => $g('orcid.environment', 'sandbox'),
            'orcid_client_id' => $g('orcid.client_id'),
            'orcid_client_secret' => $g('orcid.client_secret'),
        ];

        // Les agents IA sont un onglet de cette page : leur éditeur a besoin
        // de la liste, même quand un autre onglet est ouvert (tout est rendu).
        return view('admin.settings.index', [
            'values' => $values,
            'agents' => AiAgents::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'from_address' => ['nullable', 'email'],
            'app_url' => ['nullable', 'url'],
            'mail_mailer' => ['nullable', 'in:log,smtp'],
            'mail_port' => ['nullable', 'integer'],
            'payments_default' => ['required', 'in:manual,fedapay'],
            'fedapay_mode' => ['required', 'in:test,live'],
            'ai_default' => ['required', 'in:openai,claude,gemini,grok'],
            'meeting_default' => ['required', 'in:livekit,jitsi'],
            'livekit_meet_url' => ['nullable', 'url'],
            'livekit_egress_s3_endpoint' => ['nullable', 'url'],
            'sms_default' => ['required', 'in:log,http'],
            'sms_http_url' => ['nullable', 'url'],
            'plagiarism_provider' => ['nullable', 'in:none,prepostseo'],
            'orcid_environment' => ['nullable', 'in:sandbox,production'],
            'turnstile_site_key' => ['nullable', 'string', 'max:255'],
            'turnstile_secret_key' => ['nullable', 'string', 'max:255'],
            // Deux formes (large, compacte), chacune avec sa version pour fonds
            // sombres - facultative : à défaut, le logo clair est blanchi en CSS.
            'app_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'app_logo_white' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'app_logo_mark' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'app_logo_mark_white' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            // ⚠️ Pas la limite des logos : un bandeau est une PHOTO pleine
            // largeur, 1 Mo la refuserait presque toujours. Et pas de SVG : ces
            // images sont recadrées en `cover`, un dessin vectoriel n'a rien à
            // y faire. Le plafond suit celui du serveur, parce qu'une règle
            // plus généreuse que `upload_max_filesize` ne sert à rien : PHP
            // coupe la requête avant la validation et l'admin reçoit une page
            // vide au lieu d'un message.
            SiteBanners::HOME => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,avif', 'max:'.self::bannerMaxKb()],
            SiteBanners::EVENT_DEFAULT => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,avif', 'max:'.self::bannerMaxKb()],
        ]);

        if ($validator->fails()) {
            $tab = $this->tabForField((string) array_key_first($validator->errors()->toArray()));

            return back()->withInput($request->except('turnstile_secret_key'))
                ->withErrors($validator)->with('settings_tab', $tab);
        }

        $payload = [];
        foreach (self::MAP as $field => $key) {
            $payload[$key] = (string) $request->input($field, '');
        }
        $payload['hal.enabled'] = $request->boolean('hal_enabled');
        $payload['fedapay.card'] = $request->boolean('fedapay_card');

        // Tolère le collage d'une ligne d'.env entière (« LIVEKIT_API_KEY=xxx »)
        // dans un champ d'identifiant : on ne conserve que la valeur.
        foreach (['livekit.url', 'livekit.api_key', 'livekit.api_secret', 'livekit.meet_url'] as $key) {
            if (isset($payload[$key])) {
                $payload[$key] = AppServiceProvider::stripEnvPrefix($payload[$key]);
            }
        }

        Settings::setMany($payload);
        if ($request->boolean('turnstile_secret_key_remove')) {
            Settings::set('turnstile.secret_key', '');
        } elseif ($request->filled('turnstile_secret_key')) {
            Settings::set('turnstile.secret_key', Crypt::encryptString(trim((string) $request->input('turnstile_secret_key'))));
        }
        $this->saveLogos($request);
        $toucheBanniere = $this->saveBanners($request);

        // ⚠️ On revient sur l'onglet des bannières quand c'est lui qu'on vient
        // d'utiliser : renvoyer vers « Général » après un téléversement, c'est
        // priver l'admin du seul retour qui compte, l'aperçu de ce qu'il vient
        // de poser.
        return back()
            ->with('ukc_status', 'Réglages enregistrés.')
            ->with('settings_tab', $toucheBanniere ? 'banners' : 'general');
    }

    /**
     * Logos de la plateforme (deux formes x deux fonds) et remise à zéro.
     *
     * ⚠️ On remplace le fichier précédent plutôt que d'empiler : un logo n'a pas
     * d'historique, et les anciens resteraient servis publiquement.
     */
    private function saveLogos(Request $request): void
    {
        // La clé du réglage sert aussi de nom de champ : une seule source.
        foreach (Branding::KEYS as $key) {
            $field = $key;
            $previous = (string) (Settings::get($key) ?? '');

            if ($request->boolean($field.'_remove')) {
                $this->forget($previous);
                Settings::set($key, '');

                continue;
            }

            if (! $request->hasFile($field)) {
                continue;
            }

            $path = $request->file($field)->store('branding', 'public');
            $this->forget($previous);
            Settings::set($key, $path);
        }
    }

    /**
     * Bandeaux du site (accueil, repli des événements) et remise à zéro.
     *
     * Rend **vrai** si l'admin a touché à l'un des deux, pour que la page
     * revienne sur l'onglet « Bannières » plutôt que sur « Général ».
     *
     * ⚠️ Même règle que pour les logos : on remplace le fichier précédent au
     * lieu d'empiler. Un bandeau n'a pas d'historique, et les anciens
     * resteraient servis publiquement.
     */
    private function saveBanners(Request $request): bool
    {
        $touche = false;

        // La clé du réglage sert aussi de nom de champ : une seule source.
        foreach (SiteBanners::KEYS as $key) {
            $previous = (string) (Settings::get($key) ?? '');

            if ($request->boolean($key.'_remove')) {
                $this->forget($previous);
                Settings::set($key, '');
                $touche = true;

                continue;
            }

            if (! $request->hasFile($key)) {
                continue;
            }

            $path = $request->file($key)->store(SiteBanners::DIR, 'public');
            $this->forget($previous);
            Settings::set($key, $path);
            $touche = true;
        }

        return $touche;
    }

    /**
     * Plafond de téléversement d'un bandeau, en kilo-octets.
     *
     * 4 Mo suffisent très largement pour une image de 1920px bien compressée ;
     * on n'annonce jamais plus que ce que le serveur accepte réellement.
     */
    public static function bannerMaxKb(): int
    {
        return (int) min(4096, EventArtwork::maxUploadKb());
    }

    /** Supprime un fichier de marque devenu inutile (ignore l'absence). */
    private function forget(string $path): void
    {
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Envoie un e-mail de test avec les paramètres SMTP saisis. */
    public function sendTest(Request $request): RedirectResponse
    {
        $request->validate(['test_email' => ['required', 'email']]);

        // Applique les valeurs SMTP du formulaire pour l'envoi de test.
        if (filled($request->input('mail_host'))) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $request->input('mail_host'));
            Config::set('mail.mailers.smtp.port', (int) $request->input('mail_port'));
            Config::set('mail.mailers.smtp.username', $request->input('mail_username'));
            Config::set('mail.mailers.smtp.password', $request->input('mail_password'));
            Config::set('mail.mailers.smtp.encryption', $request->input('mail_encryption') ?: null);
        }
        Config::set('mail.from.name', $request->input('from_name') ?: config('mail.from.name'));
        Config::set('mail.from.address', $request->input('from_address') ?: config('mail.from.address'));

        $to = (string) $request->input('test_email');

        try {
            Mail::raw('Ceci est un e-mail de test envoyé depuis '.config('app.name').'.', function ($m) use ($to) {
                $m->to($to)->subject('Test SMTP - '.config('app.name'));
            });

            return back()->with('ukc_status', 'E-mail de test envoyé à '.$to.'.')->with('settings_tab', 'email');
        } catch (\Throwable $e) {
            return back()->with('ukc_error', 'Échec de l’envoi : '.$e->getMessage())->with('settings_tab', 'email');
        }
    }

    /** Associe un champ de formulaire à l'onglet qui le contient. */
    private function tabForField(string $field): string
    {
        return match (true) {
            str_starts_with($field, 'hero_') => 'banners',
            str_starts_with($field, 'payments_'), str_starts_with($field, 'fedapay_') => 'payments',
            str_starts_with($field, 'mail_'), $field === 'test_email' => 'email',
            str_starts_with($field, 'turnstile_') => 'security',
            str_starts_with($field, 'ai_') => 'ai',
            str_starts_with($field, 'meeting_'), str_starts_with($field, 'livekit_') => 'meetings',
            $field === 'hal_enabled', str_starts_with($field, 'sms_'), str_starts_with($field, 'plagiarism_'), str_starts_with($field, 'orcid_') => 'integrations',
            default => 'general',
        };
    }
}
