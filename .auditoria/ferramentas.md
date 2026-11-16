# Auditoria: ferramentas e inventário

Escopo: `index.html` e `resultados.html` servidos em http://localhost:8765. Nenhum arquivo do site foi alterado. Scripts temporários ficaram fora do projeto.

## 1. Chrome headless (puppeteer-core + Chrome instalado)

Viewports: 390x844 (mobile, com touch) e 1440x900 (desktop). Cada página foi carregada, rolada até o fim e voltada ao topo.

| Página | Viewport | Console error/warn | Erro JS | Requisição falhou | HTTP >= 400 | Imagem quebrada | Overflow horizontal |
|---|---|---|---|---|---|---|---|
| index.html | 390 | 0 | 0 | 0 | 0 | 0 | não (390/390) |
| index.html | 1440 | 0 | 0 | 0 | 0 | 0 | não (1440/1440) |
| resultados.html | 390 | 0 | 0 | 0 | 0 | 0 | não (390/390) |
| resultados.html | 1440 | 0 | 0 | 5 (ver abaixo) | 0 | 0 | não (1440/1440) |

Observações:
- Em `resultados.html` no desktop, cinco vídeos aparecem como `net::ERR_ABORTED`: `liposhape-depois3.mp4`, `liposhape-pos.mp4`, `ultrassom-depois1.mp4`, `corrente-1.mp4` e `corrente-2.mp4`. Provavelmente é o navegador cancelando o pré-carregamento de metadados (`preload="metadata"`) quando a página é fechada. Não é erro de caminho: os arquivos existem e todos ficaram com `readyState=4` e sem erro de mídia. Vale conferir em um navegador real.
- Tempo de `load` do index no mobile foi de 10,8 s na primeira carga, contra 0,6 s no desktop logo depois. A diferença provavelmente vem do cache frio e do `apresentacao.mp4` de 14 MB, que tem `preload="metadata"`. Não é medição de Lighthouse; só serve como alerta.
- Todos os `<video>` têm `poster` definido (`sobre-foto.webp` no index e fotos em `assets/img/` nos resultados).

## 2. Validação HTML (html-validate 8)

- `index.html`: 279 erros (0 avisos)
  - 272 x `no-inline-style` (atributos `style=""`, 271 no total no arquivo)
  - 2 x `unique-landmark` (linhas 308 e 326: landmarks sem `aria-label` único)
  - 5 x `no-implicit-button-type` (linhas 319, 528, 531, 558 e 596: `<button>` sem `type`)
- `resultados.html`: 121 erros (0 avisos)
  - 121 x `no-inline-style`

Os erros de estilo inline são o grosso, mas são de estilo e não afetam o funcionamento. Os de acessibilidade (landmarks e `type` nos botões) são os que realmente importam.

## 3. Inventário

### 3.1 Arquivos grandes

| Tamanho | Arquivo |
|---|---|
| 14,2 MB | assets/apresentacao.mp4 (usado no index, na seção de apresentação) |
| 2,5 MB | assets/results/corrente-3.mp4 (NÃO USADO) |
| 2,4 MB | assets/results/corrente-2.mp4 |
| 2,2 MB | assets/results/liposhape-depois3.mp4 |
| 2,1 MB | assets/results/corrente-1.mp4 |
| 1,9 MB | assets/results/liposhape-pos.mp4 |
| 1,8 MB | assets/results/liposhape-depois2.mp4 (NÃO USADO) |
| 1,5 MB | assets/results/ultrassom-depois1.mp4 |
| 260 KB | assets/results/drenagem-antes1.jpeg |
| 258 KB | assets/results/drenagem-depois1.jpeg |
| 200 KB | assets/results/liposhape-depois1.jpeg |
| 74 KB | index.html |
| 33 KB | resultados.html |

Pelo `.auditoria/orcamento.md`, fotos devem ficar em até 250 KB: só `drenagem-antes1.jpeg` (260 KB) passa do limite. Vídeos devem usar `preload="none"` ou `"metadata"` e ter poster; os de 1,5 MB a 14 MB estão acima do orçamento de peso, mas o download só acontece no play ou no pré-carregamento de metadados.

### 3.2 Assets sem uso

Não referenciados em nenhuma das duas páginas:
- assets/hero-bg.webp (2,5 KB)
- assets/logo.png (4,8 KB)
- assets/results/corrente-3.mp4 (2,5 MB)
- assets/results/liposhape-antes3.jpeg (120 KB)
- assets/results/liposhape-depois2.mp4 (1,8 MB)

Os dois vídeos não usados somam cerca de 4,3 MB. Remover só depois de confirmar com a cliente que não são necessários. Todos os arquivos de fonte (woff2) são usados.

### 3.3 Código duplicado entre index.html e resultados.html

- Bloco `@font-face` (subconjunto latin/latin-ext das fontes): idêntico nas duas páginas (index linhas 31-229; resultados linhas 28-226).
- Bloco de CSS principal: quase igual, com 73 linhas de diferença (index 230-298; resultados 227-248).
- Bloco de script: diferente, com 94 linhas de diferença (index 793-880; resultados 446-468).
- Há cerca de 48 linhas longas (mais de 60 caracteres) iguais nas duas páginas, principalmente cabeçalho, menu e rodapé.

Como são páginas HTML estáticas sem build, a duplicação é esperada. Se a edição for feita pela cliente, vale centralizar o `@font-face` e o CSS base em um único arquivo.

### 3.4 TODO / FIXME

Nenhum TODO ou FIXME real. Os poucos resultados de busca são falsos positivos: "Método" contém "todo".

## Próximos passos sugeridos
1. Trocar `<button>` sem `type` por `type="button"` e dar nome aos dois landmarks (linhas 308 e 326 do index).
2. Confirmar com a cliente, antes de apagar, os três vídeos e as duas imagens não usados.
3. Revisar o `preload` do vídeo de 14 MB da apresentação e conferir o `poster` no iPhone.
