<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAstrologerOtherDetailsRequest extends FormRequest
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
            'gender' => 'sometimes|in:male,female,other',
            'current_address' => 'sometimes|string|max:500',
            'bio' => 'sometimes|string|max:1000',
            'date_of_birth' => 'sometimes|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            'website_link' => 'sometimes|url|max:255',
            'instagram_username' => 'sometimes|string|max:100',
        ];
    }
}
