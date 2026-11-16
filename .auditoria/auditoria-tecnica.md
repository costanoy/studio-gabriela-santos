# Auditoria técnica, segurança e performance

Data: 2026-11-16. Branch `melhorias-claude`. Fase somente leitura: nenhum arquivo do site foi alterado.

## Medições novas feitas nesta auditoria

- **Teste de correção (Lighthouse 13.6, celular, simulado):** cópia do site numa pasta temporária, fora do projeto, servida na porta 8799. Três rodadas por variante.
  - `resultados.html` original: perf **77 / 84 / 75**, LCP **6,5 s / 4,2 s / 9,0 s**, peso **4,9 / 4,3 / 4,6 MB**, TBT 2–52 ms.
  - Mesma página com fotos em WebP de 800 px, posters em WebP de 720 px, `logo-gs` em WebP de 384 px, `preload="none"` nos vídeos e primeira foto sem `lazy`: perf **97 / 97 / 98**, LCP **2,52 s / 2,55 s / 2,32 s**, peso **721 KB**.
  - `index.html` original: perf 90 / 89, LCP 3,51 s / 3,46 s, 436 KB. Com `logo-gs` e `hero-bg` em WebP: perf **96 / 95**, LCP **2,29 s / 2,27 s**, **233 KB**.
  - O TBT de 10 s da primeira medição (`lh-resultados-mobile.json`) **não se repetiu**: ficou entre 2 e 61 ms em 6 rodadas. Era uma tarefa longa "Unattributable" de 8 s, provavelmente ruído do Chrome sem interface. A partir de agora, use a mediana de 3 rodadas.
- **Cabeçalhos do site real (só consultas HEAD/GET, nada alterado):**
  - Na frente do servidor existe a CDN da Hostinger (`Server: hcdn`, `x-hcdn-cache-status`).
  - O HTML vai **sem `Cache-Control`**. Arquivos estáticos vão com `max-age=604800` (7 dias). A compressão br/gzip já é feita pela Hostinger. O aviso "no compression" do Lighthouse só acontece no servidor local de Python.
  - `https://www.` responde 200, sem redirecionar. `/index.html` também responde 200, então o mesmo conteúdo fica em duas URLs. `/.git/HEAD` já responde 403.
- **Vídeos atuais:** os 8 MP4 têm `moov` antes de `mdat` (faststart). Isso está correto.

---

## T01 — Página de resultados estoura o orçamento (fotos 4x maiores que o necessário e foto principal com lazy)
- **Gravidade:** P0
- **Evidência:**
  - `performance-antes.md`: perf 31, LCP 6,6 s e 4.834 KB no celular. Nas rodadas novas: perf 75–84, LCP 4,2–9,0 s e 4,3–5,0 MB.
  - O Lighthouse (`image-delivery-insight`) estima 1.850 KiB de economia. As fotos têm 1180x1600, mas aparecem em 343x466.
  - O elemento do LCP é `resultados.html:273` (`liposhape-depois1.jpeg`), que está com `loading="lazy"`. Isso causa 3,5 s de atraso para começar a baixar (`lcp-discovery-insight`).
  - `drenagem-antes1.jpeg` (260 KB) e `drenagem-depois1.jpeg` (258 KB) passam do limite de 250 KB.
  - Os mesmos arquivos aparecem em `index.html:450–509`.
- **Como reproduzir:** `npx lighthouse http://localhost:8765/resultados.html --form-factor=mobile --only-categories=performance`, três vezes.
- **O que mudar:**
  - Gerar versões das 11 fotos dos comparadores com 800 px de largura, em WebP qualidade ~75. O teste deu de 25 a 80 KB por foto.
  - Usar essas versões nas duas páginas. Os originais ficam guardados fora da pasta pública.
  - Tirar `loading="lazy"` das 2 fotos do primeiro comparador da página de resultados e colocar `fetchpriority="high"` na foto "depois".
  - No painel, o GD gera essa versão de 800 px quando a dona envia uma foto. Ver T12.
