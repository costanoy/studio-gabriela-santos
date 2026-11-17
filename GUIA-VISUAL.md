# Guia visual — Studio Gabriela Santos

Este guia vale para o site público (`index.php`, `resultados.php`, `parciais/`) e para o painel (`painel/painel.css`). Ele segue a **Direção A, "Retrato"**, descrita em `.auditoria/direcoes-visuais.md`. A seção 11 diz o que muda se a escolhida for a Direção B.

**O que não muda:** os textos (nenhuma palavra nova, nenhuma removida), as fotos (nenhum filtro, nenhum recorte novo nos antes/depois), o marrom dominante e a logo.

---

## 1. Conceito

**Ateliê editorial.** O site passa a parecer uma revista de beleza impressa em papel bom: títulos grandes em serifa fina, muito espaço em branco, linhas de 1 px no lugar de caixas e sombras, e o marrom usado em grandes áreas chapadas. As fotos são as protagonistas, apresentadas em molduras retas ou em arco, sem enfeite por cima. O luxo vem da contenção: poucos elementos, bem alinhados e com bastante ar.

O que sai: cantos de 20 px em tudo, sombras difusas, caixas bege centralizadas, rótulos minúsculos em maiúsculas com letras espaçadas e três carrosséis seguidos. É isso que hoje dá a cara de modelo pronto.

---

## 2. Paleta

Declare como variáveis CSS em `:root` (site e painel usam os mesmos nomes).

| Token | Hex | Papel |
|---|---|---|
| `--marrom` | `#7D5843` | **Cor dominante.** Fundo do topo, de Procedimentos e de Contato. Botão principal sobre fundo claro. Rótulos e links sobre fundo claro. |
| `--marrom-fundo` | `#6B4A39` | Fim do degradê das áreas marrons. Estado hover do botão principal. |
| `--marrom-claro` | `#996C55` | Só ícones e detalhes gráficos. **Nunca fundo de texto** (creme sobre ele dá 4,1:1). |
| `--cafe` | `#3A2A22` | Títulos e texto de destaque sobre fundo claro. Fundo do cabeçalho depois de rolar. |
| `--cafe-escuro` | `#2A1D17` | Cor nova. Fundo do rodapé, do menu do celular e das molduras de foto e vídeo (no lugar do preto `#000`). |
| `--texto` | `#5C4A40` | Texto corrido sobre fundo claro. |
| `--creme` | `#FBF3EE` | Fundo padrão da página. Texto e botão sobre marrom. |
| `--areia` | `#F3E4DA` | Fundo de faixas alternadas (vídeo do Sobre, Cursos). Texto secundário sobre marrom. |
| `--linha` | `#E7CDBE` | Linhas de 1 px sobre fundo claro. |
| `--marfim` | `#FFFBF7` | Cor nova. Fundo dos cartões de curso, para destacar sobre `--areia`. |
| `--champanhe` | `#E3C9A8` | Cor nova, o único acento. Linhas e contornos sobre marrom, numerais grandes, aspas, contorno deslocado das fotos. |

Degradê das áreas marrons (topo, Procedimentos, Contato): `linear-gradient(160deg, #7D5843 0%, #74513E 55%, #6B4A39 100%)`.
Linha sobre marrom: `rgba(227,201,168,.28)`.

**Contrastes medidos (use só estes pares para texto):**

| Texto | Fundo | Contraste |
|---|---|---|
| `--creme` | `--marrom` / `--marrom-fundo` | 5,7 / 7,2 |
| `--areia` | `--marrom` / `--marrom-fundo` | 5,1 / 6,4 |
| `--champanhe` | `--marrom` | 3,9 — **só texto com 28 px ou mais, e linhas** |
| `--champanhe` | `--marrom-fundo` / `--cafe-escuro` | 5,0 / 10,3 |
| `--marrom` | `--creme` / `--areia` / `--marfim` | 5,7 / 5,1 / 6,1 |
| `--texto` | `--creme` / `--areia` | 7,6 / 6,8 |
| `--cafe` | `--creme` | 12,5 |
| `--creme` | `--cafe-escuro` | 14,9 |

A proporção de cor na página deve ficar perto de 45% marrom, 45% creme e areia, 10% café-escuro. O champanhe aparece só em traços.

---

## 3. Tipografia

### Famílias

| Uso | Família | Pesos | Arquivos |
|---|---|---|---|
| Títulos, rótulos, citações, numerais | **Cormorant Garamond** (Google Fonts, licença OFL) | 500 normal e 500 itálico | 2 arquivos woff2, subset **latin** |
| Texto, botões, menu, legendas | **Mulish** (já existe, variável) | 400, 600, 700 | o arquivo latin que já está em `assets/fonts` |

