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
        'date' => 'Date',
        'checkbox' => 'Case à cocher',
    ];

    /** Règles & limites par défaut du formulaire (onglet « Règles » de l'éditeur). */
    public const RULE_DEFAULTS = [
        'word_min' => 0,       // résumé : minimum de MOTS (0 = pas de minimum)
        'word_limit' => 300,   // résumé : maximum de MOTS
        'keywords_min' => 0,   // mots-clés : minimum (0 = pas de minimum)
        'keywords_max' => 6,   // mots-clés : maximum
        'max_authors' => 8,
        'file_max_mb' => 5,
        'file_formats' => 'pdf, docx',
        'require_account' => true,
        'no_duplicate' => true,
        'live_counter' => true,
        'captcha' => false,
    ];

    public function __construct(public Edition $edition) {}

    /** Compte les mots d'un texte (balises HTML retirées). Utilisé pour les limites du résumé. */
    public static function wordCount(string $value): int
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');

        return $text === '' ? 0 : count(preg_split('/\s+/', $text));
    }

    /**
     * Règles & limites du formulaire (fusionnées avec les valeurs par défaut).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(self::RULE_DEFAULTS, (array) ($this->raw()['rules'] ?? []));
    }

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

    /** Résumé : minimum de MOTS (les limites du résumé sont en mots, pas en caractères). */
    public function abstractMin(): int
    {
        return max(0, (int) $this->rules()['word_min']);
    }

    /** Résumé : maximum de MOTS. */
    public function abstractMax(): int
    {
        return max($this->abstractMin() + 1, (int) $this->rules()['word_limit']);
    }

    /** Mots-clés : nombre minimum. */
    public function keywordsMin(): int
    {
        return max(0, (int) $this->rules()['keywords_min']);
    }

    /** Mots-clés : nombre maximum. */
    public function keywordsMax(): int
    {
        return max($this->keywordsMin(), (int) $this->rules()['keywords_max']);
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
