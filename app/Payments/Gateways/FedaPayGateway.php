<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * FedaPay — mobile money (MTN, Moov, Togocom…) et cartes via page hébergée.
 *
 * Flux : POST /transactions → POST /transactions/{id}/token (URL hébergée) →
 * GET /transactions/{id} (status « approved ») ; webhook signé HMAC-SHA256
 * (X-FEDAPAY-Signature « t=…,s=… » sur « {t}.{payload} » avec le secret de webhook).
 *
 * ⚠️ Implémentation d'après l'API publique FedaPay — à valider avec le sandbox
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
        return 'Mobile money / carte (FedaPay)';
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
        $participant = $payment->participant;

        $create = $this->http()->post($this->baseUrl().'/transactions', [
            'description' => 'Frais d\'inscription — '.($payment->edition?->title ?? 'Colloque'),
            'amount' => (int) round((float) $payment->amount),
            'currency' => ['iso' => 'XOF'],
            'callback_url' => route('payment.return', ['gateway' => 'fedapay', 'payment' => $payment->id]),
            'customer' => [
                'firstname' => $participant?->first_name,
                'lastname' => $participant?->last_name,
                'email' => $participant?->email,
            ],
        ])->throw()->json();

        $id = $this->unwrap($create)['id'] ?? null;
        $payment->update(['gateway_ref' => $id]);

        $token = $this->http()->post($this->baseUrl()."/transactions/{$id}/token")->throw()->json();

        return ['type' => 'redirect', 'url' => (string) ($token['url'] ?? '')];
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
