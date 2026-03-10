<?php

namespace App\Http\Controllers\Api;

use App\Actions\Cotacao\OrquestrarCotacaoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSolicitacaoRequest;
use App\Models\Corretor;
use App\Models\CotacaoSolicitacao;
use Illuminate\Http\JsonResponse;

class SolicitacaoController extends Controller
{
    public function store(StoreSolicitacaoRequest $request, OrquestrarCotacaoAction $action): JsonResponse
    {
        $corretor = Corretor::where('phone', $request->phone)
            ->where('is_active', true)
            ->first();

        if (!$corretor) {
            return response()->json([
                'error' => 'Corretor não encontrado ou inativo para o telefone informado.',
            ], 404);
        }

        $solicitacao = $action->execute($corretor, $request->validated());

        return response()->json([
            'data' => [
                'id' => $solicitacao->id,
                'status' => $solicitacao->status->value,
                'sub_solicitacoes' => $solicitacao->subSolicitacoes->map(fn ($sub) => [
                    'id' => $sub->id,
                    'seguradora' => $sub->seguradora->name,
                    'status' => $sub->status->value,
                ]),
            ],
        ], 201);
    }

    public function subSolicitacoes(CotacaoSolicitacao $solicitacao): JsonResponse
    {
        $solicitacao->load('subSolicitacoes.seguradora');

        return response()->json([
            'data' => $solicitacao->subSolicitacoes->map(fn ($sub) => [
                'id' => $sub->id,
                'seguradora_id' => $sub->seguradora_id,
                'seguradora_name' => $sub->seguradora->name,
                'status' => $sub->status->value,
                'attempts' => $sub->attempts,
                'started_at' => $sub->started_at,
                'completed_at' => $sub->completed_at,
            ]),
        ]);
    }
}
