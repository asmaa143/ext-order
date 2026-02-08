<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_method' => ['required', Rule::in(PaymentMethodEnum::values())],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.999'],
            'payment_details' => ['sometimes', 'array'],
        ];

        // Payment method specific validations
        if ($this->payment_method === 'credit_card') {
            $rules['payment_details.card_number'] = ['required', 'string', 'min:13', 'max:19'];
            $rules['payment_details.expiry_month'] = ['required', 'string', 'size:2'];
            $rules['payment_details.expiry_year'] = ['required', 'string', 'size:4'];
            $rules['payment_details.cvv'] = ['required', 'string', 'min:3', 'max:4'];
        }

        if ($this->payment_method === 'paypal') {
            $rules['payment_details.email'] = ['required', 'email'];
        }

        if ($this->payment_method === 'bank_transfer') {
            $rules['payment_details.account_number'] = ['required', 'string'];
            $rules['payment_details.bank_name'] = ['required', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.exists' => 'The specified order does not exist',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method selected',
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Payment amount must be greater than 0',
            'payment_details.card_number.required' => 'Card number is required for credit card payments',
            'payment_details.email.required' => 'Email is required for PayPal payments',
            'payment_details.account_number.required' => 'Account number is required for bank transfer',
        ];
    }
}