- **Critério de pronto:** mediana de 3 rodadas no celular com perf ≥ 90, LCP ≤ 2,5 s e peso ≤ 1,5 MB, nenhuma foto acima de 250 KB e prints iguais aos de `prints/antes/`. No teste, o LCP ficou em 2,3–2,55 s. Se ficar no limite, reduza a primeira foto para 640 px.

## T02 — Vídeos baixam cerca de 1 MB cada sem ninguém dar play
- **Gravidade:** P0
- **Evidência:**
  - `preload="metadata"` em `resultados.html:284, 289, 370, 383, 388` e em `index.html:391`.
  - Na primeira medição, os 5 vídeos somaram 2,5 MB (de 459 a 590 KB cada). O `funcoes.md` mediu 1,00 MB por vídeo no Chromium.
  - O comportamento muda entre rodadas: numa delas cada vídeo baixou só 191 bytes. Não dá para confiar no `metadata`.
  - `apresentacao.mp4` tem 14,2 MB (576x1024, 1,28 Mb/s, 84,6 s) e o poster dele não é o primeiro frame.
- **Como reproduzir:** abrir a aba Rede do DevTools com "Fast 4G", carregar `resultados.html` sem rolar e filtrar por "Media".
- **O que mudar:**
  - Usar `preload="none"` em todos os `<video>`, mantendo `poster` e `playsinline`.
  - Recomprimir `apresentacao.mp4` em H.264 com cerca de 700–900 kb/s e `+faststart`. A meta é ficar abaixo de 8 MB.
  - Gerar o poster a partir do frame 0. Esse vídeo é do Vinicius, não da dona, então o ffmpeg é usado só localmente.
  - No painel, os vídeos novos já saem com `preload="none"`.
- **Critério de pronto:** com a página carregada e sem play, 0 bytes de `.mp4` na aba Rede, nas duas páginas e nos 3 projetos do Playwright. Poster igual ao frame 0, com diferença menor que 5/255. O teste de tela preta continua passando.

## T03 — Página inicial: logo invisível de 141 KB e hero de 1920 px seguram o LCP em 3,5 s
- **Gravidade:** P0, porque estoura o LCP do orçamento.
- **Evidência:**
  - `index.html:306` e `resultados.html:257`: `logo-gs.png` tem 1920x1080 e 141 KB, mas aparece em 192x108 e começa com `opacity:0`. Ele é baixado no topo e disputa a rede com o LCP.
  - `index.html:340`: `hero-bg.png` tem 1920x1080 e 90 KB e é o LCP.
  - Teste: com as duas imagens em WebP (10 KB e 14 KB), o LCP caiu de 3,5 s para 2,27 s e o peso de 436 KB para 233 KB.
- **Como reproduzir:** rodar o Lighthouse no celular em `/` e abrir o item "Improve image delivery".
- **O que mudar:**
  - Gerar `logo-gs.webp` com 384 px de largura (o dobro do tamanho na tela) e `hero-bg.webp` com cerca de 1080 px.
  - O arquivo `hero-bg.webp` que já existe (367x201) é pequeno demais. Ele precisa ser substituído.
  - Opcional: carregar o `logo-gs` só depois do primeiro scroll.
- **Critério de pronto:** LCP ≤ 2,5 s e perf ≥ 90 na mediana de 3 rodadas no celular. Visual idêntico ao dos prints de header e home, em 390 e 1440 px.

## T04 — Arquivos não usados vão junto no deploy
- **Gravidade:** P2
- **Evidência:** `ferramentas.md` 3.2 lista `corrente-3.mp4` (2,5 MB), `liposhape-depois2.mp4` (1,8 MB), `liposhape-antes3.jpeg`, `hero-bg.webp` e `logo.png`, todos versionados no git (`git ls-files`).
- **Como reproduzir:** `grep` pelo nome de cada arquivo em `index.html` e `resultados.html`. Não aparece nenhum.
- **O que mudar:**
  - Perguntar à dona se ela quer esses arquivos. Se quiser, entram no `site.padrao.json` como mídia disponível no painel.
  - Se não quiser, saem do repositório público e vão para uma pasta de originais fora do deploy.
