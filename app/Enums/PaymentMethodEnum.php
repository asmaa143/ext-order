<?php

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case CREDIT_CARD = 'credit_card';
    case PAYPAL = 'paypal';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH_ON_DELIVERY = 'cash_on_delivery';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Credit Card',
            self::PAYPAL => 'PayPal',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CASH_ON_DELIVERY => 'Cash on Delivery',
        };
    }

    public function serviceKey(): string
    {
        return 'payment.' . $this->value;
    }
}
