# Direções visuais — topo (hero) e seção "Sobre"

Duas direções para virar prévia e o Vinicius escolher. As duas usam a mesma base do `GUIA-VISUAL.md` (paleta, tipografia, botões, linhas). Só mudam o topo e o "Sobre".

Nas duas: nenhum texto muda, nenhuma foto é editada, o marrom continua dominante, a logo não é alterada. Os nomes de token (`--marrom`, `--champanhe` etc.) e as classes (`.rotulo`, `.btn`) estão definidos no `GUIA-VISUAL.md`.

Medidas de referência: celular 390 px (margem lateral 24 px, área útil 342 px) e desktop 1440 px (contêiner 1240 px, 12 colunas de 83 px com calha de 24 px).

---

## Direção A — "Retrato" (recomendada)

**Ideia.** O rosto da Gabriela sobe para o topo. Hoje o topo mostra a logo gigante, que já está no cabeçalho logo acima: é a mesma informação duas vezes e nenhum rosto. Em estética, quem vende é a profissional. O topo vira uma capa de revista: título enorme em serifa de um lado, o retrato dela dentro de uma moldura em arco do outro, saindo do marrom e invadindo a seção clara de baixo. O "Sobre" deixa de ter foto e vira uma página de texto editorial, com o vídeo como peça visual.

**Atenção:** a foto usada no topo é o mesmo arquivo de `home.sobre.foto` (não é editada nem trocada, só muda de lugar). No painel, o campo da foto do "Sobre" passa a controlar a foto do topo. A imagem `home.hero.imagem` (o monograma GS) continua em uso, como um brilho discreto atrás do retrato.

### Topo — celular (390 px)

Ordem, de cima para baixo:

1. Cabeçalho transparente por cima do topo (72 px de altura, logo com 56 px).
2. Fundo: degradê marrom `linear-gradient(160deg, #7D5843 0%, #74513E 55%, #6B4A39 100%)`.
3. Bloco de texto, alinhado à esquerda, `padding: 104px 24px 0` (os 104 px incluem o cabeçalho).
   - `h1`: Cormorant Garamond 500, 48 px, entrelinha 1,02, espaçamento -0,015em, cor `#FBF3EE`, `text-wrap: balance`. Quebra em 2 linhas.
   - Texto de apoio: Mulish 400, 17 px / 1,6, cor `#F3E4DA`, largura máxima 32ch, 16 px abaixo do título.
   - Botão principal (`.btn .btn-claro`), largura total, 52 px de altura, 28 px abaixo do texto.
   - Botão secundário vira link de texto (`.btn-texto` claro), centralizado, com 44 px de altura, 8 px abaixo do principal.
4. Retrato, 40 px abaixo dos botões:
   - largura 264 px, proporção 3/4 (352 px de altura), centralizado;
   - forma de arco: `border-radius: 999px 999px 4px 4px`, `object-fit: cover`, `object-position: center top`;
   - contorno: pseudo-elemento com a mesma forma, borda de 1 px `#E3C9A8`, deslocado 10 px para cima e 10 px para a esquerda;
   - sombra `0 30px 60px -30px rgba(42,29,23,.45)`;
   - os últimos 56 px do retrato passam para a seção seguinte (`margin-bottom: -56px; position: relative; z-index: 2`).
5. Monograma (`hero.imagem`): atrás do retrato, centralizado nele, largura 150% da tela, opacidade 0,25, com `mask-image: radial-gradient(closest-side, #000 55%, transparent 100%)` para sumir com as bordas do retângulo.

No iPhone de 844 px de altura o retrato começa por volta dos 500 px, então o rosto já aparece sem rolar.

### Topo — desktop (1440 px)

