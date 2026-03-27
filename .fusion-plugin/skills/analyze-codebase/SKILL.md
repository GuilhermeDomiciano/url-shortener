---
description: Analisa estrutura, dependencias e arquitetura do projeto
argument-hint: foco da analise (opcional — ex: "backend", "deps", "architecture")
---

Analise o codebase do projeto e gere um relatorio de contexto.

## O que analisar

1. **Estrutura** — diretorios principais, organizacao, patterns usados
2. **Stack** — linguagens, frameworks, versoes (package.json, requirements.txt, etc.)
3. **Dependencias** — libs principais e seus propositos
4. **Arquitetura** — camadas, patterns (MVC, Clean, etc.), entry points
5. **Config** — env vars, docker, CI/CD, linters

## Output

Gere um relatorio conciso em markdown com:
- Stack resumida (1 paragrafo)
- Estrutura de diretorios (tree simplificado)
- Endpoints/rotas principais (se aplicavel)
- Dependencias chave
- Patterns e convencoes detectados

Salve em `.pipeline/codebase-analysis.md` se existir o diretorio.
Este relatorio e util como contexto para outros agentes no pipeline.