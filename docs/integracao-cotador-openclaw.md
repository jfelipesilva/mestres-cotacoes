# Integração: disparo do cotador (OpenClaw) sob demanda

> **TL;DR** — Trocamos o *polling* caro (o OpenClaw chamava o LLM a cada 3 min mesmo sem
> trabalho) por *push*: o Laravel vigia o banco (query SQL, custo zero) e só aciona o
> agente cotador quando há cotação pendente. Resultado: de ~480 invocações de IA/dia → 0
> em vazio.

---

## 1. Contexto e motivação

O sistema Mestres Cotações tem duas camadas:

| Camada | O que é | Papel |
|--------|---------|-------|
| **Laravel** (este repo) | API + banco `assistente_cotacoes` | Guarda solicitações, sub-solicitações, corretores, seguradoras e os `prompt_instructions` |
| **OpenClaw** (`mestresdoseguro-assistant`) | Agentes de IA (`orquestrador`, `cotador`) | Conversa no WhatsApp e executa cotações via browser |

Originalmente, o processamento das cotações era acionado por um **cron interno do OpenClaw**
(`cotador-check`, a cada 3 minutos). Esse cron invocava o agente `cotador` — e, portanto, o
LLM (`claude-sonnet-4-6`) — a cada ciclo, **mesmo quando não havia nada a cotar**. Isso gerava
~480 execuções/dia em vazio e foi a causa de um esgotamento de créditos da API Anthropic.

A solução é inverter o controle: **o Laravel decide quando há trabalho** (uma simples query no
banco, sem custo) e só então dispara o agente. O LLM só roda quando há cotação real.

```
┌─────────────────────────┐         ┌──────────────────────────┐
│  LARAVEL (scheduler)     │         │  OPENCLAW (mestremario)  │
│  a cada 1 min            │         │                          │
│  → SÓ query SQL (grátis) │         │                          │
│                          │         │                          │
│  Há sub pendente E       │ ──────▶ │  Agente cotador          │
│  nenhuma "running"?      │ gatilho │  (LLM + browser)         │
│         │                │         │  SÓ quando há trabalho   │
│         └─ não → nada    │         │                          │
└─────────────────────────┘         └──────────────────────────┘
```

---

## 2. Como funciona o disparo

1. O scheduler do Laravel roda `cotacoes:disparar-cotador` **a cada minuto**.
2. O command verifica:
   - Existe alguma `CotacaoSubSolicitacao` com status `pending`? Se não, encerra (silencioso).
   - Existe alguma com status `running`? Se sim, encerra (já há um cotador trabalhando — **lock natural via banco**).
   - Consegue adquirir o lock de cache `cotador:dispatching` (TTL 180s)? Esse lock cobre a
     janela entre disparar o agente e ele marcar a sub como `running`, evitando disparo duplo.
3. Se passou pelas três checagens, executa o comando configurado em
   `config('openclaw.cotador.trigger_command')` — por padrão `sudo /usr/local/bin/disparar-cotador`.
4. O wrapper dispara o `docker exec ... openclaw agent --agent cotador` **em background**
   (fire-and-forget) e retorna na hora, sem bloquear o scheduler.

> O agente `cotador` processa a sub-solicitação **mais antiga** por execução. Havendo várias
> pendentes, o scheduler redispara nos minutos seguintes (após o `running` da anterior ser
> liberado), processando a fila sequencialmente.

### Transporte escolhido

O disparo usa **`docker exec` no container do OpenClaw**, autorizado ao usuário `deploy` por
uma regra **sudoers NOPASSWD restrita a um wrapper fixo** (sem wildcard). Alternativas avaliadas
e descartadas:

- *Adicionar `deploy` ao grupo docker*: simples, mas equivale a root no host.
- *Cliente WebSocket em PHP falando direto com o gateway*: sem mexer em permissão, mas exige
  reimplementar um protocolo não-documentado (`sessions.create`/`sessions.send`/`agent.wait`)
  que quebra a cada update do OpenClaw.
- *CLI `openclaw` no host via WebSocket*: robusto e sem permissão docker, mas instala o pacote
  completo (pesado, com browser/Playwright) só para disparar um RPC.

O wrapper + sudoers dá o **menor privilégio** com o **mínimo de código**: o `deploy` só consegue
"apertar o botão", nada além.

---

## 3. Componentes no código (este repositório)

| Arquivo | Papel |
|---------|-------|
| `app/Console/Commands/DispararCotadorPendente.php` | Command `cotacoes:disparar-cotador` — a lógica de decisão e disparo |
| `routes/console.php` | Agenda o command (`everyMinute`, `withoutOverlapping`) |
| `config/openclaw.php` | Bloco `cotador` (`dispatch_enabled`, `trigger_command`) |
| `.env.example` | Variáveis `OPENCLAW_COTADOR_*` |
| `deploy/disparar-cotador.sh` | Wrapper a ser instalado no servidor como `/usr/local/bin/disparar-cotador` |
| `deploy/sudoers-cotador` | Regra a ser instalada como `/etc/sudoers.d/cotador-cotacoes` |

