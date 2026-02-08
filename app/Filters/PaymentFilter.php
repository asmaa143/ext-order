<?php

namespace App\Filters;

class PaymentFilter extends QueryFilter
{
    public function orderId($value)
    {
        $this->builder->where('order_id', $value);
    }

    public function status($value)
    {
        $this->builder->where('status', $value);
    }

    public function paymentMethod($value)
    {
        $this->builder->where('payment_method', $value);
    }

    public function fromDate($value)
    {
        $this->builder->whereDate('created_at', '>=', $value);
    }

    public function toDate($value)
    {
        $this->builder->whereDate('created_at', '<=', $value);
    }

    public function minAmount($value)
    {
        $this->builder->where('amount', '>=', $value);
    }

    public function maxAmount($value)
    {
        $this->builder->where('amount', '<=', $value);
    }

    public function transactionId($value)
    {
        $this->builder->where('transaction_id', 'like', "%{$value}%");
    }

    public function sortBy($value)
    {
        $sortOrder = $this->request->get('sort_order', 'desc');
        $this->builder->orderBy($value, $sortOrder);
    }
}
