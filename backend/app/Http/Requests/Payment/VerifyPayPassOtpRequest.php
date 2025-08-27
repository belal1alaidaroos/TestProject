<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPayPassOtpRequest extends FormRequest
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
            'session_id' => 'required|string|exists:payment_sessions,id',
            'otp' => 'required|string|regex:/^[0-9]{6}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'session_id.required' => 'Session ID is required',
            'session_id.exists' => 'Payment session not found',
            'otp.required' => 'OTP code is required',
            'otp.regex' => 'OTP must be 6 digits',
        ];
    }
}