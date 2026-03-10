<?php

namespace App\Actions\Cotacao;

use App\Models\CotacaoSubSolicitacao;

class RecalcularStatusPaiAction
{
    public function execute(CotacaoSubSolicitacao $sub): void
    {
        $sub->solicitacao->recalcularStatus();
    }
}
