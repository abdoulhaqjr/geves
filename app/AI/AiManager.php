<?php

namespace App\AI;

use App\AI\Contracts\AiProvider;
use App\AI\Exceptions\AiUnavailableException;
use App\AI\Providers\AnthropicProvider;
use App\AI\Providers\GeminiProvider;
use App\AI\Providers\GrokProvider;
use App\AI\Providers\OpenAiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Registre des fournisseurs d'IA : liste, disponibilité, et résolution du
 * fournisseur à utiliser (défaut configuré, repli, préférence PDF).
 */
class AiManager
{
    /** @var array<int, AiProvider> */
    protected array $providers;

    public function __construct()
    {
        $this->providers = [
            new AnthropicProvider,
            new OpenAiProvider,
            new GeminiProvider,
            new GrokProvider,
        ];
    }

    /** @return array<int, AiProvider> */
    public function all(): array
    {
        return $this->providers;
    }

    /** @return array<int, AiProvider> */
    public function available(): array
    {
        return array_values(array_filter($this->providers, fn (AiProvider $p) => $p->isAvailable()));
    }

    public function get(string $key): ?AiProvider
    {
        foreach ($this->providers as $p) {
            if ($p->key() === $key) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Fournisseur à utiliser : le défaut configuré s'il est disponible, sinon le
     * premier disponible. Si un PDF est présent, on privilégie un fournisseur
     * qui sait lire les PDF (Claude/Gemini).
     */
    public function resolve(bool $needsPdf = false): ?AiProvider
    {
        $available = $this->available();
        if ($available === []) {
            return null;
        }

        $default = $this->get((string) config('services.ai.default'));
        $pick = ($default && $default->isAvailable()) ? $default : $available[0];

        if ($needsPdf && ! $pick->supportsPdf()) {
            foreach ($available as $p) {
                if ($p->supportsPdf()) {
                    return $p;
                }
            }
        }

        return $pick;
    }

    /**
     * Exécute une extraction avec **bascule automatique entre fournisseurs**. On
     * tente le fournisseur préféré/par défaut puis, s'il ne peut pas honorer la
     * tâche (surcharge persistante, quota/jetons épuisés, capacité manquante,
     * erreur), on passe au fournisseur capable suivant — d'un même ou d'un autre
     * fournisseur configuré. « needsPdf » (déduit des fichiers) restreint aux
     * fournisseurs sachant lire un PDF, ce qui règle le cas des IA qui ne lisent
     * pas les PDF.
     *
     * @param  array<int, array{mime: string, data: string}>  $files
     * @param  'fast'|'deep'|null  $tier
     * @return array<string, mixed>
     */
    public function extract(string $instruction, array $files = [], ?string $tier = null, ?string $preferKey = null): array
    {
        $needsPdf = collect($files)->contains(fn ($f) => str_contains((string) $f['mime'], 'pdf'));
        $candidates = $this->candidates($needsPdf, $preferKey);

        if ($candidates === []) {
            throw new AiUnavailableException($needsPdf
                ? 'Aucun assistant IA capable de lire des PDF n’est configuré (voir Réglages).'
                : 'Aucun assistant IA n’est configuré (voir Réglages).');
        }

        $last = null;
        foreach ($candidates as $provider) {
            try {
                return $provider->extract($instruction, $files, $tier);
            } catch (AiUnavailableException|RequestException|ConnectionException $e) {
                // Ce fournisseur ne peut pas honorer la tâche : on tente le suivant.
                Log::warning('IA : bascule de fournisseur après échec de '.$provider->key(), ['message' => $e->getMessage()]);
                $last = $e;

                continue;
            }
        }

        throw new AiUnavailableException(
            'Les assistants IA sont momentanément indisponibles ou à court de crédits. Merci de réessayer plus tard.',
            previous: $last,
        );
    }

    /**
     * Fournisseurs disponibles et capables, ordonnés par préférence : la clé
     * préférée (si fournie et capable), puis le défaut configuré, puis les autres.
     * Si « needsPdf », seuls ceux qui savent lire les PDF sont retenus.
     *
     * @return array<int, AiProvider>
     */
    protected function candidates(bool $needsPdf = false, ?string $preferKey = null): array
    {
        $capable = array_values(array_filter(
            $this->available(),
            fn (AiProvider $p) => ! $needsPdf || $p->supportsPdf(),
        ));

        // Ordre de préférence : clé demandée d'abord, puis le fournisseur par défaut.
        $prefKeys = [];
        $prefer = $this->normalizeKey($preferKey);
        if ($prefer !== null) {
            $prefKeys[] = $prefer;
        }
        $prefKeys[] = (string) config('services.ai.default');

        // Tri stable (PHP 8) : les non-préférés conservent l'ordre de available().
        usort($capable, fn (AiProvider $a, AiProvider $b) => $this->rank($a, $prefKeys) <=> $this->rank($b, $prefKeys));

        return $capable;
    }

    /** @param  array<int, string>  $prefKeys */
    private function rank(AiProvider $provider, array $prefKeys): int
    {
        $i = array_search($provider->key(), $prefKeys, true);

        return $i === false ? PHP_INT_MAX : $i;
    }

    /** Normalise une clé de fournisseur (alias « anthropic » → « claude », « default »/vide → null). */
    private function normalizeKey(?string $key): ?string
    {
        $key = trim((string) $key);
        if ($key === '' || $key === 'default') {
            return null;
        }

        return $key === 'anthropic' ? 'claude' : $key;
    }
}
