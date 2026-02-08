<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class CreditCardPayment implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $publicKey;
    private string $mode;

    public function __construct()
    {
        $this->secretKey = config('payment.gateways.credit_card.secret_key');
        $this->publicKey = config('payment.gateways.credit_card.public_key');
        $this->mode = config('payment.gateways.credit_card.mode', 'test');

        if (!$this->secretKey) {
            throw new \Exception('Stripe secret key not configured');
        }
    }

    public function process(Payment $payment): array
    {
        try {
            // Use configuration
            // Example: $stripe = new \Stripe\StripeClient($this->secretKey);

            $paymentDetails = $payment->payment_details;

            if (!$this->validateCardDetails($paymentDetails)) {
                return [
                    'success' => false,
                    'transaction_id' => null,
                    'gateway_response' => ['error' => 'Invalid card details'],
                ];
            }

            usleep(500000);
            $isSuccessful = rand(1, 100) <= 95;

            if ($isSuccessful) {
                $transactionId = 'ch_' . uniqid() . '_' . time();

                Log::info('Payment processed', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $transactionId,
                    'mode' => $this->mode,
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'gateway_response' => [
                        'status' => 'succeeded',
                        'mode' => $this->mode,
                        'card_brand' => $this->getCardBrand($paymentDetails['card_number'] ?? ''),
                        'last4' => substr($paymentDetails['card_number'] ?? '', -4),
                    ],
                ];
            }

            return [
                'success' => false,
                'transaction_id' => null,
                'gateway_response' => ['error' => 'Card declined'],
            ];
        } catch (\Exception $e) {
            Log::error('Payment error', ['error' => $e->getMessage()]);
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
            throw new \Exception('No transaction ID for refund');
        }

        $refundId = 're_' . uniqid() . '_' . time();

        return [
            'success' => true,
            'refund_id' => $refundId,
            'gateway_response' => [
                'status' => 'succeeded',
                'amount_refunded' => $payment->amount,
            ],
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'succeeded',
            'gateway_response' => [
                'id' => $transactionId,
                'verified_at' => now()->toIso8601String(),
            ],
        ];
    }

    private function validateCardDetails(array $details): bool
    {
        return isset($details['card_number'], $details['expiry_month'],
            $details['expiry_year'], $details['cvv']);
    }

    private function getCardBrand(string $cardNumber): string
    {
        $firstDigit = substr($cardNumber, 0, 1);
        return match ($firstDigit) {
            '4' => 'Visa',
            '5' => 'Mastercard',
            '3' => 'American Express',
            default => 'Unknown',
        };
    }
}
