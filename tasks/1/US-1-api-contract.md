# API Contract — US-1

## Agentes necessarios
- backend: sim
- frontend: nao

## Endpoints

### GET /health
- **Descricao:** Verifica se a aplicacao esta de pe. Retorna JSON simples com status ok. Disponivel sem autenticacao para health checks de infra.
- **Body:** N/A
- **Resposta 200:** `{ "status": "ok" }`
- **Resposta 4XX:** N/A (nao ha validacao — qualquer erro seria 500 de infra)

## Dados em memoria
Nenhuma estrutura de dados de negocio neste checkpoint. O unico estado verificavel e a conectividade com PostgreSQL e Redis (confirmada pelo boot do Laravel sem excecoes).

## Observacoes

- A rota `/health` e registrada em `routes/web.php` (nao em `routes/api.php`) para que seja acessivel sem o prefixo `/api` e sem middleware de autenticacao.
- O Docker Compose deve expor 4 servicos: `app` (PHP-FPM 8.3), `nginx` (reverse proxy na porta 8080), `postgres` (banco primario), `redis` (cache/queue/sessao).
- O arquivo `.env` deve ter `DB_HOST=postgres` e `REDIS_HOST=redis` para resolucao de nomes dentro da rede Docker.
- Migrations devem rodar via `php artisan migrate` dentro do container `app`; nao ha migrations de negocio neste US, apenas as migrations padrao do Laravel (users, cache, jobs).
- Nao implementar autenticacao, models de negocio ou qualquer endpoint alem de `/health` neste checkpoint.
- A aplicacao deve ser acessivel em `http://localhost:8080/health` apos `docker compose up -d`.
