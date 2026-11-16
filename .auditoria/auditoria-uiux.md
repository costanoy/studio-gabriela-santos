# Auditoria UI/UX — Studio Gabriela Santos

Agente: UI/UX. Fase somente leitura: nenhum arquivo do site foi alterado.
Fontes: `index.html`, `resultados.html`, `.auditoria/lh-*.json`, prints em `.auditoria/prints/antes/`, medições novas com Playwright (Chrome, 390x844 com toque) feitas no servidor local `http://localhost:8765`. Os scripts de medição ficaram fora do projeto.

Gravidade: **P0** = estoura o orçamento ou impede a dona de usar o painel; **P1** = atrapalha muita gente ou causa erro provável; **P2** = melhoria de qualidade.

Itens S* são do site público. Itens P* são do painel planejado em `.auditoria/decisao-painel.md` (ainda não existe; a evidência é o plano e o comportamento do iPhone).

---

## S1. Acessibilidade 85–87 no Lighthouse (meta ≥ 90): três causas exatas
- **Gravidade:** P0 (o orçamento pede ≥ 90; estourar o orçamento é P0 pelo CLAUDE.md).
- **Evidência:** `lh-index-mobile.json` (87), `lh-resultados-mobile.json` (85), `lh-index-desktop.json` (87). Só três auditorias falham:
  1. `label` (peso 10): os 10 controles deslizantes do antes/depois não têm nome. `index.html:457, 471, 485, 499, 513` e `resultados.html:280, 310, 322, 342, 354`.
  2. `color-contrast` (peso 7): texto `#FBF3EE` sobre botão `#996C55` = **4,14:1** (precisa 4,5:1). Botões "Agendar horário" (`#desktop-cta`, `index.html:318`) e "Agendar pelo WhatsApp" do topo (`index.html:347`); em resultados, o "Transformações reais" `#996C55` sobre `#FBF3EE` (13 px) = 4,14:1.
  3. `landmark-one-main` (peso 3): nenhuma das páginas tem `<main>`.
- **Como reproduzir:** `npx lighthouse http://localhost:8765/ --only-categories=accessibility --form-factor=mobile` e ver as auditorias com nota 0.
- **O que mudar:**
  - Dar nome a cada comparador: `aria-label="Comparar antes e depois — Liposhape"` e `aria-valuetext` falado ("mostrando 50% do antes"). O texto deve vir do título do procedimento (o painel vai gerar).
  - Escurecer a cor de destaque usada como **fundo de botão e texto pequeno** de `#996C55` para `#7D5843` (já é a cor de hover do site): 5,72:1 sobre `#FBF3EE` e 5,05:1 sobre `#F3E4DA`. Alternativa mais próxima do tom atual: `#875C47` (5,25 / 4,63). O hover passa a ser um tom ainda mais escuro.
  - Envolver o conteúdo entre o `</header>` e o `<footer>` num `<main id="conteudo">` (não muda o visual).
- **Critério de pronto:** Lighthouse Acessibilidade ≥ 95 nas 2 páginas, celular e desktop; `label`, `color-contrast` e `landmark-one-main` passando; comparação de prints sem diferença visível além do tom do botão.

## S2. Contrastes reprovados que o Lighthouse não viu (seções escondidas pela animação)
- **Gravidade:** P1.
- **Evidência:** as seções com `class="reveal"` começam com `opacity: 0` (`index.html:296`), então o Lighthouse não mede o texto delas. Calculei pelo WCAG (texto normal precisa 4,5:1):
  | Onde | Cores | Contraste |
  |---|---|---|
  | Texto dos cards de procedimentos (16 px), `index.html:404–428` | `#D8C4B8` / `#85604B` | **3,32** |
  | "O que oferecemos" (13 px), `index.html:398` | `#E7CDBE` / `#996C55` | **3,00** |
  | "Formação profissional", "Contato", serviço nos depoimentos | `#996C55` / `#F3E4DA` | **3,65** |
  | "Carga horária: 10 horas" (15 px), `index.html:553, 591` | `#996C55` / `#FBF3EE` | **4,14** |
  | Títulos do rodapé "MENUS / CURSOS / CONTATO" (12,5 px), `index.html:749`, `resultados.html:402` | `#996C55` / gradiente `#3A2A22→#5C4A40` | **3,02 a 1,85** |
  | Linha final "Studio Gabriela Santos" (13 px), `index.html:789`, `resultados.html:442` | `#8A7368` / gradiente | **3,08 a 1,89** |
  | "Abrir no Google Maps" (13 px), `index.html:738` | `#FBF3EE` / `#996C55` | **4,14** |
