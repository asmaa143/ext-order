<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class CashOnDeliveryPayment implements PaymentGatewayInterface
{
    private float $maxAmount;

    public function __construct()
    {
        $this->maxAmount = config('payment.gateways.cash_on_delivery.max_amount');
    }

    public function process(Payment $payment): array
    {
        try {
            if ($payment->amount > $this->maxAmount) {
                return [
                    'success' => false,
                    'transaction_id' => null,
                    'gateway_response' => [
                        'error' => "Amount exceeds COD limit ({$this->maxAmount})",
                    ],
                ];
            }

            $transactionId = 'COD-' . strtoupper(uniqid());

            Log::info('COD registered', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'gateway_response' => [
                    'status' => 'pending_delivery',
                    'amount_to_collect' => $payment->amount,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'transaction_id' => null,
                'gateway_response' => ['error' => $e->getMessage()],
            ];
        }
    }

    public function refund(Payment $payment): array
    {
        $refundId = 'CODR-' . strtoupper(uniqid());

        return [
            'success' => true,
            'refund_id' => $refundId,
            'gateway_response' => ['status' => 'processing'],
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'pending_delivery',
            'gateway_response' => ['id' => $transactionId],
        ];
    }
}

