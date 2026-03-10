<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCorretorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30|unique:corretores,phone,' . $this->route('corretore')->id,
            'claude_api_key' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }
}
