<?php

namespace App\AI\Concerns;

use App\AI\Exceptions\AiUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Throwable;

/**
 * Relance transparente des requêtes IA en échec passager. Un modèle surchargé
 * répond fréquemment 503 (ou 429 / 502 / 504) ou coupe la connexion : on rejoue
 * alors la requête jusqu'à 3 tentatives, sans que l'utilisateur ne le voie.
 * Après la 3ᵉ tentative, on lève une {@see AiUnavailableException} porteuse d'un
 * message clair destiné à l'utilisateur.
 */
trait RetriesTransientFailures
{
    /** Statuts HTTP considérés comme passagers (surcharge / passerelle). */
    private const TRANSIENT_STATUSES = [429, 500, 502, 503, 504];

    /**
     * Exécute la requête et la rejoue en cas d'échec passager (3 tentatives,
     * ~400 ms entre chacune). Les autres erreurs (4xx, etc.) remontent telles quelles.
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    protected function retryOnOverload(\Closure $callback): mixed
    {
        try {
            return retry(3, $callback, 400, fn (Throwable $e) => $this->isTransient($e));
        } catch (RequestException|ConnectionException $e) {
            if ($this->isTransient($e)) {
                throw new AiUnavailableException(
                    'L’assistant IA est très sollicité en ce moment. Merci de réessayer dans quelques instants.',
                    previous: $e,
                );
            }

            throw $e;
        }
    }

    private function isTransient(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        return $e instanceof RequestException
            && in_array((int) $e->response->status(), self::TRANSIENT_STATUSES, true);
    }
}
