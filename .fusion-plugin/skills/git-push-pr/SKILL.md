---
description: Push da branch atual e abre PR no GitHub/GitLab
argument-hint: titulo do PR (opcional)
---

Faca push da branch atual e abra um Pull Request.

## Passos

1. Execute `git branch --show-current` para saber a branch
2. Execute `git log --oneline origin/HEAD..HEAD` para ver os commits
3. Execute `git push -u origin <branch>`
4. Detecte o provider:
   - Se `gh` disponivel: use `gh pr create --title "<titulo>" --body "<descricao>"`
   - Se `glab` disponivel: use `glab mr create --title "<titulo>" --description "<descricao>"`
5. O titulo deve ser o argumento fornecido ou gerado a partir dos commits
6. O body deve listar os commits e um resumo das alteracoes
7. Exiba a URL do PR/MR criado