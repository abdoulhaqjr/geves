<?php

namespace App\Payments\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Passerelle de paiement en ligne : sait vérifier le statut d'un paiement auprès
 * du prestataire et interpréter une notification (webhook) de façon sûre.
 */
interface OnlineGateway extends PaymentGateway
{
    /**
     * Opérateurs mobile money **transmissibles au prestataire** pour que sa page
     * arrive pré-sélectionnée. Clé = code qu'il attend, valeur = libellé affiché
     * (⚠️ jamais le nom de l'agrégateur). Vide si la passerelle ne sait pas
     * cibler un opérateur.
     *
     * @return array<string, string>
     */
    public function mobileOperators(): array;

    /**
     * Moyens de paiement **proposables au payeur avant la redirection** : les
     * opérateurs ci-dessus, et tout ce qui se choisit chez nous sans être un code
     * du prestataire - la **carte bancaire** au premier chef.
     *
     * ⚠️ **Les deux listes sont distinctes à dessein.** `mobileOperators()` dit ce
     * qu'on peut TRANSMETTRE, celle-ci ce qu'on peut PROPOSER : un moyen présent
     * ici et absent de l'autre ne doit jamais partir dans la requête. Tout code
     * hors de cette liste est refusé à l'enregistrement
     * ({@see Payment::setOperator()}).
     *
     * @return array<string, string>
     */
    public function paymentMethods(): array;

    /**
     * L'opérateur **déduit du numéro de téléphone** du payeur, dit dans le
     * vocabulaire du prestataire, ou null si rien ne se déduit.
     *
     * ⚠️ **C'est une présomption, pas un fait** : la portabilité des numéros peut
     * l'avoir démentie. Elle sert à PRÉ-SÉLECTIONNER un choix qui reste à l'écran
     * et modifiable d'un geste - jamais à décider en silence.
     *
     * ⚠️ Le code rendu est forcément une clé de `mobileOperators()` : c'est la
     * seule liste transmissible au prestataire.
     */
    public function operatorFromPhone(?string $phone): ?string;

    /**
     * Interroge le prestataire et renvoie un statut normalisé.
     *
     * @return 'paid'|'pending'|'failed'
     */
    public function verify(Payment $payment): string;

    /**
     * Vérifie l'authenticité d'une notification et en extrait la référence et le
     * statut. Retourne null si la signature est invalide (fail-closed).
     *
     * @return array{reference: string, status: 'paid'|'pending'|'failed'}|null
     */
    public function parseWebhook(Request $request): ?array;
}
