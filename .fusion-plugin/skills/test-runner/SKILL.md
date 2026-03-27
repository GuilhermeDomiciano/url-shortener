---
description: Detecta e executa testes do projeto (Jest, Playwright, Vitest, etc.)
argument-hint: filtro de testes (opcional — ex: "api", "e2e", "unit")
---

Detecte o framework de testes do projeto e execute os testes.

## Deteccao

1. Verifique `package.json` na raiz e subdiretorios para scripts de teste
2. Procure por: jest.config, vitest.config, playwright.config, cypress.config
3. Verifique se ha diretorio `e2e/`, `tests/`, `__tests__/`, `spec/`

## Execucao

- Se argumento fornecido, filtre testes por nome/tag
- Execute o comando de teste detectado
- Se tiver tanto unit quanto e2e, execute unit primeiro
- Capture stdout e stderr

## Output

- Exiba resultado dos testes (passed/failed/skipped)
- Se houver falhas, exiba o stack trace completo
- Salve resultado em `.pipeline/test-results.md` se existir o diretorio