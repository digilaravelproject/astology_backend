<?php

namespace App\Http\Requests;

use App\Helpers\ApiResponse;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreKundliRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation in Asia/Kolkata timezone.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // Normalize birth_date to Y-m-d (Asia/Kolkata)
        if ($this->has('birth_date') && !is_null($this->input('birth_date'))) {
            $dobInput = trim((string) $this->input('birth_date'));
            if ($dobInput !== '') {
                try {
                    $mergeData['birth_date'] = Carbon::parse($dobInput, 'Asia/Kolkata')->format('Y-m-d');
                } catch (\Throwable $e) {
                    // Let validation rule catch format error
                }
            }
        }

        // Normalize birth_time to H:i:s (Asia/Kolkata)
        if ($this->has('birth_time') && !is_null($this->input('birth_time'))) {
            $timeInput = trim((string) $this->input('birth_time'));
            if ($timeInput !== '') {
                try {
                    $mergeData['birth_time'] = Carbon::parse($timeInput, 'Asia/Kolkata')->format('H:i:s');
                } catch (\Throwable $e) {
                    // Let validation rule catch format error
                }
            }
        }

        // Auto-derive datetime synchronized with Asia/Kolkata birth_date + birth_time
        $finalDate = $mergeData['birth_date'] ?? $this->input('birth_date');
        $finalTime = $mergeData['birth_time'] ?? $this->input('birth_time');

        if (!empty($finalDate) && !empty($finalTime)) {
            try {
                $mergeData['datetime'] = Carbon::parse("{$finalDate} {$finalTime}", 'Asia/Kolkata')->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'birth_date' => 'required|date',
            'birth_time' => 'required|date_format:H:i:s',
            'birth_place' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'datetime' => 'nullable|date_format:Y-m-d H:i:s',
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
