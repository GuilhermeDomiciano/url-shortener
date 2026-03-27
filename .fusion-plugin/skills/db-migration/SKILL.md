---
description: Cria ou executa migrations de banco de dados
argument-hint: acao: "create <nome>" ou "run" ou "status"
---

Gerencie migrations do banco de dados.

## Acoes

- **create <nome>** — Cria uma nova migration baseada no contexto fornecido
- **run** — Executa migrations pendentes
- **status** — Mostra status das migrations

## Deteccao

1. Verifique o ORM: TypeORM (ormconfig/data-source), Prisma (schema.prisma), Knex (knexfile), Sequelize
2. Use o CLI correto do ORM detectado

## Para criar migrations

1. Analise o schema atual
2. Gere a migration com SQL ou DSL adequada ao ORM
3. Inclua tanto UP quanto DOWN
4. Nomeie com timestamp + descricao

## Convencoes

- Migrations devem ser idempotentes quando possivel
- Sempre incluir rollback (down)
- Nao destruir dados em producao (use ADD, nao DROP)