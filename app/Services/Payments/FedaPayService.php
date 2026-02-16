<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FedaPayService implements PaymentServiceInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.fedapay.secret_key');
        $environment = config('services.fedapay.environment', 'sandbox');
        $this->baseUrl = $environment === 'live'
            ? "https://api.fedapay.com/v1"
            : "https://sandbox-api.fedapay.com/v1";
    }

    public function initiatePayment(
        string $transactionId,
        float $amount,
        string $currency,
        string $description,
        array $customer
    ): array {
        // Step 1: Create a Transaction in FedaPay
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/transactions", [
                'description' => $description,
                'amount' => (int) $amount,
                'currency' => [
                    'iso' => $currency
                ],
                'reference' => $transactionId,
                'callback_url' => config('services.fedapay.callback_url'),
                'customer' => [
                    'firstname' => $customer['name'] ?? 'Kofre',
                    'lastname' => $customer['surname'] ?? 'User',
                    'email' => $customer['email'] ?? 'user@kofre.com',
                    'phone_numbers' => [
                        [
                            'number' => $customer['phone'] ?? '+22600000000',
                            'country' => 'bf'
                        ]
                    ]
                ]
            ]);

        if ($response->failed()) {
            Log::error('FedaPay Transaction Creation Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Erreur lors de la création de la transaction FedaPay.');
        }

        $transaction = $response->json()['v1/transaction'];

        // Step 2: Generate a Token for Checkout
        $tokenResponse = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/transactions/{$transaction['id']}/token");

        if ($tokenResponse->failed()) {
            Log::error('FedaPay Token Generation Failed', [
                'status' => $tokenResponse->status(),
                'body' => $tokenResponse->body(),
            ]);
            throw new \Exception('Erreur lors de la génération du token FedaPay.');
        }

        $data = $tokenResponse->json();
        $tokenData = $data['v1/token'] ?? $data;

        if (!isset($tokenData['token'])) {
            Log::error('FedaPay Invalid Token Structure', ['data' => $data]);
            throw new \Exception('Réponse FedaPay invalide : token manquant.');
        }

        return [
            'payment_url' => $tokenData['url'] ?? '',
            'payment_id' => $transaction['id'],
            'payment_token' => $tokenData['token'],
        ];
    }

    public function verifyPayment(string $paymentId): bool
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/transactions/{$paymentId}");

        if ($response->failed()) {
            return false;
        }

        $data = $response->json()['v1/transaction'];

        // FedaPay status 'approved' usually means successful
        return $data['status'] === 'approved' || $data['status'] === 'transferred';
    }
}
