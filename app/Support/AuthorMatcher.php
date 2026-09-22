<?php

namespace App\Support;

use App\AI\AiManager;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Retrouve, pour un auteur saisi (nom / e-mail / téléphone / affiliation), le
 * compte utilisateur correspondant sur la plateforme.
 *
 * Politique de fiabilité :
 * - **e-mail** ou **téléphone** exact = signal FORT (auto-liaison ferme) ;
 * - **nom** = signal FAIBLE, renforcé par l'**affiliation** pour départager les
 *   homonymes, avec l'**IA en renfort** si plusieurs candidats subsistent. Une
 *   liaison par nom est marquée `name` (déclinable par la personne).
 */
class AuthorMatcher
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @param  array<string, mixed>  $author  entrée d'auteur (name, email, phone, affiliation)
     * @return array{user: ?User, type: ?string} type ∈ {email, phone, name} ou null
     */
    public function match(array $author): array
    {
        $email = self::normEmail($author['email'] ?? null);
        $phone = self::normPhone($author['phone'] ?? null);
        $name = trim((string) ($author['name'] ?? ''));
        $affiliation = trim((string) ($author['affiliation'] ?? ''));

        // 1) Signal fort : e-mail exact.
        if ($email !== '') {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if ($user) {
                return ['user' => $user, 'type' => 'email'];
            }
        }

        // 2) Signal fort : téléphone exact (E.164).
        if ($phone !== '') {
            $user = User::where('phone', $phone)->first();
            if ($user) {
                return ['user' => $user, 'type' => 'phone'];
            }
        }

        // 3) Signal faible : nom (+ affiliation, + IA en renfort).
        if ($name !== '') {
            $user = $this->matchByName($name, $affiliation);
            if ($user) {
                return ['user' => $user, 'type' => 'name'];
            }
        }

        return ['user' => null, 'type' => null];
    }

    /** Candidats par nom, départagés par affiliation puis par l'IA. */
    protected function matchByName(string $name, string $affiliation): ?User
    {
        $wanted = self::nameTokens($name);
        if (count($wanted) < 2) {
            return null; // un seul token = trop ambigu
        }

        // Présélection large sur le dernier token (indexable), affinée en PHP.
        $needle = end($wanted);
        $candidates = User::where(function ($q) use ($needle): void {
            $q->where('name', 'like', '%'.$needle.'%')
                ->orWhere('last_name', 'like', '%'.$needle.'%')
                ->orWhere('first_name', 'like', '%'.$needle.'%');
        })->limit(50)->get()
            ->filter(fn (User $u) => self::nameTokens($u->full_name ?: (string) $u->name) === $wanted)
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }
        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        // Plusieurs homonymes : départage par affiliation.
        if ($affiliation !== '') {
            $byAffiliation = $candidates->filter(
                fn (User $u) => self::affiliationMatches($affiliation, (string) $u->affiliation)
            )->values();
            if ($byAffiliation->count() === 1) {
                return $byAffiliation->first();
            }
            if ($byAffiliation->count() > 1) {
                $candidates = $byAffiliation;
            }
        }

        // Toujours plusieurs : IA en renfort (sinon on s'abstient, par prudence).
        return $this->pickWithAi($name, $affiliation, $candidates);
    }

    /** Demande à l'IA de choisir le meilleur candidat (ou aucun). */
    protected function pickWithAi(string $name, string $affiliation, Collection $candidates): ?User
    {
        if (! $this->manager->resolve(false)) {
            return null;
        }

        $list = $candidates->map(fn (User $u) => "- id={$u->id} | ".($u->full_name ?: $u->name)
            .' | affiliation: '.($u->affiliation ?: '—'))->implode("\n");

        $instruction = <<<TXT
        Tu aides à rapprocher un auteur d'une communication scientifique d'un compte utilisateur.
        AUTEUR SAISI : nom « {$name} », affiliation « {$affiliation} ».
        CANDIDATS :
        {$list}

        Renvoie STRICTEMENT un JSON : {"id": <id du meilleur candidat ou null>, "confiance": 0..1}.
        Ne choisis un id QUE si tu es très confiant (même personne). En cas de doute, id=null.
        TXT;

        try {
            $raw = $this->manager->extract($instruction, [], 'fast');
            $id = $raw['id'] ?? null;
            $confidence = (float) ($raw['confiance'] ?? $raw['confidence'] ?? 0);
            if ($id !== null && $confidence >= 0.8) {
                return $candidates->firstWhere('id', (int) $id);
            }
        } catch (\Throwable) {
            // IA indisponible / réponse inexploitable : on s'abstient (pas de mauvaise liaison).
        }

        return null;
    }

    // --- Normalisation -------------------------------------------------------

    public static function normEmail(?string $email): string
    {
        $email = mb_strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    /** Ne garde que les chiffres (et un éventuel +), pour comparer des E.164. */
    public static function normPhone(?string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', (string) $phone) ?? '';

        return strlen(preg_replace('/\D/', '', $phone) ?? '') >= 8 ? $phone : '';
    }

    /**
     * Jeu de tokens normalisés d'un nom (sans accents, minuscule, trié) — la
     * comparaison est donc insensible à l'ordre « Prénom Nom » / « Nom Prénom ».
     *
     * @return list<string>
     */
    public static function nameTokens(string $name): array
    {
        $ascii = Str::ascii($name);
        $ascii = mb_strtolower((string) preg_replace('/[^\p{L}\s]/u', ' ', $ascii));
        $tokens = array_values(array_filter(explode(' ', (string) preg_replace('/\s+/', ' ', trim($ascii)))));
        sort($tokens);

        return $tokens;
    }

    /** Deux affiliations partagent-elles un token significatif (≥ 4 lettres) ? */
    public static function affiliationMatches(string $a, string $b): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }
        $ta = array_filter(self::nameTokens($a), fn ($t) => strlen($t) >= 4);
        $tb = array_filter(self::nameTokens($b), fn ($t) => strlen($t) >= 4);

        return array_intersect($ta, $tb) !== [];
    }
}
