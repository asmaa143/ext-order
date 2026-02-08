<?php

namespace App\Contracts;

use App\Models\Payment;

/**
 * Payment Gateway Interface
 *
 * All payment gateways must implement this interface to ensure
 * consistency and extensibility across different payment methods.
 */
interface PaymentGatewayInterface
{
    /**
     * Process a payment through the gateway
     *
     * @param Payment $payment The payment model to process
     * @return array Returns array with keys: success (bool), transaction_id (string), gateway_response (array)
     */
    public function process(Payment $payment): array;

    /**
     * Refund a processed payment
     *
     * @param Payment $payment The payment to refund
     * @return array Returns array with keys: success (bool), refund_id (string), gateway_response (array)
     */
    public function refund(Payment $payment): array;

    /**
     * Verify a payment transaction with the gateway
     *
     * @param string $transactionId The transaction ID to verify
     * @return array Returns array with keys: success (bool), status (string), gateway_response (array)
     */
    public function verify(string $transactionId): array;
}
