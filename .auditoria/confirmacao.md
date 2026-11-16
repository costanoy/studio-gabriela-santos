# Confirmação dos achados das auditorias

Como foi feito: cada item que descreve um defeito do site atual foi reproduzido com Playwright (projetos desktop, iphone-webkit e iphone-chromium), com medição de rede via CDP, ffprobe ou leitura direta do HTML. Os testes estão em `tests/bugs.spec.js` e cada nome cita o id do item. Rodada completa em `.auditoria/confirmacao-run.log`: 0 passaram, 112 falharam, 23 pularam (pulos são normais: teste só de um navegador ou só de celular/desktop). Todos falham hoje e devem passar depois da correção.

Vereditos: CONFIRMADO (reproduzido, tem teste que falha), DESCARTADO (não reproduz ou já está dentro do orçamento), A CONSTRUIR (plano do painel que ainda não existe, não é bug do site atual).

## Lógica (L)

| id | título | veredito | evidência |
|---|---|---|---|
| L01 (P0) | Poster da apresentação errado e caixa que muda de tamanho | CONFIRMADO | Poster `sobre-foto.webp` x primeiro frame do vídeo: diferença 44 a 46 de 255 (limite do teste: 5). Caixa com `aspect-ratio: 2/3` enquanto o vídeo é 576x1024; com JS desligado a caixa muda de tamanho. Os posters dos outros 5 vídeos conferem (1,6 a 3,3 de 255), então só este é bug. |
| L02 (P0) | Vídeos baixam antes do play | CONFIRMADO | `preload="metadata"` nos vídeos. Em resultados.html entram 4,7 MB de mp4 sem ninguém dar play (6,29 MB na rodada final) e 6.772 KB no total. Nuance: o atributo `metadata` é permitido pelo orçamento; a violação real é o peso. |
| L03 (P1) | Camada transparente sobre os vídeos bloqueia controles | CONFIRMADO | No canto do vídeo quem recebe o toque é a camada (`onclick=toggleVideo`) ou o cabeçalho, não o vídeo. Dois vídeos tocam ao mesmo tempo (desktop, chromium e webkit). |
| L04 (P1) | Comparador antes/depois: só a barrinha funciona, sem nome acessível | CONFIRMADO | Arrastar sobre a foto (mouse e toque) deixa o valor em 50. Barra com 16 a 20 px de altura. Com valor 100 a etiqueta "Depois" continua visível. 5 sliders sem nome acessível por página. |
| L05 (P1) | Setas do carrossel pulam para o card errado (iOS até 26.1) | CONFIRMADO | Sem o evento `scrollend` (simulado), depois de deslizar até o card 3 a seta "próximo" vai para o 1, nos 3 carrosséis, em chromium e webkit. O índice só é atualizado no `scrollend`. |
| L06 (P2) | Estados soltos: menu, cursos, reveal e logo | CONFIRMADO | Menu sem `aria-expanded`; Esc não fecha; menu aberto continua a 1200 px; 2 botões de curso sem `aria-expanded`; 5 `<button>` sem `type` na index; logos do cabeçalho duplicados com o mesmo alt. (Em resultados.html os botões já têm `type`: essa parte foi DESCARTADA.) |
| L07 (P0 painel) | Conteúdo duplicado entre index e resultados | CONFIRMADO (parte do site) | Horário escrito de dois jeitos na home ("das 8h às 17h" e "8h às 17h"); 7 links `wa.me` repetidos na index. Categoria Corrente com 2 vídeos: centro da grade 720 px x centro dos itens 534 px (fica à esquerda). A parte "fonte única no JSON" é A CONSTRUIR. |
| L08 (P0) | Migração exata do enquadramento em % | A CONSTRUIR | Plano do painel. Fato conferido: `drenagem-depois1` usa `width:101.015%; height:102.814%; left:50.508%; top:48.554%`, então os valores existem e precisam migrar sem arredondar. |
| L09 (P1) | Editor de zoom e posição no iPhone | A CONSTRUIR | Painel ainda não existe. |
| L10 (P0) | Upload de vídeo do iPhone (HEVC, MOV, rotação, 4K) | A CONSTRUIR | Painel ainda não existe. Fato conferido: `corrente-1.mp4` traz rotação -90 no ffprobe, então o tratamento de rotação é necessário. |
| L11 (P1) | Extração do primeiro frame no navegador | A CONSTRUIR | Painel ainda não existe. |
| L12 (P1) | Fotos do iPhone: HEIC, canvas, orientação | A CONSTRUIR | Painel ainda não existe. |
| L13 (P0) | Desfazer, histórico de 20 versões e lixeira | A CONSTRUIR | Painel ainda não existe. |
| L14 (P1) | Duas telas ao mesmo tempo, sessão que expira | A CONSTRUIR | Painel ainda não existe. |
| L15 (P0) | Renderização em PHP, links, JSON-LD, esquema do JSON | A CONSTRUIR | Painel ainda não existe. |

## UI/UX (S e P)

