---
description: Extrai dados de design do Figma via MCP (nodes, imagens, estilos)
argument-hint: URL do Figma (ex: https://www.figma.com/design/...)
---

Extraia dados de design de um arquivo Figma.

## Passos

1. Parse a URL do Figma para extrair file_key e node_id
   - Formato: https://www.figma.com/design/<file_key>/<name>?node-id=<node_id>
2. Use as tools do MCP do Figma:
   - `mcp__figma__get_file_nodes` com fileKey e node_ids para dados estruturais
   - `mcp__figma__get_image` com fileKey e ids para gerar preview PNG
3. Salve os dados extraidos:
   - Dados JSON em `.pipeline/figma-nodes.json`
   - URL da imagem em `.pipeline/figma-preview.md`
4. Gere um resumo com:
   - Componentes encontrados (nomes, tipos, hierarquia)
   - Cores principais
   - Tipografia detectada
   - URL da imagem renderizada

## Importante

- Se receber rate limit, aguarde 30s e tente UMA vez mais
- Nao retente mais que 2 vezes no total
- Dados salvos em .pipeline/ ficam disponiveis para agentes seguintes no pipeline