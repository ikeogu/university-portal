<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdvanceCohortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_session_id' => ['required', Rule::exists('academic_sessions', 'id')],
            'from_level' => ['required', 'integer', 'min:100'],
            'to_session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id'),
                'different:from_session_id',
            ],
        ];
    }
}
