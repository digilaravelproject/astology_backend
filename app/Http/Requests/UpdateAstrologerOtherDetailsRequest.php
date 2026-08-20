<?php

namespace App\Http\Requests;

use Carbon\Carbon;
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

    protected function prepareForValidation(): void
    {
        $mergeData = [];

        if ($this->has('gender') && !is_null($this->input('gender'))) {
            $gender = strtolower(trim((string) $this->input('gender')));
            $mergeData['gender'] = $gender !== '' ? $gender : null;
        }

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
        return [
            'gender' => 'sometimes|nullable|in:male,female,other',
            'current_address' => 'sometimes|nullable|string|max:500',
            'bio' => 'sometimes|nullable|string|max:1000',
            'date_of_birth' => 'sometimes|nullable|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            'website_link' => 'sometimes|nullable|string|max:255',
            'instagram_username' => 'sometimes|nullable|string|max:100',
        ];
    }
}
