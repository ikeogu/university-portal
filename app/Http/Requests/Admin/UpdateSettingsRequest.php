<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bioUpdateOpen' => ['required', 'boolean'],
            'masterSort' => ['required', Rule::in(['cgpa', 'matno'])],
            'programme_duration_years' => ['required', 'integer', 'min:1', 'max:10'],
            'institution_name' => ['required', 'string', 'max:255'],
            'faculty_name' => ['required', 'string', 'max:255'],
            'department_name' => ['required', 'string', 'max:255'],
            'programme_name' => ['required', 'string', 'max:255'],
            'hod_signature' => ['nullable', 'image', 'max:2048'],
            'exam_officer_signature' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
