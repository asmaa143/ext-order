<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }
}
