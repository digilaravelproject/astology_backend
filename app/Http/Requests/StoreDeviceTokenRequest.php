<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token'    => ['required', 'string'],
            'device_type'  => ['required', 'in:android,ios,web'],
            'device_id'    => ['nullable', 'string', 'max:191'],
            'device_model' => ['nullable', 'string', 'max:100'],
            'app_version'  => ['nullable', 'string', 'max:50'],
        ];
    }
}
