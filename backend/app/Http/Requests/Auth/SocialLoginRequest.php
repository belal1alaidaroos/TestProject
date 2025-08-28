<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => 'required|in:google,facebook,apple,linkedin',
            'provider_user_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'portal_type' => 'required|in:Customer,Agency,Admin,Internal',
            'phone' => 'nullable|string|regex:/^\+966[0-9]{9}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'provider.required' => 'Social media provider is required.',
            'provider.in' => 'Invalid social media provider.',
            'provider_user_id.required' => 'Provider user ID is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'name.required' => 'Name is required.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'portal_type.required' => 'Portal type is required.',
            'portal_type.in' => 'Invalid portal type.',
            'phone.regex' => 'Please enter a valid Saudi mobile number (+966XXXXXXXXX).',
        ];
    }
}