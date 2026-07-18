<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseCategory;
use App\Enums\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [
            'code' => strtoupper(trim((string) $this->code)),
        ];

        foreach (['credit_units', 'semester', 'level'] as $field) {
            if ($this->filled($field)) {
                $data[$field] = (int) $this->input($field);
            }
        }

        if ($this->filled('choose_count')) {
            $data['choose_count'] = (int) $this->input('choose_count');
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('courses', 'code'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'credit_units' => ['required', Rule::in([1, 2, 3, 4, 6])],
            'semester' => ['required', Rule::enum(Semester::class)],
            'level' => ['required', Rule::in([100, 200, 300, 400])],
            'category' => ['required', Rule::enum(CourseCategory::class)],
            'elective_group' => ['required_if:category,elective', 'nullable', 'string', 'max:100'],
            'choose_count' => ['required_if:category,elective', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'A course with that code already exists.',
        ];
    }
}
