# US-1 — Bootstrap e Ambiente

**Objetivo:** Projeto Laravel 11 subindo em Docker com PostgreSQL + Redis, healthcheck funcionando e pronto para desenvolvimento.

---

## Contexto

Este é o primeiro checkpoint de um encurtador de URLs de alta performance. Precisamos estabelecer uma base sólida e reprodutível com infraestrutura containerizada, permitindo que o time trabalhe em um ambiente consistente e escalável.

---

## História de Usuário

Como **desenvolvedor**,
quero **ter um projeto Laravel 11 rodando em Docker com PostgreSQL e Redis pré-configurados**,
para que **eu possa focar em implementar features sem lidar com problemas de ambiente**.

---

## Critérios de Aceitação

### Ambiente e Infraestrutura
- [ ] Projeto Laravel 11 criado com Composer
- [ ] `docker-compose.yml` contém serviços: `app` (PHP-FPM), `nginx`, `postgres`, `redis`
- [ ] `.env` configurado com credenciais padrão (dev):
  - `DB_HOST=postgres`
  - `DB_PORT=5432`
  - `REDIS_HOST=redis`
  - `REDIS_PORT=6379`
  - `APP_KEY` gerada automaticamente
- [ ] `.env.example` inclui todos os parâmetros necessários
- [ ] Dockerfile multistage com PHP 8.2+ e extensões essenciais:
  - `php-pdo-pgsql`
  - `php-redis`
  - `php-curl`, `php-json`, `php-mbstring`
- [ ] Nginx configurado para servir Laravel na porta 8080
- [ ] OPcache habilitado em `docker/php.ini`

### Rota de Health Check
- [ ] GET `/health` retorna JSON: `{"status":"ok","timestamp":"2026-03-27T..."}`
- [ ] Health check valida conexão com PostgreSQL
- [ ] Health check valida conexão com Redis
- [ ] Resposta HTTP 200 quando ambas as dependências estão disponíveis
- [ ] Resposta HTTP 503 (Service Unavailable) se alguma dependência falhar

### Migrações e Banco de Dados
- [ ] Comando `php artisan migrate` executa sem erros dentro do container
- [ ] `php artisan migrate:reset` desfaz migrações corretamente
- [ ] Estrutura de migrations em `database/migrations/` pronta para futuros checkpoints
- [ ] Seed básico em `database/seeders/DatabaseSeeder.php` (pode estar vazio por agora)

### Documentação e Configuração
- [ ] README.md inclui:
  - Descrição breve do projeto
  - Stack técnico
  - Instruções para rodar localmente (docker-compose)
  - Como acessar a aplicação (URL)
  - Comandos úteis (migrate, tinker, queue, etc.)
- [ ] `.gitignore` inclui `/vendor`, `node_modules`, `.env` local, `storage/`, `bootstrap/cache/`
- [ ] `.dockerignore` inclui os mesmos itens para build otimizado

### Testes
- [ ] PHPUnit/Pest configurado e rodando
- [ ] Teste básico: `GET /health` retorna 200
- [ ] `./vendor/bin/phpunit tests/` executa sem erro (pode ter testes mínimos)

### Pronto para Desenvolvimento
- [ ] Comando `docker-compose up -d` inicia todos os serviços
- [ ] Comando `docker-compose exec app php artisan migrate` roda migrations
- [ ] Aplicação acessível em `http://localhost:8080/health`
- [ ] Logs aparecem via `docker-compose logs -f app`
- [ ] Teardown limpo com `docker-compose down`

---

## Regras Técnicas

- **Laravel 11:** Framework padrão com estrutura recomendada
- **PHP 8.2+:** Tipo mais recente da imagem oficial; não usar versões antigas
- **PostgreSQL 15+:** Banco de dados fonte da verdade
- **Redis 7+:** Cache, session, queue driver (não usar Memcached)
- **Nginx:** Reverse proxy para PHP-FPM, SSL pronto (mesmo que não ativado ainda)
- **Docker Compose v2+:** Não usar sintaxe v1
- **Sem ORM complexo por enquanto:** Connection simples apenas para health check
- **Logging estruturado:** Health check deve logar tentativas de conexão

