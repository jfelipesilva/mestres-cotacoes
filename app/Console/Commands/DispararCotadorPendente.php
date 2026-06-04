<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DisparaAgenteOpenClaw;
use App\Enums\StatusSubSolicitacao;
use App\Models\CotacaoSubSolicitacao;
use Illuminate\Console\Command;

/**
 * Aciona o agente "cotador" do OpenClaw sob demanda.
 *
 * Substitui o antigo cron interno do OpenClaw (cotador-check, a cada 3 min),
 * que invocava o LLM mesmo sem trabalho a fazer. Aqui o "vigia" é o scheduler
 * do Laravel, que só faz uma query SQL (custo zero) e dispara o agente apenas
 * quando há cotação real pendente — eliminando o consumo de tokens em vazio.
 *
 * Ver: docs/integracao-cotador-openclaw.md
 */
class DispararCotadorPendente extends Command
{
    use DisparaAgenteOpenClaw;

    protected $signature = 'cotacoes:disparar-cotador';

    protected $description = 'Dispara o agente cotador do OpenClaw quando há sub-solicitação pendente (push, sem polling com IA)';

    public function handle(): int
    {
        if (! config('openclaw.cotador.dispatch_enabled', true)) {
            return self::SUCCESS;
        }

        // 1. Há trabalho a fazer?
        $temPendente = CotacaoSubSolicitacao::where('status', StatusSubSolicitacao::Pending)->exists();
        if (! $temPendente) {
            return self::SUCCESS; // silencioso (roda a cada minuto)
        }

        // 2. Já há um cotador trabalhando? (lock natural via banco)
        $temRunning = CotacaoSubSolicitacao::where('status', StatusSubSolicitacao::Running)->exists();
        if ($temRunning) {
            return self::SUCCESS;
        }

        // 3. Dispara (lock de cache cobre a janela até o "running" ser setado).
        return $this->dispararAgente(
            config('openclaw.cotador.trigger_command'),
            'cotador:dispatching',
            'Cotador',
        );
    }
}
