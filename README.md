# URL Shortener (Laravel) — Projeto de Nível Pleno
Guia em formato de checkpoints (passo a passo) para implementar um encurtador de URLs com arquitetura que aguenta anos, com foco em **alta leitura (redirect)** e **escrita constante**.

---

## 0) Visão do Produto

### O que o sistema faz
- Encurta uma URL (gera um `slug`)
- Redireciona `/{slug}` para a URL original com **baixa latência**
- Suporta expiração de links
- Registra cliques (analytics) **assíncrono**
- Oferece API/admin para listar/criar/gerenciar links

### Separação de caminhos (regra do projeto)
- **Read Path (redirect)**: ultra simples e rápido (cache-first)
- **Write Path (create/manage)**: valida, persiste, aquece cache, dispara jobs

---

## 1) Requisitos do Projeto

### 1.1 Requisitos Funcionais (RF)
**RF-01** Criar link encurtado  
- Entrada: `original_url`, opcional `custom_slug`, opcional `expires_at`
- Saída: `slug`, `short_url`, `original_url`, `expires_at`, `created_at`

**RF-02** Redirecionar por slug  
- Entrada: `GET /{slug}`
- Saída: HTTP 301/302 para `original_url`

**RF-03** Expiração  
- Links expirados retornam 404 (ou 410) e não redirecionam

**RF-04** Analytics (assíncrono)  
- Registrar clique (timestamp, ip, user-agent, link_id)
- Não pode atrasar redirect

**RF-05** Rate limiting  
- Protege criação e redirect contra abuso

**RF-06** Gestão (mínimo)  
- Listar links de um usuário
- Deletar/desativar link (opcional)

---

### 1.2 Requisitos Não-Funcionais (RNF)
**RNF-01** Desempenho no redirect  
- Cache-first; sem ORM no hot path
- Tempo de resposta baixo e estável

**RNF-02** Escalabilidade horizontal  
- App stateless, múltiplas réplicas atrás de LB

**RNF-03** Resiliência  
- Falha de analytics não derruba redirect
- Cache pode cair: sistema ainda funciona via DB

**RNF-04** Observabilidade  
- Logs estruturados
- Métricas de cache hit/miss e latência
- Visibilidade de falhas de jobs

**RNF-05** Manutenibilidade  
- Controllers finos
- Use cases isolados
- Infra desacoplada (troca DB/cache sem refatorar tudo)

---

### 1.3 Metas de Carga (para orientar decisões)
- **1 milhão de URLs/dia (write)**
- **10x mais leituras que escritas (read:write = 10:1)**  
  (o redirect é o hot path)

> Observação: o projeto é desenhado para crescer. Se virar redirect massivo, o caminho natural é extrair o serviço de redirect para um serviço separado — mas o design aqui já deixa isso fácil.

---

## 2) Stack e Decisões

### Stack
- Laravel 10+
- PHP 8.2+
- PostgreSQL (fonte da verdade)
- Redis (cache + rate limit + queue)
- Nginx + PHP-FPM
- Docker Compose
- Pest/PHPUnit

### Decisões-chave
- **PostgreSQL** para consistência e índices robustos
- **Redis** como cache primário do redirect (reduz pressão no DB)
- **Jobs/Queue** para analytics e tarefas pesadas
- **Slug determinístico** (Base62 a partir de ID) ou ULID (sem colisão)

---

## 3) Checkpoints (Passo a Passo)

Cada checkpoint tem:
- Objetivo
- Entregáveis
- Critérios de aceite
- Observações

---

# CHECKPOINT 1 — Bootstrap e Ambiente

## Objetivo
Projeto Laravel subindo em Docker com Postgres + Redis, healthcheck funcionando.

## Entregáveis
- Repositório Laravel criado
- Docker Compose com serviços: app, nginx, postgres, redis
- `.env` configurado
- Rota `/health`

## Critérios de aceite
- `GET /health` retorna `{"status":"ok"}`
- `php artisan migrate` roda dentro do container

## Passos
1) Criar projeto
2) Configurar docker-compose (nginx, php-fpm, pg, redis)
3) Ajustar `.env` (DB_HOST=postgres, REDIS_HOST=redis)
4) Subir containers
5) Criar rota `/health`

---

# CHECKPOINT 2 — Schema do Banco (links e clicks)

## Objetivo
Modelagem mínima para links e eventos de clique.

## Entregáveis
- Migration `links`
- Migration `clicks`
- Índices corretos

## Critérios de aceite
- `slug` é UNIQUE
- `clicks` suporta inserção rápida
- Rodar `php artisan migrate` sem erro

## Tabelas (sugestão)
### links
- `id` (bigint PK)
- `slug` (varchar, unique)
- `original_url` (text)
- `user_id` (nullable)
- `expires_at` (nullable, indexado)
- `created_at/updated_at`
- Índices: `slug`, `expires_at`, composto `slug, expires_at`

### clicks
- `id` (bigint PK)
- `link_id` (FK/índice)
- `clicked_at`
- `ip`
- `user_agent`

> Regra: clicks é **append-only** (só insert). Nada de join pesado em tempo real.

---

# CHECKPOINT 3 — Arquitetura de Código (camadas)

## Objetivo
Estruturar a base para crescer sem virar “laravelzão em controller”.

## Entregáveis
- Pastas `Domain/`, `Application/`, `Infrastructure/`
- Contratos de repositório
- Primeiro use case “vazio” com testes simples

## Critérios de aceite
- Controller não tem regra de negócio
- Domain não importa classes do Laravel
- Use case testável

