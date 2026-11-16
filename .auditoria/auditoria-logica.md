# Auditoria de lógica e fluxos

Agente: Lógica e Fluxos. Fase somente leitura: nenhum arquivo do site foi alterado.
Base: `index.html` (script nas linhas 793 a 880), `resultados.html` (script nas linhas 446 a 468), `.auditoria/*.md`, prints em `.auditoria/prints/antes/`, ffprobe dos vídeos e fotos, e uma medição com Playwright (Chrome, 390x844).
Gravidade: **P0** = quebra uma regra do CLAUDE.md ou do orçamento, ou bloqueia o painel. **P1** = atrapalha o uso de verdade (iPhone principalmente) ou cria risco alto no painel. **P2** = detalhe.

---

## L01. Vídeo de apresentação: poster errado e caixa que muda de tamanho (P0)

- **Evidência:** `index.html:391`: `poster="assets/sobre-foto.webp"` (690x920, 3:4), mas o vídeo é 576x1024 (9:16) e o primeiro frame é outro (`.auditoria/frame0-apresentacao.png`, diferença média de 42,9/255 em `performance-antes.md`). O CSS começa com `aspect-ratio: 2/3`, e o JS (`index.html:833-838`) só corrige a proporção no `loadedmetadata`. Medição: **sem metadados, a caixa fica com 320x480; com metadados, 270x480** (largura `576 / 1024`). O poster é a mesma foto que já aparece logo acima, na seção Sobre (print `celular-index-video1-antes-play.png`).
- **Como reproduzir:** abra o index em 390 px, bloqueie `*.mp4` no DevTools (ou use `preload="none"`) e role até o vídeo: a caixa fica em 2:3. Dê play: a imagem troca da foto para o vídeo e a caixa estreita 50 px.
- **O que mudar:** gerar o poster com o frame 0 (`ffmpeg -ss 0 -i apresentacao.mp4 -frames:v 1`, ≤1600 px, JPEG ou WebP) e fixar `aspect-ratio: 576/1024` direto no HTML. No painel, gravar `largura`/`altura` do vídeo no JSON e o PHP escreve o `aspect-ratio`. Remover o IIFE do `loadedmetadata` porque ele deixa de ser necessário (e com `preload="none"`, do L02, ele nem dispara antes do play).
- **Critério de pronto:** o poster é o frame 0 (diferença média < 5/255 em escala de cinza 32x32, mesmo teste do Medidor). A caixa tem o mesmo tamanho antes e depois do play, com `.mp4` bloqueado ou não. Conferido num iPhone real.

## L02. Vídeos baixam antes do play (P0)

- **Evidência:** todos os 6 `<video>` usam `preload="metadata"` (`index.html:391`; `resultados.html:284, 289, 370, 383, 388`). A página de resultados transfere **4.834 KB** sem nenhum play (`performance-antes.md`), contra o limite de 1,5 MB. O Chromium baixa cerca de 1 MB de cada vídeo (`funcoes.md`).
- **Como reproduzir:** abra `resultados.html` no DevTools, aba Network, filtro "media", sem dar play: aparecem 5 requisições de cerca de 0,5 a 1 MB cada.
- **O que mudar:** `preload="none"` em todos, mantendo o poster (frame 0) e o `playsinline`. Isso depende do L01 (proporção fixa no HTML, sem precisar do `loadedmetadata`). No painel, o PHP sempre escreve `preload="none"`.
- **Critério de pronto:** com Lighthouse no celular, resultados ≤ 1,5 MB e nenhum `.mp4` na rede antes do play. Ao dar play, o vídeo toca nos 3 projetos do Playwright.

## L03. A camada transparente sobre os vídeos bloqueia os controles do iPhone (P1)

