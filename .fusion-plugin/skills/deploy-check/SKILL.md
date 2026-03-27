---
description: Verifica se o projeto esta pronto para deploy (build, lint, testes)
---

Execute uma verificacao completa pre-deploy.

## Checklist

1. **Build** — Execute o build de producao e verifique se passa sem erros
2. **Lint** — Execute o linter e verifique warnings/errors
3. **Testes** — Execute a suite de testes completa
4. **Tipos** — Execute type-check (tsc --noEmit, mypy, etc.)
5. **Deps** — Verifique vulnerabilidades (`npm audit`, `pip audit`)
6. **Env** — Verifique se .env.example esta atualizado

## Output

Gere um relatorio com status de cada check:
- ✅ Passou
- ⚠️ Warning (nao bloqueia deploy)
- ❌ Falhou (bloqueia deploy)

Resultado final: READY ou NOT READY

Salve em `.pipeline/deploy-check.md` se existir o diretorio.