- **Como reproduzir:** no DevTools, forçar `.reveal { opacity:1 }` e rodar o Lighthouse ou o painel de contraste; ou ver `celular-index-procedimentos.png` e `celular-index-footer-8.png` (texto apagado).
- **O que mudar:** texto dos cards → `#F3E4DA` (4,49) ou `#FBF3EE` (5,08); eyebrow sobre marrom → `#FBF3EE`; textos pequenos em destaque sobre fundo claro → `#7D5843`; títulos do rodapé → `#D8B8A5` (4,51 no pior ponto); linha final do rodapé → `#D8C4B8` (4,99). Manter a paleta, só ajustar o tom.
- **Critério de pronto:** nenhum texto com contraste < 4,5:1 (ou < 3:1 se ≥ 24 px, ou ≥ 18,66 px negrito) com as seções visíveis; checagem automática com axe com `.reveal` forçado visível passa sem `color-contrast`.

## S3. Comparador antes/depois difícil de usar com o dedo
- **Gravidade:** P1 (é a peça que mais vende o serviço).
- **Evidência:** o único jeito de mexer é a barrinha nativa no rodapé da foto, com **16 px de altura** (medido: 246x16 na home, 312x16 em resultados). Arrastar sobre a foto não faz nada. A linha branca não tem "pegador" (`.ba-line`, 2 px, `pointer-events:none`). Rótulos "ANTES/DEPOIS" com 11 px. Prints `celular-index-comparador-*-meio.png`. No celular as setas do carrossel (44 px) ficam **em cima da foto** (`celular-index-resultados.png`), competindo com o gesto.
- **Como reproduzir:** iPhone, home → Resultados; tentar arrastar a linha no meio da foto: a página rola para o lado ou nada acontece; só funciona acertando a bolinha da barra.
- **O que mudar:** (sem biblioteca, JS puro)
  - Arrastar em qualquer ponto da foto move a linha (Pointer Events; `touch-action: pan-y` no quadro, para continuar rolando a página na vertical).
  - Pegador redondo visível de 44 px no meio da linha, com setas ‹ ›.
  - Manter o `input range` (fica para teclado e leitor de tela, com o nome de S1), visualmente escondido ou fino, mas não como único meio.
  - Rótulos "Antes/Depois" com 12–13 px e o contraste de S2.
  - No celular, tirar as setas de cima da foto (ver S6).
- **Critério de pronto:** no iPhone (real ou WebKit) arrastar em qualquer altura da foto move a linha de forma contínua; rolar a página na vertical por cima do comparador continua funcionando; teclado (setas) também move; teste Playwright cobre arraste por mouse e toque no Chromium.

## S4. Alvos de toque menores que 44 px
- **Gravidade:** P1.
- **Evidência (medido em 390x844):**
  | Elemento | Tamanho | Onde |
  |---|---|---|
  | Botão do menu (hambúrguer) | 40x40 | `index.html:319` |
  | "Voltar" em resultados | 42x19 | `resultados.html:259` |
  | Barra do comparador | 246x16 / 312x16 | ver S3 |
  | "Abrir no Google Maps" | 175x38 | `index.html:738` |
  | "@gabisantos_estetica" no Contato | 170x21 | `index.html:731` |
  | Links do rodapé (telefone, Instagram, endereço) | 351x19–25, com 14 px entre eles | `index.html:770–785`, `resultados.html` rodapé |
- **Como reproduzir:** Playwright/DevTools em 390 px, medir `getBoundingClientRect()` de todo `a, button, input`.
- **O que mudar:** hambúrguer 44x44; "Voltar" com seta "←", `padding` e `min-height:44px`; links do rodapé e do Instagram com `padding:12px 0` (ou `min-height:44px; display:flex; align-items:center`); botão do Maps com `padding:13px 20px` e 15 px.
- **Critério de pronto:** script de medição não encontra nenhum alvo visível < 44x44 px no celular (exceto texto corrido dentro de parágrafo).