- **Evidência:** `index.html:393`: uma `div onclick` cobre o vídeo inteiro menos os 44 px de baixo. Em `resultados.html:286, 291, 372, 385, 390`, a mesma camada cobre tudo menos os 54 px de baixo. No Safari do iPhone, os controles nativos de **tela cheia e PiP ficam no canto superior esquerdo**, e o volume fica no canto superior direito. Com a camada por cima, não dá para tocar neles. A `apresentacao.mp4` tem áudio (AAC), então o botão de volume faz falta. A camada é uma `div`, não um botão: sem teclado e sem nome acessível. `v.play()` não trata a promessa (erro sem tratamento se o vídeo não puder tocar). Em `resultados.html:452-456` há uma variável `video` que não é usada. Vários vídeos podem tocar ao mesmo tempo.
- **Como reproduzir:** num iPhone, toque no vídeo da home e tente usar o botão de tela cheia no alto: o toque cai na camada e pausa ou toca o vídeo.
- **O que mudar:** remover a camada e deixar só os controles nativos (que já tocam e pausam). Se quiserem manter o toque para tocar, usar um `<button type="button" aria-label="Tocar vídeo">` central que some no evento `play`. Adicionar `.catch(()=>{})` ao `play()`. Ouvir o evento `play` de qualquer vídeo e pausar os outros. Apagar a variável morta.
- **Critério de pronto:** no iPhone real, tela cheia, PiP e volume funcionam. Com o teclado (Tab + Enter/Espaço), o vídeo toca. Ao dar play num vídeo, os outros pausam. Sem erro no console.

## L04. Comparador antes/depois: só a barrinha funciona, sem nome acessível (P1)

- **Evidência:** `baSlide` (`index.html:845-850`) só responde ao `<input type="range">` (`index.html:457, 471, 485, 499, 513`; `resultados.html:280, 310, 322, 342, 354`). Arrastar a foto não faz nada. No celular, o comparador fica dentro do carrossel `#resultados-grid` (`overflow-x:auto`), então arrastar a foto rola o carrossel. O Safari do iOS não pula para o ponto tocado na trilha do range (é preciso pegar a bolinha). O teste de toque em `funcoes.md` foi feito no WebKit desktop, não no iOS. O Lighthouse marca `label` em 5 inputs em cada página (acessibilidade 85-87). Com o valor 100 (tudo "antes"), a etiqueta "Depois" (`z-index:1`) continua aparecendo sobre a foto "antes". O `.ba-line` usa `left: pct%` sem `translateX(-50%)`, então fica 1 px fora do centro.
- **Como reproduzir:** no iPhone, na home, tente arrastar a linha branca sobre a foto: o carrossel anda e o comparador não muda. Toque no meio da trilha: a bolinha não pula (iOS). Leve o slider até 100: "Depois" continua visível.
- **O que mudar:** usar Pointer Events na moldura inteira (`pointerdown/move/up` + `setPointerCapture`). Para o gesto não brigar com o carrossel, aplicar `touch-action: pan-y` na moldura e só assumir o arraste quando o movimento for mais horizontal que vertical. Manter o range (escondido visualmente ou não) para teclado e leitor de tela, com `aria-label="Comparar antes e depois: <procedimento>"`. Esconder cada etiqueta quando o seu lado ficar com menos de ~15%. Uma única função para as duas páginas, sem `closest('div')`: usar `closest('.ba')` com uma classe fixa, porque o PHP vai gerar o markup.
- **Critério de pronto:** no iPhone real, arrastar sobre a foto move o corte, e o swipe vertical continua rolando a página. Lighthouse sem a falha `label`. Teste Playwright arrastando pela moldura (Chromium com toque) passa. Nos valores 0 e 100, a etiqueta do lado escondido não aparece.

## L05. Setas do carrossel pulam para o card errado no iPhone (P1)

- **Evidência:** `scrollByCard` (`index.html:810-819`) usa um índice guardado em `__carouselIdx`, que só é atualizado no evento `scrollend` (`index.html:820-830`). O `scrollend` só existe no Safari a partir do 26.2 (dez/2025). Em iPhones com iOS 26.1 ou anterior, o índice nunca é atualizado depois de um swipe. Além disso, o alvo usa `card.offsetLeft` (borda esquerda), mas `#procedimentos-grid` e `#depoimentos-grid` usam `scroll-snap-align: center` no celular (`index.html:270-276`): o `scrollTo` vai para um ponto e o snap puxa para outro (o Testador viu o primeiro card começar em 35 px).
- **Como reproduzir:** num iPhone com iOS ≤ 26.1, em Procedimentos, deslize com o dedo até o 4º card e toque na seta direita: o carrossel volta para o 2º. No Chrome, dá para simular removendo o listener `scrollend` no console.
- **O que mudar:** calcular o card atual na hora do clique, a partir do `scrollLeft` e do centro de cada card (sem estado guardado), e rolar para `card.offsetLeft - (grid.clientWidth - card.offsetWidth)/2` quando o snap for central. Opcional: deixar as setas meio apagadas e com `aria-disabled` no primeiro e no último card.
- **Critério de pronto:** depois de deslizar para qualquer card, a seta sempre avança exatamente 1. Teste Playwright no `iphone-webkit` com `scrollend` desligado passa nos 3 carrosséis.

