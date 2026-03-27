# PRD — US-1: Bootstrap e Ambiente

## Objetivo do Produto

Estabelecer a fundação técnica do encurtador de URLs: projeto Laravel 11 rodando em Docker com PostgreSQL, Redis e Nginx, com endpoint de health check funcional e ambiente completamente reprodutível para todos os desenvolvedores.

## Problema a Resolver

Sem um ambiente padronizado e reprodutível, cada desenvolvedor tem configurações diferentes (versões PHP, dependências, portas), causando inconsistências entre dev/staging/produção. Isso gera frustração, bugs ambientais e perda de produtividade.

## Solução Proposta

Container Docker com todos os serviços necessários pré-configurados (app, nginx, postgres, redis), health check endpoint para validação automática de ambiente, e documentação clara de setup.

---

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Teste |
|----|-----------|-----------|-------------------|
| RF-01 | GET /health retorna JSON com `{"status":"ok","timestamp":"..."}` quando serviços OK | Alta | HTTP 200, verifica JSON válido |
| RF-02 | Health check valida conectividade com PostgreSQL | Alta | Tenta `DB::select('SELECT 1')` |
| RF-03 | Health check valida conectividade com Redis | Alta | Tenta `Redis::ping()` |
| RF-04 | GET /health retorna HTTP 503 quando DB ou Redis falha | Alta | Simula falha, verifica resposta estruturada |
| RF-05 | Resposta 503 inclui `services` com status individual de cada dependência | Alta | Verifica JSON: `{"status":"error","services":{"database":"ok|error","redis":"ok|error"}}` |
| RF-06 | docker-compose up inicia todos os serviços (app, nginx, postgres, redis) | Alta | Verifica containers rodando, portas abertas |
| RF-07 | php artisan migrate executa sem erro dentro do container | Alta | Roda comando, valida schema criado |
| RF-08 | php artisan migrate:reset desfaz migrações corretamente | Alta | Roda comando, valida tabelas removidas |
| RF-09 | Dockerfile multistage com PHP 8.2+ e extensões essenciais | Alta | Verifica imagem, extensões instaladas |
| RF-10 | Nginx configurado para servir Laravel na porta 8080 | Alta | curl http://localhost:8080/health |
| RF-11 | Tentativas de conexão são logadas para diagnóstico | Alta | Verifica logs com `Log::info` / `Log::error` |
| RF-12 | `.gitignore` inclui vendor, node_modules, .env, storage/, bootstrap/cache/ | Alta | git status não rastreia arquivos sensíveis |
| RF-13 | `.dockerignore` otimiza build excluindo grandes diretórios | Alta | Verifica build time reduzido |
| RF-14 | PHPUnit configurado e teste de health check passa | Alta | ./vendor/bin/phpunit tests/Feature/HealthCheckTest.php |
| RF-15 | README.md inclui instruções de setup e comandos úteis | Alta | Novo dev consegue fazer clone e rodar |

## Requisitos Não Funcionais

| ID | Requisito | Critério |
|----|-----------|----------|
| RNF-01 | Tempo de resposta do health check < 500ms | Profiler ou curl -w time_total |
| RNF-02 | Logs estruturados para diagnóstico (stack, timestamps, contexto) | Log entries contêm info completa |
| RNF-03 | Build reprodutível em qualquer máquina (mesma imagem hash) | Docker run com mesmo contexto gera hash idêntico |
| RNF-04 | Ambiente idêntico entre dev/staging/produção | docker-compose.yml + Dockerfile = base compartilhada |
| RNF-05 | OPcache habilitado em php.ini para performance | Verifica config via phpinfo() |
| RNF-06 | Health checks no docker-compose garantem readiness | PostgreSQL e Redis aguardam healthcheck antes de app iniciar |
| RNF-07 | Volumes e bind mounts configurados para live reload | Code changes refletem imediatamente sem rebuild |

---

## Requisitos Funcionais Dependentes (Identificados no API Contract)