## S5. Cabeçalho alto cobre os títulos e o botão principal quase some no topo
- **Gravidade:** P1.
- **Evidência:**
  - Cabeçalho fixo com **137 px** no carregamento (16% da tela de 844) e 87 px depois de rolar. O `scroll-margin-top` é de só 30–40 px (`index.html:238, 240`).
  - Ao tocar em "Sobre" no menu, o título "Técnica e cuidado em cada atendimento" para em y = 38 px, **atrás do cabeçalho** (que vai até 87 px). Medido com `scrollIntoView` em 390 px. Nas outras seções o `padding` de 110 px esconde o problema.
  - No topo, o botão "Agendar pelo WhatsApp" é `#996C55` sobre o fundo do hero, que também é `#996C55` com gradiente: o botão principal tem o mesmo peso visual do fundo (print `celular-index-home.png`). O nome da marca aparece 3 vezes na primeira tela (logo, h1 e o "GS" grande de fundo).
- **Como reproduzir:** celular, abrir o menu, tocar em "Sobre": o título fica escondido.
- **O que mudar:** `scroll-margin-top` = altura do cabeçalho reduzido + 16 px (≈ 104 px no celular, ≈ 110 px no desktop), ou em variável CSS. Logo do cabeçalho no celular menor no carregamento (ex.: 80 px). No hero, o botão principal com fundo claro `#FBF3EE` e texto `#7D5843` (igual ao "Agendar horário" do menu aberto), deixando o contorno para o secundário.
- **Critério de pronto:** em 390 px, para as 7 âncoras, o topo do primeiro texto da seção fica abaixo do fim do cabeçalho; o botão principal do hero tem contraste ≥ 3:1 contra o fundo ao redor e se destaca no print.

## S6. Setas dos carrosséis em cima do texto e sem indicação de posição
- **Gravidade:** P2.
- **Evidência:** no celular, as setas de 44 px ficam por cima do texto dos cards: "cirurgias/lipoaspiração" (`celular-index-procedimentos.png`), "abençoando" (`celular-index-depoimentos.png`) e sobre a foto do antes/depois (`celular-index-resultados.png`). Não há "1 de 7" nem bolinhas; a pessoa não sabe quantos cards existem. Setas de resultados sem `type="button"` (`index.html:528, 531`).
- **Como reproduzir:** 390 px, rolar até Procedimentos e Depoimentos.
- **O que mudar:** no celular, colocar as setas **abaixo** do carrossel, lado a lado, com um contador "2 de 7" entre elas (texto com `aria-live="polite"`); o deslizar com o dedo continua. `type="button"` nas setas.
- **Critério de pronto:** nenhum texto ou foto coberto por seta em 390 px; contador atualiza ao tocar nas setas e ao deslizar.

## S7. Teclado e leitor de tela: foco invisível e estados não anunciados
- **Gravidade:** P2.
- **Evidência:**
  - `outline: none` em todos os links e botões do cabeçalho e do menu (`index.html:237`, `resultados.html:232`). Testado com Tab: logo e hambúrguer recebem foco com `outline: none`; não há estilo `:focus-visible` no site.
  - Hambúrguer sem `aria-expanded`/`aria-controls`, rótulo sempre "Abrir menu"; Esc não fecha o menu (testado: continua `display:flex`).
  - "Ver descrição completa" dos cursos sem `aria-expanded` (`index.html:558, 596`), e sem `type="button"`.
  - Overlays de vídeo são `div` com `onclick` (`index.html:392`, `resultados.html:286…390`): não são focáveis e cobrem o vídeo.
  - Estrelas dos depoimentos (23 SVGs) sem texto: o leitor não diz "5 de 5 estrelas".
  - Sem link "Pular para o conteúdo".
- **Como reproduzir:** desktop, apertar Tab a partir do topo; leitor de tela (VoiceOver) no menu e nos cursos.
- **O que mudar:** trocar `outline:none` por `:focus-visible { outline: 3px solid #E7CDBE; outline-offset: 3px }` (cor escura nos fundos claros); `aria-expanded` + `aria-controls` no hambúrguer e nos cursos, trocando o rótulo para "Fechar menu"; Esc fecha o menu e devolve o foco ao botão; remover os overlays ou trocá-los por `<button>` com rótulo; `role="img" aria-label="5 de 5 estrelas"` no grupo de estrelas; link "Pular para o conteúdo" que aparece só no foco.
- **Critério de pronto:** percorrer as 2 páginas só com Tab mostra sempre onde está o foco; VoiceOver anuncia "menu, recolhido/expandido"; Esc fecha o menu.

