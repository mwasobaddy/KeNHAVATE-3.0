<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdeaRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'category_id' => ['required', 'exists:idea_categories,id'],
            'problem_statement' => ['required', 'string', 'max:2000'],
            'proposed_solution' => ['required', 'string', 'max:3000'],
            'cost_benefit_analysis' => ['nullable', 'string', 'max:2000'],
            'proposal_document_path' => ['nullable', 'string', 'max:255'],
            'collaboration_enabled' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your idea.',
            'description.required' => 'Please provide a description of your idea.',
            'category_id.required' => 'Please select a category for your idea.',
            'category_id.exists' => 'The selected category is invalid.',
            'problem_statement.required' => 'Please describe the problem your idea addresses.',
            'proposed_solution.required' => 'Please describe your proposed solution.',
        ];
    }
}
