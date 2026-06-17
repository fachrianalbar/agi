<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => trim($this->input('username', '')),
            'email' => trim($this->input('email', '')),
            'customer_id' => $this->input('customer_id') ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:200'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:200', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'customer_id' => ['nullable', Rule::exists('customers', 'id')],
            'is_active' => ['boolean'],
        ];
    }
}
