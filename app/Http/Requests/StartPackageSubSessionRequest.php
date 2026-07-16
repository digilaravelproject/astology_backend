<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartPackageSubSessionRequest extends FormRequest
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
                        $fail('The selected user is not a registered astrologer.');
                    }
                }
            ],
            'mode' => 'required|in:chat,call',
        ];
    }
}
