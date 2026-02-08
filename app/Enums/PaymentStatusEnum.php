<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum PaymentStatusEnum: string
{
    use EnumHelpers;
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';


    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::SUCCESSFUL => 'Successful',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if payment is in final state
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::SUCCESSFUL,
            self::FAILED,
            self::REFUNDED,
            self::CANCELLED,
        ]);
    }

}