- Cabeçalho transparente por cima (96 px, logo com 76 px).
- Altura do topo: `min-height: min(92vh, 860px)`, mínimo de 720 px. Mesmo degradê.
- Grade de 12 colunas:
  - **Texto nas colunas 1 a 7** (714 px), centralizado na vertical, com `padding-top: 96px`.
    - `h1`: 96 px, entrelinha 0,98, -0,015em, `text-wrap: balance` (2 linhas).
    - Texto de apoio: 19 px / 1,6, largura máxima 440 px, 24 px abaixo.
    - Botões lado a lado, 40 px abaixo, com 16 px entre eles: `.btn-claro` e `.btn-contorno-claro`.
  - **Retrato nas colunas 8 a 12** (área de 511 px), alinhado à direita da área: largura 460 px, proporção 3/4 (613 px), alinhado pelo pé, com 88 px passando para a seção seguinte. Contorno champanhe deslocado 14 px para cima e para a esquerda.
- Monograma: atrás do retrato, altura 120% do topo, centro no centro do retrato, opacidade 0,22, com a mesma máscara radial.
- Linha fina horizontal de 1 px `rgba(227,201,168,.35)` no pé do topo, das colunas 1 a 7, 48 px acima da borda.

### "Boas-vindas" logo abaixo (igual nas duas direções, muda só o respiro)

Sem caixa. Na direção A, `padding-top` de 120 px no celular e 176 px no desktop, para acomodar o retrato que invade. No desktop o texto ocupa as colunas 1 a 7, alinhado à esquerda, para não ficar embaixo do retrato.

### "Sobre" — celular (390 px)

Seção sem foto. Fundo `#FBF3EE`, `padding: 80px 24px`. Tudo alinhado à esquerda (hoje é centralizado).

1. Rótulo (`.rotulo`), depois `h2` com 36 px / 1,1, margem inferior de 24 px.
2. Parágrafos de `sobre.paragrafos`: o primeiro é o destaque (19 px / 1,6, cor `#3A2A22`), os demais 16 px / 1,7, cor `#5C4A40`.
3. Números (`sobre.numeros`): grade de 2 colunas (`repeat(auto-fit, minmax(140px, 1fr))`), calha de 24 px, 40 px acima. Cada item tem linha de 1 px `#E7CDBE` no alto, `padding-top: 16px`, valor em Cormorant 500 de 30 px cor `#7D5843` e legenda em Mulish 14 px cor `#5C4A40`.
4. Faixa do vídeo: fundo `#F3E4DA` de borda a borda, `padding: 64px 24px`.
   - Parágrafos de `sobre.video_paragrafos` (16 px / 1,7).
   - `sobre.saiba_mais` em Cormorant itálico 500 de 24 px, cor `#7D5843`, seguido de uma seta para baixo (↓).
   - Vídeo centralizado, largura 78% (máximo 300 px), proporção do próprio vídeo, raio 4 px, 32 px abaixo do texto.

### "Sobre" — desktop (1440 px)

- `padding: 144px 0 120px`. Grade de 12 colunas.
- **Colunas 1 a 5**: rótulo, `h2` de 56 px / 1,05, e 56 px abaixo os números empilhados em lista, cada um com linha fina no alto, valor de 36 px e legenda de 14 px. O bloco fica `position: sticky; top: 120px` enquanto o texto da direita rola.
- **Colunas 7 a 12**: parágrafos de `sobre.paragrafos`. O primeiro é o destaque (22 px / 1,55, `#3A2A22`), os demais 17 px / 1,75.
- **Faixa do vídeo**: fundo `#F3E4DA` de borda a borda, `padding: 112px 0`.
  - Vídeo nas colunas 2 a 5 (largura 340 px, `max-height: 600px`), com contorno champanhe deslocado 12 px.
  - Texto nas colunas 7 a 11, centralizado na vertical: `video_paragrafos` (17 px / 1,75) e `saiba_mais` em Cormorant itálico de 28 px com uma seta de linha para a esquerda (←), apontando para o vídeo.

---

## Direção B — "Monograma"

**Ideia.** A marca continua sendo a dona do topo, mas com cara de papelaria de luxo em vez de papel de parede. O topo é só tipografia, centralizado, com o monograma GS como marca-d'água suave atrás do título. O retrato fica no "Sobre", em arco, grande, ao lado do texto. É a direção mais sóbria e a que menos mexe na estrutura atual.

