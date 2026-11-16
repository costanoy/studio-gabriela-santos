# Mapa do site — Studio Gabriela Santos

Gerado pelo Agente Mapa. Somente leitura do site; nada foi alterado fora de `.auditoria/`.

## 1. Estrutura de pastas

```
studio-gabriela-santos/
├── index.html            (página principal, ~74 KB, CSS e JS inline)
├── resultados.html       (galeria "Todos os resultados", ~33 KB, CSS e JS inline)
├── robots.txt, sitemap.xml
├── .gitignore            (ignora .env, portfolio-preview/, .auditoria/prints/, node_modules/)
├── .claude/launch.json   (servidor: python -m http.server 8765)
├── .auditoria/           (orcamento.md, mapa.md)
├── portfolio-preview/    (ignorada pelo git; não é do site público)
└── assets/
    ├── logo.svg, logo-gs.png, logo.png (não usado), favicon.png, og-image.jpg
    ├── hero-bg.png (usado), hero-bg.webp (não usado, 2,5 KB)
    ├── sobre-foto.webp
    ├── apresentacao.mp4  (vídeo "Sobre", 14,2 MB)
    ├── fonts/*.woff2     (7 arquivos, @font-face inline; Marcellus e corpo)
    ├── img/*.jpg         (5 posters dos vídeos de resultados)
    └── results/          (fotos antes/depois e vídeos de resultados)
```

## 2. Stack

- HTML5 estático, sem build, sem framework, sem Node no site público.
- CSS inline (`style=""`) em quase todos os elementos, mais dois blocos `<style>` por página.
- JavaScript vanilla inline (um `<script>` por página).
- Sem PHP no projeto atual (o orçamento menciona PHP apenas como possibilidade futura).
- Dependências externas: Google Maps (iframe), Google Forms (link), WhatsApp (`wa.me`), Instagram (link). Sem CDN.
- Fontes locais (woff2), com `preload` de 2 arquivos na index.
- Dados estruturados JSON-LD (BeautySalon) e meta OG/Twitter na index e na resultados.

## 3. Pontos de entrada

| Arquivo | Papel |
|---|---|
| `index.html` | Home (URL canônica `https://studiogabrielasantos.com.br/`) |
| `resultados.html` | Galeria completa de resultados, linkada por "Ver mais" |

Âncoras da home: `#home`, `#sobre`, `#procedimentos`, `#resultados`, `#cursos`, `#depoimentos`, `#contato`.

## 4. Seções e ids — index.html

| Linha aprox. | Elemento / id | Conteúdo |
|---|---|---|
| 303 | `<header id="site-header">` | Sticky; logo que troca no scroll (`#logo-full` para `#logo-gs`); menu desktop; CTA "Agendar horário"; botão hambúrguer `#nav-toggle` |
| 308 | `nav#desktop-nav` | 7 links: Início, Sobre, Procedimentos, Resultados, Cursos, Depoimentos, Contato |
| 326 | `nav#mobile-nav-wrap` | Menu mobile (mesmos 7 links + CTA WhatsApp) |
| 338 | `section#home` | Hero: fundo `#hero-bg-img`, `h1` "Studio Gabriela Santos", subtítulo, 2 botões (WhatsApp e "Ver procedimentos") |
| 363 | `section#sobre.reveal` | Foto `#sobre-foto`, título, 2 parágrafos, stats "Liposhape" e "Mentora" (`#sobre-stats`) |
| 361 | `img#sobre-photo-mobile` | Foto duplicada só para mobile |
| 383 | `#sobre-video-block` | Texto em 3 parágrafos + "Saiba mais sobre o studio" + vídeo `#apresentacao-video` |
| 396 | `section#procedimentos.reveal` | Carrossel horizontal `#procedimentos-grid` com 7 cards (ver seção 7) e setas `#procedimentos-arrow-left/right` |
| 441 | `section#resultados.reveal` | Grid `#resultados-grid` com 5 comparações antes/depois e o card "Ver mais" (link para `resultados.html`), setas mobile |
| 542 | `section#cursos.reveal` | 2 cards de curso com botão "Ver descrição completa" |
| 631 | `section#depoimentos.reveal` | Carrossel `#depoimentos-grid` com 4 depoimentos |
| 704 | `section#contato.reveal` | Endereço, horário, Instagram, botão "Avaliação Online" (Forms), botão WhatsApp, iframe Maps, botão "Abrir no Google Maps" |
| 744 | `footer` | Logo, menus (mesmos links), cursos, contato (WhatsApp, Instagram, endereço, horário), rodapé |

