<?php

namespace App\Filters;


class OrderFilter extends QueryFilter
{
    public function status($value)
    {
        $this->builder->where('status', $value);
    }

    public function fromDate($value)
    {
        $this->builder->whereDate('created_at', '>=', $value);
    }

    public function toDate($value)
    {
        $this->builder->whereDate('created_at', '<=', $value);
    }

    public function search($value)
    {
        $this->builder->where(function ($query) use ($value) {
            $query->where('customer_name', 'like', "%{$value}%")
                ->orWhere('customer_email', 'like', "%{$value}%");
        });
    }

    public function minAmount($value)
    {
        $this->builder->where('total_amount', '>=', $value);
    }

    public function maxAmount($value)
    {
        $this->builder->where('total_amount', '<=', $value);
    }

    public function sortBy($value)
    {
        $sortOrder = $this->request->get('sort_order', 'desc');
        $this->builder->orderBy($value, $sortOrder);
    }
}
