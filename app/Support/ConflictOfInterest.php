<?php

namespace App\Support;

use App\Models\Submission;

/**
 * Détection déterministe des conflits d'intérêts potentiels entre un relecteur
 * et les auteurs d'une communication, à partir des données réelles (auteurs JSON,
 * institution, historique de co-publication). Règles du mockup / guide §2 :
 * même institution, co-auteur récent (< 5 ans), homonymie (lien familial possible),
 * relecteur lui-même auteur. Le résultat est indicatif : l'admin garde la décision.
 */
class ConflictOfInterest
{
    public const HIGH = 'high';

    public const MEDIUM = 'medium';

    public const LOW = 'low';

    private const LEVEL_ORDER = [self::LOW => 1, self::MEDIUM => 2, self::HIGH => 3];

    /**
     * Conflits potentiels entre le relecteur et les auteurs de la soumission.
     *
     * @return list<array{level: string, label: string, detail: string}>
     */
    public static function check(Submission $submission, string $reviewerName, string $reviewerEmail, ?string $institution = null): array
    {
        $conflicts = [];
        $revEmail = mb_strtolower(trim($reviewerEmail));
        $revInst = self::normalize((string) $institution);
        $revLast = self::lastName($reviewerName);
        $isAuthor = false;

        foreach (self::authorsOf($submission) as $a) {
            $authorName = (string) ($a['name'] ?? '');
            $authorEmail = mb_strtolower(trim((string) ($a['email'] ?? '')));
            $authorAff = self::normalize((string) ($a['affiliation'] ?? ''));

            if ($revEmail !== '' && $authorEmail === $revEmail) {
                $conflicts[] = ['level' => self::HIGH, 'label' => 'Auteur de la communication',
                    'detail' => 'Le relecteur figure parmi les auteurs'.($authorName ? ' ('.$authorName.')' : '').'.'];
                $isAuthor = true;

                continue;
            }
            if ($revInst !== '' && $authorAff !== '' && $revInst === $authorAff) {
                $conflicts[] = ['level' => self::HIGH, 'label' => 'Même institution',
                    'detail' => trim(($authorName ?: 'Un auteur').' - '.(string) ($a['affiliation'] ?? '')).'.'];
            }
            if ($revLast !== '' && self::lastName($authorName) === $revLast) {
                $conflicts[] = ['level' => self::LOW, 'label' => 'Homonymie de nom',
                    'detail' => 'Même nom de famille que '.($authorName ?: 'un auteur').' (lien familial possible).'];
            }
        }

        // Co-publication : le relecteur a co-signé une autre communication avec un
        // auteur de celle-ci (hors le cas où il en est lui-même auteur).
        if ($revEmail !== '' && ! $isAuthor) {
            foreach (self::coPublicationWith($submission, $revEmail) as $name) {
                $conflicts[] = ['level' => self::MEDIUM, 'label' => 'Co-publication récente',
                    'detail' => 'A co-signé une communication avec '.$name.' (moins de 5 ans).'];
            }
        }

        return $conflicts;
    }

    /**
     * Niveau le plus élevé parmi une liste de conflits (ou null si aucun).
     *
     * @param  list<array{level?: string}>  $conflicts
     */
    public static function worstLevel(array $conflicts): ?string
    {
        $bestRank = 0;
        $bestLevel = null;
        foreach ($conflicts as $c) {
            $lvl = $c['level'] ?? null;
            $rank = $lvl !== null && isset(self::LEVEL_ORDER[$lvl]) ? self::LEVEL_ORDER[$lvl] : 0;
            if ($rank > $bestRank) {
                $bestRank = $rank;
                $bestLevel = $lvl;
            }
        }

        return $bestLevel;
    }

    /**
     * Auteurs normalisés (éléments non-tableau du JSON écartés).
     *
     * @return list<array<string, mixed>>
     */
    protected static function authorsOf(Submission $submission): array
    {
        return array_values($submission->authors ?? []);
    }

    /**
     * Noms des auteurs de la soumission avec qui le relecteur a déjà co-signé une
     * autre communication (dans les 5 dernières années).
     *
     * @return list<string>
     */
    protected static function coPublicationWith(Submission $submission, string $revEmail): array
    {
        $authorEmails = [];
        foreach (self::authorsOf($submission) as $a) {
            $email = mb_strtolower(trim((string) ($a['email'] ?? '')));
            if ($email !== '') {
                $authorEmails[] = $email;
            }
        }

        if ($authorEmails === []) {
            return [];
        }

        // Pré-filtre SQL sur l'e-mail du relecteur (JSON stocké en texte), affinage en PHP.
        $others = Submission::query()
            ->whereKeyNot($submission->id)
            ->where('created_at', '>=', now()->subYears(5))
            ->where('authors', 'like', '%'.$revEmail.'%')
            ->get(['id', 'authors']);

        $found = [];
        foreach ($others as $sub) {
            $subAuthors = self::authorsOf($sub);
            $reviewerIsCoAuthor = false;
            foreach ($subAuthors as $a) {
                if (mb_strtolower(trim((string) ($a['email'] ?? ''))) === $revEmail) {
                    $reviewerIsCoAuthor = true;
                    break;
                }
            }
            if (! $reviewerIsCoAuthor) {
                continue;
            }
            foreach ($subAuthors as $a) {
                $email = mb_strtolower(trim((string) ($a['email'] ?? '')));
                if ($email !== '' && $email !== $revEmail && in_array($email, $authorEmails, true)) {
                    $found[$email] = (string) ($a['name'] ?? $email);
                }
            }
        }

        return array_values(array_unique($found));
    }

    /** Normalise une chaîne (minuscule, espaces compactés) pour comparaison. */
    protected static function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($value)) ?? '');
    }

    /** Dernier mot d'un nom (heuristique de nom de famille). */
    protected static function lastName(string $name): string
    {
        $parts = preg_split('/\s+/', trim(mb_strtolower($name))) ?: [];
        $parts = array_values(array_filter($parts));

        return $parts === [] ? '' : (string) end($parts);
    }
}
