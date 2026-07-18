<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'is_current' => $this->boolean('is_current'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:20', Rule::unique('academic_sessions', 'name')],
            'is_current' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A session with that name already exists.',
        ];
    }
}