| id | título | veredito | evidência |
|---|---|---|---|
| S1 (P0) | Acessibilidade 85 a 87 no Lighthouse | CONFIRMADO | 5 sliders sem nome por página; 0 elementos `<main>`; contraste do botão `#desktop-cta` em 4,14 (mínimo 4,5). |
| S2 (P1) | Contrastes reprovados fora do Lighthouse | CONFIRMADO | 39 textos abaixo de 4,5 na home (3,0 a 4,14; o "Depois" tem 3,48), medidos com `.reveal` forçado visível. O hero foi excluído do teste (fundo de imagem, falso positivo). |
| S3 (P1) | Comparador difícil de usar com o dedo | CONFIRMADO | Mesmos testes de L04: barra de 16 a 20 px (alvo mínimo 44) e arrasto na foto sem efeito. O "pegador" visual e o contador são A CONSTRUIR. |
| S4 (P1) | Alvos de toque menores que 44 px | CONFIRMADO | Hambúrguer 40x40; "Voltar" 42x19; 5 links do rodapé com altura menor que 44. |
| S5 (P1) | Cabeçalho alto cobre títulos | CONFIRMADO | Ao ir para uma âncora, o texto da seção Sobre fica em -42 px, atrás do cabeçalho de 87 px (celular). |
| S6 (P2) | Setas dos carrosséis sobre o texto | CONFIRMADO | As setas de Procedimentos cobrem texto (seletor `.mobile-scroll-arrow, [id^="procedimentos-arrow"]`). O indicador de posição é A CONSTRUIR. |
| S7 (P2) | Teclado e leitor de tela | CONFIRMADO | Links do cabeçalho com `outline: none` sem alternativa; sem `aria-expanded` no menu e nos cursos. |
| S8 (P2) | Animação sem respeitar "Reduzir movimento" | CONFIRMADO | `.reveal` com opacidade 0 com JS desligado (conteúdo some). Com reduced-motion a transição continua em 0,8 s e o scroll continua `smooth`. |
| S9 (P2) | Página de resultados sem chamada para agendar | A CONSTRUIR | Sugestão de produto, não é defeito reproduzível. |
| P1 (P0) | Adicionar, trocar e apagar fotos e vídeos no iPhone | A CONSTRUIR | Painel ainda não existe. |
| P2 (P0) | Editor de enquadramento do antes/depois | A CONSTRUIR | Painel ainda não existe. |
| P3 (P1) | Apagar, confirmar, desfazer | A CONSTRUIR | Painel ainda não existe. |
| P4 (P1) | Mensagens de erro em português claro | A CONSTRUIR | Painel ainda não existe. |
| P5 (P1) | Interface do painel para iPhone | A CONSTRUIR | Painel ainda não existe. |
| P6 (P2) | Edição de textos sem quebrar o layout | A CONSTRUIR | Painel ainda não existe. |

## Técnica (T)

| id | título | veredito | evidência |
|---|---|---|---|
| T01 (P0) | Resultados estoura o orçamento (fotos grandes, foto principal com lazy) | CONFIRMADO | `drenagem-antes1` 260.026 B e `drenagem-depois1` 257.944 B (limite 250 KB). Fotos dos comparadores com 1179 a 1600 px de largura (limite do teste 1000). Fotos do 1º comparador com `loading="lazy"`. Peso da primeira carga 6.772 KB contra 1,5 MB. |
| T02 (P0) | Vídeos baixam cerca de 1 MB cada sem play | CONFIRMADO | Mesmo teste de L02: 4,7 a 6,3 MB de mp4 sem play em resultados.html. |
| T03 (P0) | Home: logo de 141 KB e hero de 1920 px seguram o LCP | CONFIRMADO (peso) | `logo-gs.png` 141.289 B, com 1920 px de largura exibido a 192 px; `hero-bg.png` 90.563 B. O LCP de 3,5 s vem da medição do Lighthouse, não foi refeito no teste. |
| T04 (P2) | Arquivos não usados vão no deploy | CONFIRMADO | Sem uso: `assets/hero-bg.webp`, `assets/logo.png`, `assets/results/corrente-3.mp4`, `liposhape-antes3.jpeg`, `liposhape-depois2.mp4`. |
| T05 (P1) | Sem `.htaccess` (cache, URL única, cabeçalhos) | A CONSTRUIR | Depende do deploy/painel; não é bug do código atual. |
| T06 (P1) | SEO ao migrar para `index.php` e `resultados.php` | A CONSTRUIR | Risco da migração. |
| T07 (P0) | Arquivos de desenvolvimento públicos no deploy pelo git | A CONSTRUIR | Risco do deploy, ainda não existe. |
| T08 (P0) | Deploy pelo git pode apagar o que a dona fez | A CONSTRUIR | Risco do deploy. |
| T09 (P0) | Login e sessão do painel | A CONSTRUIR | Painel ainda não existe. |
| T10 (P0) | Limite de tentativas por IP atrás da CDN | A CONSTRUIR | Painel ainda não existe. |
| T11 (P1) | CSRF e XSS vindos do JSON | A CONSTRUIR | Painel ainda não existe. |
| T12 (P0) | Upload de fotos: memória do GD, HEIC, EXIF e GPS | A CONSTRUIR | Painel ainda não existe. |
| T13 (P0) | Vídeos do iPhone carregam a localização | A CONSTRUIR | Painel ainda não existe. |
| T14 (P1) | Proteger `conteudo/` contra execução e download | A CONSTRUIR | Painel ainda não existe. |
| T15 (P1) | Limites de envio de vídeo pelo celular | A CONSTRUIR | Painel ainda não existe. |

## Descartes e nuances

- TBT de 10 s do Lighthouse: não se repetiu (ruído, como já diz a auditoria técnica). Não virou teste.
- `<button>` sem `type` em resultados.html: DESCARTADO (já tem `type`); o teste vale só para a index.
- `preload="metadata"` isolado não viola o orçamento; o teste de `preload="none"` existe para alinhar com a correção proposta, e a violação medida é o peso.
- Posters dos outros 5 vídeos: DESCARTADO (conferem com o frame 0).
- Nenhum item de bug foi totalmente descartado; 19 itens CONFIRMADOS e 26 A CONSTRUIR (painel, deploy e sugestões), de 45.