A Marcellus sai (remover os dois arquivos e os `@font-face`).

**Regras de carregamento (o LCP está em 2,49 s, o limite é 2,5 s):**
- Baixar os woff2 do Google Fonts e salvar em `assets/fonts/`. Só o subset latin.
- Teto de peso: cada arquivo da Cormorant com no máximo 40 KB. Se a versão variável passar disso, usar os arquivos estáticos de peso 500.
- `<link rel="preload">` só para dois arquivos: Cormorant 500 normal e Mulish latin. É a mesma quantidade de hoje. O itálico não entra no preload.
- `font-display: swap` em todos.
- Apagar do CSS inline os blocos `@font-face` da Mulish em cirílico e vietnamita (e os arquivos). O site é em português e isso reduz o HTML.
- Fonte reserva com métrica ajustada, para o título não pular quando a Cormorant chega (CLS ≤ 0,1):
  ```css
  @font-face { font-family: 'Cormorant Reserva'; src: local('Georgia'); size-adjust: 86%; ascent-override: 92%; descent-override: 29%; line-gap-override: 0%; }
  --serifa: 'Cormorant Garamond', 'Cormorant Reserva', Georgia, serif;
  --sem-serifa: 'Mulish', system-ui, sans-serif;
  ```
  Os valores de `size-adjust` e dos overrides são ponto de partida. Ajustar sobrepondo as duas fontes no navegador.
- Depois da troca, rodar o Lighthouse de celular. Se o LCP passar de 2,5 s, a primeira medida é tirar o itálico (os rótulos passam a usar o peso 500 normal).

### Escala

A Cormorant tem letras minúsculas pequenas. Por isso ela nunca aparece abaixo de 19 px e os tamanhos são maiores que os da Marcellus.

| Estilo | Família / peso | Celular (px / entrelinha) | Desktop (px / entrelinha) | Entre letras | Fórmula |
|---|---|---|---|---|---|
| `h1` do topo | Cormorant 500 | 48 / 1,02 | 96 / 0,98 | -0,015em | `clamp(48px, 6.7vw, 96px)` |
| `h1` de resultados | Cormorant 500 | 44 / 1,05 | 72 / 1,0 | -0,015em | `clamp(44px, 5vw, 72px)` |
| `h2` de seção | Cormorant 500 | 36 / 1,1 | 56 / 1,05 | -0,01em | `clamp(36px, 3.9vw, 56px)` |
| `h3` (cartão, curso, categoria) | Cormorant 500 | 26 / 1,15 | 32 / 1,15 | -0,005em | `clamp(26px, 2.2vw, 32px)` |
| Rótulo (`.rotulo`) | Cormorant 500 itálico | 19 / 1,2 | 22 / 1,2 | 0 | — |
| Frase de boas-vindas | Cormorant 500 itálico | 26 / 1,3 | 40 / 1,25 | -0,005em | `clamp(26px, 2.8vw, 40px)` |
| Citação (depoimento) | Cormorant 500 itálico | 21 / 1,45 | 24 / 1,45 | 0 | — |
| Numeral (01, 02, valor) | Cormorant 500 itálico | 28 / 1 | 36 / 1 | 0 | — |
| Texto de apoio (topo) | Mulish 400 | 17 / 1,6 | 19 / 1,6 | 0 | — |
| Parágrafo de destaque | Mulish 400 | 19 / 1,6 | 22 / 1,55 | 0 | — |
| Texto corrido | Mulish 400 | 16 / 1,7 | 17 / 1,75 | 0 | — |
| Legenda | Mulish 400 | 14 / 1,5 | 14 / 1,5 | 0 | — |
| Botão | Mulish 700, maiúsculas | 13 / 1 | 13 / 1 | 0,14em | — |
| Menu do desktop | Mulish 600, maiúsculas | — | 13 / 1 | 0,12em | — |
| Etiqueta (Antes, Depois) | Mulish 700, maiúsculas | 11 / 1 | 11 / 1 | 0,14em | — |

- Títulos: `text-wrap: balance`. Parágrafos: `text-wrap: pretty` e largura máxima de 62ch.
- Títulos sempre no peso 500. Não usar negrito falso (`font-synthesis: none`).
- **O rótulo muda de cara.** Hoje é Mulish 13 px em maiúsculas espaçadas. Passa a ser Cormorant itálico em caixa normal, precedido de uma linha de 32 px por 1 px (`::before`, cor `--marrom` no claro e `--champanhe` no marrom), com 12 px entre a linha e o texto. Cor do texto: `--marrom` no claro, `--areia` no marrom.
- Alinhamento padrão: **à esquerda**. Centralizado só na frase de boas-vindas e no convite final de resultados.

---