## S8. Animação de entrada sem respeitar "Reduzir movimento" e conteúdo dependente de JS
- **Gravidade:** P2.
- **Evidência:** `.reveal` (opacidade 0 e deslocamento de 28 px, `index.html:296–297`), `scroll-behavior:smooth` e `.btn-lift` sem `@media (prefers-reduced-motion: reduce)`. Se o JS falhar, as seções Sobre, Procedimentos, Resultados, Cursos, Depoimentos e Contato ficam invisíveis.
- **Como reproduzir:** iPhone com Ajustes → Acessibilidade → Movimento → Reduzir movimento: as animações continuam. Desligar o JS no DevTools: a página fica quase vazia.
- **O que mudar:** só esconder `.reveal` quando o JS marcar `<html class="js">`; dentro de `prefers-reduced-motion: reduce`, sem transição, sem deslocamento e `scroll-behavior:auto`.
- **Critério de pronto:** com "reduzir movimento" nada desliza; com JS desligado todas as seções aparecem.

## S9. Página "Todos os resultados": sem chamada para agendar e com legendas fracas
- **Gravidade:** P2.
- **Evidência:** o único link de WhatsApp está no rodapé (`resultados.html:423`); depois de ver os resultados, a cliente não tem um botão "Quero esse resultado". Os comparadores não têm legenda (só o `h2` da seção). O ultrassom mostra uma foto "Antes" solta com faixas pretas (`object-fit: contain`, `resultados.html:366`) ao lado de um vídeo "Depois". Rótulos de vídeo como "Resultado" e "Depois" dizem pouco. "Voltar" pequeno (S4).
- **Como reproduzir:** abrir `resultados.html` no celular e rolar até o fim de cada seção.
- **O que mudar:** botão "Agendar avaliação pelo WhatsApp" ao fim de cada seção (ou um só, fixo no fim), com mensagem pronta citando o procedimento; legenda curta embaixo de cada item ("Liposhape — 5 sessões", editável pelo painel); no ultrassom, foto com `object-fit: cover` ou, no futuro, um comparador.
- **Critério de pronto:** cada seção termina com um caminho claro para agendar; todo item tem legenda; nenhuma foto com faixas pretas.

---

## P1. Adicionar, trocar e apagar fotos e vídeos no iPhone
- **Gravidade:** P0 (é a tarefa principal do painel).
- **Evidência:** `decisao-painel.md`, seção 3 (uploads, vídeos, HEIC). No iPhone, o `<input type="file">` abre "Fototeca / Tirar foto / Escolher arquivo"; as fotos vêm em HEIC e os vídeos em HEVC e 4K (1 minuto em 4K passa de 300 MB, acima do limite planejado de 256 MB). O Safari pode pausar o envio se ela trocar de app.
- **Como reproduzir (quando existir):** num iPhone, gravar um vídeo de 1 min e uma foto, e tentar enviar pelo painel no 4G.
- **O que mudar (fluxo):**
  1. Um botão grande por lugar: "Trocar foto", "Adicionar vídeo". `accept="image/*"` para fotos e `accept="video/mp4,video/quicktime"` para vídeos.
  2. **Foto:** o navegador abre a imagem (o Safari lê HEIC), gira pela orientação, reduz para 1600 px e gera JPEG/WebP antes de enviar. Assim HEIC funciona mesmo se o GD do servidor não ler HEIC. O servidor não deve girar de novo (cuidado com rotação dupla).
  3. **Vídeo:** antes de enviar, mostrar duração, tamanho e a capa (primeiro frame). Se for HEVC ou passar do limite, avisar **antes** de gastar dados (ver P4).
  4. Barra de progresso com porcentagem e MB ("Enviando: 12 de 48 MB"), botão "Cancelar", aviso "Não feche esta tela até terminar". Se cair a conexão, botão "Tentar de novo".
  5. Depois de enviar, mostrar a foto/vídeo **na moldura do site** e só então "Salvar no site".
  6. Trocar = escolher novo arquivo no mesmo lugar; o antigo vai para a lixeira (P3).
  7. Texto alternativo gerado sozinho ("Liposhape — antes do tratamento"), com campo opcional para mudar.