Textos principais (para edição futura):
- Hero: título, subtítulo.
- Bloco "Seja bem-vinda..." (faixa logo após o hero, `div` sem id).
- Sobre: título "Técnica e cuidado em cada atendimento", 2 parágrafos, 2 stats, texto do bloco de vídeo (3 parágrafos).
- Procedimentos: título, subtítulo "O que oferecemos", 7 cards.
- Resultados: eyebrow "Transformações reais" e título "Resultados".
- Cursos: título, descrição, 2 cards (nome, carga horária 10 h, descrição, 2 listas: conteúdo programático e módulos).
- Depoimentos: 4 textos, nome fixo "Cliente do studio", serviço por card.
- Contato: endereço (4 linhas), horário, Instagram, texto dos botões.
- Footer: textos repetidos (endereço e horário aparecem 2 vezes na home).

## 5. Seções e ids — resultados.html

| Linha aprox. | Elemento | Conteúdo |
|---|---|---|
| 253 | `header#site-header` | Logo (troca no scroll), link "Voltar" (`#rc-back-link`) para index.html |
| 262 | `section` | Eyebrow e `h1` "Todos os resultados" |
| 267 | `section` | Liposhape: 1 comparação antes/depois + 2 vídeos (`liposhape-depois3.mp4` com poster `img/99c58818.jpg`, `liposhape-pos.mp4` com poster `img/39883127.jpg`) |
| 297 | `section` | Drenagem linfática: 2 comparações |
| 329 | `section` | Limpeza de pele: 2 comparações |
| 361 | `section` | Ultrassom Lipolítico: 1 foto "antes" (sem comparador) + vídeo `ultrassom-depois1.mp4` (poster `img/d96ec399.jpg`) |
| 378 | `section` | Corrente Fortalecedora: 2 vídeos (`corrente-1.mp4` poster `img/5c2dbddb.jpg`, `corrente-2.mp4` poster `img/bcea0fb6.jpg`) |
| 396 | `footer` | Mesmo rodapé da home, links apontando para `index.html#...` |

## 6. Funções JavaScript

### index.html (`<script>` linhas 793–880)

| Função / listener | O que faz |
|---|---|
| `toggleApresentacaoVideo()` | Play/pause do vídeo `#apresentacao-video`. Chamada pelo overlay transparente sobre o vídeo (`div` com `onclick`). |
| `goHome(e)` | Clique em "Início" (header e rodapé): rola suavemente ao topo e troca o hash por `#home`. |
| `goHomeMobile(e)` | Igual ao anterior, mas também fecha o menu mobile. |
| `scrollByCard(gridId, dir)` | Setas de carrossel (`procedimentos-grid`, `resultados-grid`, `depoimentos-grid`). Rola até o próximo card. Usa `__carouselIdx`. |
| Listener `scrollend` no grid | Atualiza o índice atual do carrossel após rolagem manual. |
| IIFE do vídeo `#apresentacao-video` | Ao carregar metadados, ajusta `aspect-ratio` para a proporção real do vídeo. |
| `toggleCurso(id, btn)` | Abre/fecha "Ver descrição completa" dos cursos (`#curso-metodo`, `#curso-limpeza`) e troca o texto do botão. |
| `baSlide(input)` | Comparador antes/depois: ajusta `clipPath` de `.ba-clip` e `left` de `.ba-line` conforme o valor do range. Usada por todos os `input type="range"`. |
| Listener de scroll (`#site-header.scrolled`) | Adiciona a classe `scrolled` acima de 80 px e remove abaixo de 20 px. Troca o logo (CSS em `@media`). |
| Listener `hashchange` | Fecha o menu mobile ao mudar de âncora. |
| IIFE de arrastar (`#procedimentos-grid`) | Arrasta o carrossel com mouse (mousedown/mousemove/mouseup) e desliga o snap durante o arraste. |
| `IntersectionObserver` (`revealObserver`) | Adiciona `.visible` aos elementos `.reveal` quando 12% estão visíveis (animação de entrada). |
| Botão hambúrguer (`#nav-toggle`, inline) | Alterna `display` de `#mobile-nav-wrap` entre `flex` e `none`. |

