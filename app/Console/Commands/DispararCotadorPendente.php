<?php

namespace App\Console\Commands;

use App\Enums\StatusSubSolicitacao;
use App\Models\CotacaoSubSolicitacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

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
    protected $signature = 'cotacoes:disparar-cotador';

    protected $description = 'Dispara o agente cotador do OpenClaw quando há sub-solicitação pendente (push, sem polling com IA)';

    /**
     * Janela (segundos) em que um disparo recém-feito bloqueia novos disparos,
     * cobrindo o intervalo entre acionar o agente e ele marcar a sub como "running".
     */
    private const LOCK_SECONDS = 180;

    public function handle(): int
    {
        if (! config('openclaw.cotador.dispatch_enabled', true)) {
            $this->info('Disparo do cotador desabilitado por configuração.');

            return self::SUCCESS;
        }

        // 1. Há trabalho a fazer?
        $temPendente = CotacaoSubSolicitacao::where('status', StatusSubSolicitacao::Pending)->exists();
        if (! $temPendente) {
            return self::SUCCESS; // nada a fazer — silencioso (roda a cada minuto)
        }

        // 2. Já há um cotador trabalhando? (lock natural via banco)
        $temRunning = CotacaoSubSolicitacao::where('status', StatusSubSolicitacao::Running)->exists();
        if ($temRunning) {
            return self::SUCCESS;
        }

        // 3. Lock curto para cobrir a janela entre disparar e o "running" ser setado.
        //    Cache::add é atômico: retorna false se a chave já existir.
        if (! Cache::add('cotador:dispatching', true, self::LOCK_SECONDS)) {
            return self::SUCCESS;
        }

        // 4. Dispara o agente (fire-and-forget — o wrapper roda em background).
        $command = config('openclaw.cotador.trigger_command');

        if (blank($command)) {
            Log::warning('[cotador] OPENCLAW_COTADOR_TRIGGER_COMMAND não configurado; disparo abortado.');

            return self::FAILURE;
        }

        try {
            // O wrapper retorna imediatamente (docker exec roda desacoplado em background).
            $result = Process::timeout(15)->run($command);

            if (! $result->successful()) {
                Log::error('[cotador] Falha ao disparar agente', [
                    'exit' => $result->exitCode(),
                    'stderr' => $result->errorOutput(),
                ]);

                return self::FAILURE;
            }

            $this->info('Cotador disparado para processar sub-solicitações pendentes.');
            Log::info('[cotador] Agente disparado (há sub-solicitação pendente).');
        } catch (\Throwable $e) {
            Log::error('[cotador] Exceção ao disparar agente: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
