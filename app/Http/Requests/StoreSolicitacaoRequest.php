<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|max:30',
            'raw_message' => 'required|string',
            'whatsapp_message_id' => 'nullable|string',
            'vehicle_data' => 'nullable|array',
            'client_data' => 'nullable|array',
        ];
    }
}
