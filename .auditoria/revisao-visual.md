# Revisão visual — Fase 6

Revisor visual (UI/UX). Somente leitura: nenhum arquivo do site foi alterado.
Material: 96 pares em `.auditoria/prints/antes/` e `.auditoria/prints/depois/`, 6 telas `painel-*.png`, `.auditoria/auditoria-uiux.md`.

Gravidade: **P0** = impede o uso ou estoura o orçamento; **P1** = atrapalha muita gente ou causa erro provável; **P2** = melhoria de qualidade.

---

## 1. Como os pares foram medidos

Script Python/PIL (fora do projeto): diferença absoluta por pixel, média e % de pixels com diferença > 24 (em 0–255), e o retângulo onde há diferença. Pares com diferença foram montados lado a lado (antes | depois | mapa de diferença ×4) e abertos.

**Cuidados com a captura (antes de ler os números):**
- Os prints de celular do **antes** foram tirados com densidade 2× (780 px de largura) e os do **depois** com 1× (390 px). Reduzi o antes pela metade para comparar. Consequência: **não dá para julgar a nitidez no iPhone (3×) pelos prints do depois.** A nitidez foi avaliada nos pares de desktop (1× nos dois).
- Diferenças que são da captura, não do site:
  - `celular-index-home.png` e `desktop-index-home.png`: o antes foi tirado com a página já rolada (altura 777/828 contra 844/900).
  - `desktop-*-hover-*.png`: o antes é um recorte do menu (≈ 440x260); o depois é a tela inteira.
  - `*-resultados-footer-7.png`: o seletor "7ª seção" agora pega a nova seção "Quer um resultado assim?", não o rodapé. O rodapé foi conferido nas páginas inteiras.
  - `*-pagina-inteira.png` do depois foram tiradas **depois** dos testes de arrastar: os comparadores aparecem com a linha em ~18% e o carrossel da home em "3 de 5". Nos prints `*-comparador-*-meio.png` a linha está em 50% como no antes, então não é mudança de comportamento.
  - O mapa do Google não carrega no print de celular nem no antes nem no depois (área vazia de ~300 px em `celular-index-contato.png`): igual nos dois.

### Resultado da medição (normalizado)

| Grupo | Diferença | Leitura |
|---|---|---|
| Desktop: sobre, depoimentos, cabeçalho, section-1 de resultados | 0,00% | Idênticos (amostra aberta: `desktop-index-sobre`, `desktop-resultados-site-header`) |
| Desktop: contato, cursos, rodapé, procedimentos, sections 2–5 de resultados | 0,01–1,3% | Só tom dos botões/textos de destaque (contraste) e o pegador do comparador |
| Desktop/celular: comparadores `*-meio` e `*-arrastado` | 3–20% | Pegador redondo novo + leve perda de detalhe da conversão; **enquadramento idêntico** |
| Vídeos antes do play | 3–7% (resultados) / ~57% (vídeo de apresentação) | Capa nova = primeiro quadro; botão de play no centro |
| Celular: procedimentos, depoimentos, resultados da home | 4–18% | Setas saíram de cima do conteúdo e ganharam contador "1 de 7" |
| Resultados: section-6, página inteira | 14–37% | Vídeos da Corrente Fortalecedora centralizados; nova chamada para WhatsApp no fim |

---

## 2. Site público: o que mudou, item a item

### Melhorou
1. **Comparador antes/depois** (`celular-resultados-comparador-1-meio.png`, `desktop-index-comparador-1-arrastado.png`): pegador redondo de ~44 px com ‹ › no meio da linha. Fica claro que dá para arrastar. Atende S3.
2. **Enquadramento das fotos**: dentro da área da foto o mapa de diferença é preto (só muda a linha e o pegador) nos 20 pares de comparador. Zoom e posição **iguais** ao antes. Nenhuma foto deslocou ou cortou diferente.
3. **Setas dos carrosséis no celular** (`celular-index-procedimentos.png`, `celular-index-depoimentos.png`, `celular-index-resultados.png`): saíram de cima do texto e da foto e foram para baixo, com contador "1 de 7", "1 de 4", "1 de 5". Atende S6.
4. **Contraste**: botões e textos de destaque passaram de `#996C55` para um marrom mais escuro (`#7D5843` aprox.) em "Agendar pelo WhatsApp", "Chamar no WhatsApp", "Ver mais", "Abrir no Google Maps", "Carga horária", eyebrows. Fundo de Procedimentos e do menu aberto mais escuro, texto dos cards mais claro (`celular-index-menu-aberto.png`, `celular-index-procedimentos.png`). Atende S1/S2.
5. **Rodapé no celular**: links de telefone, Instagram, endereço e horário com mais espaço entre si (alvos de toque maiores). Atende S4.
6. **Página de resultados**: nova chamada "Quer um resultado assim? Agende pelo WhatsApp" antes do rodapé (atende S9); vídeos da Corrente Fortalecedora agora centralizados como as outras seções (`desktop-resultados-section-6.png`).
7. **Vídeos antes do play**: os 6 vídeos mostram capa com imagem (luminância média 65–123; nenhum preto). A capa agora é o primeiro quadro, então não há "salto" de imagem ao dar play. O vídeo de apresentação mostra a capa real do vídeo ("Conheça o Studio Gabriela Santos") em vez de uma foto avulsa.

