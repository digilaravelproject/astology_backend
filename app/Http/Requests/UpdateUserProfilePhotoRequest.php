<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfilePhotoRequest extends FormRequest
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
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'profile_photo.required' => 'Please provide a profile photo.',
            'profile_photo.image' => 'Profile photo must be an image.',
            'profile_photo.mimes' => 'Profile photo must be a JPEG, PNG, or GIF image.',
            'profile_photo.max' => 'Profile photo must not exceed 2MB.',
        ];
    }
}
