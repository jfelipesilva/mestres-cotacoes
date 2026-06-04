<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Disparo do cotador (OpenClaw) sob demanda
|--------------------------------------------------------------------------
| A cada minuto faz APENAS uma query SQL: se houver sub-solicitação pendente
| e nenhuma em execução, aciona o agente cotador do OpenClaw. Substitui o
| antigo cron interno do OpenClaw que chamava o LLM em vazio a cada 3 min.
| Ver: docs/integracao-cotador-openclaw.md
*/
Schedule::command('cotacoes:disparar-cotador')
    ->everyMinute()
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Disparo da notificação ao corretor (OpenClaw) sob demanda
|--------------------------------------------------------------------------
| A cada minuto faz APENAS uma query SQL: se houver cotação finalizada não
| comunicada ao corretor (fora da carência de 5 min), aciona o orquestrador
| para enviar a devolutiva. Substitui o cron interno notificacao-pendente-check.
| Ver: docs/integracao-cotador-openclaw.md
*/
Schedule::command('cotacoes:disparar-notificacao')
    ->everyMinute()
    ->withoutOverlapping();
