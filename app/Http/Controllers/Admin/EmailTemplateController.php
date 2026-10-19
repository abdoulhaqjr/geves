<?php

namespace App\Http\Controllers\Admin;

use App\Actions\QueueEmailCampaign;
use App\Http\Controllers\Controller;
use App\Mail\CampaignMail;
use App\Support\EmailTemplates;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Événement des modèles d'e-mails (objet / corps / actif) avec balises de fusion.
 * Créés, édités et supprimés via les modales de la page « E-mails - Modèles »
 * (contrôleur + Blade, sans Livewire). Stockés dans le réglage `email_templates`
 * (surcharges des modèles intégrés + modèles personnalisés).
 */
class EmailTemplateController extends Controller
{
    /**
     * Aperçu d'un modèle : le message tel que le destinataire le recevra.
     *
     * ⚠️⚠️ **C'EST LE VRAI GABARIT QUI EST RENDU**, pas une imitation : même
     * mailable (`CampaignMail`), même thème de marque, donc bandeau, bouton et
     * pied compris. L'aperçu montrait l'objet et le corps **en texte brut** - on
     * y relisait sa prose, on n'y voyait ni la mise en page, ni ce que les
     * balises de fusion allaient donner.
     *
     * ⚠️ **Et la substitution est celle de l'ENVOI** (`QueueEmailCampaign::fill`,
     * sur un participant d'exemple non enregistré) : une seconde table de valeurs
     * propre à l'aperçu finirait par promettre autre chose que ce qui part. Une
     * balise que l'envoi laisse vide se voit donc vide ici - c'est l'information
     * la plus utile de cet écran.
     */
    public function preview(Request $request, QueueEmailCampaign $campagne): Response
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        $exemple = EmailTemplates::sampleParticipant();
        $remplir = fn (string $texte): string => $campagne->fill($texte, $exemple);

        $mail = new CampaignMail(
            $remplir(trim((string) ($data['subject'] ?? ''))),
            $remplir(trim((string) ($data['body'] ?? ''))),
        );

        // ⚠️ Le corps est posé par `{{ }}` dans le gabarit, donc échappé : ce que
        // l'admin tape reste du texte, y compris une balise HTML.
        return response((string) $mail->render())
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'templates.*.subject' => ['nullable', 'string', 'max:200'],
            'templates.*.heading' => ['nullable', 'string', 'max:200'],
            'templates.*.intro' => ['nullable', 'string', 'max:2000'],
            'templates.*.body' => ['nullable', 'string', 'max:5000'],
        ]);

        $existing = (array) Settings::get('email_templates', []);
        $payload = [];
        foreach ((array) $request->input('templates', []) as $key => $vals) {
            if (! EmailTemplates::isValid((string) $key)) {
                continue;
            }
            $payload[$key] = array_merge($existing[$key] ?? [], [
                'subject' => trim((string) ($vals['subject'] ?? '')),
                'heading' => trim((string) ($vals['heading'] ?? '')),
                'intro' => trim((string) ($vals['intro'] ?? '')),
                'body' => trim((string) ($vals['body'] ?? '')),
                'active' => array_key_exists('active', (array) $vals) ? (bool) $vals['active'] : true,
            ]);
        }

        // Fusion : permet d'éditer un seul modèle (via modale) sans écraser les autres.
        Settings::set('email_templates', array_merge($existing, $payload));

        return back()->with('ukc_status', 'Modèle d’e-mail enregistré.');
    }

    /** Crée un modèle personnalisé (bouton « Nouveau modèle » du mockup). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'trigger' => ['nullable', 'string', 'in:'.implode(',', EmailTemplates::TRIGGER_OPTIONS)],
            'subject' => ['nullable', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:5000'],
            'active' => ['nullable', 'boolean'],
        ]);

        $existing = (array) Settings::get('email_templates', []);

        // Clé unique dérivée du nom (préfixe « custom_ » pour éviter les collisions).
        $base = 'custom_'.(Str::slug($data['label'], '_') ?: 'modele');
        $key = $base;
        $i = 2;
        while (array_key_exists($key, $existing) || array_key_exists($key, EmailTemplates::TEMPLATES)) {
            $key = $base.'_'.$i++;
        }

        $existing[$key] = [
            'label' => trim($data['label']),
            'trigger' => $data['trigger'] ?? EmailTemplates::TRIGGER_OPTIONS[0],
            'subject' => trim((string) ($data['subject'] ?? '')),
            'heading' => '',
            'intro' => '',
            'body' => trim((string) ($data['body'] ?? '')),
            'active' => $request->boolean('active'),
        ];

        Settings::set('email_templates', $existing);

        return back()->with('ukc_status', 'Nouveau modèle créé.');
    }

    /** Supprime un modèle personnalisé (les modèles intégrés ne sont pas supprimables). */
    public function destroy(string $key): RedirectResponse
    {
        if (! EmailTemplates::isCustom($key)) {
            return back()->with('ukc_error', 'Ce modèle intégré ne peut pas être supprimé.');
        }

        $existing = (array) Settings::get('email_templates', []);
        unset($existing[$key]);
        Settings::set('email_templates', $existing);

        return back()->with('ukc_status', 'Modèle supprimé.');
    }
}
