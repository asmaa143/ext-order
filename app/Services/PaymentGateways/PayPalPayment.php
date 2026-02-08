<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PayPalPayment implements PaymentGatewayInterface
{
    private string $clientId;
    private string $secret;
    private string $mode;

    public function __construct()
    {
        $this->clientId = config('payment.gateways.paypal.client_id');
        $this->secret = config('payment.gateways.paypal.secret');
        $this->mode = config('payment.gateways.paypal.mode', 'sandbox');

        if (!$this->clientId || !$this->secret) {
            throw new \Exception('PayPal credentials not configured');
        }
    }

    public function process(Payment $payment): array
    {
        try {
            $paymentDetails = $payment->payment_details;

            if (!isset($paymentDetails['email'])) {
                return [
                    'success' => false,
                    'transaction_id' => null,
                    'gateway_response' => ['error' => 'PayPal email required'],
                ];
            }

            usleep(700000);
            $isSuccessful = rand(1, 100) <= 90;

            if ($isSuccessful) {
                $transactionId = 'PAYPAL-' . strtoupper(uniqid());

                Log::info('PayPal payment processed', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $transactionId,
                    'mode' => $this->mode,
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'gateway_response' => [
                        'status' => 'COMPLETED',
                        'payer_email' => $paymentDetails['email'],
                        'mode' => $this->mode,
                    ],
                ];
            }

            return [
                'success' => false,
                'transaction_id' => null,
                'gateway_response' => ['error' => 'Payment declined'],
            ];
        } catch (\Exception $e) {
            Log::error('PayPal error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'transaction_id' => null,
                'gateway_response' => ['error' => $e->getMessage()],
            ];
        }
    }

    public function refund(Payment $payment): array
    {
        if (!$payment->transaction_id) {
            throw new \Exception('No transaction ID');
        }

        $refundId = 'REFUND-' . strtoupper(uniqid());

        return [
            'success' => true,
            'refund_id' => $refundId,
            'gateway_response' => [
                'status' => 'COMPLETED',
                'refund_amount' => $payment->amount,
            ],
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'COMPLETED',
            'gateway_response' => [
                'id' => $transactionId,
                'verified_at' => now()->toIso8601String(),
            ],
        ];
    }
}
