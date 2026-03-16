<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAstrologerHomeRequest extends FormRequest
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
            'chat_enabled' => 'sometimes|boolean',
            'call_enabled' => 'sometimes|boolean',
            'video_call_enabled' => 'sometimes|boolean',
            'chat_rate_per_minute' => 'sometimes|numeric|min:0',
            'call_rate_per_minute' => 'sometimes|numeric|min:0',
            'video_call_rate_per_minute' => 'sometimes|numeric|min:0',
            'po_at_5_enabled' => 'sometimes|boolean',
            'po_at_5_rate_per_minute' => 'sometimes|numeric|min:0',
            'po_at_5_sessions' => 'sometimes|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'po_at_5_sessions.min' => 'PO@5 sessions must be at least 1.',
        ];
    }
}
