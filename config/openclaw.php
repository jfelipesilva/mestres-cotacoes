<?php

return [
    'agent_token' => env('OPENCLAW_AGENT_TOKEN'),
    'agent_endpoint' => env('OPENCLAW_AGENT_ENDPOINT'),
    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL'),
        'api_token' => env('WHATSAPP_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Disparo do agente cotador (push, sob demanda)
    |--------------------------------------------------------------------------
    | O scheduler (cotacoes:disparar-cotador) aciona o agente cotador do
    | OpenClaw apenas quando há sub-solicitação pendente. O comando abaixo é
    | um wrapper instalado no servidor (sudoers NOPASSWD) que executa o
    | "docker exec ... openclaw agent --agent cotador" em background.
    | Ver: docs/integracao-cotador-openclaw.md
    */
    'cotador' => [
        'dispatch_enabled' => env('OPENCLAW_COTADOR_DISPATCH_ENABLED', true),
        'trigger_command' => env('OPENCLAW_COTADOR_TRIGGER_COMMAND', 'sudo /usr/local/bin/disparar-cotador'),
    ],
];
