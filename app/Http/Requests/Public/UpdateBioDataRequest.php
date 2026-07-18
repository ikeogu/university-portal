<?php

namespace App\Http\Requests\Public;

use App\Enums\MaritalStatus;
use App\Enums\ModeOfStudy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBioDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'state_of_origin' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'mode_of_study' => ['required', Rule::enum(ModeOfStudy::class)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