- **Critério de pronto:** todo arquivo em `assets/` é usado pelo HTML ou pelo `site.padrao.json`, e um script simples de checagem confirma isso.

## T05 — Não existe `.htaccess`: falta cache, URL única e cabeçalhos de segurança
- **Gravidade:** P1
- **Evidência:**
  - Não existe `.htaccess` na raiz do projeto.
  - No site real, o HTML vai sem `Cache-Control`, os estáticos têm cache de 7 dias e a CDN `hcdn` fica na frente.
  - `https://www.studiogabrielasantos.com.br/` e `/index.html` respondem 200, então a mesma página existe em 3 URLs. A canonical ajuda, mas não substitui o redirecionamento.
- **Como reproduzir:** `curl -sI https://www.studiogabrielasantos.com.br/` e `curl -sI https://studiogabrielasantos.com.br/index.html`.
- **O que mudar** (criar `.htaccess` na raiz, sem nenhuma dependência):
  - Redirecionar com 301: `www` → domínio sem www, `http` → `https`, `/index.html` e `/index.php` → `/`.
  - Cache longo e `immutable` só para arquivos com nome único: `conteudo/midia/*` (nomes aleatórios) e fontes.
  - Para `assets/*` (nomes fixos), usar `?v=<filemtime>` gerado pelo PHP ou manter os 7 dias.
  - HTML com `Cache-Control: no-cache`, para a edição da dona aparecer na hora. Painel com `Cache-Control: no-store, private`.
  - Cabeçalhos: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`, `Permissions-Policy: camera=(), geolocation=()`.
  - `Strict-Transport-Security` só depois de confirmar que o HTTPS funciona com e sem www.
  - `Options -Indexes`.
  - Testar primeiro num subdomínio de teste. Um `.htaccess` errado derruba o site com erro 500.
- **Critério de pronto:**
  - `curl -sI` nas 4 variações de URL mostra um único 200 em `https://studiogabrielasantos.com.br/` e 301 nas outras.
  - Mídia com `max-age` ≥ 30 dias e HTML com `no-cache`.
  - Lighthouse "Boas práticas" ≥ 95 e "uses-long-cache-ttl" verde para mídia.

## T06 — SEO ao migrar para `index.php` e `resultados.php`
- **Gravidade:** P1
- **Evidência:**
  - `decisao-painel.md` item 2 prevê reescrita interna.
  - A canonical em `index.html:9` e `resultados.html:9` e o `sitemap.xml` apontam para `/` e `/resultados.html`.
  - O rodapé de resultados aponta para `index.html#...` (`resultados.html:404–417`).
  - Hoje o SEO dá 100 nas duas páginas.
- **Como reproduzir:** depois da migração, rodar `curl -sI` em `/`, `/index.html`, `/index.php`, `/resultados.html`, `/resultados.php` e numa URL que não existe.
- **O que mudar:**
  - Usar `DirectoryIndex index.php`. Apagar `index.html` e `resultados.html` do servidor, para não haver duas versões.
  - Criar `RewriteRule ^resultados\.html$ resultados.php [L]` como reescrita interna.
  - Redirecionar com 301 os acessos diretos a `*.php`, usando `%{THE_REQUEST}` para não criar loop.
  - `/index.html` → 301 para `/`. Os links internos passam a ser `/#sobre` e `/resultados.html`.
  - Página 404 com status 404 de verdade.
  - Canonical, `og:url`, títulos e descrições iguais aos de hoje.
  - O JSON-LD é gerado com `json_encode(..., JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG)`, nunca com `htmlspecialchars`.
  - O `lastmod` do sitemap é atualizado quando a dona salva (`sitemap.php` reescrito como `sitemap.xml`, ou o arquivo é regravado no salvamento).
  - O painel fica com `X-Robots-Tag: noindex, nofollow` e não entra no sitemap.
- **Critério de pronto:**
  - O HTML gerado pelo PHP tem a mesma `<head>` que o atual (diff vazio, a não ser o `lastmod`).
  - SEO = 100 nas duas páginas, `/resultados.html` responde 200 sem redirecionar e os `.php` respondem 301.
  - O validador de dados estruturados não mostra erro, e o teste do Playwright de links continua passando.

