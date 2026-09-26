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
     * Opérateurs mobile money proposables au payeur AVANT la redirection, pour que
     * la page du prestataire arrive pré-sélectionnée. Clé = code attendu par le
     * prestataire, valeur = libellé affiché (⚠️ jamais le nom de l'agrégateur).
     * Tableau vide si la passerelle ne sait pas cibler un opérateur.
     *
     * @return array<string, string>
     */
    public function mobileOperators(): array;

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
