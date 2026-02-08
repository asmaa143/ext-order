<?php

namespace App\Services;
use App\Enums\PaymentMethodEnum;
use App\Models\Order;
class GatewaySwitcher
{
    /**
     * Select the best gateway based on order
     */
    public function selectGateway(Order $order, ?string $preferredMethod = null): PaymentMethodEnum
    {
        // User preferred method
        if ($preferredMethod && $this->isGatewayAvailable($preferredMethod)) {
            return PaymentMethodEnum::from($preferredMethod);
        }

        // Amount-based selection
        if ($order->total_amount > config('payment.gateways.cash_on_delivery.max_amount')) {
            // COD not available for high amounts
            return $this->selectOnlineGateway();
        }

        // Default gateway
        return PaymentMethodEnum::from(config('payment.default', 'credit_card'));
    }

    /**
     * Get available gateways for amount
     */
    public function getAvailableGateways(float $amount): array
    {
        $available = [];

        foreach (PaymentMethodEnum::cases() as $method) {
            if ($this->isGatewayAvailable($method->value, $amount)) {
                $available[] = [
                    'method' => $method->value,
                    'label' => $method->label(),
                    'enabled' => true,
                ];
            }
        }

        return $available;
    }

    /**
     * Check if gateway is available
     */
    public function isGatewayAvailable(string $method, ?float $amount = null): bool
    {
        $gateway = "payment.gateways.{$method}";

        // Check if enabled
        if (!config("{$gateway}.enabled", false)) {
            return false;
        }

        // COD amount limit
        if ($method === 'cash_on_delivery' && $amount) {
            $maxAmount = config("{$gateway}.max_amount", 0);
            if ($amount > $maxAmount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Select online gateway (credit card or paypal)
     */
    private function selectOnlineGateway(): PaymentMethodEnum
    {
        // Try credit card first
        if ($this->isGatewayAvailable('credit_card')) {
            return PaymentMethodEnum::CREDIT_CARD;
        }

        // Fallback to PayPal
        if ($this->isGatewayAvailable('paypal')) {
            return PaymentMethodEnum::PAYPAL;
        }

        throw new \Exception('No online payment gateway available');
    }

    /**
     * Get gateway recommendation based on user history
     */
    public function recommendGateway(int $userId): ?PaymentMethodEnum
    {
        // Get user's most used gateway
        $mostUsed = \DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->where('payments.status', 'successful')
            ->select('payment_method', \DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->orderByDesc('count')
            ->first();

        if ($mostUsed && $this->isGatewayAvailable($mostUsed->payment_method)) {
            return PaymentMethodEnum::from($mostUsed->payment_method);
        }

        return null;
    }
}
