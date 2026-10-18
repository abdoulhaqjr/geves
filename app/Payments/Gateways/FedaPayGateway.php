<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use App\Support\MobileNetwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FedaPay - mobile money (MTN, Moov, Togocom…) et carte bancaire via page hébergée.
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
    /**
     * Le payeur annonce qu'il règlera par carte. ⚠️ **Ce code ne quitte jamais la
     * plateforme** : aucun code carte n'est documenté côté prestataire, le champ
     * `mode` ne désignant que des opérateurs mobile money. Voir `initiate()`.
     */
    public const CARD = 'card';

    public function key(): string
    {
        return 'fedapay';
    }

    public function label(): string
    {
        // ⚠️ Convention projet : on n'expose jamais le nom de l'agrégateur au payeur.
        return 'Mobile money / carte bancaire';
    }

    /**
     * Opérateurs mobile money ciblables (codes `mode` du prestataire). Sélectionner
     * l'opérateur ici évite au payeur de le rechercher sur la page suivante.
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

    /**
     * Mobile money **et carte bancaire**, quand le compte marchand l'accepte
     * (réglage `services.fedapay.card`).
     *
     * ⚠️ La carte est en TÊTE : c'est le seul moyen qui ne se devine pas depuis un
     * numéro de téléphone, et il était jusqu'ici invisible - la liste ne portait
     * que des opérateurs, si bien qu'on croyait devoir en choisir un.
     *
     * @return array<string, string>
     */
    public function paymentMethods(): array
    {
        $carte = config('services.fedapay.card', true)
            ? [self::CARD => 'Carte bancaire (Visa / Mastercard)']
            : [];

        return $carte + $this->mobileOperators();
    }

    /**
     * L'opérateur déduit du numéro, traduit dans le vocabulaire du prestataire.
     *
     * ⚠️ **Deux vocabulaires, une traduction explicite.** `MobileNetwork` nomme
     * les réseaux comme le font les régulateurs ; le prestataire a ses propres
     * codes `mode`, et n'en a pas pour tout le monde - ni Orange Côte d'Ivoire,
     * ni Orange Sénégal, ni Moov Niger. Ce qu'il ne sait pas cibler ne se déduit
     * pas : le payeur choisit, comme avant.
     *
     * ⚠️ **Et jamais un code qu'on n'offre plus.** Le dernier filtre tient la
     * promesse de `mobileOperators()`, seule liste qui part dans la requête :
     * sans lui, retirer un opérateur de la liste laisserait son code se glisser
     * dans `mode` par cette porte.
     */
    public function operatorFromPhone(?string $phone): ?string
    {
        $code = match (MobileNetwork::fromPhone($phone)) {
            'togocom' => 'togocel',
            'moov-tg' => 'moov_tg',
            'mtn-bj' => 'mtn_open',
            'moov-bj' => 'moov',
            'celtiis-bj' => 'sbin',
            'mtn-ci' => 'mtn_ci',
            'free-sn' => 'free_sn',
            'airtel-ne' => 'airtel_ne',
            default => null,
        };

        return $code !== null && array_key_exists($code, $this->mobileOperators()) ? $code : null;
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

        // Moyen choisi chez nous : la page du prestataire arrive pré-sélectionnée.
        //
        // ⚠️ **La CARTE ne se transmet PAS en `mode`.** Ce champ ne désigne que des
        // opérateurs mobile money ; aucun code carte n'est documenté. Omis, il laisse
        // la page hébergée proposer tout ce que le compte marchand accepte - carte
        // comprise, ce qui est précisément ce qu'on veut. Inventer un code ferait
        // échouer la création de la transaction, donc le paiement ENTIER.
        //
        // ⚠️ Le filtre porte sur `mobileOperators()`, pas sur `paymentMethods()` :
        // c'est la liste de ce qui est TRANSMISSIBLE. Les confondre enverrait
        // « card » au prestataire.
        $operator = (string) ($payment->meta['operator'] ?? '');
        if ($operator !== '' && array_key_exists($operator, $this->mobileOperators())) {
            $body['mode'] = $operator;
        }

        $create = $this->http()->post($this->baseUrl().'/transactions', $body)->throw()->json();

        $id = $this->unwrap($create)['id'] ?? null;
        $payment->update(['gateway_ref' => $id]);

        $token = $this->http()->post($this->baseUrl()."/transactions/{$id}/token")->throw()->json();
        $url = (string) ($token['url'] ?? '');

        // ⚠️⚠️ **LE PAIEMENT DIRECT : aucun formulaire, la demande part sur le
        // téléphone.** C'est le seul endroit où `mode` sert vraiment - sur la page
        // de paiement il n'a aucun effet, mesuré trois fois en conditions réelles.
        // On supprime ainsi les deux écrans que nous ne maîtrisons pas : le
        // sélecteur d'opérateur (qui retombait sur « Mtn Bénin ») et la case
        // « Je veux recevoir mon reçu par mail », tous deux chez le prestataire.
        // ⚠️ Pas de garde sur `$operator` ici : c'est `pousseSurLeTelephone()` qui
        // la porte, et une seule fois. Doublée, elle devient une condition qu'aucun
        // sabotage ne fait tomber - donc une ligne qu'on croit protectrice et qui
        // ne protège rien. Mesuré : le cas restait vert sans elle.
        if (filled($payer['phone']) && $this->pousseSurLeTelephone((string) ($token['token'] ?? ''), $operator, $id)) {
            return [
                'type' => 'push',
                'transaction_id' => (string) $id,
                'operator' => $operator,
                'phone' => (string) $payer['phone'],
                // ⚠️ **La porte de sortie reste ouverte**, et elle l'est encore plus
                // ici : une demande poussée peut ne jamais arriver (réseau de
                // l'opérateur, téléphone éteint), et le payeur n'aurait alors
                // RIEN - ni formulaire, ni message. La page hébergée le rattrape.
                'url' => $url,
            ];
        }

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
            // ⚠️ Et l'opérateur, quand le payeur en a choisi un. Il est déjà sur
            // la **transaction** (`$body['mode']`), mais rien ne dit que le
            // formulaire embarqué la relise jusque-là : on le lui donne aussi en
            // clair. `checkout.js` transmet toutes ses options au formulaire, et
            // `mode` est le nom que le prestataire emploie lui-même pour cela.
            // ⚠️ Ce second canal n'est pas documenté : s'il est ignoré, rien ne
            // change - la transaction, elle, porte toujours le choix.
            'mode' => (string) ($body['mode'] ?? ''),
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
     * Envoie la demande de paiement **sur le téléphone du payeur**, sans aucune
     * page : `POST /v1/{mode}` avec le jeton de la transaction (c'est le
     * `sendNowWithToken($mode, $token)` de leur SDK).
     *
     * ⚠️ **Fail-open, et c'est voulu** : si cet appel échoue - opérateur qui ne
     * répond pas, méthode fermée sur le compte marchand, réseau - on rend `false`
     * et l'appelant repart sur le formulaire. Laisser l'exception remonter
     * priverait le payeur de TOUT moyen de régler, pour un canal qui n'est qu'un
     * raccourci. Le reste du projet est fail-closed sur les STATUTS ; ici on ne
     * décide d'aucun statut, on sonne à une porte.
     *
     * ⚠️ Et cet appel ne dit **jamais** qu'un paiement a réussi : il dit qu'une
     * demande est partie. Le verdict vient de `verify()`, comme partout ailleurs.
     *
     * ⚠️⚠️ **UN REPLI SILENCIEUX EST UN REPLI QU'ON NE PEUT PAS DIAGNOSTIQUER.**
     * Mesuré en conditions réelles : l'opérateur était bien enregistré, la
     * transaction bien créée, et le payeur retombait pourtant sur le formulaire -
     * **sans une ligne nulle part**. On ne savait ni si le prestataire avait
     * refusé, ni pourquoi. Tout repli est donc journalisé dans le canal
     * `payments`, celui qu'on vient consulter pour un « j'ai payé mais le système
     * dit non ».
     *
     * ⚠️ **Le JETON n'est pas journalisé** : il autorise le règlement, c'est un
     * secret de paiement au même titre qu'une clé. On journalise la référence de
     * la transaction, qui suffit à retrouver la trace chez le prestataire.
     */
    protected function pousseSurLeTelephone(string $token, string $mode, mixed $reference = null): bool
    {
        if ($token === '' || ! array_key_exists($mode, $this->mobileOperators())) {
            Log::channel('payments')->warning('Paiement direct impossible : jeton ou opérateur inexploitable', [
                'transaction' => $reference,
                'operateur' => $mode,
                'jeton_present' => $token !== '',
            ]);

            return false;
        }

        try {
            $reponse = $this->http()
                ->timeout(20)
                ->post($this->baseUrl().'/'.$mode, ['token' => $token]);

            if ($reponse->successful()) {
                return true;
            }

            Log::channel('payments')->warning('Paiement direct refusé par le prestataire', [
                'transaction' => $reference,
                'operateur' => $mode,
                'statut' => $reponse->status(),
                // Tronqué : une page d'erreur HTML entière noierait le journal.
                'reponse' => mb_substr($reponse->body(), 0, 500),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::channel('payments')->warning('Paiement direct injoignable', [
                'transaction' => $reference,
                'operateur' => $mode,
                'erreur' => $e->getMessage(),
            ]);

            return false;
        }
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
            'declined', 'canceled', 'cancelled', 'expired', 'refunded' => 'failed',
            default => 'pending',
        };
    }
}