## L06. Estados soltos: menu, cursos, reveal e logo (P2)

- **Evidência:**
  - O menu mobile abre e fecha por `style.display` inline em 10 lugares (`index.html:303, 319, 326-334`). Não tem `aria-expanded` e o `aria-label="Abrir menu"` nunca muda. Não fecha com Esc nem com toque fora do cabeçalho. Se o menu estiver aberto e a tela passar de 1100 px (girar um iPad), ele continua aberto no desktop, porque nada esconde o `#mobile-nav-wrap` nessa largura.
  - `toggleCurso` (`index.html:839-844`) não tem `aria-expanded`.
  - 5 `<button>` sem `type` (`ferramentas.md`).
  - `.reveal` começa com `opacity:0` (`index.html:296`): sem JS, ou se o script quebrar, seções inteiras ficam invisíveis. Também não respeita `prefers-reduced-motion`.
  - Os dois logos (`index.html:305-306`, `resultados.html:256-257`) têm o mesmo `alt`. O leitor de tela lê o nome duas vezes, e o `logo-gs.png` (138 KB, 1920x1080) é baixado mesmo sem aparecer.
- **Como reproduzir:** abra o menu em 390 px e aumente a janela para 1200 px: o menu mobile fica aberto. Desligue o JS: Sobre, Procedimentos e Resultados ficam em branco.
- **O que mudar:** um único `toggleMenu(aberto)` que muda uma classe e o `aria-expanded`, e que fecha com Esc, com toque fora e com `matchMedia('(min-width:1101px)')`. `aria-expanded` nos cursos. `type="button"` nos 5 botões. Aplicar o `.reveal` só com JS ativo (por exemplo, a classe `js` no `<html>`) e desligar com `@media (prefers-reduced-motion: reduce)`. `alt=""` + `aria-hidden="true"` no logo que fica escondido, e uma versão menor do `logo-gs`.
- **Critério de pronto:** os cenários acima passam nos testes do Playwright. Sem JS, todo o conteúdo fica visível. html-validate sem `no-implicit-button-type`.

## L07. Conteúdo duplicado entre index e resultados (e dentro do index) precisa virar uma fonte só no JSON (P0 para o painel)

- **Evidência:**
  - As 5 comparações existem duas vezes, com os **mesmos 40 valores** de enquadramento: `index.html:450-509` = `resultados.html:273-350`.
  - O horário já diverge: "Segunda à sexta, das 8h às 17h" (`index.html:724`) e "Segunda à sexta, 8h às 17h" (`index.html:784`).
  - Endereço, telefone e horário também estão no JSON-LD (`index.html:26-27`, `resultados.html:23`) e no rodapé das duas páginas.
  - O link do WhatsApp aparece 7 vezes no index e outras vezes no resultados.
  - O número de colunas de cada categoria em resultados está fixo à mão: "Corrente Fortalecedora" usa `repeat(3, …)` com só 2 vídeos (`resultados.html:381`), e o bloco fica deslocado para a esquerda (print `desktop-resultados-section-6.png`).