## 4. Grade e espaçamento

- Unidade base: 8 px. Usar só 4, 8, 12, 16, 24, 32, 40, 56, 64, 80, 96, 112, 144.
- Contêiner: `max-width: 1240px`, centralizado. Margem lateral: 24 px até 600 px de tela, 40 px de 601 a 1100 px, 64 px acima disso.
- Desktop: 12 colunas, calha de 24 px. Celular: coluna única.
- Pontos de quebra: manter os que existem. Até 900 px é o layout de celular. Até 1100 px o menu vira o botão de três riscos.
- Espaço vertical das seções (`padding` em cima e embaixo): 80 px no celular, 144 px no desktop. Faixas internas (vídeo do Sobre, convite final): 64 px e 112 px.
- Entre o cabeçalho da seção (rótulo e título) e o conteúdo: 40 px no celular, 72 px no desktop.
- Cabeçalho de seção no desktop: rótulo e `h2` nas colunas 1 a 7. Quando houver um link de ação ("Ver mais"), ele fica nas colunas 10 a 12, alinhado pela base do título.
- Seções marrons vão de borda a borda. Nada de caixa arredondada flutuando sobre o fundo.

---

## 5. Componentes

### Botões

Todos: forma de pílula (`border-radius: 999px`), `min-height: 52px`, `padding: 0 32px`, Mulish 700 de 13 px em maiúsculas com 0,14em, `display: inline-flex` centralizado, sem sombra. Transição de `background-color`, `color` e `border-color` em 200 ms.

| Classe | Onde | Normal | Hover / foco |
|---|---|---|---|
| `.btn-claro` | sobre marrom (topo, Contato, menu) | fundo `--creme`, texto `--marrom-fundo` | fundo `--champanhe` |
| `.btn-contorno-claro` | sobre marrom, ação secundária | borda 1 px `--areia`, texto `--areia`, fundo transparente | fundo `rgba(243,228,218,.14)` |
| `.btn-marrom` | sobre claro (Cursos, convite final) | fundo `--marrom`, texto `--creme` | fundo `--marrom-fundo` |
| `.btn-contorno` | sobre claro, ação secundária | borda 1 px `--marrom`, texto `--marrom` | fundo `--areia` |
| `.btn-texto` | ação terciária ("Ver mais", "Ver descrição completa") | sem fundo, texto na cor do contexto, linha de 1 px embaixo, seta de linha (→) de 18 px à direita, `min-height: 44px` | a seta anda 4 px para a direita em 200 ms |

- No celular, botões do topo e de Contato ocupam a largura toda.
- Foco visível: `outline: 2px solid` na cor do texto do botão, `outline-offset: 3px`.
- Sai o efeito de subir 2 px no hover (`.btn-lift`).

### Cartões

Não existe mais o cartão bege com sombra e canto de 20 px. Há dois padrões:

1. **Item com linha** (Procedimentos, números do Sobre, depoimentos, dados de Contato): sem fundo, sem borda lateral, só uma linha de 1 px no alto e `padding-top` de 24 px.
2. **Cartão de papel** (Cursos): fundo `--marfim`, borda de 1 px `--linha`, `border-radius: 4px`, sem sombra, `padding` de 32 px 24 px no celular e 48 px 56 px no desktop.

Raio padrão de qualquer retângulo: **4 px**. Os únicos redondos são os botões (pílula), o arco do retrato e os botões circulares.

### Comparador antes/depois

Não alterar o HTML que o script usa (`.ba`, `.ba-clip`, `.ba-line`, `.ba-alca`, `.ba-antes`, `.ba-depois`, o `input[type=range]`) nem o enquadramento que vem do painel. Só o acabamento:

- Quadro: proporção 3/4 (mantida), `border-radius: 4px`, fundo `--cafe-escuro`.
- Linha divisória: 1 px, `--creme`, do alto até 44 px antes do pé.
- Alça: círculo de 44 px, fundo `--creme`, borda de 1 px `--champanhe`, sombra `0 4px 16px rgba(42,29,23,.35)`, setas de linha na cor `--marrom`.
- Etiquetas "Antes" e "Depois": sobem para os cantos de cima (12 px da borda). Pílula com fundo `rgba(42,29,23,.78)`, texto `--creme` no estilo Etiqueta, `padding: 6px 12px`. As duas iguais (hoje têm cores diferentes).
- Controle deslizante do pé: continua existindo e funcionando pelo teclado, com 44 px de altura. Visualmente vira um trilho de 2 px `rgba(251,243,238,.6)` com um marcador redondo de 16 px `--creme` (estilizar com `::-webkit-slider-thumb`, `::-webkit-slider-runnable-track`, `::-moz-range-thumb` e `::-moz-range-track`), sobre um degradê `linear-gradient(transparent, rgba(42,29,23,.55))` de 56 px no pé do quadro.
- Legenda embaixo: alinhada à esquerda, 16 px abaixo do quadro, Cormorant 500 de 22 px, cor `--cafe`.
- Nenhum zoom, filtro ou sombra sobre a foto.

