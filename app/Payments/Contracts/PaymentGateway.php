<?php

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Support\PaymentEmbed;

/**
 * Contrat commun aux passerelles de paiement. Une passerelle s'annonce
 * (clé, libellé, disponibilité) et sait initier un paiement.
 */
interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    /** Vrai si les identifiants marchands sont configurés. */
    public function isAvailable(): bool;

    /**
     * Initie le paiement. Quatre formes, et c'est l'appelant qui les distingue :
     *
     * - `redirect` : on envoie le payeur sur la page du prestataire (`url`) ;
     * - `embed` : le formulaire du prestataire s'ouvre SUR notre page
     *   ({@see PaymentEmbed}) ; `url` reste la porte de sortie si
     *   son script ne charge pas ;
     * - `push` : **aucun formulaire** - la demande d'approbation part sur le
     *   téléphone du payeur, et la page attend le verdict du serveur. `url`
     *   reste la porte de sortie si la demande n'arrive jamais ;
     * - `instructions` : rien en ligne, on affiche la marche à suivre (`message`).
     *
     * @return array{
     *     type: 'redirect'|'embed'|'push'|'instructions',
     *     url?: string,
     *     message?: string,
     *     public_key?: string,
     *     environment?: string,
     *     transaction_id?: string,
     *     customer?: array<string, mixed>,
     *     mode?: string,
     *     operator?: string,
     *     phone?: string,
     * }
     */
    public function initiate(Payment $payment): array;
}
