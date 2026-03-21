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
    private bool $isSimulation;

    public function __construct()
    {
        $this->masterKey = config('services.paydunya.master_key') ?? '';
        $this->privateKey = config('services.paydunya.private_key') ?? '';
        $this->publicKey = config('services.paydunya.public_key') ?? '';
        $this->token = config('services.paydunya.token') ?? '';
        $this->mode = config('services.paydunya.mode', 'test');
        $this->isSimulation = config('services.paydunya.simulation', false);

        $this->baseUrl = "https://app.paydunya.com/api/v1";
    }

    public function initiatePayment(
        string $transactionId,
        float $amount,
        string $currency,
        string $description,
        array $customer
    ): array {
        if ($this->isSimulation) {
            $fakeToken = 'sim_token_' . bin2hex(random_bytes(8));
            return [
                'payment_url' => url("/payments/success?token={$fakeToken}"),
                'payment_token' => $fakeToken,
            ];
        }

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
                        'cancel_url' => route('payment.callback', ['reference' => $transactionId, 'event' => 'payment.cancelled', 'provider' => 'paydunya']),
                        'return_url' => route('payment.callback', ['reference' => $transactionId, 'event' => 'payment.success', 'provider' => 'paydunya']),
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

        if (($data['response_code'] ?? '') !== '00') {
            Log::error('PayDunya API Error', $data);
            throw new \Exception($data['response_text'] ?? 'Erreur API PayDunya.');
        }

        return [
            'payment_url' => $data['response_text'],
            'payment_token' => $data['token'],
        ];
    }

    public function verifyPayment(string $token): bool
    {
        if ($this->isSimulation || str_starts_with($token, 'sim_token_')) {
            Log::info('PayDunya verifyPayment SIMULATED', ['token' => $token]);
            return true;
        }

        $response = Http::withHeaders([
            'PAYDUNYA-MASTER-KEY' => $this->masterKey,
            'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
            'PAYDUNYA-TOKEN' => $this->token,
        ])->get("{$this->baseUrl}/checkout-invoice/confirm/{$token}");

        if ($response->failed()) {
            return false;
        }

        $data = $response->json();

        return (($data['status'] ?? '') === 'completed' || ($data['status'] ?? '') === 'success');
    }

    public function payout(string $account, float $amount, string $description, ?string $method = null): bool
    {
        if ($this->isSimulation) {
            Log::info('PayDunya payout SIMULATED', [
                'account' => $account,
                'amount' => $amount,
                'description' => $description
            ]);
            return true;
        }

        $withdrawMode = $this->guessWithdrawMode($method ?? '');
        $accountAlias = preg_replace('/[^\d]/', '', $account);
        // PayDunya expects number without country prefix for alias
        if (str_starts_with($accountAlias, '226')) {
            $accountAlias = substr($accountAlias, 3);
        }

        try {
            // Step 1: Get Invoice
            $response = Http::withHeaders([
                'PAYDUNYA-MASTER-KEY' => $this->masterKey,
                'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
                'PAYDUNYA-TOKEN' => $this->token,
            ])->post("https://app.paydunya.com/api/v2/disburse/get-invoice", [
                'account_alias' => $accountAlias,
                'amount' => (int) $amount,
                'withdraw_mode' => $withdrawMode,
                'callback_url' => route('payments.callback', ['provider' => 'paydunya', 'event' => 'payout.completed']),
            ]);

            if ($response->failed() || ($response->json()['response_code'] ?? '') !== '00') {
                Log::error('PayDunya Disburse Get Invoice Failed', [
                    'account' => $accountAlias,
                    'mode' => $withdrawMode,
                    'response' => $response->json(),
                ]);
                return false;
            }

            $disburseToken = $response->json()['disburse_token'];

            // Step 2: Submit Invoice
            $submitResponse = Http::withHeaders([
                'PAYDUNYA-MASTER-KEY' => $this->masterKey,
                'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
                'PAYDUNYA-TOKEN' => $this->token,
            ])->post("https://app.paydunya.com/api/v2/disburse/submit-invoice", [
                'disburse_invoice' => $disburseToken,
            ]);

            if ($submitResponse->failed() || ($submitResponse->json()['response_code'] ?? '') !== '00') {
                Log::error('PayDunya Disburse Submit Invoice Failed', [
                    'token' => $disburseToken,
                    'response' => $submitResponse->json(),
                ]);
                return false;
            }

            $result = $submitResponse->json();
            Log::info('PayDunya Payout Success', ['result' => $result]);

            return in_array($result['status'] ?? '', ['success', 'pending']) || ($result['response_code'] ?? '') === '00';
        } catch (\Exception $e) {
            Log::error('PayDunya Payout Exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    private function guessWithdrawMode(string $method): string
    {
        $method = strtolower($method);
        if (str_contains($method, 'orange')) {
            return 'orange-money-burkina';
        }
        if (str_contains($method, 'moov')) {
            return 'moov-burkina-faso';
        }
        if (str_contains($method, 'wave')) {
            // Wave might not be available for BF for now in PayDunya disburse doc, 
            // but let's default to something if unknown
            return 'orange-money-burkina'; 
        }

        return 'orange-money-burkina';
    }
}
