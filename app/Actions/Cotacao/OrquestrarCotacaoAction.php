<?php

namespace App\Actions\Cotacao;

use App\Enums\StatusSolicitacao;
use App\Enums\StatusSubSolicitacao;
use App\Models\Corretor;
use App\Models\CotacaoSolicitacao;
use App\Models\CotacaoSubSolicitacao;

class OrquestrarCotacaoAction
{
    public function execute(Corretor $corretor, array $data): CotacaoSolicitacao
    {
        $solicitacao = CotacaoSolicitacao::create([
            'corretor_id' => $corretor->id,
            'whatsapp_message_id' => $data['whatsapp_message_id'] ?? null,
            'raw_message' => $data['raw_message'],
            'vehicle_data' => $data['vehicle_data'] ?? null,
            'client_data' => $data['client_data'] ?? null,
            'status' => StatusSolicitacao::Pending,
        ]);

        $seguradorasHabilitadas = $corretor->seguradorasHabilitadas()
            ->where('seguradoras.is_active', true)
            ->get();

        foreach ($seguradorasHabilitadas as $seguradora) {
            CotacaoSubSolicitacao::create([
                'cotacao_solicitacao_id' => $solicitacao->id,
                'seguradora_id' => $seguradora->id,
                'status' => StatusSubSolicitacao::Pending,
                'attempts' => 0,
            ]);
        }

        if ($seguradorasHabilitadas->isNotEmpty()) {
            $solicitacao->update(['status' => StatusSolicitacao::Processing]);
        }

        return $solicitacao->load('subSolicitacoes.seguradora');
    }
}
