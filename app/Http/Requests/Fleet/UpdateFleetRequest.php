<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFleetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $fleet = $this->route('fleet');

        return [
            'customer_id' => ['required', 'string', Rule::exists('customers', 'id')],
            'vehicle_name' => ['required', 'string', 'max:200'],
            'device_name' => [
                'required',
                'string',
                'max:200',
                Rule::unique('fleets', 'device_name')
                    ->where(fn ($query) => $query->where('customer_id', $this->input('customer_id')))
                    ->ignore($fleet?->id),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
