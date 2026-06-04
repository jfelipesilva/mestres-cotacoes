#!/bin/sh
#
# disparar-notificacao.sh — wrapper de disparo do orquestrador para a devolutiva
# proativa ao corretor (notificação de cotações finalizadas) no OpenClaw.
#
# Instalado no SERVIDOR como /usr/local/bin/disparar-notificacao (root:root, 0755)
# e autorizado ao usuário "deploy" via sudoers NOPASSWD (sem wildcard).
#
# Chamado pelo scheduler do Laravel (app/Console/Commands/DispararNotificacaoPendente.php)
# através de: sudo /usr/local/bin/disparar-notificacao
#
# Fire-and-forget: o turn do orquestrador roda desacoplado (setsid + &) e o
# wrapper retorna imediatamente, sem bloquear o scheduler.
#
# Ver: docs/integracao-cotador-openclaw.md
set -eu

CONTAINER="mestresdoseguro-assistant"
AGENTE="orquestrador"
MENSAGEM="Verificar sub-solicitações não notificadas"
LOG="/var/log/notificacao-trigger.log"

setsid /usr/bin/docker exec "$CONTAINER" \
  openclaw agent --agent "$AGENTE" --message "$MENSAGEM" \
  >>"$LOG" 2>&1 &

exit 0
