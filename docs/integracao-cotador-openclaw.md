# Integração: disparo dos agentes do OpenClaw sob demanda

> **TL;DR** — Trocamos o *polling* caro (o OpenClaw chamava o LLM a cada poucos minutos
> mesmo sem trabalho) por *push*: o Laravel vigia o banco (query SQL, custo zero) e só
> aciona os agentes quando há trabalho real. Dois disparos foram migrados:
> **cotador** (executa cotações) e **notificação** (devolutiva ao corretor).

---

## 1. Contexto e motivação

O sistema Mestres Cotações tem duas camadas:

| Camada | O que é | Papel |
|--------|---------|-------|
| **Laravel** (este repo) | API + banco `assistente_cotacoes` | Guarda solicitações, sub-solicitações, corretores, seguradoras e os `prompt_instructions` |
| **OpenClaw** (`mestresdoseguro-assistant`) | Agentes de IA (`orquestrador`, `cotador`) | Conversa no WhatsApp e executa cotações via browser |

Originalmente, o processamento era acionado por **crons internos do OpenClaw**, que invocavam
os agentes — e, portanto, o LLM (`claude-sonnet-4-6`) — a cada ciclo, **mesmo sem trabalho**:

| Cron antigo (OpenClaw) | Frequência | Execuções/dia em vazio |
|------------------------|------------|------------------------|
| `cotador-check` | 3 min | ~480 |
| `notificacao-pendente-check` | 5 min | ~288 |

Isso esgotou os créditos da API Anthropic. A solução inverte o controle: **o Laravel decide
quando há trabalho** (query SQL, custo zero) e só então dispara o agente. O LLM só roda
quando há, de fato, algo a fazer.

```
┌─────────────────────────┐         ┌──────────────────────────┐
│  LARAVEL (scheduler)     │         │  OPENCLAW (mestremario)  │
│  a cada 1 min            │         │                          │
│  → SÓ query SQL (grátis) │         │                          │
│                          │ ──────▶ │  cotador  (cotação)      │
│  Há trabalho?            │ gatilho │  orquestrador (devolutiva)│
│   └─ não → nada          │         │  SÓ quando há trabalho   │
└─────────────────────────┘         └──────────────────────────┘
```

---

## 2. Os dois disparos

### 2.1. Cotador — `cotacoes:disparar-cotador`

1. Existe `CotacaoSubSolicitacao` com status `pending`? Se não, encerra (silencioso).
2. Existe alguma `running`? Se sim, encerra (já há um cotador trabalhando — **lock natural via banco**).
3. Adquire o lock de cache `cotador:dispatching` (180s) e dispara o agente `cotador`, que
   processa a sub-solicitação mais antiga (login na seguradora + cotação via browser).

### 2.2. Notificação — `cotacoes:disparar-notificacao`

1. Existe sub-solicitação `completed`/`failed`, com `broker_notified_at IS NULL` e
   `completed_at` há **mais de 5 min**? Se não, encerra (silencioso).
2. Adquire o lock `notificacao:dispatching` (180s) e dispara o agente `orquestrador` com a
   mensagem *"Verificar sub-solicitações não notificadas"*. Ele envia a devolutiva ao
   corretor via WhatsApp (resultado se `completed`; erro traduzido se `failed`) e marca
   `broker_notified_at` em cada sub notificada.

> **Carência de 5 min:** dá tempo para a notificação *reativa* (quando o corretor manda
> mensagem) acontecer primeiro e permite agrupar várias seguradoras numa só devolutiva.

Em ambos, o disparo é **fire-and-forget**: o wrapper roda o `docker exec` em background e
retorna na hora, sem bloquear o scheduler. O lock de cache garante disparo único enquanto o
agente reflete o trabalho no banco (`running` / `broker_notified_at`).

### Transporte escolhido

`docker exec` no container do OpenClaw, autorizado ao usuário `deploy` por uma regra
**sudoers NOPASSWD restrita a wrappers fixos** (sem wildcard) — menor privilégio com o mínimo
de código. Alternativas (grupo docker; cliente WebSocket em PHP; CLI `openclaw` no host) foram
descartadas por dar root no host, exigir reimplementar protocolo não-documentado, ou instalar
o pacote completo do OpenClaw só para um RPC.

---

## 3. Componentes no código (este repositório)

| Arquivo | Papel |
|---------|-------|
| `app/Console/Commands/Concerns/DisparaAgenteOpenClaw.php` | Trait com a lógica comum (lock + Process + log) |
| `app/Console/Commands/DispararCotadorPendente.php` | Command `cotacoes:disparar-cotador` |
| `app/Console/Commands/DispararNotificacaoPendente.php` | Command `cotacoes:disparar-notificacao` |
| `routes/console.php` | Agenda os dois commands (`everyMinute`, `withoutOverlapping`) |
| `config/openclaw.php` | Blocos `cotador` e `notificacao` (`dispatch_enabled`, `trigger_command`) |
| `.env.example` | Variáveis `OPENCLAW_COTADOR_*` e `OPENCLAW_NOTIFICACAO_*` |
| `deploy/disparar-cotador.sh` | Wrapper → `/usr/local/bin/disparar-cotador` |
| `deploy/disparar-notificacao.sh` | Wrapper → `/usr/local/bin/disparar-notificacao` |
| `deploy/sudoers-cotacoes` | Regras → `/etc/sudoers.d/cotacoes-openclaw` |

