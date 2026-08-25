<?php

namespace App\Http\Requests;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateKundliRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|in:male,female,other',
            'birth_date' => 'sometimes|date',
            'birth_time' => 'sometimes|date_format:H:i:s',
            'birth_place' => 'sometimes|nullable|string|max:500',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'datetime' => 'sometimes|date_format:Y-m-d H:i:s',
        ];
    }

    /**
     * Custom validation failed response to match ApiResponse standard format.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('Validation failed', 422, $validator->errors())
        );
    }
}
