<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAstrologerBillingAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->user_type === 'astrologer';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'invoice_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address_line1.required' => 'Address line 1 is required.',
            'address_line1.max' => 'Address line 1 must not exceed 255 characters.',
            'city.required' => 'City is required.',
            'city.max' => 'City must not exceed 100 characters.',
            'postal_code.required' => 'Postal code is required.',
            'postal_code.max' => 'Postal code must not exceed 20 characters.',
            'country.required' => 'Country is required.',
            'country.max' => 'Country must not exceed 100 characters.',
        ];
    }
}