### Lacunas de Implementação Identificadas

1. **Rota `/health` — lógica incompleta**
   - Atual: retorna apenas `{"status":"ok"}`
   - Esperado: verificar DB e Redis, incluir `timestamp`, retornar 503 se falha

2. **`.dockerignore` ausente**
   - Impacto: build desnecessariamente lento
   - Deve excluir: vendor/, node_modules/, .git/, storage/, bootstrap/cache/, .env, logs

3. **`.gitignore` — entradas ausentes**
   - Lacuna: `bootstrap/cache/` não listado explicitamente

4. **docker-compose.yml — health checks ausentes**
   - PostgreSQL e Redis sem blocos `healthcheck:`
   - `depends_on` do app não aguarda readiness

5. **Teste PHPUnit para `/health` ausente**
   - Deve criar `tests/Feature/HealthCheckTest.php`
   - Cobertura: HTTP 200, estrutura JSON, resposta 503 em falha

---

## Stack Técnico

| Componente | Versão | Rationale |
|------------|--------|-----------|
| PHP | 8.2+ (recomenda-se 8.2-fpm) | Compatibilidade com Laravel 11, suporte LTS |
| Laravel | 11 | Framework moderno, routing, middleware, logging nativos |
| PostgreSQL | 15+ | ACID, escalável, suporta índices avançados |
| Redis | 7+ | Cache, sessions, queue, alta performance |
| Nginx | latest (alpine) | Reverse proxy, performance, configuração clara |
| Docker Compose | v2+ | Orquestração local, sintaxe moderna |
| PHPUnit | 10+ | Testing framework padrão Laravel |

---

## Entregáveis Esperados

### Código e Configuração
1. ✅ Projeto Laravel 11 com estrutura padrão (`composer.json`, app/, config/, routes/, etc.)
2. ✅ `docker-compose.yml` com 4 serviços: app (PHP-FPM), nginx, postgres, redis
3. ✅ `Dockerfile` multistage com PHP 8.2+, extensões (pdo_pgsql, redis, curl, json, mbstring)
4. ✅ `.env.example` com variáveis: DB_HOST=postgres, REDIS_HOST=redis, etc.
5. ✅ `.dockerignore` otimizado
6. ✅ `.gitignore` com entradas completas
7. ✅ `docker/php.ini` com OPcache habilitado
8. ✅ `docker/nginx/default.conf` servindo porta 8080

### Implementação da Rota de Health Check
1. ⚠️ `routes/web.php` — rota `GET /health` registrada
2. ⚠️ `app/Http/Controllers/HealthController.php` — lógica de verificação:
   - Testa `DB::select('SELECT 1')`
   - Testa `Redis::ping()`
   - Retorna 200 com `{"status":"ok","timestamp":"ISO8601"}`
   - Retorna 503 com `{"status":"error","timestamp":"...","services":{...}}` se falha
   - Loga tentativas via `Log::info()` / `Log::error()`

### Migrações e Banco
1. ✅ `database/migrations/` estrutura pronta
2. ✅ `database/seeders/DatabaseSeeder.php` existente
3. ✅ Migrations revertíveis (up/down implementado)

### Testes
1. ⚠️ `tests/Feature/HealthCheckTest.php` — cobertura de:
   - GET /health retorna 200 quando DB e Redis OK
   - GET /health retorna 503 quando DB ou Redis falha
   - Resposta inclui `timestamp` no formato ISO 8601
   - Resposta 503 inclui `services` com status individual

### Documentação
1. ✅ `README.md` com:
   - Descrição breve do projeto
   - Stack técnico
   - Instruções para rodar localmente
   - Comandos úteis (migrate, tinker, queue, etc.)
   - Troubleshooting de ambiente

