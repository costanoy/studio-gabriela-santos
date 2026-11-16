# Revisão do diff 6bcd1068..HEAD (Fase 6, revisor Opus)

O revisor trabalha só lendo. Este arquivo foi gravado pelo orquestrador a partir do relatório dele.

**Resultado:** nenhum P0. Encontrou 2 P1 e 9 P2.

## P1

1. **Foto "apagada" continua pública (LGPD).**
   - Onde: `nucleo/conteudo.php:517-542`, `painel/painel.js:1312-1338`, `conteudo/midia/.htaccess:24`.
   - O item sai do JSON, mas o arquivo fica em `public_html/conteudo/midia/...` com cache immutable. Ele só vai para a lixeira quando a versão sai do histórico, ou seja, depois de 20 salvamentos.
   - Correção: ao salvar, tirar do public_html a mídia que o conteúdo atual não usa, guardando uma cópia privada para restaurar depois.
2. **Vídeo recusado só depois do envio inteiro.**
   - Onde: `painel/midia.js:447,518-520`, `nucleo/midia.php:626-658`.
   - Codec HEVC e tamanho acima de 1080p só são checados em `video_concluir`. Se a capa falhar, o envio continua, termina em `poster_falhou` e deixa um arquivo órfão.
   - Correção: checar antes de enviar e abortar quando não houver capa.

## P2

3. **Campos que o painel não edita não são validados.** `maps_embed`, `maps_link`, `seo.*`, `marca.*` etc. aceitam `javascript:`, um iframe de outro domínio ou um tipo errado, que derruba a home com 500.
   - Onde: `conteudo.php:187-406`.
   - Correção: validar tipo e esquema `https://`; para o iframe, só `https://www.google.com/maps`.
4. **Limite de tentativas de senha pode ser contornado.** A conferência e o registro usam travas separadas, então pedidos em paralelo passam juntos. Também falta agrupar IPv6 por /64 e um limite global.
   - Onde: `seguranca.php:84-92,247-259`.
5. **Proxy promete CIDR, mas a comparação é exata.**
   - Onde: `criar-senha.php:77` e `seguranca.php:194,199`.
6. **Restaurar da lixeira não traz a foto de volta ao site.**
   - Onde: `conteudo.php:626-646`, `painel.js:1362-1368`.
7. **Caminho de mídia aceita arquivo oculto**, como `conteudo/midia/.htaccess`.
   - Onde: `conteudo.php:209,432-441`.
   - Correção: exigir o formato `AAAA/<hex>.(webp|jpg|mp4)`.
8. **Recuperar rascunho passa por cima do 409.**
   - Onde: `painel.js:206-219`.
   - Correção: pedir uma confirmação explícita.
9. **Envios nunca usados ficam no servidor para sempre.**
   - Onde: `midia.php:65-79,663-672`.
10. **Sessão:**
    - não há tempo máximo absoluto;
    - toda visita anônima cria um arquivo de sessão;
    - `api-midia` não chama `session_write_close()` depois do CSRF.
    - Onde: `seguranca.php:33-71`, `api-midia.php:138-191`.
11. **Arquivo pode ficar preso na lixeira** se o processo cair entre o `rename` e a gravação do `meta.json`.
    - Onde: `conteudo.php:602-607`.

## Suspeitas que precisam de teste

- No iOS, a capa desenhada em `loadeddata` pode sair preta (`midia.js:368-377`). Testar no iPhone real ou buscar `currentTime=0.001` e esperar `seeked`.
- Conferir se o LiteSpeed respeita o `FilesMatch` com lookahead e o `RemoveHandler`.
- Conferir a limpeza automática das sessões (gc).
- Conferir o `REMOTE_ADDR` atrás da CDN.

## Conferido e correto

- **Login e sessão:** CSRF com `hash_equals`, regenerate no login, `use_strict_mode`, cookie `__Host-` com HttpOnly, Secure e SameSite=Strict, CSP.
- **Fotos:** tipo conferido por finfo e foto refeita pelo GD, que remove EXIF e GPS.
- **Vídeos:**
  - as caixas udta, meta e uuid são zeradas;
  - os `<video>` têm `poster`, `playsinline` e `preload="none"`.
- **Caminhos:** regex nos IDs e recusa de `..`.
- **Escape:** feito por `e()` e JSON_HEX_TAG.
- **Publicação e segredos:** o zip é montado por lista branca e não há segredos no código.
- **Gravação:** atômica, com a checagem de 409 dentro da trava.
- **Orçamento:** o JS do painel tem 95 KB, abaixo do limite de 150 KB.