## Estrutura sugerida
- `app/Domain/Link`
  - `Link` (entidade)
  - `LinkRepository` (interface)
- `app/Application/Link`
  - `CreateLinkAction`
  - `ResolveLinkAction`
- `app/Infrastructure/Persistence`
  - `EloquentLinkRepository`

---

# CHECKPOINT 4 — Geração de Slug sem colisão

## Objetivo
Gerar slugs curtos sem depender de random e sem colisão.

## Entregáveis
- Serviço Base62 (ou ULID)
- Estratégia definida e testada
- Opção de obfuscação (ex.: Hashids) se necessário

## Critérios de aceite
- Criar 1.000 slugs em teste sem colisão
- Slug tem tamanho consistente (ex: 6–10)

## Estratégia recomendada (Base62 por ID)
1) Inserir registro em `links` (sem slug)
2) Pegar `id`
3) Gerar Base62(id) => slug
4) Atualizar `links.slug`

Vantagem: **colisão zero**, previsível, rápido.

---

# CHECKPOINT 5 — Write Path: criar link (API)

## Objetivo
Endpoint para criar links com validação e cache warming.

## Entregáveis
- `POST /api/links`
- Request validation
- Use case `CreateLinkAction`
- Persistência no Postgres
- Cache no Redis (write-through)

## Critérios de aceite
- Retorna `short_url` e `slug`
- Salva no DB
- Após criar, `Redis GET slug` retorna `original_url`

## Validações mínimas
- `original_url` deve ser URL válida
- `custom_slug` (se existir) só caracteres permitidos e tamanho máximo
- `expires_at` (se existir) > now

---

# CHECKPOINT 6 — Read Path: redirect ultra rápido

## Objetivo
Implementar `GET /{slug}` com **Redis-first** e mínima sobrecarga.

## Entregáveis
- Rota pública do redirect
- Cache hit: responde sem DB
- Cache miss: busca no DB e popula cache
- Sem Eloquent no hot path (query simples)
- Cache negative para slug inexistente

## Critérios de aceite
- Cache hit: não executa query no DB (validar via log/monitor)
- Cache miss: executa 1 query simples e seta Redis
- Link expirado não redireciona

## Regras do redirect
- Sem middleware pesado
- Nada de sessão
- Nada de auth
- Nada de analytics síncrono

---

# CHECKPOINT 7 — Analytics assíncrono (jobs)

## Objetivo
Registrar clique em background, sem afetar redirect.

## Entregáveis
- Job `RegisterClick`
- Queue driver Redis
- Worker rodando
- Inserção em `clicks`
- Estratégia de agregação (Redis counter + flush) para volume

## Critérios de aceite
- Redirect responde mesmo com worker desligado
- Com worker ligado, clicks começam a aparecer no DB
- Falhas do job são logadas

---

# CHECKPOINT 8 — Rate Limiting e proteção

## Objetivo
Evitar abuso e manter sistema estável.

## Entregáveis
- Rate limit para `POST /api/links`
- Rate limit para `GET /{slug}` (leve, por IP)
- Configurável por env
- Limites diferentes para create vs redirect

## Critérios de aceite
- Ao exceder limite, retorna 429
- Rate limit usa Redis

---

# CHECKPOINT 9 — Observabilidade mínima

## Objetivo
Não operar no escuro.

## Entregáveis
- Logs estruturados com:
  - `slug`
  - `cache_hit` (true/false)
  - `latency_ms`
- Métrica simples (mesmo que no log) de hit/miss
- Tratamento e log de exceções
- Métricas da fila (lag, failed jobs)

## Critérios de aceite
- Conseguir diferenciar cache hit vs miss só olhando log
- Erros de DB/Redis aparecem claros

---

# CHECKPOINT 10 — Testes (nível pleno)

## Objetivo
Provar que o core não quebra.

## Entregáveis
- Unit tests dos use cases
- Feature tests:
  - criar link
  - redirect cache miss
  - redirect cache hit
  - expiração
  - rate limit

## Critérios de aceite
- `./vendor/bin/pest` (ou phpunit) passa
- Teste mostra que redirect não depende do job

---

# CHECKPOINT 11 — Produção (config, performance, hardening)

## Objetivo
Ajustes que fazem diferença fora do dev.

## Entregáveis
- OPcache ligado (Dockerfile/php.ini)
- Config cache / route cache
- Headers de segurança no Nginx (mínimo)
- `.env.example` completo
- README com “como rodar” e “decisões técnicas”
- Pooling de conexões (ex.: PgBouncer) se necessário

## Critérios de aceite
- Build reprodutível
- Ambiente sobe limpo em máquina nova
- Docs suficientes para alguém clonar e rodar

---

## 4) Evoluções (após MVP)

### Analytics agregado (sem matar o banco)
- Tabela `daily_clicks` (dia, link_id, count)
- Job diário para consolidar

### Particionamento de `clicks` (se crescer demais)
- Particionar por mês/dia no Postgres

### Separar Redirect Service (se virar gigante)
- Laravel fica como Admin/API
- Redirect vira serviço mínimo (Go/Node) usando o mesmo Redis+DB

---

## 5) Definição de “pronto”
- Redirect cache-first funcionando
- Criação de link persistindo e aquecendo cache
- Analytics 100% assíncrono
- Rate limit ativo
- Testes cobrindo fluxos principais
- Logs permitem diagnosticar problemas

---

## 6) Convenção de commits (sugestão)
- `chore:` infra/config
- `feat:` funcionalidade
- `test:` testes
- `docs:` documentação
- `fix:` correção