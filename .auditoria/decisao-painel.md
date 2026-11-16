# Decisão de arquitetura: painel para a dona do studio editar o site

Decidido pelo orquestrador. O planejador detalha, não troca a arquitetura sem motivo forte.

## Contexto
- Hospedagem: Hostinger compartilhada (CloudLinux, PHP 8, usuário u837316869),
  raiz `/home/u837316869/domains/studiogabrielasantos.com.br/public_html`.
- A dona (Gabriela) não é técnica e vai usar principalmente o **celular (iPhone)**.
- Ela precisa: adicionar, trocar e apagar fotos e vídeos; montar comparações antes/depois
  ajustando **zoom e posição** de cada foto; editar textos.
- Sem serviços externos, sem banco de dados, sem dependências novas.

## Arquitetura
1. **Conteúdo em JSON.** Todo conteúdo editável sai do HTML e vai para um JSON:
   - `dados/site.padrao.json` (versionado no git): o conteúdo atual do site, migrado 1:1.
   - `conteudo/site.json` (fora do git, só no servidor): a versão editada pela dona.
     Se não existir, o site usa o padrão.
   - Uploads da dona em `conteudo/midia/` (fora do git). Assets atuais continuam em `assets/`.
   - Assim um deploy pelo git nunca apaga o que ela fez.
2. **Páginas renderizadas no servidor.** `index.php` e `resultados.php` leem o JSON e geram o
   mesmo HTML de hoje, com SEO intacto e sem depender de JavaScript. O `.htaccess` mantém as
   URLs: `/` → index.php e `/resultados.html` → resultados.php (reescrita interna, sem redirecionar).
   Todo texto vindo do JSON passa por `htmlspecialchars`.
   O visual público tem que ficar idêntico ao atual (comparar prints).
3. **Painel em `/painel/`** (PHP + JS puro, mobile-first, português simples, botões grandes):
   - Login só com senha. O hash fica num arquivo de configuração **fora do public_html**
     (`../painel-config.php`), criado pelo script `ferramentas/criar-senha.php`.
     Para o preview local existe uma config de teste ignorada pelo git.
   - Segurança: `password_hash`/`password_verify`, sessão com cookie Secure, HttpOnly e
     SameSite=Strict, `session_regenerate_id` no login, expira após inatividade, token CSRF
     em toda ação, limite de 5 tentativas a cada 15 min por IP (em arquivo), cabeçalhos de
     segurança e `noindex` no painel.
   - Uploads: whitelist por `finfo` (JPEG, PNG, WebP, HEIC se o GD suportar; MP4, MOV) e nome
     aleatório. As **imagens são reprocessadas com GD**: respeita a orientação EXIF, redimensiona
     para no máximo 1600px, salva como WebP ou JPEG e **remove os metadados**. Isso tira o GPS das
     fotos das clientes, o que importa pela LGPD. O navegador também reduz a imagem antes de
     enviar, para economizar dados no celular.
   - `conteudo/.htaccess` bloqueia execução de PHP e listagem de pastas. `conteudo/site.json` e
     os arquivos de histórico não podem ser baixados diretamente.
   - **Vídeos:** ao escolher o vídeo, o navegador extrai o primeiro frame com conteúdo (canvas) e
     envia como poster JPEG. Isso evita tela preta no iPhone. Os vídeos saem com `preload="none"`
     ou `"metadata"`, `playsinline` e o poster. Se o arquivo for HEVC (caixa `hvc1`/`hev1`), o
     painel avisa que o vídeo pode não tocar no Android ou no Windows e explica como gravar em
     "Mais compatível". Também avisa sobre arquivos grandes.
   - **Editor de antes/depois:** prévia ao vivo na mesma moldura do site. Para cada foto, zoom
     (slider e pinça) e posição (arrastar com o dedo). O formato salvo é compatível com o atual
     (width/height/left/top em %), para a migração ficar exata. Botão "voltar ao enquadramento
     automático".
   - Gerenciar: comparações antes/depois (criar, editar, reordenar, apagar, escolher se aparece
     na página inicial e/ou em resultados, categoria), vídeos de resultados, vídeo de
     apresentação, foto "sobre", textos das seções, procedimentos, cursos, depoimentos e contato.
   - Toda gravação é atômica (arquivo temporário + rename) e guarda as **20 últimas versões**
     do JSON, com o botão "Desfazer última alteração". Apagar sempre pede confirmação.
     Arquivos de mídia apagados vão para `conteudo/lixeira/` e não são apagados de vez.
   - `.user.ini` aumenta `upload_max_filesize` e `post_max_size` para vídeos (ex.: 256M).
4. **Guia para a dona:** `painel/ajuda` ou uma página simples dentro do painel com passo a passo
   em linguagem simples.
5. O site público não carrega nada do painel.