### Variáveis de ambiente

```dotenv
# Liga/desliga cada disparo (pausar sem mexer no cron)
OPENCLAW_COTADOR_DISPATCH_ENABLED=true
OPENCLAW_COTADOR_TRIGGER_COMMAND="sudo /usr/local/bin/disparar-cotador"
OPENCLAW_NOTIFICACAO_DISPATCH_ENABLED=true
OPENCLAW_NOTIFICACAO_TRIGGER_COMMAND="sudo /usr/local/bin/disparar-notificacao"
```

---

## 4. Setup no servidor (deploy)

> Servidor: `ssh mestremario` · Projeto: `/var/www/mestres-cotacoes` (usuário `deploy`) ·
> Container OpenClaw: `mestresdoseguro-assistant`.

### 4.1. Instalar os wrappers (como root)

```bash
sudo install -m 0755 -o root -g root \
  /var/www/mestres-cotacoes/deploy/disparar-cotador.sh /usr/local/bin/disparar-cotador
sudo install -m 0755 -o root -g root \
  /var/www/mestres-cotacoes/deploy/disparar-notificacao.sh /usr/local/bin/disparar-notificacao
```

### 4.2. Instalar a regra sudoers (como root)

```bash
sudo install -m 0440 -o root -g root \
  /var/www/mestres-cotacoes/deploy/sudoers-cotacoes /etc/sudoers.d/cotacoes-openclaw
sudo visudo -cf /etc/sudoers.d/cotacoes-openclaw      # validar (NÃO pular)
sudo rm -f /etc/sudoers.d/cotador-cotacoes            # remover regra antiga (só cotador), se existir
```

### 4.3. Testar os disparos (como deploy)

```bash
sudo su deploy
sudo /usr/local/bin/disparar-cotador        # não deve pedir senha
sudo /usr/local/bin/disparar-notificacao    # não deve pedir senha
tail -f /var/log/cotador-trigger.log /var/log/notificacao-trigger.log
```

### 4.4. Cron do scheduler do Laravel (como deploy)

> Uma única entrada cobre **todos** os commands agendados. Se já existe (do cotador), pular.

```bash
sudo su deploy
crontab -e
# adicionar:
* * * * * cd /var/www/mestres-cotacoes && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### 4.5. Desativar os crons internos do OpenClaw

```bash
ssh mestremario "docker exec mestresdoseguro-assistant openclaw cron list"   # confirmar IDs

# cotador-check:
ssh mestremario "docker exec mestresdoseguro-assistant openclaw cron disable a6cc879c-cbf4-4fa1-89dd-4a5a2f0a7952"
# notificacao-pendente-check:
ssh mestremario "docker exec mestresdoseguro-assistant openclaw cron disable 8723f4c3-a013-4f13-a81b-10f859a2f78e"
```

> Observação: `openclaw cron list` só mostra jobs **enabled**. Para ver todos (incl. disabled),
> consulte `/root/.openclaw/cron/jobs.json` dentro do container.

---

## 5. Validação

```bash
# Commands rodam sem erro mesmo sem trabalho (silencioso):
cd /var/www/mestres-cotacoes
php artisan cotacoes:disparar-cotador
php artisan cotacoes:disparar-notificacao

# Cron do sistema executando o scheduler:
grep 'schedule:run' /var/log/syslog | tail -3

# Com trabalho pendente, confirmar disparo nos logs:
tail /var/log/cotador-trigger.log /var/log/notificacao-trigger.log
```

---

## 6. Pausar / Rollback

| Cenário | Ação |
|---------|------|
| Pausar um disparo | `OPENCLAW_COTADOR_DISPATCH_ENABLED=false` (ou `_NOTIFICACAO_`) no `.env` + `php artisan config:clear` |
| Voltar ao cron antigo do OpenClaw | `docker exec mestresdoseguro-assistant openclaw cron enable <id>` e remover a linha do crontab |
| Remover a permissão | `sudo rm /etc/sudoers.d/cotacoes-openclaw` |

---

## 7. Troubleshooting

| Sintoma | Causa provável | Verificação |
|---------|----------------|-------------|
| Cotações ficam `pending` e nada acontece | scheduler do Laravel não está rodando | `crontab -l` (deploy); `php artisan schedule:list` |
| Corretor não recebe a devolutiva | disparo de notificação desabilitado / agente com erro | `tail /var/log/notificacao-trigger.log`; conferir `broker_notified_at` no banco |
| `sudo: a senha é necessária` no log | sudoers não instalado/incorreto | `sudo visudo -cf /etc/sudoers.d/cotacoes-openclaw` |
| Disparo ocorre mas nada acontece | container parado ou agente com erro | `docker ps`; logs do trigger; `docker logs mestresdoseguro-assistant` |
| Notificação duplicada ao corretor | turn do orquestrador > lock e `broker_notified_at` não setado | revisar `LOCK_SECONDS`/`CARENCIA_MINUTOS`; conferir se o agente marca `broker_notified_at` |
| Esgotamento de créditos volta | cron antigo do OpenClaw reativado | `docker exec ... openclaw cron list` → não deve listar `cotador-check`/`notificacao-pendente-check` |
