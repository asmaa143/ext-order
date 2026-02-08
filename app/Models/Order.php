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

    /**
     * Scope a query to only include orders of a given status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include orders for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if order can be paid
     */
    public function canBePaid(): bool
    {
        return $this->status === OrderStatusEnum::CONFIRMED;
    }

    /**
     * Check if order can be updated
     */
    public function canBeUpdated(): bool
    {
        return in_array($this->status, [
            OrderStatusEnum::PENDING,
            OrderStatusEnum::CONFIRMED,
        ]);
    }


    /**
     * Update order status
     */
    public function updateStatus(OrderStatusEnum $status): bool
    {
        $this->status = $status;
        return $this->save();
    }
}
