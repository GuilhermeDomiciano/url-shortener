# API Contract — US-1

## Agentes necessarios
- backend: sim
- frontend: nao

## Endpoints

### GET /health

- **Descricao:** Verifica se a aplicacao esta saudavel, testando conectividade com PostgreSQL e Redis. Retorna 200 quando ambas as dependencias estao disponiveis e 503 quando qualquer uma delas falha.
- **Body:** N/A
- **Headers de requisicao:** nenhum obrigatorio
- **Resposta 200 (todos os servicos ok):**
  ```json
  {
    "status": "ok",
    "timestamp": "2026-03-27T12:00:00+00:00"
  }
  ```
- **Resposta 503 (uma ou mais dependencias com falha):**
  ```json
  {
    "status": "error",
    "timestamp": "2026-03-27T12:00:00+00:00",
    "services": {
      "database": "ok | error",
      "redis": "ok | error"
    }
  }
  ```

**Notas:**
- `timestamp` no formato ISO 8601 (DATE_ATOM).
- O campo `services` so aparece na resposta 503 para facilitar diagnostico.
- A rota nao deve exigir autenticacao nem session.
- Tentativas de conexao devem ser logadas via `Log::info` / `Log::error` para rastreabilidade.

---

## Estado atual do repositorio vs. requisitos da US-1

### Ja implementado
| Item | Arquivo | Status |
|------|---------|--------|
| Projeto Laravel 11 | `composer.json` | OK |
| `docker-compose.yml` com app, nginx, postgres, redis, pgbouncer | `docker-compose.yml` | OK (pgbouncer e bonus) |
| `.env.example` com DB_HOST, REDIS_HOST, etc. | `.env.example` | OK |
| Dockerfile com PHP-FPM, pdo_pgsql, redis PECL, OPcache | `Dockerfile` | OK (ver lacuna abaixo) |
| Nginx na porta 8080 | `docker/nginx/default.conf` | OK |
| OPcache habilitado | `docker/php.ini` | OK |
| Rota `GET /health` registrada | `routes/web.php` | PARCIAL (ver lacuna) |
| Migrations existentes | `database/migrations/` | OK |
| DatabaseSeeder existente | `database/seeders/DatabaseSeeder.php` | OK |
| `.gitignore` basico | `.gitignore` | PARCIAL (ver lacuna) |
| README.md com instrucoes | `README.md` | OK |
| PHPUnit configurado | `phpunit.xml` | OK |

### Lacunas identificadas (o que o backend precisa implementar)

#### 1. Rota `/health` — logica incompleta
**Arquivo:** `routes/web.php` (linhas 10-12)

O handler atual retorna apenas `{"status":"ok"}` sem verificar PostgreSQL nem Redis, sem `timestamp` e sem resposta 503.

**Implementacao esperada:** Mover a logica para `App\Http\Controllers\HealthController` (ou closure expandida) que:
1. Tenta executar `DB::select('SELECT 1')` dentro de `try/catch`.
2. Tenta executar `Redis::ping()` dentro de `try/catch`.
3. Loga cada tentativa com `Log::info` ou `Log::error`.
4. Retorna 200 com `{"status":"ok","timestamp":"..."}` se ambos passarem.
5. Retorna 503 com `{"status":"error","timestamp":"...","services":{...}}` se qualquer um falhar.

#### 2. Dockerfile — versao do PHP
**Arquivo:** `Dockerfile` (linha 3)

Usa `php:8.4-fpm`. A user story exige `php:8.2+`. Embora 8.4 seja compativel, o time deve alinhar se quer fixar em 8.2 (mais conservador, CI mais estavel) ou aceitar 8.4. Decisao tecnica registrada abaixo.

#### 3. `.dockerignore` ausente
Nao existe `.dockerignore` no projeto. Sem ele, o contexto de build do Docker inclui `vendor/`, `node_modules/`, `.git/`, `storage/`, etc., tornando o build desnecessariamente lento.

