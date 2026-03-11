<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAstrologerProfileRequest extends FormRequest
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
        $userId = optional($this->user())->id;

        return [
            'full_name' => 'sometimes|string|max:255',
            'phone' => [
                'sometimes',
                'regex:/^[0-9]{10}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'city' => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',

            'id_proof_number' => 'sometimes|string|max:50',
            'date_of_birth' => 'sometimes|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),

            'profile_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id_proof' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificate' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be a valid 10-digit number.',
            'phone.unique' => 'This phone number is already registered.',
            'email.unique' => 'This email is already registered.',
            'date_of_birth.before_or_equal' => 'You must be at least 18 years old.',
            'profile_photo.image' => 'Profile photo must be an image.',
            'profile_photo.mimes' => 'Profile photo must be a JPEG, PNG, or GIF image.',
            'profile_photo.max' => 'Profile photo must not exceed 2MB.',
            'id_proof.file' => 'ID proof must be a file.',
            'id_proof.mimes' => 'ID proof must be a PDF, JPG, JPEG, or PNG file.',
            'id_proof.max' => 'ID proof must not exceed 5MB.',
            'certificate.file' => 'Certificate must be a file.',
            'certificate.mimes' => 'Certificate must be a PDF, JPG, JPEG, or PNG file.',
            'certificate.max' => 'Certificate must not exceed 5MB.',
        ];
    }
}
