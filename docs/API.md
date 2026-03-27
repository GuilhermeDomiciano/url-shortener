# API Documentation

## Endpoints

---

### GET /health

**Descricao:** Verifica se a aplicacao esta saudavel, testando conectividade com PostgreSQL e Redis.

**Autenticacao:** Nenhuma

**Headers de requisicao:** Nenhum obrigatorio

#### Resposta 200 — Todos os servicos ok

```json
{
  "status": "ok",
  "timestamp": "2026-03-27T12:00:00+00:00"
}
```

#### Resposta 503 — Uma ou mais dependencias com falha

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
- `timestamp` no formato ISO 8601 (DATE_ATOM)
- O campo `services` so aparece na resposta 503 para facilitar diagnostico
- A rota nao exige autenticacao nem session
- Tentativas de conexao sao logadas via `Log::info` / `Log::error`

**Implementacao:** `app/Http/Controllers/HealthController.php`

---
