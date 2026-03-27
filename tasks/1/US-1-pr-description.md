# PR: US-1 — Bootstrap e Ambiente

## Summary
- Laravel 11 project running in Docker with PostgreSQL + Redis + Nginx
- `GET /health` endpoint checking PostgreSQL and Redis connectivity
- Returns 200 OK with `{"status":"ok","timestamp":"..."}` when healthy
- Returns 503 with per-service status when any dependency fails
- PHPUnit tests for all health check scenarios (happy path + failure cases)
- Content negotiation: JSON for API clients, HTML status page for browsers

## Changes
- `app/Http/Controllers/HealthController.php` — dedicated health check controller
- `resources/views/health.blade.php` — browser-friendly health status page
- `routes/web.php` — GET /health route
- `docker-compose.yml` — healthchecks for postgres/redis, service dependencies
- `tests/Feature/HealthCheckTest.php` — PHPUnit tests with mocks
- `.dockerignore` — optimized Docker build context

## Test plan
- [ ] `GET /health` → 200 `{"status":"ok","timestamp":"..."}` when services up
- [ ] `GET /health` → 503 `{"status":"error","services":{...}}` when DB down
- [ ] `GET /health` → 503 `{"status":"error","services":{...}}` when Redis down
- [ ] `./vendor/bin/phpunit tests/Feature/HealthCheckTest.php` passes
- [ ] `docker-compose up -d` starts all services

🤖 Generated with FusionCode by BRQ
