<?php

namespace App\Http\Requests;

use App\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', Rule::in(OrderStatusEnum::values())],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:10000'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0', 'max:999999.999'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_email.email' => 'Please provide a valid email address',
            'status.in' => 'Invalid order status',
            'items.min' => 'Order must contain at least one item',
            'items.*.product_name.required_with' => 'Product name is required for all items',
            'items.*.quantity.required_with' => 'Quantity is required for all items',
            'items.*.price.required_with' => 'Price is required for all items',
        ];
    }
}
