<?php

namespace App\Http\Requests\Api;

use App\Models\Assignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Assignment $assignment */
        $assignment = $this->route('assignment');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('assignments', 'code')->ignore($assignment->id)],
            'description' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'open', 'closed'])],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
