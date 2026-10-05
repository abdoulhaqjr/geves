<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CashPay (SEMOA) - OAuth 2.0, facture hébergée (bill_url) et callback JWT.
 *
 * Doc : POST /auth → access_token ; POST /orders → { order_reference, bill_url } ;
 * GET /orders/{ref} → { state } ; callback = corps JWT signé HS256 avec l'API key.
 * Montants en unité mineure XOF (entiers = FCFA).
 */
class CashpayGateway implements OnlineGateway
{
    public function key(): string
    {
        return 'cashpay';
    }

    public function label(): string
    {
        // ⚠️ Convention projet : on n'expose jamais le nom de l'agrégateur au payeur.
        return 'Mobile money';
    }

    /**
     * Le prestataire ne permet pas de cibler l'opérateur en amont : il est choisi
     * sur sa propre page.
     *
     * @return array<string, string>
     */
    public function mobileOperators(): array
    {
        return [];
    }

    public function isAvailable(): bool
    {
        $c = config('services.cashpay');

        return filled($c['base_url'] ?? null)
            && filled($c['username'] ?? null)
            && filled($c['password'] ?? null)
            && filled($c['client_secret'] ?? null);
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.cashpay.base_url'), '/');
    }

    /** Jeton OAuth mis en cache jusqu'à peu avant expiration. */
    protected function token(): string
    {
        return Cache::remember('cashpay.token', now()->addMinutes(50), function () {
            $c = config('services.cashpay');
            $res = Http::acceptJson()->post($this->baseUrl().'/auth', [
                'username' => $c['username'],
                'password' => $c['password'],
                'client_id' => $c['client_id'] ?? 'cashpay',
                'client_secret' => $c['client_secret'],
            ])->throw()->json();

            return (string) ($res['access_token'] ?? '');
        });
    }

    public function initiate(Payment $payment): array
    {
        $payer = $payment->payerContact();

        $res = Http::acceptJson()
            ->withToken($this->token())
            ->post($this->baseUrl().'/orders', [
                'amount' => (int) round((float) $payment->amount),
                'currency' => 'XOF',
                'merchant_reference' => 'GESCO-'.$payment->id,
                'description' => 'Frais d\'inscription - '.($payment->event?->title ?? 'Événement'),
                'callback_url' => route('payment.webhook', ['gateway' => 'cashpay']),
                'redirect_url' => route('payment.return', ['gateway' => 'cashpay', 'payment' => $payment->id]),
                // Pré-remplit la page CashPay (repli sur le compte lié si le
                // participant ne porte pas la donnée).
                'client' => array_filter([
                    'firstname' => $payer['firstname'],
                    'lastname' => $payer['lastname'],
                    'phone' => $payer['phone'],
                ], fn ($v) => filled($v)),
                'type_notif' => ['MAIL'],
            ])->throw()->json();

        $payment->update(['gateway_ref' => $res['order_reference'] ?? null]);

        return ['type' => 'redirect', 'url' => (string) ($res['bill_url'] ?? '')];
    }

    public function verify(Payment $payment): string
    {
        if (blank($payment->gateway_ref)) {
            return 'pending';
        }

        $res = Http::acceptJson()
            ->withToken($this->token())
            ->get($this->baseUrl().'/orders/'.$payment->gateway_ref)
            ->throw()->json();

        return $this->mapState($res['state'] ?? '');
    }

    public function parseWebhook(Request $request): ?array
    {
        $apiKey = (string) config('services.cashpay.api_key');
        if ($apiKey === '') {
            return null; // fail-closed : pas de clé => on ne fait rien
        }

        $token = $request->input('token');
        if (! $token) {
            $body = trim($request->getContent());
            $json = json_decode($body, true);
            $token = is_array($json) && isset($json['token']) ? $json['token'] : $body;
        }

        try {
            $data = (array) JWT::decode((string) $token, new Key($apiKey, 'HS256'));
        } catch (\Throwable $e) {
            Log::warning('CashPay webhook: JWT invalide', ['error' => $e->getMessage()]);

            return null;
        }

        $reference = $data['order_reference'] ?? null;
        if (! $reference) {
            return null;
        }

        return ['reference' => (string) $reference, 'status' => $this->mapState($data['state'] ?? '')];
    }

    /** Normalise l'état CashPay (Pending/Paid/Partial/Error/Excess). */
    protected function mapState(string $state): string
    {
        return match (mb_strtolower($state)) {
            'paid', 'excess' => 'paid',
            'error' => 'failed',
            default => 'pending',
        };
    }
}
