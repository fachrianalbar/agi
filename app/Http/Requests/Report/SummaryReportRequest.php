<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SummaryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => trim((string) $this->input('customer_id')),
            'device_name' => trim((string) $this->input('device_name')),
            'start_time' => trim((string) $this->input('start_time')),
            'end_time' => trim((string) $this->input('end_time')),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'string',
                Rule::exists('customers', 'id')
                    ->where(fn ($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at')),
            ],
            'device_name' => [
                'required',
                'string',
                'max:200',
                Rule::exists('fleets', 'device_name')
                    ->where(fn ($query) => $query
                        ->where('customer_id', $this->input('customer_id'))
                        ->where('is_active', true)
                        ->whereNull('deleted_at')),
            ],
            'start_time' => ['required', 'date_format:Y-m-d\TH:i'],
            'end_time' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_name.exists' => 'The selected fleet does not belong to the selected customer.',
            'end_time.after_or_equal' => 'The end time must be after or equal to the start time.',
        ];
    }
}
