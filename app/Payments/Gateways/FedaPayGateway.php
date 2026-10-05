<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * FedaPay - mobile money (MTN, Moov, Togocom…) et cartes via page hébergée.
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
        return 'Mobile money / carte bancaire';
    }

    /**
     * Opérateurs mobile money ciblables (codes `mode` du prestataire). Sélectionner
     * l'opérateur ici évite au payeur de le rechercher sur la page suivante.
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
            'bmo' => 'BESTCASH Money (Bénin)',
            'coris_money' => 'Coris Money',
            'mtn_open_ci' => 'MTN (Côte d’Ivoire)',
            'wave_direct_ci' => 'Wave (Côte d’Ivoire)',
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

        // Opérateur choisi chez nous : la page du prestataire arrive pré-sélectionnée.
        $operator = (string) ($payment->meta['operator'] ?? '');
        if ($operator !== '' && array_key_exists($operator, $this->mobileOperators())) {
            $body['mode'] = $operator;
        }

        $create = $this->http()->post($this->baseUrl().'/transactions', $body)->throw()->json();

        $id = $this->unwrap($create)['id'] ?? null;
        $payment->update(['gateway_ref' => $id]);

        $token = $this->http()->post($this->baseUrl()."/transactions/{$id}/token")->throw()->json();

        return ['type' => 'redirect', 'url' => (string) ($token['url'] ?? '')];
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
