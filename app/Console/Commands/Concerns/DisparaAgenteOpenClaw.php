<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Lógica compartilhada para acionar um agente do OpenClaw sob demanda.
 *
 * Usado pelos commands de disparo (cotador e notificação). O comando executado
 * é um wrapper instalado no servidor (sudoers NOPASSWD) que roda o agente em
 * background — ver docs/integracao-cotador-openclaw.md.
 */
trait DisparaAgenteOpenClaw
{
    /**
     * Executa o comando de disparo protegido por um lock de cache, garantindo
     * que apenas um disparo ocorra dentro da janela (cobre o intervalo até o
     * agente refletir o trabalho no banco — status "running"/broker_notified_at).
     *
     * @return int Command::SUCCESS | Command::FAILURE
     */
    protected function dispararAgente(string $command, string $lockKey, string $label, int $lockSeconds = 180): int
    {
        if (blank($command)) {
            Log::warning("[openclaw] comando de disparo não configurado ({$lockKey}); abortado.");

            return Command::FAILURE;
        }

        // Cache::add é atômico: false se a chave já existir (disparo recente em andamento).
        if (! Cache::add($lockKey, true, $lockSeconds)) {
            return Command::SUCCESS;
        }

        try {
            // O wrapper retorna imediatamente (o agente roda desacoplado em background).
            $result = Process::timeout(15)->run($command);

            if (! $result->successful()) {
                Log::error("[openclaw] falha ao disparar {$label}", [
                    'exit' => $result->exitCode(),
                    'stderr' => $result->errorOutput(),
                ]);

                return Command::FAILURE;
            }

            $this->info("{$label} disparado.");
            Log::info("[openclaw] {$label} disparado (havia trabalho pendente).");
        } catch (\Throwable $e) {
            Log::error("[openclaw] exceção ao disparar {$label}: ".$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
