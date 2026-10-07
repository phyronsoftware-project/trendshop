<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'source' => ['nullable', Rule::in(['cart', 'product'])],
            'payment_method' => ['required', Rule::in(['cash_on_delivery', 'bank_transfer'])],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'checkout_token' => ['required', 'uuid'],
        ];
    }

    /** Require a product only for direct product checkout. */
    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('product_id', ['required'], fn (): bool => $this->input('source', 'product') === 'product');
    }
}
