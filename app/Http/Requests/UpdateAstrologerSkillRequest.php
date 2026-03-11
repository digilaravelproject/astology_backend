<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAstrologerSkillRequest extends FormRequest
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
            'category' => 'sometimes|string|max:255',
            'primary_skills' => 'sometimes|array',
            'primary_skills.*' => 'string|max:100',
            'all_skills' => 'sometimes|array',
            'all_skills.*' => 'string|max:100',
            'languages' => 'sometimes|array',
            'languages.*' => 'string|max:50',
            'experience_years' => 'sometimes|integer|min:0|max:100',
            'daily_contribution_hours' => 'sometimes|integer|min:0|max:24',
            'heard_about' => 'sometimes|string|max:255',
        ];
    }
}
