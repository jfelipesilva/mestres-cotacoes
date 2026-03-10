<?php

namespace App\Observers;

use App\Actions\Cotacao\RecalcularStatusPaiAction;
use App\Models\CotacaoSubSolicitacao;

class CotacaoSubSolicitacaoObserver
{
    public function updated(CotacaoSubSolicitacao $sub): void
    {
        if ($sub->isDirty('status')) {
            app(RecalcularStatusPaiAction::class)->execute($sub);
        }
    }
}