- **Critério de pronto:** num iPhone real: foto HEIC em pé chega em pé, ≤ 1600 px, ≤ 250 KB e sem GPS; vídeo de 30 s envia com barra de progresso; vídeo grande demais é barrado antes do envio com a mensagem de P4; cancelar no meio não deixa arquivo pela metade no site.

## P2. Editor de enquadramento do antes/depois (zoom e posição)
- **Gravidade:** P0.
- **Evidência:** o site guarda width/height/left/top em % para cada foto (`mapa.md` 7.1). Conferi as 10 fotos atuais: a proporção exibida é igual à da foto (distorção 0,0%), ou seja, width e height sempre andam juntos. O alinhamento entre antes e depois é o que faz a comparação convencer (ver `desktop-index-resultados.png`). A moldura tem tamanhos diferentes: 78% da tela no carrossel do celular, ~400 px no desktop, 340 px em resultados.
- **Como reproduzir (quando existir):** trocar a foto "depois" de Liposhape e tentar deixá-la alinhada com a "antes" só com o dedo.
- **O que mudar:**
  - Moldura 3:4 do mesmo tamanho do celular (≈ 300 px), com a linha do comparador e os rótulos, desenhada com o mesmo CSS do site.
  - Abas grandes "Foto ANTES" / "Foto DEPOIS" para escolher qual foto mexer.
  - **Arrastar com 1 dedo** = mover; **pinça com 2 dedos** = zoom (Pointer Events, `touch-action:none` só na moldura e `preventDefault` em `gesturestart` para o Safari não dar zoom na página).
  - Zoom com **um único valor** (mantém a proporção da foto; nunca width e height separados), slider "Zoom" + botões "−" e "+" de 44 px e o valor escrito ("Zoom 125%"). Setas ↑ ↓ ← → para ajuste fino de posição.
  - Não deixar sobrar borda vazia: zoom mínimo cobre a moldura e a posição é limitada às bordas.
  - **Modo "Sobrepor"**: mostra a outra foto por cima com 50% de transparência (slider de transparência) para alinhar umbigo, nariz etc. É o recurso que mais ajuda a acertar.
  - Botões "Ver como no site" (alterna celular / computador / página de resultados), "Enquadramento automático" e "Desfazer último ajuste".
  - Salvar no formato atual (width/height/left/top em %), calculando width/height a partir do zoom e da proporção real da foto.
- **Critério de pronto:** abrir uma comparação já existente no editor e salvar sem mexer gera os mesmos números (diferença < 0,01%) e o mesmo print; no iPhone, pinça e arraste não fazem a página rolar nem dar zoom; uma pessoa não técnica alinha duas fotos em menos de 2 minutos num teste com o Vinicius.

## P3. Apagar, confirmar, desfazer e nunca perder trabalho
- **Gravidade:** P1.
- **Evidência:** plano prevê 20 versões, "Desfazer última alteração", lixeira e confirmação ao apagar (`decisao-painel.md` 3). No iPhone, `beforeunload` quase não funciona (não dá para avisar "você tem mudanças não salvas" de forma confiável) e a sessão expira por inatividade.
- **Como reproduzir (quando existir):** editar um texto, deixar o celular bloqueado 40 min, voltar e salvar.
- **O que mudar:**
  - Apagar abre uma janela própria (não o `confirm()` do navegador), com a miniatura e o texto: "Apagar esta comparação? Ela sai do site agora. Você pode trazer de volta pela Lixeira." Botões "Cancelar" (destacado) e "Apagar" (vermelho, separado).
  - Depois de qualquer gravação: aviso no rodapé "Salvo no site. [Desfazer]" por 10 s.
  - Tela "Histórico" em linguagem simples: "Hoje 14:32 — trocou a foto Depois de Liposhape" com botão "Voltar para esta versão".
  - Tela "Lixeira" com "Restaurar".
  - Rascunho guardado no aparelho (localStorage) enquanto ela edita. Se a sessão expirar, pedir a senha de novo e **manter o rascunho**: "Por segurança, entre de novo. Suas mudanças foram guardadas."
- **Critério de pronto:** nenhuma ação apaga algo sem confirmação; toda gravação pode ser desfeita em 1 toque; sessão expirada não perde o que foi digitado (testado no Safari do iPhone).

