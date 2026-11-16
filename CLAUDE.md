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

## Fluxo de trabalho com agentes

A sessão principal (Sonnet) coordena e delega. Siga estas regras:

- **Recurso novo ou mudança em mais de 2 arquivos:** planejador → executores em paralelo (um por lote, até 6) → revisor.
- **Mudança pequena e óbvia (1 ou 2 arquivos):** faça direto, sem planejador.
- **Bug relatado:** explorador roda as ferramentas → confirmador reproduz → executor corrige → revisor confere.
- **Tela ou modelo 3D criado ou alterado:** explorador tira os prints ou renderiza a prévia → auditor-ux revisa → executor ajusta.
- **Leitura ampla ou busca no projeto:** sempre o explorador, nunca a sessão principal.
- **Problema muito difícil em que o Sonnet travou:** use "ultrathink" no pedido antes de chamar um modelo maior.
- Use /clear entre tarefas diferentes para o contexto não crescer à toa.
- **Várias frentes ao mesmo tempo:** o Vinicius gosta de ver várias frentes trabalhando simultaneamente e de acompanhar cada uma. Divida todo trabalho independente em frentes paralelas (até 6 com Haiku e Sonnet, até 3 com Opus), dê um nome claro a cada frente, mantenha a lista de tarefas atualizada e avise em uma linha quando cada frente começar e terminar.
- Nada pode estourar o orçamento em .auditoria/orcamento.md. O projeto precisa continuar leve.
- Todo visual segue o GUIA-VISUAL.md, se existir.
- Assets, quando necessários: ElevenLabs gera tudo que não é música, Suno gera tudo que é música, Blender faz tudo que é 3D e Canva faz toda a arte 2D (logo, ícones, banners, arte da loja e divulgação).
- Não sugerir a estética Graffiti Core.
- Mantenha o ROADMAP.md atualizado quando um recurso for concluído.
