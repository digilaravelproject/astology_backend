<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AstrologerSignupRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Step 1: Basic Details
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[0-9]{10}$/|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',

            // Step 2: Expertise
            'years_of_experience' => 'required|integer|min:0|max:100',
            'areas_of_expertise' => 'required|array|min:1',
            'areas_of_expertise.*' => 'string|in:Vedic Astrology,Tarot,Numerology,Palmistry,Vastu,KP Astrology,Nadi Astrology,Feng Shui,Face Reading,Prashna',

            // Step 3: Languages
            'languages' => 'required|array|min:1',
            'languages.*' => 'string|in:Hindi,English,Bengali,Tamil,Telugu,Marathi,Gujarati,Kannada,Malayalam,Punjabi,Odia,Urdu',

            // Step 4: Profile
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'bio' => 'nullable|string|max:300',

            // Step 5: Documents
            'id_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_proof_number' => 'required|string|max:50',
            'date_of_birth' => 'required|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Full name is required.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must be a valid 10-digit number.',
            'phone.unique' => 'This phone number is already registered.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'city.required' => 'City is required.',
            'country.required' => 'Country is required.',
            'years_of_experience.required' => 'Years of experience is required.',
            'years_of_experience.integer' => 'Years of experience must be a number.',
            'areas_of_expertise.required' => 'Please select at least one area of expertise.',
            'areas_of_expertise.*.in' => 'Selected area of expertise is invalid.',
            'languages.required' => 'Please select at least one language.',
            'languages.*.in' => 'Selected language is invalid.',
            'profile_photo.image' => 'Profile photo must be an image.',
            'profile_photo.mimes' => 'Profile photo must be a JPEG, PNG, or GIF image.',
            'profile_photo.max' => 'Profile photo must not exceed 2MB.',
            'bio.max' => 'Bio must not exceed 300 characters.',
            'id_proof.file' => 'ID proof must be a file.',
            'id_proof.mimes' => 'ID proof must be a PDF, JPG, JPEG, or PNG file.',
            'id_proof.max' => 'ID proof must not exceed 5MB.',
            'certificate.file' => 'Certificate must be a file.',
            'certificate.mimes' => 'Certificate must be a PDF, JPG, JPEG, or PNG file.',
            'certificate.max' => 'Certificate must not exceed 5MB.',
            'id_proof_number.required' => 'ID proof number is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.date' => 'Date of birth must be a valid date.',
            'date_of_birth.before_or_equal' => 'You must be at least 18 years old.',
        ];
    }
}
