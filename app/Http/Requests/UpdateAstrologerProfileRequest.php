<?php

namespace App\Http\Requests;

use Carbon\Carbon;
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
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // Name / Full name normalization
        if (!$this->has('full_name') && $this->has('name')) {
            $mergeData['full_name'] = $this->input('name');
        }
        if (!$this->has('name') && $this->has('full_name')) {
            $mergeData['name'] = $this->input('full_name');
        }

        // Years of experience aliasing
        if (!$this->has('years_of_experience') && $this->has('experience_years')) {
            $mergeData['years_of_experience'] = $this->input('experience_years');
        }

        // Gender normalization
        if ($this->has('gender') && !is_null($this->input('gender'))) {
            $gender = strtolower(trim((string) $this->input('gender')));
            $mergeData['gender'] = $gender !== '' ? $gender : null;
        }

        // Parse array fields if passed as JSON strings or comma-separated
        $arrayFields = ['languages', 'areas_of_expertise', 'primary_skills', 'all_skills'];
        foreach ($arrayFields as $field) {
            if ($this->has($field) && !is_null($this->input($field))) {
                $val = $this->input($field);
                if (is_string($val)) {
                    $decoded = json_decode($val, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $val = $decoded;
                    } else {
                        $val = array_map('trim', explode(',', $val));
                    }
                }
                if (is_array($val)) {
                    $val = array_values(array_filter($val, fn($item) => !is_null($item) && trim((string)$item) !== ''));
                }
                $mergeData[$field] = $val;
            }
        }

        // Date of birth normalization
        if ($this->has('date_of_birth') && !is_null($this->input('date_of_birth'))) {
            $dobInput = trim((string) $this->input('date_of_birth'));
            if ($dobInput !== '') {
                try {
                    $mergeData['date_of_birth'] = Carbon::parse($dobInput)->format('Y-m-d');
                } catch (\Throwable $e) {
                    // Let validation catch format error
                }
            } else {
                $mergeData['date_of_birth'] = null;
            }
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
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
            'full_name' => 'sometimes|nullable|string|max:255',
            'name' => 'sometimes|nullable|string|max:255',
            'phone' => [
                'sometimes',
                'nullable',
                'regex:/^[0-9]{10}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'city' => 'sometimes|nullable|string|max:100',
            'country' => 'sometimes|nullable|string|max:100',
            'bio' => 'sometimes|nullable|string|max:2000',
            'years_of_experience' => 'sometimes|nullable|numeric|min:0|max:100',
            'experience_years' => 'sometimes|nullable|numeric|min:0|max:100',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'areas_of_expertise' => 'sometimes|nullable|array',
            'areas_of_expertise.*' => 'string|max:100',
            'primary_skills' => 'sometimes|nullable|array',
            'primary_skills.*' => 'string|max:100',
            'all_skills' => 'sometimes|nullable|array',
            'all_skills.*' => 'string|max:100',
            'languages' => 'sometimes|nullable|array',
            'languages.*' => 'string|max:100',
            'website_link' => 'sometimes|nullable|string|max:255',
            'instagram_username' => 'sometimes|nullable|string|max:100',
            'current_address' => 'sometimes|nullable|string|max:500',
            'id_proof_number' => 'sometimes|nullable|string|max:50',
            'date_of_birth' => 'sometimes|nullable|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),

            'profile_photo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'id_proof' => 'sometimes|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificate' => 'sometimes|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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
            'profile_photo.mimes' => 'Profile photo must be a valid image file.',
            'profile_photo.max' => 'Profile photo must not exceed 5MB.',
            'id_proof.file' => 'ID proof must be a file.',
            'id_proof.mimes' => 'ID proof must be a PDF, JPG, JPEG, or PNG file.',
            'id_proof.max' => 'ID proof must not exceed 5MB.',
            'certificate.file' => 'Certificate must be a file.',
            'certificate.mimes' => 'Certificate must be a PDF, JPG, JPEG, or PNG file.',
            'certificate.max' => 'Certificate must not exceed 5MB.',
        ];
    }
}
