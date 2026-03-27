# Decisoes Tecnicas

## US-1 — Bootstrap e Ambiente

### Versao do PHP no Dockerfile

**Data:** 2026-03-27
**Decisao:** Manter `php:8.4-fpm` no Dockerfile.
**Contexto:** A user story especifica "PHP 8.2+". O Dockerfile usa `php:8.4-fpm`. PHP 8.4 e compativel com o requisito "8.2+" e e a versao mais recente com suporte ativo. Mantido para aproveitar melhorias de performance e seguranca da versao mais recente.
**Consequencias:** Ambiente de desenvolvimento usa PHP 8.4. Todos os testes passam. CI deve usar a mesma imagem.

### Localizacao da logica de health check

**Data:** 2026-03-27
**Decisao:** Logica de health check em `App\Http\Controllers\HealthController` (classe dedicada) em vez de closure no `routes/web.php`.
**Contexto:** Facilita teste unitario isolado e segue o padrao RESTful de controllers do projeto (conforme CLAUDE.md). Closures em rotas sao dificeis de mockar e testar.
**Consequencias:** Controller dedicado em `app/Http/Controllers/HealthController.php`, injetado via `[HealthController::class, 'check']` na rota.

### Campo `services` apenas na resposta 503

**Data:** 2026-03-27
**Decisao:** O campo `services` com status individual de cada dependencia so aparece na resposta 503, nao na 200.
**Contexto:** Na resposta 200 todos os servicos estao ok — a informacao seria redundante. Na 503 o campo e essencial para diagnostico rapido de qual dependencia falhou.
**Consequencias:** Clientes consumindo a API de health devem tratar a ausencia do campo `services` na resposta 200 como comportamento esperado.

### PgBouncer no docker-compose

**Data:** 2026-03-27
**Decisao:** Manter o servico `pgbouncer` no `docker-compose.yml` mesmo sendo um entregavel do Checkpoint 11.
**Contexto:** O servico foi adicionado antecipadamente como preparacao para producao. Nao interfere no desenvolvimento da US-1. O `.env.example` documenta o uso via `DB_PGBOUNCER_PORT=6432`.
**Consequencias:** Docker compose sobe 5 servicos (app, nginx, postgres, pgbouncer, redis) em vez de 4. O servico extra e opcional para desenvolvimento local.
