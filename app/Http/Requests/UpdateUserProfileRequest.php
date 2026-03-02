<?php

namespace App\Http\Requests;

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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in('male', 'female')],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'time_of_birth' => ['required', 'date_format:H:i'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => [
                'required',
                'string',
                Rule::in('English', 'Hindi', 'Tamil', 'Bengali', 'Telugu', 'Marathi'),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name cannot exceed 255 characters.',

            'gender.required' => 'Gender is required. Please select male or female.',
            'gender.in' => 'Gender must be either male or female.',

            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.date' => 'Date of birth must be a valid date (YYYY-MM-DD).',
            'date_of_birth.before' => 'Date of birth must be in the past.',

            'time_of_birth.required' => 'Time of birth is required.',
            'time_of_birth.date_format' => 'Time of birth must be in HH:MM format (e.g., 14:30).',

            'place_of_birth.required' => 'Place of birth is required.',
            'place_of_birth.string' => 'Place of birth must be a string.',
            'place_of_birth.max' => 'Place of birth cannot exceed 255 characters.',

            'languages.required' => 'Languages are required. Select at least one language.',
            'languages.array' => 'Languages must be an array.',
            'languages.min' => 'Please select at least one language.',
            'languages.*.required' => 'Each language must be provided.',
            'languages.*.string' => 'Each language must be a string.',
            'languages.*.in' => 'Invalid language. Allowed languages are: English, Hindi, Tamil, Bengali, Telugu, Marathi.',
        ];
    }
}
