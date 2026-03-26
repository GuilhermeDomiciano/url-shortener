# Backend — US-1

## Endpoints implementados

### GET /health
- **Body:** N/A
- **Resposta 200:** `{ "status": "ok" }`
- **Auth:** Nenhuma — rota publica registrada em `routes/web.php` sem middleware
- **URL acessivel:** `http://localhost:8080/health`

## Arquivos criados/modificados

- `routes/web.php` — rota `/health` ja estava corretamente implementada (verificado, sem alteracoes necessarias)
- `docker-compose.yml` — removido servico `pgbouncer` (era 5 servicos, agora sao exatamente 4: app, nginx, postgres, redis)
- `.env.example` — verificado: ja possui `DB_HOST=postgres`, `REDIS_HOST=redis`, `DB_CONNECTION=pgsql` (sem alteracoes)
- `Dockerfile` — verificado: usa `php:8.4-fpm` (PHP 8.4 >= 8.3+, conforme requisito), pdo_pgsql e redis instalados (sem alteracoes)
- `database/migrations/` — verificado: migrations padrao do Laravel presentes (users, cache, jobs) (sem alteracoes)
- `docs/API.md` — documentado endpoint /health

## Observacoes

- A rota `/health` esta em `routes/web.php` (nao em `routes/api.php`), portanto nao tem prefixo `/api` e nenhum middleware de autenticacao.
- O `docker-compose.yml` agora tem exatamente 4 servicos conforme o contrato: `app` (PHP-FPM), `nginx` (porta 8080), `postgres`, `redis`.
- O `.env.example` ja estava configurado corretamente com `DB_CONNECTION=pgsql`, `DB_HOST=postgres`, `REDIS_HOST=redis`.
- O Dockerfile usa PHP 8.4-fpm com extensoes `pdo`, `pdo_pgsql`, `opcache` e `redis`.
- Migrations padrao do Laravel estao presentes e prontas para executar com `php artisan migrate` dentro do container `app`.
- Nenhum endpoint adicional alem de `/health` foi implementado (fora do escopo do US-1).
