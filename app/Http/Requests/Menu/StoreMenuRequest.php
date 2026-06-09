<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'route_name' => $this->input('route_name') ?: null,
            'url' => $this->input('url') ?: null,
            'active_pattern' => $this->input('active_pattern') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'section' => ['required', 'string', 'max:100'],
            'icon' => ['required', Rule::in(Menu::ICONS)],
            'route_name' => ['nullable', 'string', 'max:150'],
            'url' => [
                'nullable',
                'string',
                'max:2048',
                'regex:/^(\/(?!\/)|https?:\/\/).+$/i',
            ],
            'active_pattern' => ['nullable', 'string', 'max:150'],
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->route_name && ! Route::has($this->route_name)) {
                    $validator->errors()->add('route_name', 'The selected route name is not registered.');
                }
            },
        ];
    }
}
