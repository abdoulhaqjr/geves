<?php

namespace App\Actions;

use App\AI\AiManager;
use Illuminate\Support\Carbon;

/**
 * Paramétrage assisté par IA : extrait les métadonnées d'un événement depuis des
 * documents (appel à communications, affiche…) et les normalise pour
 * pré-remplir le formulaire. N'invente rien ; seules les valeurs trouvées sont
 * renvoyées.
 */
class ExtractEventFromDocuments
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @param  array<int, array{mime: string, data: string}>  $files
     * @return array<string, mixed>
     */
    public function handle(array $files, ?string $prompt = null): array
    {
        $needsPdf = collect($files)->contains(fn ($f) => str_contains($f['mime'], 'pdf'));

        if (! $this->manager->resolve($needsPdf)) {
            throw new \RuntimeException('Aucun fournisseur d’IA n’est configuré.');
        }

        // Extraction depuis des documents : palier qualité + bascule de fournisseur
        // (indispensable ici : certains fournisseurs ne lisent pas les PDF).
        $instruction = $this->instruction();
        if (filled(trim((string) $prompt))) {
            $instruction .= "\n\nDescription fournie par l'administrateur :\n".trim($prompt);
        }

        return $this->normalize($this->manager->extract($instruction, $files, 'deep'));
    }

    protected function instruction(): string
    {
        return <<<'TXT'
        Tu es un assistant qui extrait les métadonnées d'un colloque scientifique à partir des
        documents fournis (appel à communications, affiche, programme…).

        Réponds UNIQUEMENT par un objet JSON valide, sans aucun texte autour. Omets ou laisse
        vide toute information introuvable. N'INVENTE RIEN.

        Schéma attendu :
        {
          "title": string,               // titre de l'événement
          "event_label": string,       // ex. "3e événement"
          "subtitle": string,
          "theme": string,
          "year": number,
          "date_start": "YYYY-MM-DD",
          "date_end": "YYYY-MM-DD",
          "location": string,
          "submission_deadline": "YYYY-MM-DD",
          "intro": string,               // argumentaire / présentation
          "tracks": [string],            // axes thématiques
          "fees": [{"label": string, "amount": number}],
          "committees": [{"role": "organisation|scientifique|supervision", "person_name": string, "person_title": string, "qualite": string, "institution": string, "phone": string, "email": string}],
          "partners": [{"name": string, "url": string, "level": string}],
          "faq": [{"question": string, "answer": string}]
        }

        Pour chaque membre de comité :
        - "person_name" = le nom et prénom SEULS, sans civilité (M., Monsieur, Mme) ni grade.
        - "person_title" = le GRADE / titre académique : « Professeur », « Pr », « Maître de
          conférences », « MC », « MCA », « Maître-assistant », « MA », « Dr »… souvent entre
          parenthèses après le nom (« … (MC) »).
        - "qualite" = la FONCTION DANS LE COMITÉ, à ne pas confondre avec le grade :
          « Président », « 1er Vice-président », « 2ème Vice-président », « 1er Rapporteur »,
          « Rapporteur », « Secrétaire », « Trésorier », « Membre »… Elle est le plus souvent
          écrite juste après le nom (« …, Président »), OU donnée par un intitulé de section :
          toutes les personnes listées sous un titre « Membres » ont la qualité « Membre ».
          Laisse vide si vraiment aucune fonction n'est indiquée.
        - "phone" et "email" = coordonnées du membre si elles figurent.
        TXT;
    }

    /** @return array<string, mixed> */
    protected function normalize(array $raw): array
    {
        $out = [];

        foreach (['title', 'event_label', 'subtitle', 'theme', 'location', 'intro'] as $k) {
            if (filled($raw[$k] ?? null)) {
                $out[$k] = (string) $raw[$k];
            }
        }

        if (filled($raw['year'] ?? null) && is_numeric($raw['year'])) {
            $out['year'] = (int) $raw['year'];
        }

        foreach (['date_start', 'date_end', 'submission_deadline'] as $k) {
            if ($date = $this->toDate($raw[$k] ?? null)) {
                $out[$k] = $date;
            }
        }

        $tracks = collect($raw['tracks'] ?? [])->map(fn ($t) => trim((string) $t))->filter()->values()->all();
        if ($tracks) {
            $out['tracks'] = $tracks;
        }

        $fees = collect($raw['fees'] ?? [])
            ->filter(fn ($f) => filled($f['label'] ?? null))
            ->map(fn ($f) => ['label' => (string) $f['label'], 'amount' => (float) ($f['amount'] ?? 0)])
            ->values()->all();
        if ($fees) {
            $out['fees'] = $fees;
        }

        $committees = collect($raw['committees'] ?? [])
            ->filter(fn ($c) => filled($c['person_name'] ?? null))
            ->map(fn ($c) => [
                'role' => in_array($c['role'] ?? '', ['organisation', 'scientifique', 'supervision'], true) ? $c['role'] : 'organisation',
                'person_name' => (string) $c['person_name'],
                'person_title' => (string) ($c['person_title'] ?? ''),
                // ⚠️ La qualité (fonction dans le comité : Président, Rapporteur,
                // Membre…) est DISTINCTE du grade, et n'était pas extraite - les
                // champs Qualité / Téléphone / E-mail du comité d'organisation
                // restaient vides. Le formulaire les lie pour `comOrg`.
                'qualite' => (string) ($c['qualite'] ?? ''),
                'institution' => (string) ($c['institution'] ?? ''),
                'phone' => (string) ($c['phone'] ?? ''),
                'email' => (string) ($c['email'] ?? ''),
                'photo_url' => '',
            ])->values()->all();
        if ($committees) {
            $out['committees'] = $committees;
        }

        $partners = collect($raw['partners'] ?? [])
            ->filter(fn ($p) => filled($p['name'] ?? null))
            ->map(fn ($p) => ['name' => (string) $p['name'], 'url' => (string) ($p['url'] ?? ''), 'logo_url' => '', 'level' => (string) ($p['level'] ?? '')])
            ->values()->all();
        if ($partners) {
            $out['partners'] = $partners;
        }

        $faq = collect($raw['faq'] ?? [])
            ->filter(fn ($f) => filled($f['question'] ?? null))
            ->map(fn ($f) => ['question' => (string) $f['question'], 'answer' => (string) ($f['answer'] ?? '')])
            ->values()->all();
        if ($faq) {
            $out['faq'] = $faq;
        }

        return $out;
    }

    protected function toDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
