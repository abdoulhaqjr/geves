<?php

namespace App\AI;

use App\AI\Contracts\AiProvider;
use App\AI\Providers\AnthropicProvider;
use App\AI\Providers\GeminiProvider;
use App\AI\Providers\GrokProvider;
use App\AI\Providers\OpenAiProvider;

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
            new AnthropicProvider(),
            new OpenAiProvider(),
            new GeminiProvider(),
            new GrokProvider(),
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
}
