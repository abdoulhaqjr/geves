<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * FedaPay - mobile money (MTN, Moov, Togocom…) via page hébergée ou formulaire
 * embarqué. ⚠️ La carte bancaire demande une couverture que le compte marchand
 * n'a pas : elle n'est ni proposée ni annoncée (cf. `label()`).
 *
 * Flux : POST /transactions → POST /transactions/{id}/token (URL hébergée) →
 * GET /transactions/{id} (status « approved ») ; webhook signé HMAC-SHA256
 * (X-FEDAPAY-Signature « t=…,s=… » sur « {t}.{payload} » avec le secret de webhook).
 *
 * ⚠️ Implémentation d'après l'API publique FedaPay - à valider avec le sandbox
 * marchand (enveloppe de réponse et format d'événement peuvent évoluer).
 */
class FedaPayGateway implements OnlineGateway
{
    public function key(): string
    {
        return 'fedapay';
    }

    public function label(): string
    {
        // ⚠️ Convention projet : on n'expose jamais le nom de l'agrégateur au payeur.
        //
        // ⚠️⚠️ **PAS DE « CARTE BANCAIRE » TANT QU'ELLE N'EST PAS OUVERTE.** Le
        // libellé la promettait ; elle demande une couverture que le compte
        // marchand n'a pas, et le formulaire du prestataire ne propose que du
        // mobile money. Annoncer un moyen qui n'apparaît pas sur la page
        // suivante, c'est faire renoncer un payeur venu pour lui. Le jour où
        // elle est accordée, c'est ici qu'on la rajoute - et nulle part ailleurs.
        return 'Mobile money';
    }

    /**
     * Le lexique des opérateurs mobile money du prestataire (ses codes `mode`).
     *
     * ⚠️ **Il sert à LIRE, plus à choisir** : le payeur désigne son opérateur sur
     * la page du prestataire, et cette liste ne traduit plus que le `mode` qu'il
     * RAPPORTE sur un encaissement abouti ({@see self::details()}).
     *
     * ⚠️⚠️ **CETTE LISTE EST CELLE DU PRESTATAIRE, PAS LA NÔTRE.** Elle portait
     * `mtn_open_ci` pour MTN Côte d'Ivoire - le code documenté est **`mtn_ci`** -
     * ainsi que `bmo`, `coris_money` et `wave_direct_ci`, qui ne figurent dans
     * aucune liste officielle. Les quatre ont été écrits d'après l'API publique
     * sans jamais rencontrer un vrai compte marchand (cf. l'avertissement en
     * tête de classe), et un code inconnu est **ignoré en silence** : le payeur
     * croyait choisir, et rien ne partait.
     *
     * ⚠️ N'ajouter ici qu'un code **documenté** par le prestataire. Ce qu'il
     * n'accepte pas ne se transmet pas : mieux vaut laisser le payeur choisir
     * sur la page du prestataire que lui promettre un ciblage qui n'a pas lieu.
     *
     * @return array<string, string>
     */
    public function mobileOperators(): array
    {
        return [
            'togocel' => 'Togocel T-Money (Togo)',
            'moov_tg' => 'Moov (Togo)',
            'mtn_open' => 'MTN (Bénin)',
            'moov' => 'Moov (Bénin)',
            'sbin' => 'Celtiis Cash (Bénin)',
            'mtn_ci' => 'MTN (Côte d’Ivoire)',
            'free_sn' => 'Free (Sénégal)',
            'airtel_ne' => 'Airtel (Niger)',
        ];
    }

    public function isAvailable(): bool
    {
        return filled(config('services.fedapay.secret'));
    }

    protected function baseUrl(): string
    {
        return config('services.fedapay.mode') === 'live'
            ? 'https://api.fedapay.com/v1'
            : 'https://sandbox-api.fedapay.com/v1';
    }

    protected function http()
    {
        return Http::acceptJson()->withToken((string) config('services.fedapay.secret'));
    }

    /** FedaPay enveloppe ses objets sous « v1/transaction ». */
    protected function unwrap(array $res): array
    {
        return $res['v1/transaction'] ?? $res['transaction'] ?? $res['data'] ?? $res;
    }

    public function initiate(Payment $payment): array
    {
        $payer = $payment->payerContact();

        // Le téléphone pré-remplit la page FedaPay (mobile money). Il est stocké en
        // E.164 (`<x-ukc.phone>`) : on en déduit le pays attendu par l'API.
        $customer = array_filter([
            'firstname' => $payer['firstname'],
            'lastname' => $payer['lastname'],
            'email' => $payer['email'],
        ], fn ($v) => filled($v));

        if (filled($payer['phone'])) {
            $customer['phone_number'] = array_filter([
                'number' => $payer['phone'],
                'country' => self::countryFromE164((string) $payer['phone']),
            ], fn ($v) => filled($v));
        }

        $body = [
            'description' => 'Frais d\'inscription - '.($payment->event?->title ?? 'Événement'),
            'amount' => (int) round((float) $payment->amount),
            'currency' => ['iso' => 'XOF'],
            'callback_url' => route('payment.return', ['gateway' => 'fedapay', 'payment' => $payment->id]),
            'customer' => $customer,
        ];

        // ⚠️⚠️ **AUCUN `mode` N'EST TRANSMIS, ET C'EST VOULU.** Le moyen de paiement
        // se choisit sur la page du prestataire, qui propose tout ce que le compte
        // marchand accepte - opérateurs mobile money ET carte. Le champ `mode` ne
        // désignait d'ailleurs que du mobile money (aucun code carte n'est
        // documenté), et mesuré en conditions réelles, **il ne pré-sélectionnait
        // rien** : la page affichait son propre sélecteur quand même. Le remettre
        // ne ferait que risquer un code refusé, donc la création de la transaction
        // - donc le paiement ENTIER.
        $create = $this->http()->post($this->baseUrl().'/transactions', $body)->throw()->json();

        $id = $this->unwrap($create)['id'] ?? null;
        $payment->update(['gateway_ref' => $id]);

        $token = $this->http()->post($this->baseUrl()."/transactions/{$id}/token")->throw()->json();
        $url = (string) ($token['url'] ?? '');

        // ⚠️ **Sans clé PUBLIQUE, pas de formulaire embarqué** : on repart en
        // redirection. C'est la seule règle - pas de réglage de plus à tenir
        // d'accord avec la réalité. Qui renseigne la clé publique veut le
        // formulaire sur sa page ; qui ne la renseigne pas ne peut pas l'avoir.
        $publique = (string) config('services.fedapay.public_key');
        if ($publique === '') {
            return ['type' => 'redirect', 'url' => $url];
        }

        return [
            'type' => 'embed',
            'public_key' => $publique,
            // ⚠️ **Le formulaire EMBARQUÉ ne reçoit pas la page hébergée** : il
            // s'ouvre sur checkout.fedapay.com avec les options qu'on lui donne,
            // et sans pays il retombe sur celui du prestataire - **le Bénin**.
            // Mesuré en production : un numéro togolais (+228 90…) arrivait sur
            // « Mtn Bénin », avec le drapeau béninois. Le payeur devait corriger
            // à la main ce que nous savions déjà.
            //
            // ⚠️ C'est le MÊME `customer` que celui de la transaction, pas une
            // seconde construction : deux assemblages se décaleraient, et le
            // formulaire contredirait la transaction qu'il règle.
            'customer' => $customer,
            'environment' => config('services.fedapay.mode') === 'live' ? 'live' : 'sandbox',
            'transaction_id' => (string) $id,
            // ⚠️ **La page hébergée reste accessible**, et ce n'est pas un luxe : si
            // le script du prestataire ne charge pas - réseau, blocage, coupure de
            // son CDN - le payeur doit pouvoir finir quand même. Un formulaire
            // embarqué qui ne s'affiche pas sans porte de sortie, c'est un paiement
            // perdu.
            'url' => $url,
        ];
    }

    /**
     * Pays ISO-2 (attendu par FedaPay) déduit d'un numéro E.164. Couvre la zone
     * XOF et les voisins ; null si l'indicatif est inconnu (FedaPay déduit alors
     * lui-même le pays à partir du numéro).
     */
    private static function countryFromE164(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        // Paires [indicatif, ISO-2] : un tableau associatif transformerait les
        // clés numériques en int (str_starts_with attend une string).
        $byPrefix = [
            ['228', 'tg'], ['229', 'bj'], ['225', 'ci'], ['226', 'bf'], ['223', 'ml'],
            ['221', 'sn'], ['227', 'ne'], ['245', 'gw'], ['234', 'ng'], ['233', 'gh'],
            ['237', 'cm'], ['241', 'ga'], ['242', 'cg'], ['235', 'td'], ['236', 'cf'],
        ];

        foreach ($byPrefix as [$prefix, $iso]) {
            if (str_starts_with($digits, $prefix)) {
                return $iso;
            }
        }

        return null;
    }

    public function verify(Payment $payment): string
    {
        if (blank($payment->gateway_ref)) {
            return 'pending';
        }

        $res = $this->http()->get($this->baseUrl().'/transactions/'.$payment->gateway_ref)->throw()->json();

        return $this->mapStatus($this->unwrap($res)['status'] ?? '');
    }

    /**
     * Le détail de l'encaissement, relevé sur la transaction du prestataire.
     *
     * ⚠️ **`include=payment_method` n'est pas facultatif** : sans lui, le numéro
     * qui a réellement payé n'est pas dans la réponse - seul son identifiant
     * l'est, et l'adresse `/payment_methods/{id}` répond 404. C'est pourtant la
     * seule ligne qui rattache un paiement contesté à un téléphone.
     *
     * ⚠️ **On ne garde NI le jeton NI l'URL du reçu** : le premier autorise le
     * règlement, la seconde en porte un dans son adresse. Ce tableau est écrit
     * en base et lu par un gestionnaire.
     *
     * @return array<string, scalar>
     */
    public function details(Payment $payment): array
    {
        if (blank($payment->gateway_ref)) {
            return [];
        }

        $t = $this->unwrap($this->http()
            ->get($this->baseUrl().'/transactions/'.$payment->gateway_ref, ['include' => 'payment_method'])
            ->throw()->json());

        $moyen = is_array($t['payment_method'] ?? null) ? $t['payment_method'] : [];

        return array_filter([
            // L'opérateur EMPLOYÉ, qui dément parfois celui qu'on avait déduit.
            'operator' => (string) ($t['mode'] ?? $moyen['brand'] ?? ''),
            'operator_label' => $this->mobileOperators()[(string) ($t['mode'] ?? '')] ?? '',
            'operator_ref' => (string) ($t['transaction_key'] ?? ''),
            'payer_phone' => (string) ($moyen['number'] ?? ''),
            'amount_debited' => $t['amount_debited'] ?? '',
            'fees' => $t['fees'] ?? '',
            'paid_at' => (string) ($t['approved_at'] ?? ''),
            'transfer_due_at' => (string) ($t['to_be_transferred_at'] ?? ''),
        ], fn ($v) => filled($v));
    }

    public function parseWebhook(Request $request): ?array
    {
        $secret = (string) config('services.fedapay.webhook_secret');
        $header = (string) $request->header('X-FEDAPAY-Signature', '');

        if ($secret === '' || $header === '') {
            return null; // fail-closed
        }

        // En-tête « t=<timestamp>,s=<signature> ».
        parse_str(str_replace(',', '&', $header), $parts);
        $timestamp = $parts['t'] ?? '';
        $signature = $parts['s'] ?? '';

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        if (! hash_equals($expected, (string) $signature)) {
            return null;
        }

        $payload = $request->json()->all();
        $entity = $payload['entity'] ?? $payload['data']['object'] ?? [];

        $reference = $entity['id'] ?? null;
        if (! $reference) {
            return null;
        }

        return ['reference' => (string) $reference, 'status' => $this->mapStatus($entity['status'] ?? '')];
    }

    /** Normalise le statut FedaPay. */
    protected function mapStatus(string $status): string
    {
        return match (mb_strtolower($status)) {
            'approved', 'transferred' => 'paid',
            // ⚠️ `refunded` est DISTINCT de `failed` : il déclenche la révocation des
            // droits (cf. PaymentController::webhook → RefundPayment), là où `failed`
            // ne fait qu'écarter un paiement jamais abouti.
            'refunded' => 'refunded',
            'declined', 'canceled', 'cancelled', 'expired' => 'failed',
            default => 'pending',
        };
    }
}
