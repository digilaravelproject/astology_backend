<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpsertPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'default_amount' => 'required|numeric|min:0',
            'default_duration' => 'required|integer|min:1', // in seconds
            'is_default' => 'nullable|boolean',
        ];
    }
}