### Carrosséis

Sobram dois: Resultados da home (só no celular) e Depoimentos. Procedimentos deixa de ser carrossel.

- Rolagem horizontal nativa com `scroll-snap-type: x mandatory`, barra escondida, calha de 16 px no celular e 32 px no desktop.
- O primeiro item alinha com a margem do contêiner. O último item aparece cortado para indicar que há mais.
- Setas: botões circulares de 44 px, borda de 1 px (`--marrom` no claro), fundo transparente, seta de linha. Hover: fundo `--marrom` e seta `--creme`. Sem sombra. Ficam abaixo do carrossel, à direita no desktop e centralizadas no celular, com 12 px entre elas.
- Contador (`.carrossel-contador`): Cormorant itálico de 20 px, à esquerda das setas.
- Com poucos itens (todos cabem na tela), as setas e o contador somem.

### Vídeos

- Mantêm `preload="none"`, `poster`, `playsinline` e `controls`.
- Quadro: `border-radius: 4px`, fundo `--cafe-escuro`.
- Botão de play: círculo de 72 px, fundo `rgba(42,29,23,.55)`, borda de 1 px `--creme`, triângulo `--creme`. Hover: `transform: scale(1.06)` em 200 ms e fundo `rgba(42,29,23,.75)`.
- Etiqueta do vídeo (resultados): mesma pílula das etiquetas do comparador, no canto de cima à esquerda.
- O vídeo de apresentação do Sobre ganha o contorno champanhe deslocado (ver "Tratamento das fotos").

### Cabeçalho

- Home, no alto da página: **transparente**, por cima do topo marrom (o topo recebe `margin-top` negativo igual à altura do cabeçalho, via variável `--cab-h`). Sem borda, sem degradê.
- Depois de rolar (classe `.scrolled`, que o script já aplica): fundo `rgba(58,42,34,.94)` com `backdrop-filter: blur(10px)` e linha de 1 px `rgba(227,201,168,.18)` embaixo.
- Página de resultados: sempre no estado "depois de rolar".
- Alturas: desktop 96 px (logo com 76 px), depois de rolar 64 px (monograma GS com 44 px). Celular 72 px (logo com 56 px), depois de rolar 60 px (monograma com 40 px). Transição de 250 ms.
- Desktop: logo à esquerda, menu no centro, botão à direita (hoje a logo fica solta no centro da primeira coluna).
- Links do menu: estilo "Menu do desktop", cor `--creme`, 32 px entre eles. Hover: linha de 1 px `--champanhe` que cresce da esquerda para a direita em 250 ms, 6 px abaixo do texto. Área clicável com 44 px de altura.
- Botão "Agendar horário": `.btn-contorno-claro` com `min-height: 44px` e `padding: 0 24px`.
- Botão de três riscos (celular): 44 x 44 px, sem borda, dois riscos de 22 px por 1,5 px `--creme` com 7 px entre eles. Com o menu aberto, viram um X (rotação de 45 graus em 200 ms).

### Menu do celular

- Painel que desce do cabeçalho ocupando a largura toda, fundo `--cafe-escuro`, sem cantos arredondados, `padding: 8px 24px 32px`.
- Links: Cormorant 500 de 28 px, cor `--creme`, 56 px de altura cada, linha de 1 px `rgba(227,201,168,.18)` entre eles.
- Botão "Agendar horário": `.btn-claro`, largura toda, 24 px abaixo do último link.
- Abertura: `opacity` de 0 a 1 e `translateY` de -8 px a 0 em 250 ms, por `@keyframes` disparado quando o script troca o `display` (não precisa de JS novo).

### Rodapé

- Fundo `--cafe-escuro`, `padding` de 64 px 24 px 32 px no celular e 96 px 0 40 px no desktop.
- Desktop: logo (120 px de altura) nas colunas 1 a 4, e as três listas (menu, cursos, contato) nas colunas 6 a 12.
- Título de cada lista: estilo Rótulo, cor `--champanhe`, sem a linha antes.
- Links: Mulish 15 px, cor `#D8C4B8`, hover `--creme`. No celular, 44 px de altura.
- Linha de 1 px `rgba(227,201,168,.18)` acima da linha de crédito. Crédito em Mulish 13 px, cor `#CDB9AC`.
- Celular: tudo centralizado, logo com 120 px, só a lista de contato (como hoje).

---

## 6. Tratamento das fotos