### resultados.html (`<script>` linhas 446–468)

| Função / listener | O que faz |
|---|---|
| Listener de scroll | Mesma lógica de `.scrolled` do header. |
| `toggleVideo(overlay)` | Play/pause do vídeo irmão do overlay (`div` transparente de 54 px no rodapé do card). Há variável não usada (`video`), sem efeito prático. |
| `baSlide(input)` | Mesma lógica do index. |

Não há funções inline em `<a href>` nem fetch/XHR. Não há formulário nem envio de dados pelo site.

## 7. Blocos editáveis pela dona do studio

### 7.1 Comparações antes/depois — index.html

Cada comparação é um `div` com `aspect-ratio: 3/4` contendo:
- foto "depois" (`img`, z-index base), com `width`, `height`, `left`, `top` em %;
- `.ba-clip` (`clip-path: inset(0 50% 0 0)`), que contém a foto "antes" com seus próprios `width`, `height`, `left`, `top`;
- `.ba-line` (linha de 2 px no centro), com `left: 50%`;
- `input type="range"` (0–100) que controla o recorte.

| # | Linha | Título (`p` abaixo) | Arquivo "depois" | Arquivo "antes" | Ajustes depois (width/height/left/top) |
|---|---|---|---|---|---|
| 1 | 450 / 453 | Liposhape | `liposhape-depois1.jpeg` | `liposhape-antes1.jpeg` | depois: 125,5% / 100% / 45,888% / 50%; antes: 138,799% / 100% / 50% / 50% |
| 2 | 464 / 467 | Drenagem linfática | `drenagem-depois1.jpeg` | `drenagem-antes1.jpeg` | depois: 101,015% / 102,814% / 50,508% / 48,554%; antes: 106,896% / 108,708% / 51,524% / 53,216% |
| 3 | 478 / 481 | Drenagem linfática | `drenagem-depois2.jpeg` | `drenagem-antes2.jpeg` | depois: 113,869% / 132,921% / 50,062% / 46,914%; antes: 100% / 104,439% / 50% / 50% |
| 4 | 492 / 495 | Limpeza de pele | `limpeza-depois1.jpeg` | `limpeza-antes1.jpeg` | depois: 154,652% / 100% / 41,775% / 50%; antes: 152,449% / 100% / 50% / 50% |
| 5 | 506 / 509 | Limpeza de pele | `limpeza-depois2.jpeg` | `limpeza-antes2.jpeg` | depois: 162,121% / 100% / 52,661% / 50%; antes: 179,27% / 110,892% / 58,562% / 44,554% |

Os ajustes de `width`, `height`, `left` e `top` são o que faz as fotos ficarem alinhadas entre antes e depois (enquadramento). Mudar uma foto sem ajustar a outra desalinha a comparação.

Legendas: "Antes" e "Depois" são `span` fixos; o nome do procedimento é o `p` abaixo de cada card, repetido para os pares de drenagem e limpeza.

### 7.2 Comparações e mídias — resultados.html

| # | Linha (antes/depois) | Bloco | Arquivos | Observação |
|---|---|---|---|---|
| R1 | 273–283 | Liposhape antes/depois | `liposhape-depois1.jpeg` / `liposhape-antes1.jpeg` | Mesmo arquivo da home |
| R2 | 301–323 | Drenagem (2 pares) | `drenagem-depois1/antes1`, `drenagem-depois2/antes2` | Mesmos arquivos da home |
| R3 | 333–355 | Limpeza de pele (2 pares) | `limpeza-depois1/antes1`, `limpeza-depois2/antes2` | Mesmos arquivos da home |
| R4 | 366 | Ultrassom antes | `ultrassom-antes1.jpeg` | Só foto "antes", sem comparador. `object-fit: contain`, `object-position: calc(50% - 0.86%) 50%` |