## T07 — Arquivos de desenvolvimento ficariam públicos no deploy pelo git
- **Gravidade:** P0, porque bloqueia o deploy.
- **Evidência:**
  - `git ls-files` versiona `package.json`, `package-lock.json`, `playwright.config.js`, `tests/site.spec.js`, `CLAUDE.md`, `.claude/launch.json`, `.gitignore` e `.auditoria/*.md`.
  - O `.auditoria/decisao-painel.md` mostra o usuário da hospedagem (`u837316869`), o caminho completo e o desenho de segurança do painel.
  - O deploy pelo git da Hostinger publica a raiz inteira do repositório. O `.git/` já é bloqueado (403), mas o resto não.
  - Também estão planejados `ferramentas/criar-senha.php` e `dados/`.
- **Como reproduzir:** depois de um deploy num subdomínio de teste, rodar `curl -s -o /dev/null -w '%{http_code}' https://<teste>/CLAUDE.md` (e o mesmo para `.auditoria/mapa.md`, `package.json` e `tests/site.spec.js`).
- **O que mudar:**
  - **Opção A (mais segura):** criar uma branch só de publicação, gerada por script (por exemplo, `git subtree split` de uma pasta `site/`), com apenas `index.php`, `resultados.php`, `painel/`, `assets/`, `dados/`, `.htaccess`, `.user.ini`, `robots.txt` e `sitemap`.
  - **Opção B (mínimo):** no `.htaccess`, negar com 404 `^(\.auditoria|\.claude|tests|node_modules|ferramentas|dados)/` e os arquivos `(CLAUDE\.md|package(-lock)?\.json|playwright\.config\.js|\.gitignore|\.user\.ini|.*\.md)$`.
  - `ferramentas/criar-senha.php` começa com `if (PHP_SAPI !== 'cli') exit;`.
  - Usar as duas opções juntas é o ideal.
- **Critério de pronto:** um script de verificação faz `curl` numa lista fixa com todos os arquivos versionados que não são públicos, e todos respondem 403 ou 404 no subdomínio de teste e depois no domínio.

## T08 — Deploy pelo git pode apagar ou sobrescrever o que a dona fez
- **Gravidade:** P0
- **Evidência:**
  - `decisao-painel.md` item 1 deixa `conteudo/` fora do git, dentro de `public_html`.
  - As instruções da Hostinger avisam que o deploy sobrescreve o conteúdo do site, e o comportamento com arquivos não versionados não está documentado.
  - O `.gitignore` atual ainda não tem `conteudo/`.
  - Gravar ao mesmo tempo do iPhone e do computador pode perder uma edição se o arquivo não for travado.
- **Como reproduzir:**
  - No subdomínio de teste, criar `conteudo/teste.txt` e `conteudo/midia/x.webp` pelo Gerenciador de Arquivos.
  - Fazer um deploy pelo git e conferir se os arquivos continuam lá.
  - Para a gravação simultânea, salvar o mesmo texto ao mesmo tempo de duas abas.
- **O que mudar:**
  - Guardar `site.json`, `historico/`, `lixeira/`, o arquivo de tentativas de login e as sessões **fora do `public_html`**, por exemplo em `/home/u837316869/domains/studiogabrielasantos.com.br/painel-dados/`. Só `conteudo/midia/` fica público.
  - Colocar `conteudo/` no `.gitignore` antes do primeiro deploy.
  - Fazer backup automático (zip de `painel-dados/` e `conteudo/`) antes de cada deploy, ou um botão "baixar cópia" no painel.
  - Usar `flock` na gravação, além do arquivo temporário com `rename`, e um número de versão no JSON para recusar uma gravação feita sobre uma versão antiga.
- **Critério de pronto:** o teste de deploy de cima mostra os arquivos intactos, e o roteiro fica escrito em `.auditoria/`. Duas gravações simultâneas não perdem dados: a segunda recebe o aviso "o site foi alterado em outro aparelho, recarregue".

