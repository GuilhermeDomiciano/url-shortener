---
description: Sincroniza status e comentarios com Jira via MCP
argument-hint: issue key (ex: PROJ-123) e acao (status/comment/transition)
---

Interaja com issues do Jira via MCP.

## Acoes

- **status** — Busca e exibe status atual da issue
- **comment <texto>** — Adiciona comentario na issue
- **transition <status>** — Move a issue para outro status (ex: "In Progress", "Done")
- **info** — Exibe detalhes completos (descricao, assignee, labels, sprint)

## Tools MCP disponiveis

- `mcp__jira__jira_get_issue` — detalhes da issue
- `mcp__jira__jira_search` — buscar issues por JQL
- `mcp__jira__jira_add_comment` — adicionar comentario
- `mcp__jira__jira_transition_issue` — mover status
- `mcp__jira__jira_update_issue` — atualizar campos

## Output

Exiba o resultado da acao de forma clara e concisa.
Se salvando em pipeline, use `.pipeline/jira-sync.md`.