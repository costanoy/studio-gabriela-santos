# Studio Gabriela Santos — site

Site institucional de um studio de estética em Goianésia-GO.
Hospedagem: Hostinger compartilhada (PHP 8), domínio studiogabrielasantos.com.br.
Repo privado costanoy/studio-gabriela-santos. Branch de trabalho: `melhorias-claude` (não fazer merge em main sem o Vinicius).

## Estrutura
- `index.html` (home) e `resultados.html`: HTML estático com CSS e JS inline, sem build e sem framework.
- `assets/`: fontes woff2 locais, fotos, vídeos (`results/`), posters dos vídeos (`img/`).
- `robots.txt`, `sitemap.xml`.
- `tests/site.spec.js` + `playwright.config.js`: testes (Playwright é só devDependency).
- `.auditoria/`: arquivos de trabalho da auditoria (orçamento, mapa, relatórios).
- Planejado: painel PHP para a dona editar fotos, vídeos e textos. Ver `.auditoria/decisao-painel.md`.

## Comandos
- Servir local: `python -m http.server 8765` (estático) ou `php -S localhost:8765` (depois do painel).
- Testes: `npx playwright test`.

## Regras
- Orçamento de performance em `.auditoria/orcamento.md`. Estourar o orçamento é P0.
- Todo `<video>` precisa de `poster` com o PRIMEIRO frame do vídeo (evita tela preta no iPhone),
  `playsinline`, `preload="metadata"` ou `"none"`, H.264 MP4 ≤1080p.
- Fotos ≤1600px e ≤250 KB (WebP/JPEG).
- Nenhuma chave, senha ou token no código. A config do painel fica fora do public_html.
- Sem dependências novas de runtime. Só PHP nativo (GD) e JS puro.
- Nunca publicar no domínio sem o Vinicius ver e aprovar o resultado antes.
- Cada mudança é commitada e enviada (push) para a branch atual.
- Textos e interface do painel em português simples, porque a dona não é técnica e usa iPhone.
