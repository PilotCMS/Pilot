<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class FrontendBlockUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && config('cms.frontend_editor.enabled', true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['nullable'],
            'locale' => ['nullable', 'string', 'max:12'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        return $this->validated('fields', []);
    }
}