## P4. Mensagens de erro em português claro, dizendo o que fazer
- **Gravidade:** P1.
- **Evidência:** erros técnicos esperados no plano: limite de upload (`.user.ini`), tipo de arquivo (`finfo`), HEVC, CSRF, limite de 5 tentativas de login, sessão expirada, falha de gravação.
- **Como reproduzir (quando existir):** forçar cada erro (arquivo .pdf, vídeo de 300 MB, senha errada 5 vezes, token CSRF inválido).
- **O que mudar:** nunca mostrar código, "Erro 413", "CSRF" ou texto em inglês. Sempre frase + o que fazer + botão. Exemplos:
  | Situação | Mensagem |
  |---|---|
  | Arquivo de tipo errado | "Este arquivo não é foto nem vídeo. Escolha uma foto ou um vídeo da sua galeria." |
  | Vídeo grande | "Este vídeo tem 380 MB e o limite é 250 MB. No app Fotos, toque em Editar e corte o começo ou o fim. Depois tente de novo." |
  | Vídeo HEVC | "Este vídeo pode não abrir em alguns celulares Android e computadores. Para os próximos: Ajustes → Câmera → Formatos → Mais compatível." + "Enviar mesmo assim" |
  | Sem internet / caiu | "A internet caiu no meio do envio. Nada foi mudado no site. [Tentar de novo]" |
  | Senha errada | "Senha incorreta. Você ainda pode tentar 3 vezes." / "Muitas tentativas. Espere 15 minutos ou fale com o Vinicius." |
  | Sessão expirada / CSRF | "Por segurança, entre de novo. Suas mudanças foram guardadas." |
  | Falha ao salvar | "Não consegui salvar. O site continua como estava. [Tentar de novo]" |
- Mensagens perto do campo, com ícone e cor, com `role="alert"`, e nunca só pela cor.
- **Critério de pronto:** lista de todos os erros do painel com a mensagem aprovada pelo Vinicius; teste forçando cada erro mostra a frase certa e nenhum termo técnico.

## P5. Interface do painel pensada para iPhone e para quem não é técnica
- **Gravidade:** P1.
- **Evidência:** CLAUDE.md ("botões grandes", "português simples", dona usa iPhone); no Safari do iOS, campos com fonte < 16 px dão zoom automático ao tocar; o iPhone tem barra inferior (home indicator) que cobre botões colados no rodapé; arrastar para reordenar é difícil no toque.
- **O que mudar:**
  - Tela inicial com cartões grandes e ícone + palavra: "Antes e depois", "Vídeos", "Textos", "Contato", "Histórico", "Ajuda".
  - Fonte mínima 17 px; campos com 16 px ou mais; botões com 48 px de altura; contraste ≥ 4,5:1; foco visível.
  - Botão de ação principal fixo embaixo, respeitando `env(safe-area-inset-bottom)`.
  - Reordenar com botões "Subir" / "Descer" (não arrastar).
  - "Mostrar na página inicial" e "Mostrar em Todos os resultados" como interruptores com texto, não caixas pequenas.
  - Login: campo com `autocomplete="current-password"` (o iPhone guarda a senha), botão "Mostrar senha", um botão "Entrar".
  - Ícone para "Adicionar à Tela de Início" (`apple-touch-icon`) e título curto, para abrir o painel como um app.
  - Página "Ajuda" com passo a passo e prints do próprio painel.
- **Critério de pronto:** Lighthouse Acessibilidade do painel ≥ 90; nenhum zoom automático ao tocar em campos no iPhone; nenhum botão coberto pela barra do iPhone; o JS do painel ≤ 150 KB (orçamento).

## P6. Edição de textos sem quebrar o layout
- **Gravidade:** P2.
- **Evidência:** textos curtos que seguram o layout: título do hero (`clamp(40px,5vw,64px)`), nomes de procedimentos nos cards de 300 px, rótulos de vídeo, botões. Depoimentos com emojis e textos longos (`celular-index-depoimentos.png`). Endereço e horário repetidos em 3 lugares (`mapa.md` 7.8).
- **O que mudar:** cada campo com rótulo simples ("Título grande do topo"), dica de onde aparece e limite de caracteres com contador ("32 de 40"); prévia do trecho como no site antes de salvar; Enter vira quebra de linha onde faz sentido (endereço); endereço, horário e WhatsApp editados **uma vez só** e repetidos sozinhos em todos os lugares; aceitar emojis.
- **Critério de pronto:** nenhum campo permite texto que quebre o layout em 390 px; mudar o horário uma vez atualiza contato, rodapé e JSON-LD.
