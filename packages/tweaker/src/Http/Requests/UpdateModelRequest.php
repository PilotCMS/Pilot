<?php

namespace Tweaker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('tweaker.enabled');
    }

    public function rules(): array
    {
        return [
            'model' => ['required', 'string'],
            'id' => ['required'],
            'field' => ['required', 'string'],
            'value' => ['nullable'],
        ];
    }
}
