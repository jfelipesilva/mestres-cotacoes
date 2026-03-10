<?php

namespace App\Jobs;

use App\Models\CotacaoSubSolicitacao;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessarSubSolicitacaoJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CotacaoSubSolicitacao $subSolicitacao
    ) {}

    public function handle(): void
    {
        // Estrutura base — a logica do agente IA sera implementada depois.
        // Este job sera despachado quando o orquestrador precisar processar
        // uma sub-solicitacao de forma assincrona.
    }
}
