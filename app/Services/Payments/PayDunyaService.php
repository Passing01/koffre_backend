<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayDunyaService implements PaymentServiceInterface
{
    private string $masterKey;
    private string $privateKey;
    private string $publicKey;
    private string $token;
    private string $mode;
    private string $baseUrl;

    public function __construct()
    {
        $this->masterKey = config('services.paydunya.master_key');
        $this->privateKey = config('services.paydunya.private_key');
        $this->publicKey = config('services.paydunya.public_key');
        $this->token = config('services.paydunya.token');
        $this->mode = config('services.paydunya.mode', 'test');

        $this->baseUrl = "https://app.paydunya.com/api/v1";
    }

    public function initiatePayment(
        string $transactionId,
        float $amount,
        string $currency,
        string $description,
        array $customer
    ): array {
        $response = Http::withHeaders([
            'PAYDUNYA-MASTER-KEY' => $this->masterKey,
            'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
            'PAYDUNYA-TOKEN' => $this->token,
        ])->post("{$this->baseUrl}/checkout-invoice/create", [
                    'invoice' => [
                        'total_amount' => (float) $amount,
                        'description' => $description,
                    ],
                    'store' => [
                        'name' => 'Kofre',
                    ],
                    'custom_data' => [
                        'transaction_id' => $transactionId,
                    ],
                    'actions' => [
                        'cancel_url' => url('/payments/cancel'),
                        'return_url' => url('/payments/success'),
                    ]
                ]);

        if ($response->failed()) {
            Log::error('PayDunya Invoice Creation Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Erreur lors de la création de la facture PayDunya.');
        }

        $data = $response->json();

        if ($data['response_code'] !== '00') {
            Log::error('PayDunya API Error', $data);
            throw new \Exception($data['response_text'] ?? 'Erreur API PayDunya.');
        }

        return [
            'payment_url' => $data['response_text'], // PayDunya returns URL in response_text for successful invoice creation
            'payment_token' => $data['token'],
        ];
    }

    public function verifyPayment(string $token): bool
    {
        $response = Http::withHeaders([
            'PAYDUNYA-MASTER-KEY' => $this->masterKey,
            'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
            'PAYDUNYA-TOKEN' => $this->token,
        ])->get("{$this->baseUrl}/checkout-invoice/confirm/{$token}");

        if ($response->failed()) {
            return false;
        }

        $data = $response->json();

        return ($data['status'] === 'completed' || $data['status'] === 'success');
    }
}
