<?php

namespace App\AI\Exceptions;

use RuntimeException;

/**
 * Levée lorsqu'un fournisseur d'IA reste indisponible après les tentatives de
 * relance transparentes (surcharge 503, 429, erreurs passerelle, réseau). Son
 * message est destiné à l'utilisateur final : les contrôleurs de chat le
 * renvoient tel quel comme réponse explicite.
 */
class AiUnavailableException extends RuntimeException {}