- **Como reproduzir:** compare as linhas citadas. Veja o print do desktop: o bloco Corrente tem um espaço vazio à direita.
- **O que mudar:** no `site.padrao.json`, cada item de resultado aparece uma única vez, com `id` estável (não a posição na lista), `tipo` (`comparacao` | `video` | `foto`), `categoria`, `na_home` (sim/não), `em_resultados` (sim/não) e `ordem`. As categorias ficam numa lista própria com ordem. Os contatos (telefone só com dígitos, mensagem padrão, endereço, horário, Instagram) ficam num bloco `contato` só, de onde saem header, hero, contato, rodapé, cursos e JSON-LD. Colunas: `min(nº de itens, 3)`. Categoria vazia: some o título. Home sem comparações: some a seção ou mostra só o "Ver mais".
- **Critério de pronto:** um `grep` no JSON padrão encontra cada arquivo de foto e o telefone uma vez só. Trocar o horário no JSON muda os 3 lugares e o JSON-LD. Com 1, 2, 3 e 4 itens, a categoria fica centralizada (print). O HTML gerado a partir do JSON padrão fica igual ao atual (ver L08).

## L08. Migração exata do enquadramento (width/height/left/top em %) (P0)

- **Evidência:** cada foto usa `position:absolute; width:W%; height:H%; left:L%; top:T%; transform:translate(-50%,-50%)` dentro de uma moldura 3:4, sem `object-fit`. Ou seja: **`left`/`top` marcam o CENTRO da foto, não o canto**, e a foto é esticada até W×H. Conferi as 10 fotos: todas obedecem `H = W × 0,75 ÷ (largura/altura natural)`. Exemplo: `liposhape-depois1` tem 1506x1600, W 125,5 e H 100, o que dá exatamente o "cover" automático. Uma das fotos fica no limite: `drenagem-depois1` tem L = 50,508, e o máximo sem deixar faixa vazia é 50 + (101,015 − 100)/2 = 50,5075. O caso Ultrassom é outro modelo: `object-fit:contain; object-position: calc(50% - 0.86%) 50%` (`resultados.html:366`).
- **Como reproduzir:** troque `liposhape-depois1.jpeg` por uma foto de outra proporção mantendo W/H: ela fica esticada. Num editor que trate `left`/`top` como canto, todas as fotos pulam meio quadro.
- **O que mudar:**
  - Guardar no JSON `{w, h, x, y}` exatamente como hoje (com `x`/`y` = centro), mais `larg_natural` e `alt_natural` de cada arquivo.
  - O editor só deixa mudar zoom (escala) e centro. Ele recalcula sempre `h` a partir de `w` e da proporção, para nunca distorcer.
  - Enquadramento automático: se a proporção for > 0,75, `h = 100` e `w = 100·prop/0,75`; senão, `w = 100` e `h = 75/prop`. `x = y = 50`.
  - Limitar (clamp) `|x−50| ≤ (w−100)/2` e `|y−50| ≤ (h−100)/2` **só quando ela mexer**, nunca ao abrir (senão abrir e salvar já muda a `drenagem-depois1`).
  - Ao trocar só uma foto do par, só ela volta ao automático.
  - Ultrassom: tipo `foto` com `ajuste: "contain"` e deslocamento próprio.
  - No PHP, formatar os números sem notação científica e sem zeros sobrando (por exemplo `rtrim(rtrim(sprintf('%.3F',$v),'0'),'.')`), para que `125.5` continue `125.5%`.
- **Critério de pronto:** um teste que gera `index.php`/`resultados.php` com o `site.padrao.json` e compara os atributos `style` das 11 fotos com o HTML atual dá igualdade de texto. Os prints dos 10 comparadores antes/depois (com o slider no meio e arrastado) ficam iguais pixel a pixel. Abrir e salvar no editor sem mexer não muda nenhum número.

## L09. Editor de zoom e posição no iPhone: gestos e pinça (P1)

- **Evidência:** a arquitetura pede pinça e arraste com o dedo (`decisao-painel.md`, item 3). No Safari do iOS, a pinça dá zoom na página inteira (o iOS ignora `user-scalable=no` desde o iOS 10), e o arraste rola a página, a não ser que a moldura bloqueie isso.
- **Como reproduzir:** num protótipo sem `touch-action`, faça pinça na moldura no iPhone: a página toda aumenta e a foto não muda.
- **O que mudar:** `touch-action: none` só na moldura do editor. Pointer Events com até 2 dedos: distância entre dedos = zoom, ponto médio = âncora. O zoom é feito em volta do ponto entre os dedos (ajustar `x`/`y` junto com `w`). `preventDefault` em `gesturestart`/`gesturechange` para o iOS não dar zoom na página. Usar o slider de zoom como alternativa, com botões +/− grandes (≥44 px). O "Voltar ao enquadramento automático" usa a fórmula do L08. A prévia usa o mesmo CSS do site (moldura 3:4, `translate(-50%,-50%)`), sem uma cópia própria.
- **Critério de pronto:** no iPhone real, pinça e arraste mudam só a foto e nunca a página. A foto não sai da moldura (sem faixa vazia). O valor salvo, aberto no site, mostra o mesmo corte da prévia (print lado a lado).

