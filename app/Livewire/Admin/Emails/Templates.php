<?php

namespace App\Livewire\Admin\Emails;

use App\Support\EmailTemplates;
use App\Support\Settings;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Édition des modèles d'e-mails (sujet / titre / intro) avec placeholders.
 * Les valeurs vides retombent sur les défauts (EmailTemplates). Stockées dans
 * le réglage `email_templates`.
 */
#[Title('Modèles d’e-mails')]
class Templates extends Component
{
    /** @var array<string, array{subject:string, heading:string, intro:string}> */
    public array $templates = [];

    public function mount(): void
    {
        foreach (array_keys(EmailTemplates::TEMPLATES) as $key) {
            $this->templates[$key] = EmailTemplates::raw($key);
        }
    }

    public function resetTemplate(string $key): void
    {
        if (! EmailTemplates::isValid($key)) {
            return;
        }

        $this->templates[$key] = [
            'subject' => EmailTemplates::TEMPLATES[$key]['subject'],
            'heading' => EmailTemplates::TEMPLATES[$key]['heading'],
            'intro' => EmailTemplates::TEMPLATES[$key]['intro'],
        ];
    }

    public function save(): void
    {
        $this->validate([
            'templates.*.subject' => ['nullable', 'string', 'max:200'],
            'templates.*.heading' => ['nullable', 'string', 'max:200'],
            'templates.*.intro' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = [];
        foreach ($this->templates as $key => $vals) {
            if (! EmailTemplates::isValid($key)) {
                continue;
            }
            $payload[$key] = [
                'subject' => trim((string) ($vals['subject'] ?? '')),
                'heading' => trim((string) ($vals['heading'] ?? '')),
                'intro' => trim((string) ($vals['intro'] ?? '')),
            ];
        }

        Settings::set('email_templates', $payload);

        session()->flash('ukc_status', 'Modèles d’e-mails enregistrés.');
    }

    public function render()
    {
        $labels = array_map(fn ($t) => $t['label'], EmailTemplates::TEMPLATES);

        return view('livewire.admin.emails.templates', [
            'labels' => $labels,
            'placeholders' => EmailTemplates::PLACEHOLDERS,
        ])->layout('components.layouts.admin', ['title' => 'Modèles d’e-mails']);
    }
}
