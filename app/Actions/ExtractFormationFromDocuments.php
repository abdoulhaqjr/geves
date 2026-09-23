<?php

namespace App\Actions;

use App\AI\AiManager;
use Illuminate\Support\Carbon;

/**
 * Paramétrage assisté par IA d'une formation : extrait titre/thème/objectif/lieu/
 * salle/frais/dates depuis des documents (flyer, programme…) ou une consigne, et
 * normalise pour pré-remplir le formulaire. N'invente rien.
 */
class ExtractFormationFromDocuments
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @param  array<int, array{mime: string, data: string}>  $files
     * @return array<string, mixed>
     */
    public function handle(array $files): array
    {
        $needsPdf = collect($files)->contains(fn ($f) => str_contains($f['mime'], 'pdf'));

        if (! $this->manager->resolve($needsPdf)) {
            throw new \RuntimeException('Aucun fournisseur d’IA n’est configuré.');
        }

        return $this->normalize($this->manager->extract($this->instruction(), $files, 'deep'));
    }

    protected function instruction(): string
    {
        return <<<'TXT'
        Tu extrais les informations d'une FORMATION (atelier de formation professionnelle)
        à partir des documents fournis (flyer, programme, descriptif…).

        Réponds UNIQUEMENT par un objet JSON valide, sans aucun texte autour. Omets ou laisse
        vide toute information introuvable. N'INVENTE RIEN.

        Schéma attendu :
        {
          "title": string,        // titre / thème principal de la formation
          "theme": string,        // sous-thème éventuel
          "objective": string,    // objectifs pédagogiques
          "location": string,     // lieu
          "room": string,         // salle
          "fee": number,          // frais (0 si gratuite)
          "starts_at": "YYYY-MM-DD HH:MM",
          "ends_at": "YYYY-MM-DD HH:MM"
        }
        TXT;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    protected function normalize(array $raw): array
    {
        $out = [];

        foreach (['title', 'theme', 'objective', 'location', 'room'] as $k) {
            $v = trim((string) ($raw[$k] ?? ''));
            if ($v !== '') {
                $out[$k] = $v;
            }
        }

        if (isset($raw['fee']) && is_numeric($raw['fee'])) {
            $out['fee'] = (float) $raw['fee'];
        }

        foreach (['starts_at', 'ends_at'] as $k) {
            $v = trim((string) ($raw[$k] ?? ''));
            if ($v === '') {
                continue;
            }
            try {
                $out[$k] = Carbon::parse($v)->format('Y-m-d\TH:i');
            } catch (\Throwable) {
                // Date non exploitable : on l'ignore.
            }
        }

        return $out;
    }
}
