<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // Name / Full name fallback
        if (!$this->has('name') && $this->has('full_name')) {
            $mergeData['name'] = $this->input('full_name');
        }

        // Gender normalization
        if ($this->has('gender') && !is_null($this->input('gender'))) {
            $gender = strtolower(trim((string) $this->input('gender')));
            $mergeData['gender'] = $gender !== '' ? $gender : null;
        }

        // Languages parsing (handles stringified array, comma-separated, or JSON)
        if ($this->has('languages') && !is_null($this->input('languages'))) {
            $languages = $this->input('languages');
            if (is_string($languages)) {
                $decoded = json_decode($languages, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $languages = $decoded;
                } else {
                    $languages = array_map('trim', explode(',', $languages));
                }
            }
            if (is_array($languages)) {
                $languages = array_values(array_filter($languages, fn($l) => !is_null($l) && trim((string)$l) !== ''));
            }
            $mergeData['languages'] = $languages;
        }

        // Time of birth normalization to H:i
        if ($this->has('time_of_birth') && !is_null($this->input('time_of_birth'))) {
            $timeInput = trim((string) $this->input('time_of_birth'));
            if ($timeInput !== '') {
                try {
                    $parsedTime = Carbon::parse($timeInput)->format('H:i');
                    $mergeData['time_of_birth'] = $parsedTime;
                } catch (\Throwable $e) {
                    // Let validation rule catch format error if unparseable
                }
            } else {
                $mergeData['time_of_birth'] = null;
            }
        }

        // Date of birth normalization to Y-m-d
        if ($this->has('date_of_birth') && !is_null($this->input('date_of_birth'))) {
            $dobInput = trim((string) $this->input('date_of_birth'));
            if ($dobInput !== '') {
                try {
                    $parsedDate = Carbon::parse($dobInput)->format('Y-m-d');
                    $mergeData['date_of_birth'] = $parsedDate;
                } catch (\Throwable $e) {
                    // Let validation rule catch format error if unparseable
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
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'regex:/^[0-9]{10}$/'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'time_of_birth' => ['sometimes', 'nullable', 'date_format:H:i'],
            'place_of_birth' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'relationship_status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'languages' => ['sometimes', 'nullable', 'array'],
            'languages.*' => ['string', 'max:50'],
            'profile_photo' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'gender.in' => 'Gender must be male, female, or other.',
            'date_of_birth.date' => 'Date of birth must be a valid date (YYYY-MM-DD).',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'time_of_birth.date_format' => 'Time of birth must be in HH:MM format (e.g., 14:30).',
            'place_of_birth.string' => 'Place of birth must be a string.',
            'latitude.numeric' => 'Latitude must be a numeric value.',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'longitude.numeric' => 'Longitude must be a numeric value.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
            'languages.array' => 'Languages must be an array or list.',
        ];
    }
}
