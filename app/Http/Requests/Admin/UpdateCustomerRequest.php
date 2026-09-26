<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customer)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($customer)],
            'role' => ['required', 'in:admin,customer'],
            'locale' => ['required', 'in:km,en,zh'],
            'status' => ['required', 'in:active,inactive,blocked'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_image' => [
                Rule::prohibitedIf($customer->role !== 'admin' || $this->input('role') !== 'admin'),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ];
    }
}
