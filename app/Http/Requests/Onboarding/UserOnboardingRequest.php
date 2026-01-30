<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class UserOnboardingRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'id_number' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide your full name.',
            'mobile.required' => 'Mobile number is required.',
            'id_number.required' => 'ID number is required.',
            'gender.required' => 'Please select your gender.',
            'gender.in' => 'Gender must be either male or female.',
        ];
    }
}