- **Nunca** aplicar filtro, sobreposição de cor, desfoque ou zoom nas fotos de resultado. Elas são prova de trabalho.
- Cantos de 4 px em todas as fotos e vídeos retangulares.
- **Retrato da Gabriela:** moldura em arco, `border-radius: 999px 999px 4px 4px`, proporção 3/4 (a proporção original do arquivo, sem corte), `object-fit: cover`, `object-position: center top`.
- **Contorno deslocado:** pseudo-elemento com a mesma forma da foto, borda de 1 px `--champanhe`, deslocado para cima e para a esquerda (10 px no celular, 14 px no desktop), atrás da foto. Usado só no retrato e no vídeo de apresentação.
- Sombra só no retrato: `0 30px 60px -30px rgba(42,29,23,.45)`.
- Foto que não preenche o quadro (`object-fit: contain`): fundo `--cafe-escuro`, nunca preto.
- Monograma GS do topo (`hero.imagem`): vira um brilho atrás do retrato, com opacidade entre 0,22 e 0,25 e `mask-image: radial-gradient(closest-side, #000 55%, transparent 100%)` para esconder as bordas do retângulo. Conferir na tela que não aparece nenhum retângulo.
- Todas as imagens continuam com `width` e `height` (ou `aspect-ratio`) declarados, para não haver salto de layout.

---

## 7. Movimento

Tudo em CSS. O único JS é o observador que já existe e põe a classe `.visible`.

| O quê | Como | Duração | Curva |
|---|---|---|---|
| Entrada de seção (`.reveal`) | `opacity` 0 → 1 e `translateY` 16 px → 0 | 700 ms | `cubic-bezier(.22,.61,.36,1)` |
| Itens em sequência (procedimentos, resultados, cursos) | mesma entrada, com `transition-delay: calc(var(--i) * 80ms)`, limitado a 400 ms. O `--i` é escrito pelo PHP no `style` de cada item | 600 ms | a mesma |
| Topo, ao carregar | texto de apoio e botões: `opacity` 0 → 1 e `translateY` 12 px → 0, com atraso de 150 ms e 250 ms. Contorno do retrato: `opacity` 0 → 1 com atraso de 400 ms | 600 ms | a mesma |
| Hover de botão | cor de fundo e de texto | 200 ms | `ease` |
| Seta do `.btn-texto` | `translateX` 4 px | 200 ms | `ease` |
| Sublinhado do menu | `scaleX` 0 → 1, origem à esquerda | 250 ms | `ease` |
| Cabeçalho ao rolar | altura, fundo e troca de logo | 250 ms | `ease` |
| Abrir "Ver descrição completa" | `opacity` 0 → 1 e `translateY` -8 px → 0, e a seta do botão gira 180 graus | 250 ms | `ease` |
| Menu do celular | ver componente | 250 ms | `ease` |

**Regras:**
- **O `h1` e o retrato do topo nunca começam com `opacity: 0`.** Eles são o LCP, e escondê-los atrasa a medida. Eles aparecem parados.
- Animar só `opacity` e `transform`.
- Nada de paralaxe, de movimento contínuo, de texto digitando ou de contadores subindo.
- `@media (prefers-reduced-motion: reduce)`: todas as transições e animações acima ficam com duração 0 e sem `transform`. O conteúdo aparece direto e `scroll-behavior` fica `auto`.
- Sem JS, o conteúdo precisa estar visível (o esconder de `.reveal` continua preso à classe `.js`).

---

## 8. Seção por seção

### Antes de começar (vale para tudo)

- Os estilos hoje estão em atributos `style` com vários `!important` nas regras de celular. Passar para classes, com as variáveis da seção 2, dentro de `parciais/estilo-home.php` e `parciais/estilo-resultados.php`. O que for comum às duas páginas (variáveis, fontes, botões, comparador, cabeçalho, rodapé) vai para um parcial novo de estilo, incluído pelos dois. Isso também reduz o HTML (limite de 120 KB).
- **Não renomear** ids e classes usados por `parciais/script-home.php`, `parciais/script-resultados.php` e `tests/site.spec.js`. Fazer um `grep` antes de mexer em cada seção. Exemplos: `site-header`, `logo-full`, `logo-gs`, `nav-toggle`, `mobile-nav-wrap`, `desktop-nav`, `resultados-grid`, `depoimentos-grid`, `procedimentos-grid`, `carrossel-contador`, `apresentacao-video`, `video-play`, `reveal`.
- Rodar `npx playwright test` e o Lighthouse de celular ao fim de cada lote.

### Home

