<?php

namespace App\Services\Payments;

interface PaymentServiceInterface
{
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
    ): array;

    /**
     * @param string $paymentId
     * @return bool
     */
    public function verifyPayment(string $paymentId): bool;
}
