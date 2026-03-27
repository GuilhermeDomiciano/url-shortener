---
name: Code Review
description: Revisa codigo com foco em qualidade, seguranca e boas praticas
argument-hint: escopo da revisao: arquivo, diretorio ou branch diff
---
Realize uma revisao de codigo completa.

## Escopo

Se o argumento for um arquivo ou diretorio, revise apenas esse escopo.
Se for vazio, revise os arquivos modificados no working tree (`git diff --name-only`).

## Criterios de revisao

Para cada arquivo, analise:

1. **Seguranca** — SQL injection, XSS, secrets expostos, inputs nao validados
2. **Qualidade** — funcoes muito longas, duplicacao, complexidade desnecessaria
3. **Erros** — null/undefined nao tratados, promises sem catch, edge cases
4. **Performance** — queries N+1, loops desnecessarios, imports pesados
5. **Convencoes** — naming, formatacao, patterns do projeto

## Output

Gere um relatorio em markdown com:
- Lista de issues encontradas (severity: critico/alto/medio/baixo)
- Sugestao de fix para cada issue
- Score geral (0-10)
- Salve em `.pipeline/code-review.md` se existir o diretorio