## L10. Upload de vídeo do iPhone: HEVC, MOV, rotação, 4K e upload que cai (P0)

- **Evidência:** a regra do CLAUDE.md é "H.264 MP4 ≤1080p", e não há ffmpeg nem dependência nova (então o servidor não converte). Os vídeos atuais vieram de celular com rotação de −90° no cabeçalho (ffprobe: `corrente-1.mp4`, `liposhape-pos.mp4` e `liposhape-depois2.mp4` estão gravados em 1024x576 com `rotation=-90` e aparecem em pé). Vídeos do iPhone costumam ser `.MOV`, em HEVC, às vezes 4K, e muitas vezes com a caixa `moov` no FIM do arquivo. O `.user.ini` com 256M depende também dos limites do LiteSpeed da Hostinger e do `max_execution_time`. No iPhone, o envio para quando a tela bloqueia ou ela troca de app.
- **Como reproduzir:** grave um vídeo em 4K/HEVC no iPhone e envie por um formulário simples: é aceito, não toca no Chrome do Windows nem em parte dos Androids e passa de 1080p. Um arquivo de 150 MB num 4G fraco falha no meio e precisa recomeçar do zero.
- **O que mudar:**
  - Detectar o codec **lendo a estrutura de caixas** (`ftyp` → `moov` → `trak` → `stsd`) com `File.slice`, pulando o `mdat`, e não só os primeiros KB. Repetir a checagem no servidor (PHP lendo as caixas), sem confiar na extensão nem no MIME.
  - HEVC (`hvc1`/`hev1`): **bloquear** com a explicação de "Ajustes > Câmera > Formatos > Mais compatível", em vez de só avisar, porque a regra exige H.264.
  - Largura ou altura exibida (já com a rotação) acima de 1080 → bloquear e explicar como gravar em 1080p.
  - Salvar como `.mp4` com `video/mp4` quando for H.264 em MOV.
  - Enviar em pedaços (por exemplo de 5 MB) com retomada, usando JS puro + PHP que junta as partes, e mostrar uma barra de progresso.
  - Avisar quando o `moov` estiver no fim (o início do vídeo fica mais lento). Sem ffmpeg, não dá para corrigir no servidor.
- **Critério de pronto:** testes com 4 arquivos de exemplo (H.264 1080p MP4; H.264 MOV com `moov` no fim; HEVC; 4K): os dois primeiros entram e tocam no Chrome desktop e no WebKit, e os dois últimos são recusados com a mensagem certa. Um upload interrompido e retomado termina com o arquivo íntegro (mesmo SHA-256).

## L11. Extração do primeiro frame no navegador (poster) (P1)

- **Evidência:** a decisão fala em "primeiro frame **com conteúdo**", mas o CLAUDE.md exige "poster = PRIMEIRO frame". Se escolher um frame posterior, volta o salto de imagem do L01 ao dar play. Armadilhas conhecidas no iOS: o vídeo precisa estar `muted` + `playsinline` para carregar sem gesto; `currentTime = 0` não dispara `seeked` se o tempo já for 0; `loadeddata` pode não vir sem um `play()`/`pause()`. Um vídeo com rotação (como o `corrente-1.mp4`) precisa sair em pé. Um HEVC não decodifica no Chrome do Windows.
- **Como reproduzir:** extraia o poster de `assets/results/corrente-1.mp4` com um canvas do tamanho de `videoWidth`x`videoHeight` sem esperar o frame: sai preto ou deitado em algum navegador.
- **O que mudar:** `video.muted = true; playsInline = true; src = URL.createObjectURL(file)`. Esperar `loadeddata` (com `play().then(pause)` como plano B), buscar `currentTime = 0.001` e esperar `seeked` (ou `requestVideoFrameCallback`). Desenhar num canvas de no máximo 1600 px no lado maior, com `videoWidth`/`videoHeight` (que já levam a rotação em conta), e gerar `toBlob('image/jpeg', 0.85)`. Se o frame 0 estiver quase todo preto (luminância média < 16), mostrar a imagem e perguntar "A capa ficou escura. Usar mesmo assim?", sem trocar de frame sozinho. Se der erro ou passar do tempo limite, não deixar salvar o vídeo sem poster.
- **Critério de pronto:** com os 5 vídeos atuais de `assets/results/` e a `apresentacao.mp4`, o poster gerado no navegador bate com o `frame0-*.png` desta pasta (diferença média < 5/255) no Chrome e no WebKit, e é conferido num iPhone real. O `corrente-1.mp4` sai 576x1024 (em pé).

