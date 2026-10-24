<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\FedaPayGateway;
use App\Payments\Gateways\ManualGateway;

/**
 * Registre des passerelles de paiement. Résout la liste des passerelles
 * disponibles (identifiants configurés) et la passerelle par défaut.
 */
class GatewayRegistry
{
    /** @var array<int, PaymentGateway> */
    protected array $gateways;

    public function __construct()
    {
        $this->gateways = [
            new FedaPayGateway,
            new ManualGateway,
        ];
    }

    /** @return array<int, PaymentGateway> */
    public function all(): array
    {
        return $this->gateways;
    }

    /** @return array<int, PaymentGateway> Passerelles utilisables (le manuel l'est toujours). */
    public function available(): array
    {
        return array_values(array_filter($this->gateways, fn (PaymentGateway $g) => $g->isAvailable()));
    }

    /**
     * Ce qu'on PROPOSE au payeur, en liste (les écrans en font des cartes).
     *
     * ⚠️⚠️ **LA PASSERELLE RÉGLÉE EST LA SEULE PROPOSÉE.** Les écrans partaient
     * d'`available()`, c'est-à-dire de tout ce qui est utilisable - donc
     * **toujours** le paiement manuel, qui ne demande aucun identifiant. Un
     * événement qui encaisse en ligne offrait quand même « sur place /
     * virement » au participant, et récoltait des règlements que personne
     * n'attendait au guichet. Le réglage de l'événement disait « FedaPay », la
     * page en proposait deux.
     *
     * ⚠️ **Le repli vit dans `default()`**, et il est essentiel : une passerelle
     * choisie mais **pas configurée** retombe sur le manuel. On ne laisse jamais
     * le payeur devant zéro moyen de paiement - une clé oubliée ne doit pas
     * fermer la caisse.
     *
     * @return array<int, PaymentGateway>
     */
    public function offered(): array
    {
        return [$this->default()];
    }

    /**
     * Cette passerelle est-elle celle qu'on propose ?
     *
     * ⚠️ **On ferme la porte, pas seulement le formulaire.** Ne plus afficher une
     * carte n'empêche pas de poster sa clé à la main : sans cette garde, on
     * enregistrerait au guichet un règlement sur un événement qui encaisse
     * uniquement en ligne, et personne ne verrait d'où il vient.
     */
    public function offers(string $key): bool
    {
        foreach ($this->offered() as $gateway) {
            if ($gateway->key() === $key) {
                return true;
            }
        }

        return false;
    }

    public function get(string $key): ?PaymentGateway
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->key() === $key) {
                return $gateway;
            }
        }

        return null;
    }

    /** Passerelle par défaut (réglage), avec repli sur le manuel. */
    public function default(): PaymentGateway
    {
        $key = (string) config('payments.default', 'manual');

        $gateway = $this->get($key);

        return $gateway && $gateway->isAvailable() ? $gateway : new ManualGateway;
    }
}
