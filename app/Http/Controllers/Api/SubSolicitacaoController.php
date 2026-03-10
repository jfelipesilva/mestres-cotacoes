<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AtualizarSubSolicitacaoRequest;
use App\Enums\StatusSubSolicitacao;
use App\Models\CotacaoSubSolicitacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SubSolicitacaoController extends Controller
{
    public function show(CotacaoSubSolicitacao $subSolicitacao): JsonResponse
    {
        $subSolicitacao->load(['solicitacao.corretor', 'seguradora']);

        $vinculo = $subSolicitacao->solicitacao->corretor
            ->seguradoras()
            ->where('seguradora_id', $subSolicitacao->seguradora_id)
            ->first();

        return response()->json([
            'data' => [
                'id' => $subSolicitacao->id,
                'status' => $subSolicitacao->status->value,
                'attempts' => $subSolicitacao->attempts,
                'solicitacao' => [
                    'id' => $subSolicitacao->solicitacao->id,
                    'raw_message' => $subSolicitacao->solicitacao->raw_message,
                    'vehicle_data' => $subSolicitacao->solicitacao->vehicle_data,
                    'client_data' => $subSolicitacao->solicitacao->client_data,
                ],
                'seguradora' => [
                    'id' => $subSolicitacao->seguradora->id,
                    'name' => $subSolicitacao->seguradora->name,
                    'system_url' => $subSolicitacao->seguradora->system_url,
                    'prompt_instructions' => $subSolicitacao->seguradora->prompt_instructions,
                ],
                'credentials' => $vinculo ? [
                    'login_username' => $vinculo->pivot->login_username,
                    'login_password' => $vinculo->pivot->login_password,
                    'extra_credentials' => $vinculo->pivot->extra_credentials,
                ] : null,
            ],
        ]);
    }

    public function update(AtualizarSubSolicitacaoRequest $request, CotacaoSubSolicitacao $subSolicitacao): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['status'])) {
            $newStatus = StatusSubSolicitacao::from($data['status']);

            if ($newStatus === StatusSubSolicitacao::Running && !$subSolicitacao->started_at) {
                $data['started_at'] = now();
            }

            if (in_array($newStatus, [StatusSubSolicitacao::Completed, StatusSubSolicitacao::Failed])) {
                $data['completed_at'] = now();
            }

            if ($newStatus === StatusSubSolicitacao::Retrying) {
                $data['attempts'] = $subSolicitacao->attempts + 1;
            }
        }

        if ($request->hasFile('pdf')) {
            $path = "cotacoes/{$subSolicitacao->cotacao_solicitacao_id}/{$subSolicitacao->id}.pdf";
            $request->file('pdf')->storeAs(dirname($path), basename($path));
            $data['pdf_path'] = $path;
        }

        unset($data['pdf']);
        $subSolicitacao->update($data);

        return response()->json([
            'data' => [
                'id' => $subSolicitacao->id,
                'status' => $subSolicitacao->status->value,
                'attempts' => $subSolicitacao->attempts,
            ],
        ]);
    }
}
