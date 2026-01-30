<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StaffOnboardingRequest extends FormRequest
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
            'work_email' => 'required|email|max:255',
            'personal_email' => 'nullable|email|max:255',
            'region_id' => 'nullable|integer|exists:regions,id',
            'directorate_id' => 'nullable|integer|exists:directorates,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'designation' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'work_email.required' => 'Work email is required.',
            'work_email.email' => 'Please provide a valid work email address.',
            'personal_email.email' => 'Please provide a valid personal email address.',
        ];
    }
}