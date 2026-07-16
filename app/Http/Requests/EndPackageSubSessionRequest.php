<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EndPackageSubSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sub_session_id' => 'required|exists:package_sub_sessions,id',
        ];
    }
}