**1. Topo (`#home`)** — Direção A.
Especificação completa em `.auditoria/direcoes-visuais.md`. Resumo: fundo em degradê marrom, título de 48 a 96 px à esquerda, retrato da Gabriela em arco à direita (embaixo do texto no celular), invadindo a seção seguinte, com o monograma como brilho atrás. Somem as duas camadas de degradê escuro de hoje. O retrato usa `home.sobre.foto` com `fetchpriority="high"`, e o `head.php` ganha `<link rel="preload" as="image">` para ele. A imagem do monograma perde o `fetchpriority="high"` e ganha `loading="eager"` e `decoding="async"`.

**2. Boas-vindas**
- Sai a caixa bege arredondada.
- Fundo `--creme`. `padding`: 120 px em cima e 64 px embaixo no celular; 176 px e 112 px no desktop (o espaço de cima acomoda o retrato).
- Primeiro parágrafo no estilo "Frase de boas-vindas", cor `--cafe`. Segundo parágrafo em texto corrido, 24 px abaixo, largura máxima 52ch.
- Desktop: colunas 1 a 7, à esquerda. Celular: à esquerda.
- Antes da frase, uma linha vertical de 1 px por 48 px, cor `--marrom-claro`.
- A seção aguenta 1 ou mais parágrafos: o primeiro é sempre a frase, os outros são texto corrido.

**3. Sobre (`#sobre`)** — Direção A.
Especificação completa em `.auditoria/direcoes-visuais.md`. Resumo: sem foto. No desktop, título e números à esquerda (fixos enquanto rola) e texto à direita. Depois, faixa `--areia` de borda a borda com o vídeo e os parágrafos do vídeo. Some o `#sobre-photo-mobile`. O texto deixa de ser centralizado no celular.

**4. Procedimentos (`#procedimentos`)**
- **Deixa de ser carrossel e vira lista editorial numerada.** Fundo no degradê marrom, de borda a borda.
- Cabeçalho: rótulo e `h2` à esquerda, cor `--creme`.
- Lista: 1 coluna no celular; 2 colunas no desktop (`grid-template-columns: 1fr 1fr`, 96 px entre colunas).
- Cada item: linha de 1 px `rgba(227,201,168,.28)` no alto, `padding: 28px 0 32px` (desktop `36px 0 44px`). À esquerda, o numeral ("01", "02"...) por contador CSS (`counter()` com `decimal-leading-zero`), estilo Numeral, cor `--champanhe`, em coluna de 56 px (72 px no desktop). À direita, `h3` em `--creme` e, 8 px abaixo, o texto em `--areia`, 16 px / 1,7.
- Somem os cartões `#6B4A39`, as setas e o contador desta seção. Conferir no script e nos testes o que depende de `procedimentos-grid` e das setas e ajustar junto.
- Aguenta qualquer número de itens e textos de qualquer tamanho. Com número ímpar, a última linha fica só na coluna da esquerda.

**5. Resultados (`#resultados`)**
- Fundo `--creme`. Cabeçalho à esquerda. No desktop, o "Ver mais" vira `.btn-texto` nas colunas 10 a 12, alinhado pela base do título. Some a célula `#ver-mais-cell` com o botão de 34 px.
- Desktop: grade de 3 colunas, 24 px de calha na horizontal e 56 px na vertical. Com 5 itens, a segunda linha tem 2 e fica alinhada à esquerda.
- Celular: carrossel com cada item ocupando 82% da largura, 16 px de calha, e o botão "Ver mais" (`.btn-marrom`, largura toda) 40 px abaixo das setas.
- Comparador e legenda conforme a seção 5.

**6. Cursos (`#cursos`)**
- Fundo `--areia`. Cabeçalho à esquerda. A descrição da seção vai abaixo do `h2`, com largura máxima de 52ch.
- Cada curso é um "cartão de papel", com 24 px entre eles.
- Desktop, dentro do cartão: título (`h3`), carga horária e descrição nas colunas da esquerda (largura máxima 560 px). À direita, empilhados e alinhados à direita: `.btn-marrom` ("Chamar no WhatsApp") e, abaixo, `.btn-texto` ("Ver descrição completa") com seta para baixo que gira ao abrir.
- Carga horária: estilo Etiqueta (Mulish 700 de 12 px em maiúsculas, 0,14em), cor `--marrom`, **acima** do título, com 12 px de distância.
- Celular: tudo em coluna, botões com a largura toda.
- Área aberta: linha de 1 px `--linha` no alto, 2 colunas no desktop e 1 no celular. Subtítulos (`h4`) em Cormorant 500 de 22 px. Itens de lista com um traço de 12 px `--marrom-claro` no lugar da bolinha.

