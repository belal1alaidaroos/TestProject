<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CreatePayPassSessionRequest extends FormRequest
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
            'contract_id' => 'required|string|exists:contracts,id',
            'phone' => 'required|string|regex:/^[0-9]{10,15}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contract_id.required' => 'Contract ID is required',
            'contract_id.exists' => 'Contract not found',
            'phone.required' => 'Phone number is required',
            'phone.regex' => 'Phone number must be 10-15 digits',
        ];
    }
}