## T09 — Login e sessão do painel
- **Gravidade:** P0, porque bloqueia a publicação do painel.
- **Evidência:**
  - `decisao-painel.md` item 3: só senha, hash em `../painel-config.php`, cookie Secure/HttpOnly/SameSite=Strict e expiração por inatividade.
  - A CDN `hcdn` fica na frente do site.
  - Em hospedagem compartilhada, as sessões ficam no `session.save_path` padrão.
- **Como reproduzir:** depois de implementado, conferir os cookies no DevTools, tentar usar um ID de sessão inventado e repetir uma requisição depois do logout.
- **O que mudar:**
  - Usar `password_hash(PASSWORD_DEFAULT)` e `password_verify`, com senha mínima de 12 caracteres.
  - O arquivo de configuração fica fora do `public_html`, com permissão 600.
  - Sem SSH, o hash é gerado no PC (`php ferramentas/criar-senha.php`) e o arquivo é enviado pelo Gerenciador de Arquivos.
  - Na sessão: `session.use_strict_mode=1`, `use_only_cookies=1`, `session_name('gs_painel')`, `session.save_path` numa pasta privada fora do `public_html`, `session_regenerate_id(true)` no login e `gc_maxlifetime` igual ao tempo de inatividade (sugestão: 30 min, com limite absoluto de 12 h).
  - Logout por POST com CSRF, destruindo a sessão e o cookie.
  - Painel com `Cache-Control: no-store` e sem passar pelo cache da CDN.
  - Observação sobre o iPhone: com `SameSite=Strict`, quem abre o painel por um link do WhatsApp chega sem o cookie e vê a tela de login. Isso precisa estar explicado na ajuda, ou o link precisa ser guardado na tela inicial do iPhone.
- **Critério de pronto:**
  - Cookie com Secure, HttpOnly e SameSite=Strict, e ID de sessão diferente antes e depois do login.
  - Um ID inventado não é aceito, a sessão expira após 30 min parada e o logout invalida o ID antigo.
  - `curl` em `/painel-config.php` e em `/painel/` sem sessão não mostra nada sensível.

## T10 — O limite de tentativas por IP pode não funcionar atrás da CDN
- **Gravidade:** P0
- **Evidência:**
  - O cabeçalho `Server: hcdn` e o `x-hcdn-request-id` mostram que as requisições passam pela CDN da Hostinger.
  - Se o `REMOTE_ADDR` que o PHP recebe for o IP do servidor da CDN, o limite de "5 tentativas a cada 15 min por IP" bloqueia todo mundo ao mesmo tempo, inclusive a dona.
  - Se o código confiar em `X-Forwarded-For` sem validar, um atacante troca de "IP" a cada tentativa.
- **Como reproduzir:** num PHP temporário no subdomínio de teste (apagar depois), mostrar `REMOTE_ADDR` e `HTTP_X_FORWARDED_FOR` acessando de duas redes diferentes (Wi-Fi e 4G).
- **O que mudar:**
  - Usar `REMOTE_ADDR` só se for o IP real do visitante. Caso contrário, usar o primeiro IP do cabeçalho que a Hostinger documentar como confiável, e só quando `REMOTE_ADDR` for um IP da CDN.
  - Além do limite por IP, criar um freio global: depois de 20 erros por hora, cada nova tentativa espera alguns segundos, sem bloquear de vez.
  - Gravar as tentativas em arquivo fora do `public_html`, com `flock`.
  - Mostrar à dona uma mensagem clara: "Muitas tentativas. Espere 15 minutos."
- **Critério de pronto:** um teste automatizado faz 6 logins errados e o 6º é recusado. Um login de outra rede continua funcionando, e o IP gravado é o do visitante, não o da CDN.

## T11 — CSRF e textos/links vindos do JSON (XSS)
- **Gravidade:** P1
- **Evidência:**
  - `decisao-painel.md` prevê CSRF e `htmlspecialchars`.
  - As páginas públicas têm 28 `onclick` inline (23 no index e 5 em resultados) e estilos inline com números do JSON (`width`, `left` e `top` em %, como em `resultados.html:273`).
  - Links de WhatsApp, Forms e Maps vão ser editáveis pela dona.
