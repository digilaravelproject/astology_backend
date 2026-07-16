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
            'default_duration_minutes' => 'required|numeric|min:0.1', // duration in minutes
            'is_default' => 'nullable|boolean',
        ];
    }
}
