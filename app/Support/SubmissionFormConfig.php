<?php

namespace App\Support;

use App\Models\Edition;

/**
 * Configuration du formulaire de soumission d'une édition (paramétrable par
 * l'admin, stockée dans settings.submission_form). Résout les libellés, aides,
 * options requis/activé et les champs personnalisés, avec des valeurs par
 * défaut alignées sur le formulaire historique.
 *
 * Rappel : l'identité (civilité, nom, e-mail, téléphone, pays, affiliation) est
 * portée par le compte (source de vérité), pas par ce formulaire.
 */
class SubmissionFormConfig
{
    /** Champs standard et leurs valeurs par défaut. */
    public const STANDARD_DEFAULTS = [
        'title' => ['label' => 'Titre', 'help' => '', 'required' => true],
        'track' => ['label' => 'Axe thématique', 'help' => '', 'required' => true],
        'type' => ['label' => 'Mode de présentation', 'help' => '', 'enabled' => true, 'required' => true],
        'keywords' => ['label' => 'Mots-clés', 'help' => 'Séparés par des virgules', 'enabled' => true, 'required' => false],
        'abstract' => ['label' => 'Résumé', 'help' => '', 'required' => true, 'min' => 50, 'max' => 8000],
        'file' => ['label' => 'Fichier joint', 'help' => 'PDF/DOC/DOCX, max 8 Mo', 'enabled' => true, 'required' => false],
    ];

    /** Types de champs personnalisés disponibles. */
    public const CUSTOM_TYPES = [
        'text' => 'Texte court',
        'textarea' => 'Texte long',
        'select' => 'Liste déroulante',
        'email' => 'E-mail',
        'number' => 'Nombre',
        'checkbox' => 'Case à cocher',
    ];

    public function __construct(public Edition $edition) {}

    /** @return array<string, mixed> */
    protected function raw(): array
    {
        return $this->edition->settings['submission_form'] ?? [];
    }

    /** @return array<string, mixed> */
    protected function fieldConf(string $key): array
    {
        return $this->raw()['fields'][$key] ?? [];
    }

    public function label(string $key): string
    {
        $v = $this->fieldConf($key)['label'] ?? null;

        return filled($v) ? (string) $v : (string) (self::STANDARD_DEFAULTS[$key]['label'] ?? $key);
    }

    public function help(string $key): string
    {
        $conf = $this->fieldConf($key);

        return array_key_exists('help', $conf)
            ? (string) $conf['help']
            : (string) (self::STANDARD_DEFAULTS[$key]['help'] ?? '');
    }

    public function enabled(string $key): bool
    {
        $v = $this->fieldConf($key)['enabled'] ?? null;

        return $v === null ? (bool) (self::STANDARD_DEFAULTS[$key]['enabled'] ?? true) : (bool) $v;
    }

    public function required(string $key): bool
    {
        $v = $this->fieldConf($key)['required'] ?? null;

        return $v === null ? (bool) (self::STANDARD_DEFAULTS[$key]['required'] ?? true) : (bool) $v;
    }

    public function abstractMin(): int
    {
        return max(0, (int) ($this->fieldConf('abstract')['min'] ?? self::STANDARD_DEFAULTS['abstract']['min']));
    }

    public function abstractMax(): int
    {
        $min = $this->abstractMin();
        $max = (int) ($this->fieldConf('abstract')['max'] ?? self::STANDARD_DEFAULTS['abstract']['max']);

        return max($min + 1, $max);
    }

    /**
     * Champs personnalisés normalisés.
     *
     * @return array<int, array{key:string, label:string, type:string, required:bool, help:string, options:list<string>}>
     */
    public function customFields(): array
    {
        $out = [];
        foreach ($this->raw()['custom'] ?? [] as $i => $field) {
            $label = trim((string) ($field['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $type = in_array($field['type'] ?? '', array_keys(self::CUSTOM_TYPES), true) ? $field['type'] : 'text';
            $options = array_values(array_filter(array_map(
                fn ($o) => trim((string) $o),
                (array) ($field['options'] ?? []),
            ), fn ($o) => $o !== ''));

            $out[] = [
                'key' => (string) ($field['key'] ?? ('custom_'.$i)),
                'label' => $label,
                'type' => $type,
                'required' => (bool) ($field['required'] ?? false),
                'help' => (string) ($field['help'] ?? ''),
                'options' => $options,
            ];
        }

        return $out;
    }
}
