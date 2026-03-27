---
description: Faz stage e commit das alteracoes com mensagem semantica
argument-hint: mensagem do commit (opcional — gera automaticamente se omitido)
---

Faca commit das alteracoes pendentes no repositorio.

## Passos

1. Execute `git status` para ver o que mudou
2. Execute `git diff --stat` para entender o escopo
3. Adicione os arquivos relevantes com `git add` (nunca use `git add -A` sem revisar)
4. Se o argumento foi fornecido, use-o como mensagem. Senao, gere uma mensagem semantica baseada no diff:
   - Prefixo: feat/fix/refactor/docs/test/chore
   - Formato: `prefixo: descricao concisa`
5. Execute `git commit -m "mensagem"`
6. Exiba o resultado do commit