### Topo — celular (390 px)

- Cabeçalho transparente por cima (72 px).
- Mesmo degradê marrom. `min-height: 86svh` (mínimo 600 px). Conteúdo centralizado na horizontal e na vertical, `padding: 104px 24px 64px`.
- Monograma: centralizado, largura 130% da tela, opacidade 0,25, com máscara radial. O título fica em cima dele.
- `h1`: 52 px / 1,0, centralizado, `text-wrap: balance`.
- Texto de apoio: 17 px / 1,6, centralizado, largura máxima 30ch, 20 px abaixo.
- Botões empilhados, largura total, 52 px de altura cada, 12 px entre eles, 32 px abaixo do texto.
- Linha vertical de 1 px por 48 px, cor `#E3C9A8`, centralizada no pé do topo.

### Topo — desktop (1440 px)

- `min-height: min(88vh, 800px)`. Tudo centralizado nas colunas 3 a 10.
- Monograma: centro do topo, altura 110% do topo, opacidade 0,30, com máscara radial.
- `h1`: 112 px / 0,96, centralizado, largura máxima 12ch (2 linhas).
- Texto de apoio: 19 px / 1,6, largura máxima 46ch, 28 px abaixo.
- Botões lado a lado, centralizados, 40 px abaixo.
- Linha vertical de 1 px por 72 px no pé, centralizada, levando o olho para a seção seguinte.

### "Sobre" — celular (390 px)

1. Rótulo e `h2` (36 px), alinhados à esquerda.
2. Retrato em arco: largura 72% (280 px), proporção 3/4, centralizado, contorno champanhe deslocado 10 px, 32 px abaixo do título.
3. Parágrafos (o primeiro em destaque), depois os números em 2 colunas com linha no alto.
4. Faixa do vídeo igual à da direção A.

### "Sobre" — desktop (1440 px)

- `padding: 144px 0 120px`.
- **Colunas 1 a 5**: retrato em arco, 503 px de largura, proporção 3/4 (670 px), contorno champanhe deslocado 14 px para cima e para a esquerda. `position: sticky; top: 120px`.
- **Colunas 7 a 12**: rótulo, `h2` de 56 px, parágrafos (primeiro em destaque de 22 px) e os números em linha, com linha fina no alto, 56 px abaixo do texto.
- Faixa do vídeo igual à da direção A, mas com o vídeo nas colunas 8 a 11 e o texto nas colunas 2 a 6 (seta → apontando para o vídeo), para alternar com o retrato que está à esquerda.

---

## Recomendação: Direção A, "Retrato"

1. **Resolve a maior fraqueza de hoje.** O topo atual repete a logo que já está no cabeçalho. A direção B melhora o acabamento, mas mantém a repetição.
2. **Estética de alto padrão se vende com gente.** O rosto da profissional acima da dobra passa confiança em menos de um segundo, e a cliente chega do Instagram já esperando ver a Gabriela.
3. **É a que mais se afasta do "padrão".** O retrato em arco saindo do marrom para o creme dá profundidade e quebra o empilhamento de blocos retangulares.
4. **Cabe no orçamento.** A foto tem 35 KB. Com `fetchpriority="high"` e `<link rel="preload" as="image">` ela vira o LCP sem piorar os 2,49 s. A regra é não animar a opacidade dela nem do título (ver "Movimento" no guia).

**Riscos da A que a prévia precisa mostrar:**
- A foto tem 690 x 920 px. No desktop com tela retina ela fica um pouco suave em 460 px de largura. Se a dona tiver o original, vale subir de novo pelo painel (até 1600 px).
- O fundo da foto é cinza-azulado. Dentro do arco, sobre o marrom, isso precisa ser visto na prévia antes de decidir.
- Se o Vinicius considerar que mudar a foto de lugar fere a regra de "não mudar as fotos", a escolha é a B.

A direção B é a opção segura: não muda o papel de nenhum campo do painel e mantém a foto onde está.