### Pronto para Desenvolvimento
1. `docker-compose up -d` inicia todos os serviços
2. `docker-compose exec app php artisan migrate` roda migrations
3. `http://localhost:8080/health` acessível e retorna 200
4. `docker-compose logs -f app` mostra logs úteis
5. `docker-compose down` teardown limpo sem artefatos

---

## Plano de Testes (QA)

### Testes Automatizados (PHPUnit)

#### HealthCheckTest.php
```
✓ GET /health retorna HTTP 200 com status "ok" quando DB e Redis OK
✓ GET /health inclui timestamp em formato ISO 8601
✓ GET /health retorna HTTP 503 quando PostgreSQL falha
✓ GET /health retorna HTTP 503 quando Redis falha
✓ Resposta 503 inclui services com status individual
✓ Health check não requer autenticação
```

### Testes Manuais (Smoke Tests)

#### Setup e Startup
```bash
# Clonar, build e rodar
git clone <repo>
cd <repo>
docker-compose build --no-cache
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

#### Cenário 1: Health Check Funciona
- Quando: `curl -i http://localhost:8080/health`
- Então: HTTP 200, JSON `{"status":"ok","timestamp":"2026-03-27T..."}`

#### Cenário 2: Health Check Falha com DB Down
- Quando: `docker-compose stop postgres && curl -i http://localhost:8080/health`
- Então: HTTP 503, JSON inclui `"database":"error"`
- Depois: `docker-compose start postgres`

#### Cenário 3: Health Check Falha com Redis Down
- Quando: `docker-compose stop redis && curl -i http://localhost:8080/health`
- Então: HTTP 503, JSON inclui `"redis":"error"`
- Depois: `docker-compose start redis`

#### Cenário 4: Migrations Funcionam
- Quando: `docker-compose exec app php artisan migrate`
- Então: Saída mostra migrações executadas, nenhum erro

#### Cenário 5: Migrations Revertidas
- Quando: `docker-compose exec app php artisan migrate:reset`
- Então: Tabelas removidas, banco vazio (sem erro)

#### Cenário 6: Logs Mostram Detalhes
- Quando: `docker-compose logs app | grep -i "health\|database\|redis"`
- Então: Logs incluem tentativas de conexão com timestamps

#### Cenário 7: Performance
- Quando: `time curl http://localhost:8080/health`
- Então: Response time < 500ms

#### Cenário 8: Ports e Services
- Quando: `docker-compose ps`
- Então: Todos os containers rodando e healthy

---

## Critérios de Aceitação (Checklist Final)

### Infraestrutura
- [ ] `docker-compose up -d` inicia 4 containers saudáveis
- [ ] `docker-compose.yml` inclui healthchecks para postgres e redis
- [ ] `depends_on` aguarda `condition: service_healthy`
- [ ] Volumes montados corretamente (live reload funciona)
- [ ] `.dockerignore` reduz tamanho do build context

### Rota de Health Check
- [ ] `GET /health` retorna 200 com `{"status":"ok","timestamp":"..."}`
- [ ] `GET /health` retorna 503 com detalhes de serviços quando falha
- [ ] Timestamp em formato ISO 8601 (DATE_ATOM)
- [ ] Não requer autenticação ou session
- [ ] Tentativas de conexão logadas via Log::info/Log::error
- [ ] Resposta time < 500ms

### Banco de Dados
- [ ] `php artisan migrate` executa sem erro
- [ ] `php artisan migrate:reset` reverte sem erro
- [ ] Migrations são idempotentes e reversíveis
- [ ] `.gitignore` não rastreia storage/, bootstrap/cache/

### Testes
- [ ] `tests/Feature/HealthCheckTest.php` criado com 5+ testes
- [ ] `./vendor/bin/phpunit` passa com 100% de sucesso
- [ ] PHPUnit.xml configurado corretamente

### Documentação e Convenções
- [ ] `README.md` inclui setup, comandos, troubleshooting
- [ ] `.gitignore` e `.dockerignore` completos
- [ ] `.env.example` com todos os parâmetros necessários
- [ ] Segue convenções do CLAUDE.md (separação de concerns, DDD-like)

