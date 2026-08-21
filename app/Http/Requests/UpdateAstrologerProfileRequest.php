<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateAstrologerProfileRequest
 *
 * Validates and normalizes astrologer profile updates:
 * - Sanitizes identity, contact, bio, and experience data
 * - Normalizes array payloads (JSON strings or comma-separated values)
 * - Enforces statutory compliance (18+ age limit, official 15-char GSTIN format)
 * - Validates media & document uploads (size, MIME type constraints)
 */
class UpdateAstrologerProfileRequest extends FormRequest
{
    // =========================================================================
    // 1. AUTHORIZATION
    // =========================================================================

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return (bool) ($user && $user->user_type === 'astrologer');
    }

    // =========================================================================
    // 2. INPUT PREPARATION & NORMALIZATION
    // =========================================================================

    /**
     * Prepare and sanitize request data prior to validation.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // 1. Name & Full Name Aliasing
        $this->normalizeNames($mergeData);

        // 2. Experience Aliasing
        $this->normalizeExperience($mergeData);

        // 3. Gender Normalization
        $this->normalizeGender($mergeData);

        // 4. Array/List Fields Normalization (JSON & CSV inputs)
        $this->normalizeArrayFields($mergeData);

        // 5. Date of Birth Normalization
        $this->normalizeDateOfBirth($mergeData);

        // 6. GSTIN Normalization
        $this->normalizeGstNumber($mergeData);

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    // =========================================================================
    // 3. VALIDATION RULES
    // =========================================================================

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $userId = optional($this->user())->id;

        return [
            // ── User Account Credentials ─────────────────────────────────────
            'full_name'            => 'sometimes|nullable|string|max:255',
            'name'                 => 'sometimes|nullable|string|max:255',
            'phone'                => [
                'sometimes',
                'nullable',
                'regex:/^[0-9]{10}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'email'                => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            // ── Demographics, Location & Bio ─────────────────────────────────
            'gender'               => 'sometimes|nullable|in:male,female,other',
            'city'                 => 'sometimes|nullable|string|max:100',
            'country'              => 'sometimes|nullable|string|max:100',
            'current_address'      => 'sometimes|nullable|string|max:500',
            'bio'                  => 'sometimes|nullable|string|max:2000',
            'website_link'         => 'sometimes|nullable|string|max:255',
            'instagram_username'   => 'sometimes|nullable|string|max:100',

            // ── Professional Background & Experience ─────────────────────────
            'years_of_experience'  => 'sometimes|nullable|numeric|min:0|max:100',
            'experience_years'     => 'sometimes|nullable|numeric|min:0|max:100',

            // ── Skills & Languages Arrays ────────────────────────────────────
            'areas_of_expertise'   => 'sometimes|nullable|array',
            'areas_of_expertise.*' => 'string|max:100',
            'primary_skills'       => 'sometimes|nullable|array',
            'primary_skills.*'     => 'string|max:100',
            'all_skills'           => 'sometimes|nullable|array',
            'all_skills.*'         => 'string|max:100',
            'languages'            => 'sometimes|nullable|array',
            'languages.*'          => 'string|max:100',

            // ── Statutory Compliance & Tax Verification ──────────────────────
            'id_proof_number'      => 'sometimes|nullable|string|max:50',
            'gst_number'           => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            ],
            'date_of_birth'        => [
                'sometimes',
                'nullable',
                'date',
                'before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            ],

            // ── Media & File Uploads ─────────────────────────────────────────
            'profile_photo'        => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'id_proof'             => 'sometimes|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificate'          => 'sometimes|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    // =========================================================================
    // 4. CUSTOM ERROR MESSAGES
    // =========================================================================

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex'                   => 'Phone number must be a valid 10-digit number.',
            'phone.unique'                  => 'This phone number is already registered.',
            'email.unique'                  => 'This email is already registered.',
            'gst_number.regex'              => 'The GST number format is invalid. Please provide a valid 15-character GSTIN (e.g. 07AAAAA0000A1Z5).',
            'date_of_birth.before_or_equal' => 'You must be at least 18 years old.',
            'profile_photo.image'           => 'Profile photo must be an image file.',
            'profile_photo.mimes'           => 'Profile photo must be a valid image (jpeg, png, jpg, gif, webp).',
            'profile_photo.max'             => 'Profile photo must not exceed 5MB.',
            'id_proof.file'                 => 'ID proof must be a valid document file.',
            'id_proof.mimes'                => 'ID proof must be a PDF, JPG, JPEG, or PNG file.',
            'id_proof.max'                  => 'ID proof must not exceed 5MB.',
            'certificate.file'              => 'Certificate must be a valid document file.',
            'certificate.mimes'             => 'Certificate must be a PDF, JPG, JPEG, or PNG file.',
            'certificate.max'               => 'Certificate must not exceed 5MB.',
        ];
    }

    // =========================================================================
    // 5. PRIVATE NORMALIZATION HELPERS
    // =========================================================================

    /**
     * Normalize name and full_name inputs.
     */
    private function normalizeNames(array &$mergeData): void
    {
        if (!$this->has('full_name') && $this->has('name')) {
            $mergeData['full_name'] = $this->input('name');
        }
        if (!$this->has('name') && $this->has('full_name')) {
            $mergeData['name'] = $this->input('full_name');
        }
    }

    /**
     * Normalize years of experience aliasing.
     */
    private function normalizeExperience(array &$mergeData): void
    {
        if (!$this->has('years_of_experience') && $this->has('experience_years')) {
            $mergeData['years_of_experience'] = $this->input('experience_years');
        }
    }

    /**
     * Normalize gender string.
     */
    private function normalizeGender(array &$mergeData): void
    {
        if ($this->has('gender') && !is_null($this->input('gender'))) {
            $gender = strtolower(trim((string) $this->input('gender')));
            $mergeData['gender'] = $gender !== '' ? $gender : null;
        }
    }

    /**
     * Parse array fields if passed as JSON string or comma-separated string.
     */
    private function normalizeArrayFields(array &$mergeData): void
    {
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
                    $val = array_values(array_filter(
                        $val,
                        fn ($item) => !is_null($item) && trim((string) $item) !== ''
                    ));
                }

                $mergeData[$field] = $val;
            }
        }
    }

    /**
     * Normalize date of birth format to Y-m-d.
     */
    private function normalizeDateOfBirth(array &$mergeData): void
    {
        if ($this->has('date_of_birth') && !is_null($this->input('date_of_birth'))) {
            $dobInput = trim((string) $this->input('date_of_birth'));
            if ($dobInput !== '') {
                try {
                    $mergeData['date_of_birth'] = Carbon::parse($dobInput)->format('Y-m-d');
                } catch (\Throwable $e) {
                    // Let validator catch invalid date format
                }
            } else {
                $mergeData['date_of_birth'] = null;
            }
        }
    }

    /**
     * Normalize and uppercase GST number.
     */
    private function normalizeGstNumber(array &$mergeData): void
    {
        if ($this->has('gst_number') && !is_null($this->input('gst_number'))) {
            $gst = strtoupper(trim((string) $this->input('gst_number')));
            $mergeData['gst_number'] = $gst !== '' ? $gst : null;
        }
    }
}
