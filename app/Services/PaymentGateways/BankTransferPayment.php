<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class BankTransferPayment implements PaymentGatewayInterface
{
    private string $processingTime;

    public function __construct()
    {
        $this->processingTime = config('payment.gateways.bank_transfer.processing_time');
    }

    public function process(Payment $payment): array
    {
        try {
            $paymentDetails = $payment->payment_details;

            if (!isset($paymentDetails['account_number'], $paymentDetails['bank_name'])) {
                return [
                    'success' => false,
                    'transaction_id' => null,
                    'gateway_response' => ['error' => 'Bank details required'],
                ];
            }

            $transactionId = 'BT-' . strtoupper(uniqid());

            Log::info('Bank transfer initiated', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'gateway_response' => [
                    'status' => 'pending_verification',
                    'processing_time' => $this->processingTime,
                    'bank_name' => $paymentDetails['bank_name'],
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
        $refundId = 'BTR-' . strtoupper(uniqid());

        return [
            'success' => true,
            'refund_id' => $refundId,
            'gateway_response' => [
                'status' => 'processing',
                'processing_time' => $this->processingTime,
            ],
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'pending_verification',
            'gateway_response' => ['id' => $transactionId],
        ];
    }
}
