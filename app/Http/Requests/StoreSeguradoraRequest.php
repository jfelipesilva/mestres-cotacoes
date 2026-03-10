<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeguradoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'system_url' => 'nullable|url|max:500',
            'prompt_instructions' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