**7. Depoimentos (`#depoimentos`)**
- Fundo `--creme`. Cabeçalho à esquerda. Carrossel conforme a seção 5.
- Largura de cada depoimento: 84% no celular; um terço do contêiner no desktop (`calc((100% - 64px) / 3)`).
- Cada depoimento é um "item com linha" (linha `--linha` no alto, sem caixa, sem fundo), alinhado pelo alto.
- Ordem interna: aspas de abertura decorativas (Cormorant itálico de 72 px, cor `--champanhe`, `aria-hidden`, altura de linha 0,6), texto no estilo Citação em `--cafe`, estrelas com 14 px na cor `--marrom-claro`, e por fim o nome (Mulish 700 de 14 px, `--cafe`) e o procedimento (Mulish 14 px, `--texto`).
- Depoimentos longos crescem para baixo. Não cortar o texto nem igualar alturas.

**8. Contato (`#contato`)**
- **Passa a ser a faixa marrom de fechamento** (degradê marrom, de borda a borda), antes do rodapé escuro.
- Desktop: colunas 1 a 6 com rótulo, `h2` de 56 px em `--creme` e o botão `.btn-claro` 40 px abaixo. Colunas 8 a 12 com a lista de dados.
- Lista de dados (endereço, horário, Instagram): cada bloco é um "item com linha" (linha `rgba(227,201,168,.28)`). Título do bloco no estilo Etiqueta com 12 px, cor `--areia` (champanhe não passa em contraste nesse tamanho), com o ícone de linha em `--champanhe`. Conteúdo em Mulish 16 px / 1,6, cor `--creme`.
- "Abrir no Google Maps" e "Avaliação Online": `.btn-contorno-claro`, lado a lado no desktop e empilhados no celular.
- Celular: título, botão, depois a lista.

**9. Rodapé** — conforme a seção 5.

### Página de resultados

- **Cabeçalho:** sempre no estado sólido. "Voltar" vira `.btn-texto` claro com a seta para a esquerda, à direita no desktop e à esquerda no celular, com 44 px de área de toque.
- **Abertura:** fundo `--creme`, `padding` de 64 px 24 px 40 px no celular e 112 px 0 64 px no desktop. Rótulo e `h1` (estilo "`h1` de resultados") à esquerda.
- **Atalhos de categoria (novo):** logo abaixo do título, uma fileira de links com os nomes das categorias (vêm do JSON, não é texto novo). Cada link é uma pílula de 44 px de altura com borda de 1 px `--linha`, texto `--marrom` em Mulish 600 de 14 px, levando à âncora da categoria. No celular, a fileira rola na horizontal. Com uma categoria só, a fileira não aparece. Cada `<section>` de categoria ganha um `id` e `scroll-margin-top: 96px`.
- **Categoria:** `h2` no estilo `h3` ampliado (32 px no celular, 44 px no desktop), com o numeral da categoria ("01") em Cormorant itálico `--marrom-claro` antes do nome, por contador CSS, e a linha de 1 px `--linha` embaixo, com 20 px de respiro. 40 px entre o título e a grade.
- **Grade:** respeita `$grupo['colunas']`. Calha de 24 px no desktop. No celular, 1 coluna com largura máxima de 360 px, centralizada. Espaço entre categorias: 80 px no celular, 120 px no desktop.
- **Comparador, foto e vídeo:** conforme as seções 5 e 6. O fundo preto dos itens vira `--cafe-escuro`.
- **Convite final:** vira faixa marrom de borda a borda (o mesmo degradê), centralizada, `padding` de 80 px no celular e 120 px no desktop. Frase em Cormorant 500 de 30 a 44 px, cor `--creme`, largura máxima 20ch. Botão `.btn-claro` 32 px abaixo.
- **Rodapé:** igual ao da home.

---

## 9. Painel da dona

O painel continua simples, com botões grandes e pensado para o iPhone. Ele ganha o mesmo acabamento, sem mudar nenhum fluxo e nenhum texto.

