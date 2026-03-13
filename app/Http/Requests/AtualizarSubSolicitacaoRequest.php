<?php

namespace App\Http\Requests;

use App\Enums\StatusSubSolicitacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarSubSolicitacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(StatusSubSolicitacao::class)],
            'agent_log' => 'nullable|string',
            'error_message' => 'nullable|string',
            'result_data' => 'nullable|array',
            'proposal_url' => 'nullable|string|max:500',
            'broker_notified_at' => 'nullable|date',
            'pdf' => 'nullable|file|mimes:pdf|max:10240',
        ];
    }
}
