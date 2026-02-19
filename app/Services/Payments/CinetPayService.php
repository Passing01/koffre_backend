<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CinetPayService implements PaymentServiceInterface
{
    private string $apiKey;
    private string $siteId;
    private string $baseUrl = "https://api-checkout.cinetpay.com/v2/payment";

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
    }

    public function initiatePayment(
        string $transactionId,
        float $amount,
        string $currency,
        string $description,
        array $customer
    ): array {
        $response = Http::post($this->baseUrl, [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
            'amount' => (int) $amount,
            'currency' => $currency,
            'description' => $description,
            'notify_url' => config('services.cinetpay.notify_url'),
            'return_url' => config('services.cinetpay.return_url'),
            'channels' => 'ALL', // Enable all: MOBILE_MONEY, CARD, etc.
            'customer_name' => $customer['name'] ?? 'Kofre',
            'customer_surname' => $customer['surname'] ?? 'User',
            'customer_phone_number' => $customer['phone'] ?? '',
            'customer_email' => $customer['email'] ?? 'user@kofre.com',
            'customer_address' => $customer['address'] ?? 'Burkina Faso',
            'customer_city' => $customer['city'] ?? 'Ouagadougou',
            'customer_country' => 'BF',
            'customer_state' => 'BF',
            'customer_zip_code' => '00226',
        ]);

        if ($response->failed()) {
            Log::error('CinetPay Payment Initialization Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Erreur lors de l\'initialisation du paiement CinetPay.');
        }

        $data = $response->json();

        if ($data['code'] !== '201') {
            Log::error('CinetPay API Error', $data);
            throw new \Exception($data['message'] ?? 'Erreur API CinetPay.');
        }

        return [
            'payment_url' => $data['data']['payment_url'],
            'payment_token' => $data['data']['payment_token'],
        ];
    }

    public function verifyPayment(string $token): bool
    {
        $response = Http::post("{$this->baseUrl}/check", [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'token' => $token,
        ]);

        if ($response->failed()) {
            return false;
        }

        $data = $response->json();

        return ($data['code'] === '00' || $data['message'] === 'SUCCES');
    }

    public function payout(string $account, float $amount, string $description, ?string $method = null): bool
    {
        Log::warning('CinetPay payout called but not implemented.');
        return false;
    }
}
