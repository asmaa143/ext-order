<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total_amount',
        'status',
        'notes',
    ];
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:3',
            'status' => OrderStatusEnum::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }
    public function calculateTotal(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });
    }
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($order) {
            if ($order->payments()->exists()) {
                throw new \Exception('Cannot delete order with associated payments');
            }
        });
    }
}
