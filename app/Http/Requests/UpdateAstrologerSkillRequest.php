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

    protected function prepareForValidation(): void
    {
        $mergeData = [];

        $arrayFields = ['primary_skills', 'all_skills', 'languages'];
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
        return [
            'category' => 'sometimes|nullable|string|max:255',
            'primary_skills' => 'sometimes|nullable|array',
            'primary_skills.*' => 'string|max:100',
            'all_skills' => 'sometimes|nullable|array',
            'all_skills.*' => 'string|max:100',
            'languages' => 'sometimes|nullable|array',
            'languages.*' => 'string|max:50',
            'experience_years' => 'sometimes|nullable|integer|min:0|max:100',
            'daily_contribution_hours' => 'sometimes|nullable|integer|min:0|max:24',
            'heard_about' => 'sometimes|nullable|string|max:255',
        ];
    }
}