### Neutro
- **Fontes e quebras de linha**: todos os títulos, parágrafos, cards de cursos, depoimentos e rodapé quebram nas mesmas palavras. Nenhuma fonte trocada. Única diferença de texto: "Segunda à sexta, das 8h às 17h" no rodapé (antes "8h às 17h"), coerente com o Contato.
- **Duração do vídeo some antes do play**: com `preload="none"` o player mostra "0:00" em vez de "0:00 / 0:10". É esperado e aceitável.
- Botão de play próprio + controles nativos: dois "plays" visíveis, mas o grande é o óbvio. Neutro.

### Piorou
1. **Nitidez das fotos convertidas** (desktop, 1×): energia de bordas caiu 3–14% nas fotos dos comparadores (ex.: `desktop-index-comparador-1-meio.png` 603 → 524; `desktop-resultados-comparador-2-meio.png` 1940 → 1671). Em zoom 3× a textura da pele fica levemente lavada; em tamanho real quase não se nota. As fotos têm **800 px** de largura; a moldura tem 400 px no desktop e ~300 px no celular, e o enquadramento usa zoom acima de 100%. Num iPhone 3× a moldura pede ~900–1200 px, então a foto já é ampliada no aparelho.
2. **Botão principal do topo some no fundo** (`celular-index-pagina-inteira.png` topo, `desktop-index-hover-3-procedimentos.png`): o botão "Agendar pelo WhatsApp" escureceu para `rgb(125,88,67)` e o fundo do hero ao lado é `rgb(121,86,67)`: **contraste 1,04:1** entre botão e fundo. O texto passa no contraste, mas o botão mais importante do site tem menos peso visual que o secundário "Ver procedimentos", que tem contorno. Antes já era fraco (S5), agora ficou pior.

---

## 3. Painel (6 telas, pensando na dona no iPhone)

### O que está bom
- **Login** (`painel-login.png`): uma senha só, "Mostrar senha", botão "Entrar" grande. Simples.
- **Tela inicial** (`painel-tela-inicial.png`): cartões grandes com ícone, nome e uma frase do que fazem. "O que você quer fazer?" é ótimo.
- **Textos** (`painel-edicao-textos.png`): grupos com nome do lugar no site ("Topo do site", "Boas-vindas") e contador de letras.
- **Lista** (`painel-lista-resultados.png`): miniatura, Subir/Descer em vez de arrastar, botões com ~44 px, "Apagar" em vermelho.
- **Editor** (`painel-editor-antes-depois.png`): abas Foto ANTES / Foto DEPOIS, − e + de 44 px, "Zoom: 100%", "Voltar ao automático", prévia "Como fica no site" com a barra.
- **Ajuda** (`painel-ajuda.png`): passo a passo curto, em português simples, cobre apagar/desfazer, versões, avisos e "Adicionar à Tela de Início".

### Problemas encontrados

