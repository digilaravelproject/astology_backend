<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAssignPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'astrologer_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::find($value);
                    if (!$user || $user->user_type !== 'astrologer') {
                        $fail('The selected user is not an astrologer.');
                    }
                }
            ],
            'amount' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1', // in seconds
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