**Arquivo a criar:** `.dockerignore` na raiz com ao menos:
```
vendor/
node_modules/
.git/
storage/
bootstrap/cache/
.env
.env.*
*.log
public/build/
public/hot/
```

#### 4. `.gitignore` — entradas ausentes
O `.gitignore` atual nao inclui `storage/` nem `bootstrap/cache/`. A user story exige explicitamente esses itens.

**Linhas a adicionar em `.gitignore`:**
```
/storage/*.key
/bootstrap/cache/*.php
```
(Nota: `/storage/pail` ja esta presente; `bootstrap/cache/` como diretorio ainda nao esta explicitamente listado.)

#### 5. `docker-compose.yml` — health checks ausentes
Os servicos `postgres` e `redis` nao possuem blocos `healthcheck:`. Sem isso, o servico `app` pode iniciar antes do banco estar pronto, causando falhas intermitentes.

**Adicionar em `postgres`:**
```yaml
healthcheck:
  test: ["CMD-SHELL", "pg_isready -U postgres"]
  interval: 10s
  timeout: 5s
  retries: 5
```

**Adicionar em `redis`:**
```yaml
healthcheck:
  test: ["CMD", "redis-cli", "ping"]
  interval: 10s
  timeout: 5s
  retries: 5
```

**Atualizar `depends_on` do servico `app` para usar `condition: service_healthy`.**

#### 6. Teste PHPUnit para `GET /health` ausente
`tests/Feature/ExampleTest.php` testa `GET /`, mas nao existe teste para `GET /health`.

**Arquivo a criar:** `tests/Feature/HealthCheckTest.php` cobrindo:
- `GET /health` retorna HTTP 200 quando DB e Redis estao disponiveis (usando mocks/fakes).
- Estrutura JSON contem `status` e `timestamp`.

---

## Checklist de implementacao para o backend

- [ ] Implementar handler completo de `GET /health` com verificacao de DB e Redis
- [ ] Adicionar campo `timestamp` (ISO 8601) na resposta
- [ ] Retornar HTTP 503 com detalhes de servicos quando falha
- [ ] Logar tentativas de conexao via `Log::info` / `Log::error`
- [ ] Criar `.dockerignore` na raiz do projeto
- [ ] Adicionar entradas `bootstrap/cache/` ao `.gitignore`
- [ ] Adicionar blocos `healthcheck:` em `postgres` e `redis` no `docker-compose.yml`
- [ ] Atualizar `depends_on` do servico `app` para aguardar healthcheck dos dependentes
- [ ] Criar `tests/Feature/HealthCheckTest.php` cobrindo HTTP 200 e estrutura da resposta
- [ ] (Decisao) Definir se Dockerfile deve fixar em `php:8.2-fpm` ou manter `php:8.4-fpm`

---

## Observacoes

### Decisao: Versao do PHP no Dockerfile
A user story especifica "PHP 8.2+". O Dockerfile atual usa `php:8.4-fpm`. Para manter compatibilidade com o CLAUDE.md (que referencia PHP 8.2+) e evitar surpresas em CI, recomenda-se fixar em `php:8.2-fpm` por ora. Se o time preferir a versao mais recente, e aceitavel manter 8.4 desde que todos os testes passem e a decisao seja documentada.

### Decisao: Localizacao da logica de health check
A logica de verificacao de DB e Redis deve residir em `App\Http\Controllers\HealthController` (classe dedicada) em vez de closure no `routes/web.php`. Isso facilita teste unitario e segue o padrao de Controllers RESTful do projeto (conforme CLAUDE.md).

### Decisao: Nao ha endpoint de API para frontend nesta US
US-1 e puramente infraestrutura. O agente frontend nao e necessario.

### Observacao: PgBouncer no docker-compose
O `docker-compose.yml` ja inclui o servico `pgbouncer` (porta 6432), que e um entregavel do Checkpoint 11 (producao), nao do Checkpoint 1. Isso e aceitavel — o servico esta presente como preparacao antecipada. O `.env.example` documenta o uso via `DB_PGBOUNCER_PORT=6432`.
