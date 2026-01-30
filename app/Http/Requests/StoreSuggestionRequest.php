<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuggestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'in:improvement,question,concern,support,general'],
            'parent_id' => ['nullable', 'exists:suggestions,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Please provide content for your suggestion.',
            'type.required' => 'Please select a type for your suggestion.',
            'type.in' => 'The selected suggestion type is invalid.',
            'parent_id.exists' => 'The parent suggestion does not exist.',
        ];
    }
}