---

## Plano de Testes (QA)

### Testes Funcionais

#### Setup
```bash
docker-compose build --no-cache
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

#### Cenário 1: Health Check funciona
- **Dado:** Docker rodando com todos os serviços
- **Quando:** Faz `curl http://localhost:8080/health`
- **Então:** Resposta HTTP 200 com JSON `{"status":"ok"}`

#### Cenário 2: Health Check falha quando PostgreSQL está down
- **Dado:** Docker rodando, PostgreSQL stopado
- **Quando:** Faz `curl http://localhost:8080/health`
- **Então:** Resposta HTTP 503 com mensagem de erro

#### Cenário 3: Migrations rodam sem erro
- **Dado:** Docker rodando, `.env` configurado
- **Quando:** Executa `docker-compose exec app php artisan migrate`
- **Então:** Nenhum erro; output mostra migrações executadas

#### Cenário 4: Migrations podem ser revertidas
- **Dado:** Migrations já foram rodadas
- **Quando:** Executa `docker-compose exec app php artisan migrate:reset`
- **Então:** Tabelas são removidas sem erro

### Testes de Integração

#### Cenário 5: Teste PHPUnit passa
- **Dado:** Projeto com PHPUnit configurado
- **Quando:** Executa `./vendor/bin/phpunit`
- **Então:** Todos os testes passam (pelo menos o health check)

#### Cenário 6: Logs mostram detalhes de conexão
- **Dado:** Docker rodando
- **Quando:** Acessa `/health` e `docker-compose logs app`
- **Então:** Logs mostram tentativas de conexão com DB/Redis

---

## Referência Visual (Figma)

N/A — Este checkpoint é puramente técnico/infraestrutura.

---

## Fora do Escopo

- Autenticação de usuários (vem em checkpoint futuro)
- Criação de links (vem em checkpoint 5)
- Redirect de links (vem em checkpoint 6)
- Analytics/rastreamento de clicks (vem em checkpoint 7)
- Rate limiting (vem em checkpoint 8)
- Frontend/UI (vem em checkpoint futuro)
- Configuração de produção (vem em checkpoint 11)
- SSL/HTTPS (será adicionado em hardening)
- Monitoramento/APM (será adicionado em checkpoint 9)

---

## Entregáveis

1. **Projeto Laravel 11** criado com estrutura padrão
2. **docker-compose.yml** com 4 serviços (app, nginx, postgres, redis)
3. **Dockerfile** multistage otimizado
4. **.env.example** com todas as variáveis necessárias
5. **Rota GET /health** testável
6. **README.md** com instruções de setup
7. **Teste PHPUnit** para health check
8. **Gitignore e Dockerignore** adequados

---

## Notas de Implementação

- Use imagens oficiais: `php:8.2-fpm`, `postgres:15-alpine`, `redis:7-alpine`, `nginx:latest`
- PHP-FPM escuta em porta 9000 (padrão)
- PostgreSQL escuta em porta 5432 (padrão, exposto apenas internamente)
- Redis escuta em porta 6379 (padrão, exposto apenas internamente)
- Nginx expõe porta 8080 para o host (mapeada de 80 no container)
- Use health checks no docker-compose para garantir readiness
- Volumes: `./app:/app` para live reload, volumes nomeados para dados persistentes
- Configurar timezone em Dockerfile (TZ environment variable)
- Preparar `.env` automático no bootstrap se não existir

---

## Critério de Sucesso

A issue é considerada "pronta" quando:

1. Um novo desenvolvedor consegue fazer `git clone`, `docker-compose up -d`, `docker-compose exec app php artisan migrate` e acessar `http://localhost:8080/health` com sucesso.
2. Todos os testes passam: `./vendor/bin/phpunit`
3. Logs são claros e ajudam a diagnosticar problemas
4. Ambiente é identico entre máquinas (dev, CI, staging)
