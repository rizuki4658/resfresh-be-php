<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Allow all authenticated users
        // return auth()->check();
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'deadline' => 'nullable|date|after:now'
        ];

        // On update, deadline can be any date (not just future)
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['deadline'] = 'nullable|date';
        }

        return $rules;
    }

    /**
     * Get custom error messages
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.required' => 'Task title is required',
            'title.max' => 'Task title cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1000 characters',
            'status.in' => 'Invalid task status',
            'deadline.date' => 'Deadline must be a valid date',
            'deadline.after' => 'Deadline must be a future date'
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation()
    {
        // Set default status if not provided
        if (!$this->has('status') && $this->isMethod('POST')) {
            $this->merge([
                'status' => 'pending'
            ]);
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Unauthorized to perform this action'
            ], 403)
        );
    }
}
