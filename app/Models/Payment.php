<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'payment_method',
        'amount',
        'status',
        'transaction_id',
        'payment_details',
        'gateway_response',
        'processed_at',
    ];
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:3',
            'status' => PaymentStatusEnum::class,
            'payment_method' => PaymentMethodEnum::class,
            'payment_details' => 'array',
            'gateway_response' => 'array',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',

        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