- **Cores:** as mesmas variáveis da seção 2. Fundo `--creme`, cartões `--marfim`, linhas `--linha`. As cores de erro, sucesso e aviso continuam as de hoje.
- **Fonte:** texto, campos e botões continuam na fonte do sistema (carrega na hora e é a mais legível). Só `h1` e `h2` passam para Cormorant Garamond 500, carregada de `../assets/fonts/` com `font-display: swap`: `h1` de 32 px e `h2` de 26 px. O itálico não é carregado no painel.
- **Barra do alto:** fundo `--marrom` chapado, título em Cormorant 500 de 26 px cor `--creme`, botão "Sair" em pílula com borda de 1 px `--areia`.
- **Botões (`.botao`):** forma de pílula, `min-height: 52px`, borda de 1,5 px (hoje 2 px), fonte de 17 px peso 700 **em caixa normal** (sem maiúsculas no painel, para a leitura ficar fácil). Principal: fundo `--marrom`, texto `--creme`. Leve: borda `--linha`. Perigo e apagar: mantêm o vermelho.
- **Cartões da tela inicial:** fundo `--marfim`, borda de 1 px `--linha` (hoje é grossa), `border-radius: 8px`, `padding: 24px`, sem sombra. Título em Cormorant 500 de 26 px `--marrom`, descrição de 16 px `--texto`. Área de toque: o cartão inteiro.
- **Ícones dos cartões:** os emojis coloridos saem. Entram 6 ícones SVG de linha (traço de 1,5 px, cor `--marrom`), de 28 px, dentro de um círculo de 56 px com fundo `--areia`. SVG inline, sem arquivo novo.
- **Campos:** altura mínima de 52 px, fonte de 17 px (16 px ou mais evita o zoom do iPhone), borda de 1 px `#D9C2B4`, `border-radius: 8px`, fundo branco. Foco: borda de 2 px `--marrom`.
- **Abas e interruptores:** mantêm tamanho e comportamento. Cor ativa `--marrom`, inativa `--linha`.
- **Listas de fotos:** miniaturas com `border-radius: 4px`, linha de 1 px entre os itens no lugar de caixas.
- **Avisos:** mantêm as cores, com `border-radius: 8px` e borda esquerda de 3 px na cor do tipo.
- **Movimento:** só a transição de cor dos botões (150 ms). Nada de entrada animada.
- O JS do painel continua abaixo de 150 KB e o painel não carrega nada no site público.

---

## 10. O que evitar

- A estética Graffiti Core.
- Cantos de 20 px, sombras difusas e caixas bege centralizadas (a cara atual).
- Rótulos em maiúsculas minúsculas e espaçadas acima de todo título. O rótulo agora é itálico em serifa.
- Centralizar tudo. O padrão é à esquerda.
- Emojis como ícones da interface (os emojis que estão dentro dos textos da dona ficam).
- Cor nova fora da tabela. Em especial dourado brilhante, rosa e degradês coloridos.
- Texto creme sobre `--marrom-claro` (`#996C55`): não passa em contraste.
- Champanhe em texto menor que 28 px sobre `--marrom`.
- Filtro, zoom, sombra interna ou qualquer efeito sobre as fotos de antes e depois.
- Cormorant abaixo de 19 px ou em texto corrido longo.
- Terceira família de fonte, ou peso além do 500 na Cormorant.
- Animar a opacidade do `h1` ou do retrato do topo. Paralaxe, contadores animados e reprodução automática de vídeo.
- Biblioteca de JS, de ícones ou de animação. Fonte carregada de fora do `assets/fonts`.
- Texto novo, mesmo decorativo ("Desde 20XX", "Goianésia — GO"). Numerais gerados por CSS podem.
- Alvo de toque menor que 44 px.
- Estourar `.auditoria/orcamento.md`: LCP até 2,5 s, CLS até 0,1, HTML da home até 120 KB, JS até 30 KB.

---

## 11. Se a escolhida for a Direção B ("Monograma")

Tudo neste guia continua valendo, com estas trocas:

| Item | Direção A (este guia) | Direção B |
|---|---|---|
| Topo | Texto à esquerda e retrato em arco à direita, invadindo a seção de baixo | Só tipografia, centralizada, com o monograma como marca-d'água atrás do título. `h1` de 52 px no celular e 112 px no desktop. Linha vertical champanhe no pé |
| Alinhamento do topo | À esquerda | Centralizado (é a única exceção, além das já previstas) |
| Botão secundário do topo no celular | `.btn-texto` | `.btn-contorno-claro` com a largura toda |
| Monograma (`hero.imagem`) | Brilho atrás do retrato, opacidade 0,22 a 0,25 | Marca-d'água central, opacidade 0,25 a 0,30. Mantém `fetchpriority="high"` |
| LCP do topo | O retrato (`sobre.foto`), com preload de imagem | O `h1`. Não há preload de imagem novo |
| Boas-vindas | `padding-top` de 120 px / 176 px e texto nas colunas 1 a 7 | `padding-top` de 80 px / 144 px, texto centralizado nas colunas 3 a 10 |
| Sobre | Sem foto: título e números à esquerda, texto à direita | Retrato em arco nas colunas 1 a 5 (fixo ao rolar) e título, texto e números nas colunas 7 a 12 |
| Faixa do vídeo | Vídeo à esquerda, texto à direita (seta ←) | Texto à esquerda, vídeo à direita (seta →) |
| Foto do Sobre no painel | Passa a controlar a foto do topo | Continua controlando a foto do Sobre |

As medidas completas da Direção B estão em `.auditoria/direcoes-visuais.md`.