### Definição de Pronto
1. ✅ Novo desenvolvedor consegue: `git clone → docker-compose up -d → docker-compose exec app php artisan migrate → curl localhost:8080/health`
2. ✅ Todos os PHPUnit tests passam
3. ✅ Health check < 500ms
4. ✅ Logs ajudam diagnosticar problemas
5. ✅ Ambiente idêntico entre máquinas

---

## Fora do Escopo

Esta US é **puramente infraestrutura e bootstrap**. As seguintes features vêm em checkpoints posteriores:

- Autenticação de usuários (Checkpoint 2)
- Modelos de usuário e link (Checkpoint 3-4)
- Criação de links (Checkpoint 5)
- Redirect e rastreamento (Checkpoint 6-7)
- Rate limiting (Checkpoint 8)
- Monitoramento/APM (Checkpoint 9)
- Frontend/UI (Checkpoint 10)
- Configuração de produção (Checkpoint 11)
- SSL/HTTPS (hardening)

---

## Observações e Decisões Técnicas

### Decisão: Versão do PHP
- **Recomendação:** Fixar em `php:8.2-fpm` para compatibilidade com CLAUDE.md
- **Rationale:** 8.2 é LTS, mais estável em CI, alinhado com documentação existente
- **Alternativa aceita:** Manter `php:8.4-fpm` se todos os testes passarem e decisão for documentada

### Decisão: Localização da Lógica de Health Check
- **Recomendação:** Usar `App\Http\Controllers\HealthController` (classe dedicada)
- **Rationale:** Facilita teste unitário, segue padrão RESTful do projeto, reutilizável

### Decisão: PgBouncer no docker-compose
- **Observação:** `.yml` já inclui `pgbouncer` (porta 6432), que é entregável do Checkpoint 11
- **Status:** Aceitável como preparação antecipada; será utilizado em configuração de produção

### Decisão: Sem ORM Complexo no Health Check
- **Rationale:** Health check deve ser rápido e confiável; usar apenas `DB::select('SELECT 1')` em vez de Eloquent

### Decisão: Nenhum Endpoint API para Frontend
- **Rationale:** US-1 é puramente infraestrutura; agente frontend não é necessário

---

## Estimativas e Tempo (Guidance)

| Atividade | Tempo Estimado | Notas |
|-----------|----------------|-------|
| Setup inicial + Dockerfile | 1-2h | Estrutura base, imagens oficiais |
| Health Check Controller | 1-2h | DB/Redis checks, logging, resposta estruturada |
| Migrations + Seeder | 30min | Estrutura pronta, apenas validação |
| Testes (PHPUnit) | 1-2h | Cobertura de happy path e failure modes |
| Documentação (README) | 30min | Setup local, commands, troubleshooting |
| QA/Smoke Tests | 1-2h | Manual testing de todos os cenários |
| **Total** | **5-10h** | Tipicamente 1 day com múltiplos devs |

---

## Métricas de Sucesso

1. **Onboarding Time:** Novo dev consegue rodar ambiente em < 15 min
2. **Build Time:** Docker build < 2 min (com cache)
3. **Health Check Latency:** < 500ms consistentemente
4. **Test Coverage:** 90%+ de code coverage em HealthController
5. **Zero Environment Issues:** CI/CD passa sem ajustes locais
6. **Documentation Clarity:** Nenhuma pergunta de setup em Slack/docs

---

## Links de Referência

- [Laravel 11 Docs](https://laravel.com/docs/11.x)
- [Docker Compose Docs](https://docs.docker.com/compose/)
- [PostgreSQL Official Images](https://hub.docker.com/_/postgres)
- [Redis Official Images](https://hub.docker.com/_/redis)
- [PHP Official Images](https://hub.docker.com/_/php)
- [Laravel Health Checks](https://laravel.com/docs/11.x/monitoring#defining-health-checks)
