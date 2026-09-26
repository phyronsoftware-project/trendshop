<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
        return [
            'settings' => ['required', 'array:city_delivery_fee,province_delivery_fee,free_delivery_minimum,support_email,support_phone'],
            'settings.city_delivery_fee' => ['required', 'numeric', 'min:0', 'max:9999'],
            'settings.province_delivery_fee' => ['required', 'numeric', 'min:0', 'max:9999'],
            'settings.free_delivery_minimum' => ['required', 'numeric', 'min:0'],
            'settings.support_email' => ['required', 'email'],
            'settings.support_phone' => ['required', 'string', 'max:30'],
            'social_links' => ['nullable', 'array'],
            'social_links.*.url' => ['required', 'url', 'max:2048'],
            'social_links.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
