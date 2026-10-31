<?php

namespace App\Payments;

use RuntimeException;

/**
 * Un refus que le PAYEUR peut corriger lui-même.
 *
 * ⚠️⚠️ « Le service de paiement est momentanément indisponible. Réessayez plus
 * tard. » est le bon message quand la panne est chez nous ou chez le
 * prestataire : il n'y a rien à faire, sinon attendre. C'est le **mauvais**
 * message quand la donnée fautive est dans le profil du payeur - il attend une
 * réparation qui ne viendra jamais, pour quelque chose qu'il corrige en trente
 * secondes. Constaté le 30/10/2026 : un numéro de téléphone sans indicatif
 * faisait refuser la transaction, et l'écran parlait d'indisponibilité.
 *
 * ⚠️ Le message de cette exception est **affiché tel quel** au payeur : il doit
 * dire quoi corriger et où, et ne jamais nommer le prestataire (convention du
 * projet) ni exposer un détail technique.
 */
class PayerInputException extends RuntimeException {}
