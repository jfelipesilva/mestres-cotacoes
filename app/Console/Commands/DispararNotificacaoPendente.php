<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DisparaAgenteOpenClaw;
use App\Enums\StatusSubSolicitacao;
use App\Models\CotacaoSubSolicitacao;
use Illuminate\Console\Command;

/**
 * Aciona o agente "orquestrador" do OpenClaw para notificar corretores sobre
 * cotações finalizadas (devolutiva proativa via WhatsApp).
 *
 * Substitui o antigo cron interno do OpenClaw (notificacao-pendente-check, a
 * cada 5 min), que invocava o LLM mesmo sem nada a notificar. Aqui o scheduler
 * do Laravel só faz uma query SQL e dispara o agente apenas quando há
 * sub-solicitação finalizada e ainda não comunicada ao corretor.
 *
 * A janela de 5 minutos (completed_at < now - 5min) é proposital: dá tempo para
 * a notificação reativa (quando o corretor interage) acontecer primeiro e
 * permite agrupar várias seguradoras da mesma solicitação numa só devolutiva.
 *
 * Ver: docs/integracao-cotador-openclaw.md
 */
class DispararNotificacaoPendente extends Command
{
    use DisparaAgenteOpenClaw;

    protected $signature = 'cotacoes:disparar-notificacao';

    protected $description = 'Dispara o orquestrador para notificar corretores sobre cotações finalizadas não comunicadas (push, sem polling com IA)';

    /** Carência antes de notificar — espelha a regra do agente (Seção 7 do prompt). */
    private const CARENCIA_MINUTOS = 5;

    public function handle(): int
    {
        if (! config('openclaw.notificacao.dispatch_enabled', true)) {
            return self::SUCCESS;
        }

        // Há cotação finalizada, ainda não notificada e fora da carência?
        $temPendente = CotacaoSubSolicitacao::whereIn('status', [
            StatusSubSolicitacao::Completed,
            StatusSubSolicitacao::Failed,
        ])
            ->whereNull('broker_notified_at')
            ->where('completed_at', '<', now()->subMinutes(self::CARENCIA_MINUTOS))
            ->exists();

        if (! $temPendente) {
            return self::SUCCESS; // silencioso (roda a cada minuto)
        }

        // O lock cobre a duração do turn do orquestrador (que marca broker_notified_at
        // por sub conforme notifica), evitando disparo duplo no minuto seguinte.
        return $this->dispararAgente(
            config('openclaw.notificacao.trigger_command'),
            'notificacao:dispatching',
            'Notificação',
        );
    }
}
