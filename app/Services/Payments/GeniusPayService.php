<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeniusPayService implements PaymentServiceInterface
{
    private string $publicKey;
    private string $secretKey;
    private string $baseUrl = "https://pay.genius.ci/api/v1/merchant";
    private bool $isSimulation;
    private ?string $defaultMethod;

    public function __construct(?string $defaultMethod = null)
    {
        $this->publicKey = config('services.geniuspay.public_key') ?? '';
        $this->secretKey = config('services.geniuspay.secret_key') ?? '';
        $this->isSimulation = config('services.geniuspay.simulation', false);
        $this->defaultMethod = $defaultMethod;
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

        $response = Http::withHeaders([
            'X-API-Key' => $this->publicKey,
            'X-API-Secret' => $this->secretKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/payments", [
                    'amount' => (int) $amount,
                    'currency' => $currency,
                    'description' => $description,
                    'success_url' => url('/payments/success'),
                    'error_url' => url('/payments/cancel'),
                    'customer' => [
                        'name' => $customer['name'] ?? 'Kofre User',
                        'email' => $customer['email'] ?? 'user@kofre.com',
                        'phone' => $customer['phone'] ?? '',
                    ],
                    'metadata' => [
                        'transaction_id' => $transactionId,
                    ],
                    'payment_method' => $this->defaultMethod,
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
                'account' => $account,
                'amount' => $amount,
                'description' => $description,
                'method' => $method
            ]);
            return true;
        }

        $provider = $this->guessProvider($account, $method);

        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/payouts", [
                'amount' => (int) $amount,
                'currency' => 'XOF',
                'description' => $description,
                'destination' => [
                    'type' => 'mobile_money',
                    'provider' => $provider,
                    'account' => $account,
                ],
                'recipient' => [
                    'name' => 'Kofre User',
                    'phone' => $account,
                ],
                'idempotency_key' => 'payout_' . time() . '_' . $account,
            ]);

        if ($response->failed()) {
            Log::error('GeniusPay Payout Failed', [
                'account' => $account,
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
        if ($method) {
            // Map common methods to GeniusPay providers
            $method = strtolower($method);
            if (str_contains($method, 'wave'))
                return 'wave';
            if (str_contains($method, 'orange'))
                return 'orange_money_ci';
            if (str_contains($method, 'mtn'))
                return 'mtn_money_ci';
            if (str_contains($method, 'moov'))
                return 'moov_money_ci';
            if ($method === 'cinetpay')
                return 'cinetpay';
        }

        // Default to wave if unknown
        return 'wave';
    }
}
