<?php

namespace App\Providers;
use App\Services\PaymentGateways\BankTransferPayment;
use App\Services\PaymentGateways\CashOnDeliveryPayment;
use App\Services\PaymentGateways\CreditCardPayment;
use App\Services\PaymentGateways\PayPalPayment;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register payment gateways
        $this->app->bind('payment.credit_card', CreditCardPayment::class);
        $this->app->bind('payment.paypal', PayPalPayment::class);
        $this->app->bind('payment.bank_transfer', BankTransferPayment::class);
        $this->app->bind('payment.cash_on_delivery', CashOnDeliveryPayment::class);
    }

    public function boot(): void
    {
        //
    }
}