### Variáveis de ambiente

```dotenv
# Liga/desliga o disparo (útil para pausar sem mexer no cron)
OPENCLAW_COTADOR_DISPATCH_ENABLED=true
# Comando que dispara o agente (wrapper com sudoers no servidor)
OPENCLAW_COTADOR_TRIGGER_COMMAND="sudo /usr/local/bin/disparar-cotador"
```

---

## 4. Setup no servidor (deploy)

> Servidor: `ssh mestremario` · Projeto: `/var/www/mestres-cotacoes` (usuário `deploy`) ·
> Container OpenClaw: `mestresdoseguro-assistant`.

### 4.1. Instalar o wrapper (como root)

```bash
sudo install -m 0755 -o root -g root \
  /var/www/mestres-cotacoes/deploy/disparar-cotador.sh \
  /usr/local/bin/disparar-cotador
```

### 4.2. Instalar a regra sudoers (como root)

```bash
sudo install -m 0440 -o root -g root \
  /var/www/mestres-cotacoes/deploy/sudoers-cotador \
  /etc/sudoers.d/cotador-cotacoes

# Validar a sintaxe (NÃO pular — sudoers quebrado trava o sudo):
sudo visudo -cf /etc/sudoers.d/cotador-cotacoes
```

### 4.3. Testar o disparo manual (como deploy)

```bash
sudo su deploy
sudo /usr/local/bin/disparar-cotador          # não deve pedir senha
tail -f /var/log/cotador-trigger.log          # acompanhar a execução do agente
```

### 4.4. Instalar o cron do scheduler do Laravel (como deploy)

> **Importante:** este servidor **não tinha** o scheduler do Laravel rodando — toda a
> automação periódica vinha do cron interno do OpenClaw. É preciso criar a entrada.

```bash
sudo su deploy
crontab -e
```

Adicionar a linha:

```cron
* * * * * cd /var/www/mestres-cotacoes && php artisan schedule:run >> /dev/null 2>&1
```

### 4.5. Desativar o cron interno do OpenClaw

O cron antigo (`cotador-check`) precisa ser desativado para não duplicar o processamento:

```bash
# Listar e confirmar o ID:
ssh mestremario "docker exec mestresdoseguro-assistant openclaw cron list"

# Desativar (NÃO remover — facilita rollback):
ssh mestremario "docker exec mestresdoseguro-assistant openclaw cron disable a6cc879c-cbf4-4fa1-89dd-4a5a2f0a7952"
```

> O cron `notificacao-pendente-check` (a cada 5 min, agente `orquestrador`) é leve e **não é
> alterado** por esta integração.

---

## 5. Validação

```bash
# 1. O command roda sem erro mesmo sem pendências (deve ser silencioso):
cd /var/www/mestres-cotacoes && php artisan cotacoes:disparar-cotador

# 2. Com uma sub-solicitação pendente, confirmar que dispara:
#    - criar/registrar uma cotação de teste
#    - rodar o command e ver "Cotador disparado..." + a entrada em /var/log/cotador-trigger.log

# 3. Confirmar que o agente marcou a sub como running/completed:
#    via dashboard ou: SELECT status FROM cotacao_sub_solicitacoes ORDER BY created_at DESC LIMIT 5;

# 4. Acompanhar consumo: não deve mais haver execuções do cotador em vazio.
```

---

## 6. Pausar / Rollback

| Cenário | Ação |
|---------|------|
| Pausar o disparo temporariamente | `OPENCLAW_COTADOR_DISPATCH_ENABLED=false` no `.env` + `php artisan config:clear` |
| Voltar ao modelo antigo (cron OpenClaw) | Reabilitar: `docker exec mestresdoseguro-assistant openclaw cron enable a6cc879c-...` e remover a linha do crontab do scheduler |
| Remover a permissão | `sudo rm /etc/sudoers.d/cotador-cotacoes` |

---

## 7. Troubleshooting

| Sintoma | Causa provável | Verificação |
|---------|----------------|-------------|
| Cotações ficam `pending` e nada acontece | scheduler do Laravel não está rodando | `crontab -l` (usuário deploy); `php artisan schedule:list` |
| `sudo: a senha é necessária` no log | sudoers não instalado/incorreto | `sudo visudo -cf /etc/sudoers.d/cotador-cotacoes` |
| Disparo ocorre mas nada cota | container parado ou agente com erro | `docker ps`; `tail /var/log/cotador-trigger.log`; `docker logs mestresdoseguro-assistant` |
| Cotador dispara em duplicidade | lock/`running` não está sendo setado | conferir se o agente chama o PATCH `status=running`; revisar `LOCK_SECONDS` no command |
| Esgotamento de créditos volta | cron antigo do OpenClaw reativado | `docker exec ... openclaw cron list` → `cotador-check` deve estar `disabled` |
