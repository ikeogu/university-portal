<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\ModeOfStudy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mat_no' => strtoupper(trim((string) $this->mat_no)),
        ]);
    }

    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'mat_no' => [
                'required', 'string', 'max:50',
                Rule::unique('students', 'mat_no'),
            ],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'state_of_origin' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'mode_of_study' => ['required', Rule::enum(ModeOfStudy::class)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'mat_no.unique' => 'A student with that matriculation number already exists.',
        ];
    }
}
