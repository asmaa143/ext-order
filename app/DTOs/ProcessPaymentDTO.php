<?php

namespace App\DTOs;

class ProcessPaymentDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $paymentMethod,
        public readonly float $amount,
        public readonly ?array $paymentDetails = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            paymentMethod: $data['payment_method'],
            amount: $data['amount'],
            paymentDetails: $data['payment_details'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'payment_method' => $this->paymentMethod,
            'amount' => $this->amount,
            'payment_details' => $this->paymentDetails,
        ];
    }
}
