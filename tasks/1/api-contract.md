# API Contract — US-1: Bootstrap e Ambiente

## Agentes necessarios
- backend: sim
- frontend: nao (puramente tecnico/infra)

## Endpoints

### GET /health
- **Descricao:** Verifica disponibilidade dos servicos (PostgreSQL + Redis). Retorna 200 quando ambas as dependencias estao disponiveis e 503 quando qualquer uma delas falha.
- **Body:** N/A
- **Auth:** Nenhuma (endpoint publico, sem autenticacao ou session)
- **Headers de resposta:** `Content-Type: application/json`
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
- `timestamp` no formato ISO 8601 (DATE_ATOM via `Carbon::now()->format(DATE_ATOM)`).
- O campo `services` so aparece na resposta 503 para facilitar diagnostico.
- Tentativas de conexao sao logadas via `Log::info('health.database')` / `Log::error('health.redis')`.
- Endpoint deve responder em menos de 500ms.

---

## Infraestrutura Docker

### Servicos docker-compose
- `app` (PHP-FPM 8.2+): `depends_on` postgres (healthy), redis (healthy)
- `nginx`: porta `8080:80`
- `postgres` (15+): healthcheck com `pg_isready`
- `redis` (7+): healthcheck com `redis-cli ping`
- `pgbouncer` (opcional): porta `6432`

### Health checks necessarios no docker-compose
**postgres:**
```yaml
healthcheck:
  test: ["CMD-SHELL", "pg_isready -U postgres"]
  interval: 10s
  timeout: 5s
  retries: 5
```

**redis:**
```yaml
healthcheck:
  test: ["CMD", "redis-cli", "ping"]
  interval: 10s
  timeout: 5s
  retries: 5
```

O servico `app` deve usar `condition: service_healthy` no `depends_on`.

---

## Implementacao de referencia

A logica de health check reside em `App\Http\Controllers\HealthController::check()`:

1. Executa `DB::select('SELECT 1')` em `try/catch` para verificar PostgreSQL.
2. Executa `Redis::ping()` em `try/catch` para verificar Redis.
3. Loga cada tentativa com `Log::info` ou `Log::error`.
4. Retorna HTTP 200 com `{"status":"ok","timestamp":"..."}` se ambos passarem.
5. Retorna HTTP 503 com `{"status":"error","timestamp":"...","services":{...}}` se qualquer um falhar.

Rota registrada em `routes/web.php`:
```php
Route::get('/health', [HealthController::class, 'check']);
```

---

## Dados em memoria
N/A — sem estado em memoria

## Observacoes

### Versao do PHP no Dockerfile
A user story especifica "PHP 8.2+". O Dockerfile usa `php:8.4-fpm`, que e compativel. Decisao: manter 8.4 (versao mais recente disponivel) e documentar como decisao tecnica.

### Controller dedicado
`HealthController` e uma classe dedicada (nao closure) em `App\Http\Controllers\HealthController`. Isso facilita testes unitarios com mocks e segue o padrao de Controllers RESTful do projeto (conforme CLAUDE.md e CONVENTIONS.md).

### Nao ha endpoint de API para frontend nesta US
US-1 e puramente infraestrutura. O agente frontend nao e necessario.

### PgBouncer
O `docker-compose.yml` inclui o servico `pgbouncer` (porta 6432) como preparacao antecipada para producao. Nao e obrigatorio para o health check da US-1.

### Teste PHPUnit esperado
Arquivo: `tests/Feature/HealthCheckTest.php`
- `GET /health` retorna HTTP 200 quando DB e Redis estao disponiveis (usando mocks/fakes).
- Estrutura JSON contem campos `status` e `timestamp`.