## L12. Fotos do iPhone: HEIC, tamanho do canvas e orientação (P1)

- **Evidência:** o GD do PHP 8 em geral não lê HEIC (`decisao-painel.md` já diz "se o GD suportar"). iPhones recentes tiram fotos de 24 MP (5712x4284) ou 48 MP, e o Safari do iOS limita o canvas a cerca de 16,7 milhões de pixels: desenhar a foto inteira num canvas desse tamanho sai em branco ou derruba a aba. As fotos atuais não têm EXIF de orientação nem GPS (conferido com PIL), mas as novas vão ter.
- **Como reproduzir:** escolha uma foto de 24 MP no iPhone num protótipo que cria o canvas com o tamanho original: o resultado sai vazio.
- **O que mudar:** `accept="image/jpeg,image/png,image/webp"` (sem `heic`), para o iOS converter para JPEG sozinho na hora de escolher. Reduzir **direto** para um canvas de no máximo 1600 px (`createImageBitmap(file, {resizeWidth})` quando existir; senão, `drawImage` com destino pequeno), nunca no tamanho cheio. O canvas já sai em pé e sem EXIF. No servidor, o GD aplica a orientação EXIF só se ela existir, assim não gira duas vezes, e grava a largura e a altura finais no JSON (usadas no L08).
- **Critério de pronto:** foto de 24 MP e foto HEIC do iPhone entram, saem com ≤1600 px e ≤250 KB, em pé, sem EXIF/GPS (`exiftool` vazio). Foto de retrato tirada com o celular deitado sai na orientação certa.

## L13. Desfazer, histórico de 20 versões e lixeira (P0)

- **Evidência:** a decisão define "20 últimas versões" + "Desfazer última alteração" + mídia apagada vai para `conteudo/lixeira/`, mas não define: (a) se desfazer **apaga** a versão atual ou **cria** uma nova (desfazer duas vezes pode voltar duas versões ou só alternar entre duas); (b) o que acontece com uma foto que já foi para a lixeira quando o desfazer traz de volta um JSON que ainda aponta para ela, o que dá **imagem quebrada no site**; (c) se cada movimento do slider de zoom grava uma versão (as 20 versões acabam em segundos); (d) os uploads feitos e nunca salvos ficam órfãos.
- **Como reproduzir (no desenho):** apagar a comparação X (as fotos vão para a lixeira) e depois tocar em "Desfazer": o JSON volta, mas `conteudo/midia/x.webp` não existe mais.
- **O que mudar:** uma versão só nasce no botão "Salvar" (nunca durante o arraste). "Desfazer" = restaurar a versão anterior **como uma nova versão**, e mostrar a lista "Versões" com data e hora em português ("hoje 14:32 – trocou foto de Drenagem"). Só mover uma mídia para a lixeira quando nenhuma das 20 versões apontar para ela (ou fazer o desfazer trazer o arquivo de volta da lixeira). Uma limpeza agendada no próprio save remove da `midia/` os uploads com mais de 24 h que nenhuma versão usa.
- **Critério de pronto:** um teste PHP faz "apagar → desfazer → desfazer o desfazer" e o site mostra todas as imagens, sem 404. 25 gravações seguidas deixam exatamente 20 versões. Arrastar o zoom por 10 s sem salvar não cria nenhuma versão.

