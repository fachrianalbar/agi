<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => trim($this->input('login', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Please enter your email or username.',
            'password.required' => 'Please enter your password.',
        ];
    }
}