| # | Gravidade | Print | Problema | Correção sugerida |
|---|---|---|---|---|
| V1 | **P1** | `celular-index-pagina-inteira.png` (topo), `desktop-index-hover-3-procedimentos.png` | Botão "Agendar pelo WhatsApp" do hero com 1,04:1 contra o fundo; o secundário chama mais atenção. | No hero, botão principal com fundo `#FBF3EE` e texto `#7D5843` (igual ao "Agendar horário" do menu aberto), mantendo "Ver procedimentos" só com contorno. Critério: ≥ 3:1 entre o botão e o fundo. |
| V2 | **P1** | `painel-lista-resultados.png` | "Apagar" muda de lugar de um card para outro (2ª linha à esquerda no 1º card, à direita no 2º; colado em "Descer"). Ao tocar várias vezes em Descer/Subir, é fácil tocar em Apagar. | Tirar "Apagar" da lista: deixar só dentro de "Editar" (já existe lá). Na lista, ficar com Editar, ↑ Subir, ↓ Descer, sempre nas mesmas posições (Subir/Descer desabilitados, não escondidos, no primeiro e no último). |
| V3 | **P1** | `painel-ajuda.png` ("Apagar e desfazer" vs. "Mudar a ordem"), `painel-lista-resultados.png` | Dois jeitos de gravar: ordem e textos precisam de "Salvar no site"; apagar vale na hora (aviso com Desfazer). Se ela muda a ordem e depois apaga, não fica claro se a ordem foi salva ou perdida. | Um modelo só. Melhor: apagar também entra como mudança pendente ("1 item será apagado") e tudo vai junto no "Salvar no site". Ou: ao apagar com mudanças pendentes, perguntar "Salvar a nova ordem antes de apagar?". Testar o caminho reordenar → apagar → sair. |
| V4 | **P1** | `painel-editor-antes-depois.png` | Não há modo "Sobrepor" (outra foto por cima, meio transparente) nem "Desfazer último ajuste". É o que mais ajuda a alinhar umbigo/nariz entre antes e depois (P2 da auditoria). | Botão "Sobrepor a outra foto" com controle de transparência (padrão 50%) na moldura de enquadramento, e botão "Desfazer" que volta o último arraste/zoom. |
| V5 | P2 | `painel-ajuda.png` passo "Trocar uma foto…" 3, `painel-editor-antes-depois.png` | A Ajuda manda usar "as setas", mas o editor não mostra setas. | Pôr 4 botões ↑ ↓ ← → de 44 px abaixo do zoom (ajuste fino de 1%), ou tirar "e as setas" da Ajuda. |
| V6 | P2 | `painel-editor-antes-depois.png` (prévia) | A prévia "Como fica no site" não tem o pegador redondo ‹ › que o site agora tem. Deveria ser idêntica ao site. | Usar o mesmo CSS/HTML do `.ba-handle` do site na prévia. |
| V7 | P2 | `painel-editor-antes-depois.png`, `painel-edicao-textos.png`, `painel-lista-resultados.png` | Botão "Salvar no site" desabilitado aparece cinza-rosado com texto branco (2,34:1) e sem explicação. Ela não sabe se está quebrado. | Quando não há mudança, texto "Nada para salvar" no botão; quando há, "Salvar no site (2 mudanças)" no marrom escuro. |
| V8 | P2 | `painel-lista-resultados.png` | Itens com o mesmo nome ("Drenagem linfática" ×2, "Limpeza de pele" ×2) e subtítulo que repete o nome ("Antes e depois · Drenagem linfática"). Difícil saber qual é qual. | Subtítulo útil: posição e tipo ("Antes e depois · 2º da página inicial") ou data de envio; numerar duplicados ("Drenagem linfática 2"). Miniatura do DEPOIS ao lado da do ANTES. |
| V9 | P2 | `painel-lista-resultados.png` | Aba "Fora do site" é ambígua (parece "outro site"). | Renomear para "Escondidos" com a frase "Guardados aqui, não aparecem no site". |
| V10 | P2 | `painel-tela-inicial.png` | "Diagnóstico — Para o Vinicius" aparece para a dona e o primeiro cartão é "Ver o site", não as tarefas do dia a dia. | Esconder "Diagnóstico" atrás de um link pequeno no rodapé ("Para o técnico"); ordem: Antes e depois, Textos, Ver o site, Versões, Lixeira, Ajuda. |
| V11 | P2 | `painel-editor-antes-depois.png` | "Nome do tratamento" e "Categoria" mostram o mesmo valor ("Liposhape"); não fica claro para que serve cada um. Título "Enquadramento" colado no checkbox de cima. | Dica sob Categoria: "Em qual seção da página de resultados ele entra". Preencher o nome a partir da categoria. Margem de 24 px antes de "Enquadramento". |
| V12 | P2 | `painel-editor-antes-depois.png` | "Onde aparece no site" usa checkboxes (~26 px); o plano pedia interruptores com texto. | Interruptor de 48 px de altura, linha inteira tocável, com "Sim/Não" escrito. |
| V13 | P2 | `desktop-index-comparador-1-meio.png` (zoom), `assets/results/*.webp` | Fotos dos comparadores com 800 px e leve perda de detalhe; no iPhone 3× ficam ampliadas. | Reexportar do original em 1200 px, WebP qualidade 82–85 (cabe nos 250 KB; hoje 24–78 KB), com `srcset` 800w/1200w. Pôster `ultrassom-depois1.webp` (480x480, quadrado numa moldura em pé) em 576x1024 como os outros. |
| V14 | P2 | `desktop-index-hover-3-procedimentos.png` | O círculo do "GS" de fundo cruza o texto do hero e o botão "Ver procedimentos" no desktop (já existia). | Deslocar a marca d'água para a direita (`right` maior) ou reduzir a opacidade atrás do texto. |
| V15 | P2 | (sem print) | Não há prints da janela de confirmação de apagar, do aviso com Desfazer, da Lixeira e Versões vazias, do envio com barra de progresso nem das mensagens de erro (senha errada, sessão expirada, arquivo grande). A segurança contra apagar sem querer só pode ser confirmada pela Ajuda. | Gerar esses prints no mesmo 390x844 antes da aprovação: `painel-confirmar-apagar`, `painel-desfazer`, `painel-lixeira-vazia`, `painel-versoes`, `painel-enviando`, `painel-erro-senha`, `painel-erro-arquivo-grande`, `painel-sessao-expirada`. A janela de apagar deve ter miniatura, "Cancelar" destacado e "Apagar" vermelho separado. |
| V16 | P2 | todos os prints do depois de celular | Prints do depois em 1× não permitem julgar nitidez nem tipografia como no iPhone. | Recapturar o depois com `deviceScaleFactor: 3` (ou 2 como o antes) e recapturar as páginas inteiras com o estado inicial (recarregar antes da foto). |

Nenhum P0 encontrado: nada impede a dona de usar o painel e nenhum vídeo aparece com tela preta.