- **Como reproduzir:**
  - Salvar no painel um texto com `"><script>alert(1)</script>`, um enquadramento com `left: "50%;background:url(x)"` e um link `javascript:alert(1)`, e abrir o site.
  - Enviar um POST sem token ou de outra origem.
- **O que mudar:**
  - Usar um token CSRF por sessão (`random_bytes(32)`) em todo POST, conferido com `hash_equals`, e checar também se o cabeçalho `Origin` é o próprio domínio.
  - Na saída: `htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8')` em todo texto. Números passam por `(float)` com limites (por exemplo, de 0 a 400%).
  - Links só com `https://` e domínios permitidos (`wa.me`, `instagram.com`, `forms.gle`, `maps.app.goo.gl`, `google.com/maps`). O número de WhatsApp aceita só dígitos.
  - JSON-LD: ver T06.
  - O painel é código novo, então pode ter uma CSP rígida sem inline (`default-src 'self'; img-src 'self' blob: data:; media-src 'self' blob:; frame-ancestors 'none'`).
  - No site público, a CSP só depois de tirar os `onclick` inline. Isso é opcional e não está nesta fase.
- **Critério de pronto:** os 3 valores de ataque aparecem como texto ou são recusados ao salvar, e nenhum alerta é executado. Um POST sem token ou com `Origin` diferente recebe 403.

## T12 — Upload de fotos: memória do GD, HEIC, EXIF e GPS (LGPD)
- **Gravidade:** P0
- **Evidência:**
  - `decisao-painel.md` prevê `finfo`, reprocessamento com GD e remoção de metadados.
  - Uma foto de 48 MP do iPhone (8064x6048) ocupa cerca de 195 MB de memória no GD (largura × altura × 4 bytes) e pode passar do `memory_limit`.
  - O GD do PHP 8 não lê HEIC.
  - Ler a orientação EXIF depende da extensão `exif`, que pode não estar ativa.
- **Como reproduzir:** enviar pelo painel, de um iPhone real, uma foto de 48 MP, uma foto HEIC com localização ativada e um arquivo `.php` renomeado para `.jpg`. Depois conferir o resultado com `exiftool` no PC.
- **O que mudar:**
  - No navegador: usar `accept="image/jpeg,image/png,image/webp"`, para o iOS já converter HEIC em JPEG, e reduzir para no máximo 1600 px num canvas antes de enviar.
  - No servidor:
    - `finfo_file` com lista permitida. A extensão sai do tipo detectado, nunca do nome enviado.
    - `getimagesize` antes de abrir a imagem, recusando acima de cerca de 40 MP.
    - Orientação com `exif_read_data` só se `function_exists`.
    - Redimensionar para 1600 px (exibição) e 800 px (comparador, ver T01), gravar com `imagewebp` ou `imagejpeg` (isso remove todos os metadados) e apagar o arquivo enviado.
  - Nome do arquivo: `bin2hex(random_bytes(16))`.
  - Limite de tamanho por arquivo e aviso quando o espaço em disco estiver baixo.
- **Critério de pronto:**
  - O `exiftool` não mostra GPS, Make, Model nem DateTimeOriginal nas fotos gravadas.
  - Foto de 48 MP aceita sem erro 500, `.php` renomeado recusado com mensagem simples e fotos finais ≤ 250 KB.

## T13 — Vídeos do iPhone carregam a localização (LGPD) e o GD não remove
- **Gravidade:** P0
- **Evidência:**
  - Vídeos `.MOV` e `.MP4` do iPhone guardam a localização em `moov/udta/©xyz` e em `moov/meta` (`com.apple.quicktime.location.ISO6709`).
  - O GD não processa vídeo, e a hospedagem compartilhada não tem ffmpeg.
  - O plano atual (`decisao-painel.md`) remove metadados só das imagens.
