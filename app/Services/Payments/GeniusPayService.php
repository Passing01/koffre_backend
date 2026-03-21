<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeniusPayService implements PaymentServiceInterface
{
    private string $publicKey;
    private string $secretKey;
    private string $walletId;
    private string $baseUrl;
    private bool $isSimulation;
    private ?string $defaultMethod;
    private ?int $centralization;
    private ?string $country;

    public function __construct(?string $defaultMethod = null)
    {
        $this->publicKey = config('services.geniuspay.public_key') ?? '';
        $this->secretKey = config('services.geniuspay.secret_key') ?? '';
        $this->walletId = config('services.geniuspay.wallet_id') ?? '';
        $this->baseUrl = config('services.geniuspay.base_url', 'https://pay.genius.ci/api/v1/merchant');
        $this->isSimulation = config('services.geniuspay.simulation', false);
        $this->defaultMethod = $defaultMethod ?? config('services.geniuspay.gateway');
        $this->centralization = (int) config('services.geniuspay.centralization', 70);
        $this->country = config('services.geniuspay.country', 'BF');
    }

    /**
     * @param string $transactionId
     * @param float $amount
     * @param string $currency
     * @param string $description
     * @param array $customer
     * @return array contains 'payment_url' and 'payment_id'
     */
    public function initiatePayment(
        string $transactionId,
        float $amount,
        string $currency,
        string $description,
        array $customer
    ): array {
        if ($this->isSimulation) {
            $fakeToken = 'sim_genius_' . bin2hex(random_bytes(8));
            return [
                'payment_url' => url("/payments/success?token={$fakeToken}"),
                'payment_id' => $fakeToken,
                'payment_token' => $fakeToken,
            ];
        }

        // GeniusPay doc: success_url et error_url uniquement pour la redirection navigateur
        $successUrl = route('payment.callback', ['reference' => $transactionId, 'event' => 'payment.success', 'provider' => 'geniuspay']);
        $errorUrl = route('payment.callback', ['reference' => $transactionId, 'event' => 'payment.cancelled', 'provider' => 'geniuspay']);

        $payload = [
            'amount' => (int) $amount,
            'currency' => $currency,
            'description' => $description,
            'success_url' => $successUrl,
            'error_url' => $errorUrl,
            'customer' => [
                'name' => $customer['name'] ?? 'Kofre User',
                'email' => $customer['email'] ?? 'user@kofre.com',
                'phone' => $this->formatPhone($customer['phone'] ?? '', $this->country),
            ],
            'metadata' => [
                'transaction_id' => $transactionId,  // Notre référence interne (KOF-... ou TON-...)
                'order_id' => $transactionId,         // Alias pour compatibilité avec le payload webhook
            ],
        ];

        if ($this->country !== null) {
            $payload['customer']['country'] = $this->country;
        }

        if ($this->defaultMethod !== null) {
            $payload['gateway'] = $this->defaultMethod;
        }

        if ($this->centralization !== null) {
            $payload['centralization_rate'] = $this->centralization; // ou 'centralization' selon l'API exacte
        }

        Log::info('GeniusPay Initiate Payment', [
            'reference' => $transactionId,
            'payload' => $payload,
        ]);

        $response = Http::withHeaders([
            'X-API-Key' => $this->publicKey,
            'X-API-Secret' => $this->secretKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/payments", $payload);

        Log::info('GeniusPay Response', [
            'reference' => $transactionId,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->failed()) {
            Log::error('GeniusPay Payment Initialization Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Erreur lors de l\'initialisation du paiement GeniusPay.');
        }

        $result = $response->json();

        // According to docs, response contains a 'data' object with 'checkout_url' and 'reference'
        $data = $result['data'] ?? [];

        if (empty($data['checkout_url'])) {
            Log::error('GeniusPay API Error: Empty checkout URL', $result);
            throw new \Exception($result['message'] ?? 'Erreur API GeniusPay.');
        }

        return [
            'payment_url' => $data['checkout_url'],
            'payment_id' => $data['reference'] ?? $transactionId,
            'payment_token' => $data['reference'] ?? $transactionId,
        ];
    }

    private function formatPhone(string $phone, ?string $country): string
    {
        if (empty($phone)) return '';
        
        // Remove all non-digits except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        if (str_starts_with($phone, '+')) return $phone;
        if (str_starts_with($phone, '00')) return '+' . substr($phone, 2);
        
        $prefixes = [
            'BF' => '+226',
            'CI' => '+225',
            'SN' => '+221',
            'BJ' => '+229',
            'ML' => '+223',
            'NE' => '+227',
            'TG' => '+228',
            'CM' => '+237',
        ];

        $prefix = $prefixes[strtoupper($country ?? 'BF')] ?? '+226';
        
        return $prefix . ltrim($phone, '0');
    }

    /**
     * @param string $paymentId
     * @return bool
     */
    public function verifyPayment(string $paymentId): bool
    {
        if ($this->isSimulation || str_starts_with($paymentId, 'sim_genius_')) {
            Log::info('GeniusPay verifyPayment SIMULATED', ['payment_id' => $paymentId]);
            return true;
        }

        $response = Http::withHeaders([
            'X-API-Key' => $this->publicKey,
            'X-API-Secret' => $this->secretKey,
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/payments/{$paymentId}");

        if ($response->failed()) {
            Log::error('GeniusPay Payment Verification Failed', [
                'payment_id' => $paymentId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        $result = $response->json();
        $status = $result['data']['status'] ?? '';

        return in_array($status, ['completed', 'success', 'paid']);
    }

    /**
     * @param string $account (phone or bank account)
     * @param float $amount
     * @param string $description
     * @return bool
     */
    public function payout(string $account, float $amount, string $description, ?string $method = null): bool
    {
        if ($this->isSimulation) {
            Log::info('GeniusPay payout SIMULATED', [
                'account'     => $account,
                'amount'      => $amount,
                'description' => $description,
                'method'      => $method
            ]);
            return true;
        }

        // Vérification wallet_id
        if (empty($this->walletId)) {
            Log::error('GeniusPay Payout Error: GENIUSPAY_WALLET_ID non configuré dans .env', [
                'account' => $account,
                'amount'  => $amount,
            ]);
            throw new \Exception('La configuration GENIUSPAY_WALLET_ID est manquante. Veuillez la définir dans le fichier .env.');
        }

        $payoutAccount = $this->formatPhone($account, $this->country);
        $provider = $this->guessProvider($payoutAccount, $method);

        $payload = [
            'wallet_id'      => $this->walletId,
            'amount'         => (int) $amount,
            'currency'       => 'XOF',
            'description'    => $description,
            'destination'    => [
                'type'     => 'mobile_money',
                'provider' => $provider,
                'account'  => $payoutAccount,
            ],
            'recipient'      => [
                'name'  => 'Kofre User',
                'phone' => $payoutAccount,
            ],
            'idempotency_key' => 'payout_' . md5($account . $amount . $description),
        ];

        Log::info('GeniusPay Payout Request', [
            'account'   => $payoutAccount,
            'provider'  => $provider,
            'amount'    => $amount,
            'wallet_id' => $this->walletId,
            'payload'   => $payload,
        ]);

        $response = Http::withHeaders([
            'X-API-Key'    => $this->publicKey,
            'X-API-Secret' => $this->secretKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/payouts", $payload);

        if ($response->failed()) {
            Log::error('GeniusPay Payout Failed', [
                'account' => $payoutAccount,
                'provider' => $provider,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        $result = $response->json();
        $status = $result['data']['status'] ?? '';

        return in_array($status, ['completed', 'success', 'pending']);
    }

    private function guessProvider(string $account, ?string $method = null): string
    {
        $country = strtoupper($this->country ?? 'BF');
        $suffixes = [
            'BF' => 'bfa',
            'CI' => 'civ',
            'SN' => 'sen',
            'BJ' => 'ben',
            'ML' => 'mli',
            'NE' => 'ner',
            'TG' => 'tgo',
        ];
        $suffix = $suffixes[$country] ?? 'bfa';

        if ($method) {
            // Map common methods to GeniusPay providers
            $method = strtolower($method);
            if (str_contains($method, 'wave'))
                return 'wave_' . $suffix;
            if (str_contains($method, 'orange'))
                return 'orange_money_' . $suffix;
            if (str_contains($method, 'mtn'))
                return 'mtn_money_' . $suffix;
            if (str_contains($method, 'moov'))
                return 'moov_money_' . $suffix;
            if ($method === 'cinetpay')
                return 'cinetpay';
        }

        // Default to wave if unknown
        return 'wave_' . $suffix;
    }
}
