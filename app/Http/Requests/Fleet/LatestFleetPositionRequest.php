<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class LatestFleetPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'devices' => ['required', 'array', 'min:1', 'max:100'],
            'devices.*.ref' => [
                'required',
                'string',
                'size:64',
                'regex:/^[a-f0-9]{64}$/',
                'distinct',
            ],
            'devices.*.device_name' => ['required', 'string', 'max:200'],
        ];
    }
}
