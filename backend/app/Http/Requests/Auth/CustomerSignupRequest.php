<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CustomerSignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:app_users,email',
            'phone' => 'required|string|regex:/^\+966[0-9]{9}$/|unique:app_users,phone',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'company_name_en' => 'required|string|max:255',
            'company_name_ar' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'commercial_license' => 'nullable|string|max:100',
            'contact_person' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required.',
            'name.max' => 'Full name cannot exceed 255 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone.required' => 'Mobile number is mandatory for customer portal.',
            'phone.regex' => 'Please enter a valid Saudi mobile number (+966XXXXXXXXX).',
            'phone.unique' => 'This mobile number is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.min' => 'Password confirmation must be at least 8 characters.',
            'company_name_en.required' => 'Company name in English is required.',
            'company_name_en.max' => 'Company name cannot exceed 255 characters.',
            'company_name_ar.max' => 'Company name in Arabic cannot exceed 255 characters.',
            'tax_number.max' => 'Tax number cannot exceed 50 characters.',
            'commercial_license.max' => 'Commercial license cannot exceed 100 characters.',
            'contact_person.required' => 'Contact person name is required.',
            'contact_person.max' => 'Contact person name cannot exceed 255 characters.',
        ];
    }
}