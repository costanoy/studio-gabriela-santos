# Funcoes do site: resultado dos testes (Agente Testador)

Ferramenta: Playwright 1.65 (`tests/site.spec.js`, `playwright.config.js`, `package.json` na raiz). Servidor: http://localhost:8765.
Projetos: `desktop` (Chrome instalado, 1440x900), `iphone-webkit` (WebKit do Playwright, perfil iPhone 13), `iphone-chromium` (Chrome com emulacao iPhone 13).
Rodada completa: 83 passaram, 10 ignorados (teste que so vale em desktop ou so em celular), 3 falharam. As 3 falhas eram erro do proprio teste (rolagem suave do site deixava a posicao desatualizada, e o primeiro card do carrossel no celular comeca em 35 px por causa do padding). Corrigi os testes e repeti: 8 passaram, 4 ignorados, 0 falhas. Logs em `.auditoria/playwright-run.log` e `playwright-run2.log`.

Nenhum arquivo do site foi alterado.

## Index

| Funcao / fluxo | Veredito | Motivo |
|---|---|---|
| Carregamento sem erro de JS, titulo, h1 | FUNCIONA | 3 projetos, nenhum pageerror |
| Navegacao por ancoras (6 secoes) | FUNCIONA | Desktop pelo menu; celular pelo menu hamburguer. Secao chega ao topo e o hash muda |
| Link "Inicio" (goHome / goHomeMobile) | FUNCIONA | Volta a scrollY 0 e troca hash para #home |
| Menu mobile abre/fecha | FUNCIONA | Abre, fecha pelo botao e fecha ao tocar num link |
| Menu desktop / hamburguer escondido | FUNCIONA | |
| Logo troca no scroll | FUNCIONA | Depois de 80 px, `.scrolled` aplicado e logo-gs com opacidade 1 e logo-full 0. Volta ao topo, volta ao normal |
| Comparadores antes/depois, mouse | FUNCIONA | 5 de 5 na index (desktop e iphone-chromium): arrastar muda clip-path e posicao da linha |
| Comparadores antes/depois, toque | FUNCIONA | Toque (tap) funciona nos 3 projetos. Arrastar com dedo funciona no Chromium (valor foi de 17 para 82). Arrastar com dedo no WebKit: NAO TESTAVEL, pois o Playwright nao simula arraste por toque em WebKit |
| "Ver mais" para resultados.html | FUNCIONA | Celular usa #ver-mais-mobile, desktop usa #ver-mais-cell |
| Links externos (WhatsApp, Instagram, Forms, Maps) | FUNCIONA | Todos target=_blank e rel contendo noopener; destinos corretos. Nos testes de rede, os 4 responderam 200 |
| Carrosseis procedimentos e depoimentos (setas) | FUNCIONA | 7 e 4 cards; seta direita avanca e esquerda volta ao primeiro card |
| Carrossel de resultados (setas no celular) | FUNCIONA | |
| Arrastar carrossel de procedimentos com mouse | FUNCIONA | Desktop |
| Cursos "Ver descricao completa" (toggleCurso) | FUNCIONA | Os 2 abrem/fecham e o texto do botao troca |
| Animacao reveal (IntersectionObserver) | FUNCIONA | Todas as secoes .reveal ficam com .visible e opacidade 1 |
| Mapa embed | FUNCIONA | iframe com src correto, lazy, altura boa, e o frame do Google Maps carregou. Depende de rede externa |
| Botao Google Maps | FUNCIONA | |
| Contato e rodape | FUNCIONA | WhatsApp, Instagram, Maps e links de ancora presentes |
| Imagens sem quebra | FUNCIONA | Nenhuma com naturalWidth 0 |
| Sem rolagem horizontal | FUNCIONA | |
| Hover/estados visuais (btn-fill, nav-link) | NAO TESTAVEL | Sem asserção visual automatizada; so conferido por existencia |

## Video da home (apresentacao.mp4, 14,2 MB)

| Teste | Veredito | Motivo |
|---|---|---|
| Tem poster e o poster carrega | FUNCIONA | assets/sobre-foto.webp |
| Sem tela preta antes do play (captura do elemento) | FUNCIONA | Luminancia media 112 a 128 nos 3 projetos, inclusive WebKit |
| Toca ao clicar (toggleApresentacaoVideo) e pausa no 2o clique | FUNCIONA | Os 3 projetos |
| Nao baixa o video inteiro antes do play | FUNCIONA, COM RESSALVA | Chromium recebe 1,00 MB (e o buffer sobe a 7 s de 84 s) mesmo com preload="metadata". Nao baixa os 14 MB. O Hostinger responde 206 a pedidos de faixa (testado com cabecalho Range, sem baixar o arquivo). Em WebKit, o byte-count nao e mensuravel pelo teste (buffered 0, nenhuma requisicao do mp4 visivel) |
| Poster e o primeiro frame do video | QUEBRADO (parcial) | O poster e uma foto diferente (sobre-foto.webp), nao o primeiro frame. Nao e tela preta, mas ao dar play a imagem muda de repente |

## resultados.html

| Funcao / fluxo | Veredito | Motivo |
|---|---|---|
| Carrega, logo troca, botao Voltar | FUNCIONA | |
| 5 comparadores (mouse em desktop, toque em iPhone) | FUNCIONA | |
| Links externos e rodape para index.html#... | FUNCIONA | |
| toggleVideo (overlay) tocar | FUNCIONA | Os 5 videos tocam nos 3 projetos (readyState 4, sem erro de midia) |
| Poster existe e carrega nos 5 videos | FUNCIONA | assets/img/*.jpg |
| Sem tela preta antes do play, nos 5 videos | FUNCIONA | Luminancia media de 65 a 140 em Chrome, iPhone emulado e WebKit |
| Videos nao baixados por inteiro antes do play | FUNCIONA, COM RESSALVA | Chromium baixa 1,00 MB de cada um dos 5 videos (cerca de 5 MB) so ao abrir a pagina, por causa de preload="metadata". Nao e o arquivo inteiro, mas pesa no celular com dados moveis. preload="none" com poster evitaria isso |

## Tela preta no iPhone real

NAO TESTAVEL com certeza. O WebKit do Playwright (Windows) mostrou o poster em todos os 6 videos, sem tela preta, mas nao e o Safari do iOS. O iOS so decide mostrar o quadro preto de acordo com o poster e o preload no aparelho real. O que da para dizer: todos os 6 videos tem poster valido, playsinline, preload="metadata" e fundo #000 no CSS; se o poster falhar em carregar, o fundo preto aparece. Recomenda-se uma conferencia final num iPhone de verdade antes de publicar.

## Observacoes

- O servidor local (Python http.server) nao suporta Range e devolve 200 completo; o Chromium cortou a leitura em 1 MB. O comportamento real no Hostinger e com 206.
- Os testes de rede (links, mapa) dependem da internet.
- Para rodar de novo: `npx playwright test` na raiz (servidor em 8765 ligado). `node_modules` esta no .gitignore.
