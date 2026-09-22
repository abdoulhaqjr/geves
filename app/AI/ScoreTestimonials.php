<?php

namespace App\AI;

use App\Models\Testimonial;
use Illuminate\Support\Collection;

/**
 * Évalue l'« impact » des témoignages approuvés et propose lesquels mettre en
 * avant sur la page d'accueil, dans un ordre suggéré. L'IA ne fait que
 * PROPOSER : elle renseigne un score + une justification et pré-remplit la
 * sélection « à la une » ; l'admin garde la main (peut ajuster la mise en avant
 * et l'ordre ensuite).
 */
class ScoreTestimonials
{
    /** Nombre de témoignages mis en avant proposés par défaut. */
    public const FEATURED_TARGET = 6;

    public function __construct(private AiManager $ai) {}

    /**
     * Note les témoignages approuvés et applique la sélection proposée.
     *
     * @return array{scored: int, featured: int}
     */
    public function handle(int $target = self::FEATURED_TARGET): array
    {
        /** @var Collection<int, Testimonial> $testimonials */
        $testimonials = Testimonial::where('status', 'approved')->get();

        if ($testimonials->isEmpty()) {
            return ['scored' => 0, 'featured' => 0];
        }

        $result = $this->ai->extract($this->instruction($testimonials, $target), [], 'deep');

        return $this->apply($testimonials, $result, $target);
    }

    /**
     * @param  Collection<int, Testimonial>  $testimonials
     */
    private function instruction(Collection $testimonials, int $target): string
    {
        $items = $testimonials->map(fn (Testimonial $t) => [
            'id' => $t->id,
            'auteur' => trim($t->author_name.' — '.(string) $t->author_role),
            'affiliation' => (string) $t->author_affiliation,
            'temoignage' => $t->quote,
        ])->values()->all();

        $json = (string) json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return "Tu aides une plateforme universitaire d'événements scientifiques à choisir les témoignages à mettre en avant "
            ."sur sa page d'accueil publique. On te fournit une liste de témoignages approuvés (identifiant, auteur, "
            ."affiliation, texte).\n\n"
            ."Évalue l'IMPACT de chaque témoignage pour un futur participant : sincérité et crédibilité, clarté, éléments "
            ."concrets, bénéfice mis en avant, et intérêt à figurer sur l'accueil. Attribue un score entier de 0 à 100 et "
            ."une justification en une phrase (français, sobre). Propose ensuite les $target témoignages (au plus) les plus "
            ."percutants à mettre « à la une », en variant les profils si possible, classés du plus fort au plus faible.\n\n"
            ."Témoignages :\n".$json."\n\n"
            .'Réponds UNIQUEMENT par un objet JSON de la forme '
            .'{"results":[{"id":<id>,"score":<0-100>,"reason":"<phrase>"}],"featured":[<id>,<id>,...]}. '
            .'Chaque id de "featured" doit exister dans "results". N\'invente aucun id.';
    }

    /**
     * Applique scores + sélection proposée. Robuste à une réponse partielle.
     *
     * @param  Collection<int, Testimonial>  $testimonials
     * @param  array<string, mixed>  $result
     * @return array{scored: int, featured: int}
     */
    private function apply(Collection $testimonials, array $result, int $target): array
    {
        $byId = $testimonials->keyBy('id');

        // Scores + justifications.
        $scored = 0;
        foreach ((array) ($result['results'] ?? []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            $testimonial = $byId->get($id);
            if (! $testimonial) {
                continue;
            }

            $testimonial->impact_score = max(0, min(100, (int) ($row['score'] ?? 0)));
            $testimonial->impact_reason = mb_substr(trim((string) ($row['reason'] ?? '')), 0, 300) ?: null;
            $testimonial->save();
            $scored++;
        }

        // Sélection « à la une » proposée : uniquement des id connus et approuvés.
        $featuredIds = collect((array) ($result['featured'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $byId->has($id))
            ->unique()
            ->take($target)
            ->values();

        foreach ($testimonials as $testimonial) {
            $rank = $featuredIds->search($testimonial->id);
            $testimonial->featured = $rank !== false;
            $testimonial->position = $rank !== false ? (int) $rank : 0;
            $testimonial->save();
        }

        return ['scored' => $scored, 'featured' => $featuredIds->count()];
    }
}
