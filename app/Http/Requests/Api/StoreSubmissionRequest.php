<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
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
        return [
            'assignment_member_id' => ['required', 'integer', Rule::exists('assignment_members', 'id')],
            'file' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png,gif,webp', 'max:51200'],
        ];
    }
}