## L14. Duas telas ao mesmo tempo, sessão que expira e trabalho perdido (P1)

- **Evidência:** gravar no temporário e depois fazer `rename` protege contra arquivo corrompido, mas **não contra perder alterações**. Se o painel estiver aberto no iPhone e no PC (ou em duas abas), a última gravação apaga sem aviso o que a outra fez. A sessão expira por inatividade, e o iOS recarrega a aba quando ela vai à Fotos escolher um vídeo grande. Com isso, o token CSRF muda e o que ela estava editando se perde.
- **Como reproduzir (no desenho):** abra o painel em duas abas, troque o título na aba A e salve, troque um depoimento na aba B e salve: o título da A volta ao antigo.
- **O que mudar:** um campo `revisao` (número inteiro) no JSON. O painel manda a revisão que carregou, e o PHP, dentro de um `flock` exclusivo sobre `conteudo/.lock` (cobrindo ler, comparar, gravar e girar o histórico), recusa com 409 se a revisão mudou. Aí o painel mostra "Este conteúdo foi mudado em outro aparelho. Recarregar?". Guardar o rascunho de cada tela em `sessionStorage`/`localStorage` até salvar. Se a sessão expirar, depois do login ela volta para a mesma tela com o rascunho. Um 403 de CSRF no save mostra "Entre de novo para salvar", sem perder nada.
- **Critério de pronto:** teste com dois clientes simultâneos: o segundo recebe 409 e nada se perde. Teste de sessão expirada: depois do login, o rascunho reaparece e o save funciona.

## L15. Renderização em PHP: arquivos .html antigos, links, JSON-LD e esquema do JSON (P0)

- **Evidência:**
  - Se `index.html` continuar no `public_html`, o `DirectoryIndex` da Hostinger pode servi-lo antes do `index.php`: a home fica congelada na versão estática e as edições da dona "não aparecem".
  - O rodapé de `resultados.html` tem 9 links `index.html#...`, que também precisam cair no PHP.
  - A decisão diz para passar todo texto por `htmlspecialchars`, mas dentro do `<script type="application/ld+json">` (`index.html:26`, `resultados.html:23`) isso gera `&amp;` no JSON. O certo ali é `json_encode` com `JSON_HEX_TAG|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES` (o que também impede um `</script>` digitado num texto).
  - As mensagens do WhatsApp precisam de `rawurlencode`.
  - Os depoimentos têm emoji e os textos têm vários parágrafos (Sobre: 2; bloco do vídeo: 3).
  - `conteudo/site.json` é uma cópia completa. Quando um deploy adicionar um campo novo ao `site.padrao.json`, o arquivo editado não vai ter esse campo.
- **Como reproduzir:** num servidor PHP local, deixe `index.html` e `index.php` juntos e abra `/`. Digite `Tom & Jerry </script>` num texto de contato e veja o JSON-LD no Rich Results Test.
- **O que mudar:**
  - Tirar `index.html`/`resultados.html` do deploy (ou renomear) e reescrever internamente `/`, `/index.html` e `/resultados.html` para os `.php` com `RewriteRule` incondicional, mais `DirectoryIndex index.php`.
  - Função `e()` = `htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8')` para o HTML e `json_encode` para o JSON-LD.
  - Parágrafos guardados como lista no JSON.
  - `"versao_esquema"` no JSON e, ao ler, mesclar o padrão com o editado (os campos que faltam vêm do padrão), com uma função de migração por versão.
  - `Cache-Control: no-cache` nas páginas PHP, e mídias com nome novo a cada troca.
- **Critério de pronto:** com o servidor local PHP, `/`, `/index.html` e `/resultados.html` mostram o conteúdo do JSON (trocar um texto aparece nas 3). O Rich Results Test valida o JSON-LD com `&` e aspas no endereço. Um `site.json` antigo, sem um campo novo, ainda gera a página sem aviso de PHP.

---

### Fora do escopo, só registrado
- `backdrop-filter` no cabeçalho sem `-webkit-backdrop-filter` (Safari do iOS anterior ao 18 não aplica o desfoque): visual, para o agente de visual.
- Arraste com mouse só existe em Procedimentos, não em Depoimentos: detalhe de consistência.