Vídeos em `resultados.html` (todos com `controls`, `muted`, `playsinline`, `preload="metadata"`, `poster` e overlay de 54 px no rodapé):

| Vídeo | Linha | Arquivo | Poster | Legenda |
|---|---|---|---|---|
| V1 | 284 | `liposhape-depois3.mp4` (2,2 MB) | `assets/img/99c58818.jpg` | Resultado |
| V2 | 289 | `liposhape-pos.mp4` (1,9 MB) | `assets/img/39883127.jpg` | Pós Liposhape |
| V3 | 370 | `ultrassom-depois1.mp4` (1,5 MB) | `assets/img/d96ec399.jpg` | Depois |
| V4 | 383 | `corrente-1.mp4` (2,1 MB) | `assets/img/5c2dbddb.jpg` | Corrente Fortalecedora |
| V5 | 388 | `corrente-2.mp4` (2,4 MB) | `assets/img/bcea0fb6.jpg` | Corrente Fortalecedora |

### 7.3 Vídeos na home

| Vídeo | Linha | Arquivo | Poster | Observação |
|---|---|---|---|---|
| `#apresentacao-video` | 391 | `assets/apresentacao.mp4` (14,2 MB) | `assets/sobre-foto.webp` | Poster é uma foto, não o primeiro frame do vídeo. `preload="metadata"`. Controles nativos. Overlay `div` (linha ~393) chama `toggleApresentacaoVideo()`. |

### 7.4 Procedimentos — index.html, `#procedimentos-grid` (linhas 401 a 440)

Sete cards, cada um com `h3` (título) e `p` (descrição):
1. Liposhape
2. Drenagem linfática
3. Massagem modeladora
4. Limpeza de pele
5. Corrente Fortalecedora
6. Ultrassom — quebra de gordura
7. Massagem relaxante

Não há imagem nos cards.

### 7.5 Cursos — index.html, `#cursos` (linhas 542 a 630)

- Card "Método Gabriela Santos" (linha ~550): título, "Carga horária: 10 horas", descrição, botão WhatsApp (linha ~557), botão "Ver descrição completa" abrindo `#curso-metodo` (linha 561) com duas listas: "Conteúdo programático" (7 itens) e "Módulos" (5 itens).
- Card "Limpeza de Pele" (linha ~585): mesmo formato, `#curso-limpeza` (linha 599), 8 itens no conteúdo e 7 em módulos.

### 7.6 Depoimentos — index.html, `#depoimentos-grid` (linhas 636 a 703)

Quatro cards, cada um com 5 estrelas (SVG fixo), um texto em itálico e o rótulo "Cliente do studio" com o procedimento:
1. Massagem Modeladora (texto longo, com emojis)
2. Liposhape
3. Ultrassom
4. Drenagem Linfática

Não há foto de cliente. Os nomes estão genéricos.

### 7.7 Contato e links — index.html, `#contato` (linhas 704 a 743)

- Endereço, horário e Instagram: textos fixos. O endereço também aparece no footer.
- Botão "Avaliação Online" → `https://forms.gle/nkKaVqY9BxyV5XVPA`
- Iframe do Maps: `https://www.google.com/maps?q=-15.332350,-49.133907&output=embed`
- Botão "Abrir no Google Maps" → `https://maps.app.goo.gl/SFJzFnDdAkket245A`

### 7.8 Textos e links que se repetem

- O número de WhatsApp `5562984141116` aparece em 7 links na index e em vários no resultados.
- Mensagem padrão: "Olá! Gostaria de agendar um horário no Studio Gabriela Santos."
- Mensagens dos cursos: "Quero saber mais sobre o curso Método Gabriela Santos." e "Quero saber mais sobre o curso Limpeza de Pele."
- Telefone e endereço também estão no JSON-LD da linha 23–25 (index) e 23–25 (resultados).

