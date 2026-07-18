<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class CheckResultRequest extends FormRequest
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
            'mat_no' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'mat_no.required' => 'Please enter your matriculation number.',
            'pin.required' => 'Please enter your access PIN.',
            'pin.digits' => 'Your access PIN is 6 digits.',
        ];
    }
}
