<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^05[0-9]{8}$/'],
            'code' => ['required', 'string', 'size:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must be a valid Saudi mobile number (e.g., 0501234567).',
            'code.required' => 'OTP code is required.',
            'code.size' => 'OTP code must be exactly 4 digits.',
        ];
    }
}