- **Como reproduzir:** gravar um vídeo de 3 s no iPhone com a localização ligada, enviar pelo painel e rodar `exiftool -GPS* -Location* arquivo` no arquivo que ficou no servidor.
- **O que mudar:**
  - No PHP, percorrer as caixas (atoms) do MP4 e MOV e sobrescrever com zeros ou espaços o conteúdo de `©xyz` e dos itens `location` do `meta`, sem mudar o tamanho das caixas. Isso não usa nenhuma dependência e não estraga o arquivo.
  - Se não der para limpar, recusar o vídeo e explicar como desligar a localização na câmera.
  - Avisar quando o `moov` estiver no fim do arquivo. Ele toca, mas demora mais para começar.
- **Critério de pronto:** o `exiftool` não mostra localização no vídeo gravado no servidor, o vídeo toca no iPhone, no Android e no Chrome do Windows, e um teste automatizado com um vídeo de exemplo que tem `©xyz` passa.

## T14 — Proteger a pasta `conteudo/` contra execução e download
- **Gravidade:** P1
- **Evidência:**
  - `decisao-painel.md` item 3 pede um `.htaccess` em `conteudo/` que bloqueie PHP, listagem de pastas e o download de `site.json` e do histórico.
  - O servidor é LiteSpeed/hcdn, onde `php_flag engine off` pode ser ignorado ou dar erro 500.
- **Como reproduzir:**
  - Pelo Gerenciador de Arquivos (não pelo painel), colocar `conteudo/midia/teste.php` com `<?php echo 1;`.
  - Acessar esse arquivo, `conteudo/`, `conteudo/site.json` e `conteudo/historico/`.
- **O que mudar:**
  - Preferir o T08, com o JSON e o histórico fora do `public_html`.
  - Em `conteudo/.htaccess`:
    - `Options -Indexes -ExecCGI`.
    - `<FilesMatch "\.(php\d?|phtml|phar|pht|phps|cgi|pl|py|sh|shtml|html?|svg|json)$"> Require all denied </FilesMatch>`.
    - `RemoveHandler .php .phtml .phar`.
    - `Header set X-Content-Type-Options nosniff`.
  - Só deixar servir `webp`, `jpg`, `png` e `mp4`. SVG não é aceito no upload.
- **Critério de pronto:** o `teste.php` responde 403 ou 404 e não executa, a listagem responde 403, `site.json` e histórico respondem 403 ou 404 (ou nem existem no `public_html`), as mídias respondem 200 com o tipo certo e o arquivo de teste é apagado depois.

## T15 — Limites de envio de vídeo pelo celular (`.user.ini`)
- **Gravidade:** P1
- **Evidência:**
  - `decisao-painel.md` prevê um `.user.ini` com 256M.
  - O `.user.ini` é lido só a cada `user_ini.cache_ttl` (5 min), e a Hostinger também define limites no hPanel.
  - Um envio único de 200 MB pelo 4G costuma passar de `max_execution_time` e `max_input_time` e falha sem uma mensagem clara.
  - O `.user.ini` em si não está protegido (ver T07).
- **Como reproduzir:** num PHP temporário no subdomínio de teste, mostrar `ini_get` de `upload_max_filesize`, `post_max_size`, `memory_limit`, `max_execution_time` e `max_input_time`. Depois enviar um vídeo de 150 MB pelo 4G.
- **O que mudar:**
  - Definir no `.user.ini` (ou no hPanel): `upload_max_filesize` e `post_max_size` de 256M, `max_input_time` e `max_execution_time` de 300 e `memory_limit` de 256M.
  - Melhor ainda: enviar em pedaços de 5 MB com JS puro, com barra de progresso e nova tentativa automática. O servidor junta os pedaços numa pasta privada e valida o arquivo inteiro no fim. Assim os limites de POST deixam de ser um problema.
  - O painel mostra os limites atuais e avisa antes de enviar um arquivo grande demais.
  - Recomendar vídeos de até 60 s e 1080p.
- **Critério de pronto:** um vídeo de 150 MB enviado de um iPhone pelo 4G chega completo, ou mostra uma mensagem em português simples. Os valores de `ini_get` ficam registrados em `.auditoria/` e `/.user.ini` responde 403 ou 404.
