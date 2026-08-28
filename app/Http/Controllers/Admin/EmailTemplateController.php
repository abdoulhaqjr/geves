<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\EmailTemplates;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Édition des modèles d'e-mails (sujet / titre / intro) avec placeholders
 * (contrôleur + Blade, sans Livewire). Les valeurs vides retombent sur les
 * défauts (EmailTemplates). Stockées dans le réglage `email_templates`. Le
 * bouton « Défaut » réinitialise un modèle côté client (Alpine).
 */
class EmailTemplateController extends Controller
{
    public function edit(): View
    {
        $initial = [];
        $defaults = [];
        foreach (EmailTemplates::TEMPLATES as $key => $t) {
            $initial[$key] = EmailTemplates::raw($key);
            $defaults[$key] = ['subject' => $t['subject'], 'heading' => $t['heading'], 'intro' => $t['intro']];
        }

        return view('admin.emails.templates', [
            'labels' => array_map(fn ($t) => $t['label'], EmailTemplates::TEMPLATES),
            'placeholders' => EmailTemplates::PLACEHOLDERS,
            'initial' => $initial,
            'defaults' => $defaults,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'templates.*.subject' => ['nullable', 'string', 'max:200'],
            'templates.*.heading' => ['nullable', 'string', 'max:200'],
            'templates.*.intro' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = [];
        foreach ((array) $request->input('templates', []) as $key => $vals) {
            if (! EmailTemplates::isValid((string) $key)) {
                continue;
            }
            $payload[$key] = [
                'subject' => trim((string) ($vals['subject'] ?? '')),
                'heading' => trim((string) ($vals['heading'] ?? '')),
                'intro' => trim((string) ($vals['intro'] ?? '')),
            ];
        }

        Settings::set('email_templates', $payload);

        return back()->with('ukc_status', 'Modèles d’e-mails enregistrés.');
    }
}
