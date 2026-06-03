#!/bin/sh
#
# disparar-cotador.sh — wrapper de disparo do agente cotador do OpenClaw.
#
# Instalado no SERVIDOR como /usr/local/bin/disparar-cotador (root:root, 0755)
# e autorizado ao usuário "deploy" via sudoers NOPASSWD (sem wildcard), de modo
# que o deploy só consegue "apertar este botão" — não controla argumentos nem
# pode rodar docker arbitrariamente.
#
# Chamado pelo scheduler do Laravel (app/Console/Commands/DispararCotadorPendente.php)
# através de: sudo /usr/local/bin/disparar-cotador
#
# Fire-and-forget: o agente pode levar minutos (cotação via browser), então o
# docker exec roda desacoplado (setsid + &) e o wrapper retorna imediatamente,
# sem bloquear o scheduler.
#
# Ver: docs/integracao-cotador-openclaw.md
set -eu

CONTAINER="mestresdoseguro-assistant"
AGENTE="cotador"
MENSAGEM="Verificar e processar sub-solicitações pendentes."
LOG="/var/log/cotador-trigger.log"

setsid /usr/bin/docker exec "$CONTAINER" \
  openclaw agent --agent "$AGENTE" --message "$MENSAGEM" \
  >>"$LOG" 2>&1 &

exit 0