## 8. Links externos

| Destino | Onde | Tipo |
|---|---|---|
| `https://wa.me/5562984141116?text=...` | Header desktop (`#desktop-cta`), menu mobile, hero, contato, footer, 2 cursos | Ação principal |
| `https://instagram.com/gabisantos_estetica` | Contato, footer | Perfil |
| `https://forms.gle/nkKaVqY9BxyV5XVPA` | Contato (`#avaliacao-online-link`) | Google Forms |
| `https://maps.app.goo.gl/SFJzFnDdAkket245A` | Contato, footer | Rota |
| `https://www.google.com/maps?q=...&output=embed` | iframe em Contato (lazy) | Mapa |
| `resultados.html` | Card "Ver mais" em `#resultados`, e botão mobile `#ver-mais-mobile` | Interno |
| `index.html#...` | Links de rodapé em resultados.html | Interno |

Todos os links externos abrem em `_blank` com `rel="noopener"`.

## 9. Fluxos do usuário

1. Entrada pelo Instagram ou WhatsApp, cai no hero. CTA "Agendar pelo WhatsApp" abre conversa com mensagem pronta.
2. Lê Sobre, assiste ao vídeo de apresentação (toggle de play via overlay).
3. Rola até Procedimentos e usa o carrossel horizontal (setas no desktop, arraste com mouse, swipe no celular).
4. Em Resultados, arrasta o slider de cada antes/depois. Clica em "Ver mais" e vai para `resultados.html`, onde há comparadores e vídeos.
5. Vê cursos e abre "Ver descrição completa", ou chama no WhatsApp.
6. Lê depoimentos com o carrossel.
7. Em Contato, escolhe entre WhatsApp, Avaliação Online (Forms), Maps ou Instagram.
8. Fluxo mobile: hambúrguer abre o menu. Qualquer âncora fecha o menu.

Não há formulário com envio, login, carrinho ou pagamento no site público.

## 10. Observações para o restante da auditoria (sem alterar nada)

- Vídeos: todos têm `poster`, mas o poster do vídeo da home (`sobre-foto.webp`) não é o primeiro frame do vídeo. Os posters de resultados vêm de `assets/img/*.jpg`. Em iPhone, a tela preta costuma aparecer quando não há poster ou quando ele não coincide com o primeiro frame. Vale checar se os 5 vídeos de resultados e o da home mostram imagem antes do play.
- Peso: `apresentacao.mp4` tem 14,2 MB. O orçamento de performance (`.auditoria/orcamento.md`) limita o peso do primeiro carregamento a 1,5 MB e pede H.264 com `preload="none"` ou `"metadata"`. Com `preload="metadata"`, o navegador ainda pode buscar partes do arquivo.
- Arquivos sem referência (candidatos a limpeza, só após confirmação): `assets/results/liposhape-antes3.jpeg`, `assets/results/liposhape-depois2.mp4` (1,7 MB), `assets/results/corrente-3.mp4` (2,5 MB), `assets/hero-bg.webp`, `assets/logo.png`.
- `assets/img/*.jpg` são posters. Não estão no HTML como imagens visíveis, só como `poster`.
- Duplicação: `sobre-foto.webp` aparece 2 vezes na index (desktop e mobile), e `logo.svg` aparece 2 vezes (header e footer). Não causa problema de ids, porque os ids são únicos.
- A index tem 5 comparações e a resultados tem 5 comparações. Os arquivos são os mesmos. Se a dona editar um, precisa atualizar os dois lugares ou centralizar em um só.
- Ultrassom na index não tem comparação. Só na resultados (foto "antes" sem "depois" e vídeo "depois").
- Textos repetidos (endereço, horário, WhatsApp) estão em 2 ou mais lugares. Mudar em um não muda nos outros.
- Não há campo de dados externo. Tudo está escrito no HTML, então qualquer edição de conteúdo exige editar o arquivo.
- As âncoras do menu mobile fecham o menu no `hashchange` e no `onclick`. Não há bug aparente.
