# Como publicar o site (roteiro para o Vinicius)

Este roteiro vale para o staging (subdomínio de teste) e para a produção. Faça sempre no staging primeiro.

## Regra de ouro

**Publicar é só extrair o zip pelo Gerenciador de Arquivos da Hostinger.** Extrair por cima não apaga nada que não esteja no zip. É isso que protege as fotos e vídeos que a Gabriela enviou pelo painel.

**Nunca use** (substituem tudo e apagam o conteúdo dela):
- `hosting_deploy` (nem pela ferramenta da Hostinger no Claude)
- `hostinger up --yes`
- deploy automático pelo git (conectar o repositório ao hPanel)

Se alguém (ou alguma ferramenta) sugerir um desses, não faça.

## R1. Montar o pacote

No computador, na pasta do site:

```
php ferramentas/montar-publicacao.php
```

- Gera `publicar/site/` e `publicar/site-AAAAMMDD-HHMM.zip`.
- Mostra a lista de arquivos e o tamanho total. Confira se `conteudo/midia/` (só o `.htaccess`) e `acervo/` não têm nada além do esperado.
- Se aparecer "PACOTE RECUSADO", tem um arquivo fora da lista (por exemplo um `.md` dentro de `assets/`). O script diz qual. Tire o arquivo e rode de novo. Nada é gerado enquanto der erro.
- O zip é pesado (cerca de 16 MB) por causa dos vídeos. É normal.

## R2. Senha do painel (fora do public_html)

Só na primeira vez (ou quando quiser trocar a senha):

1. No computador:
   ```
   php ferramentas/criar-senha.php --saida=publicar/painel-config.php
   ```
   Digite a senha (mínimo 12 caracteres) e aperte Enter. O arquivo guarda só o hash, nunca a senha.
2. No Gerenciador de Arquivos da Hostinger, envie `painel-config.php` para a pasta **ao lado** de `public_html`, não dentro dela:
   `/home/u837316869/domains/studiogabrielasantos.com.br/painel-config.php`
   (para o staging, a pasta equivalente do subdomínio)
3. Na mesma pasta, crie a pasta vazia `painel-dados/`.
   O texto salvo, o histórico (Desfazer), a lixeira e as sessões ficam ali, fora do site.
4. Apague `publicar/painel-config.php` do computador. A senha em si só você e a Gabriela sabem; não mande por mensagem sem apagar depois.

Nunca coloque `painel-config.php` dentro de `public_html`.

## R3. Publicar e o conteúdo dela sobreviver

Onde fica o que ela cria:
- Textos e histórico: `painel-dados/` (fora do `public_html`).
- Fotos e vídeos enviados: `public_html/conteudo/midia/` (o zip nunca leva nada daqui).

Passo a passo:
1. Faça o backup (item R4, passo 1).
2. No Gerenciador de Arquivos, abra `public_html`, envie o zip e clique em **Extrair** (extrair aqui, sobrescrevendo). Depois apague o zip do servidor.
3. Confira que `conteudo/midia/` continua com as pastas e fotos dela.

### Teste obrigatório no staging: o conteúdo sobrevive?

1. No painel do staging, entre e crie um antes e depois e um vídeo de teste. Salve.
2. Anote o endereço de uma das fotos (clique com o botão direito na foto, copiar endereço da imagem).
3. Rode `php ferramentas/montar-publicacao.php` de novo e extraia o novo zip por cima, como no passo 2.
4. Confira: a foto ainda abre pelo endereço anotado, o antes e depois e o vídeo continuam no site, e o painel ainda entra com a mesma senha.
5. Se **sumiu**: restaure do backup, não publique em produção e use o plano B (abaixo).

**Plano B** (só se a mídia sumir): a mídia passa a ficar em `painel-dados/midia` e o PHP serve com Range. É um pouco mais lento, mas fica fora do `public_html`. Avise o Claude para implementar.

## R4. Checklist depois de publicar

1. **Backup antes**: baixe a pasta `painel-dados/` e a pasta `public_html/conteudo/midia/`.
2. **Extrair** o zip (R3).
3. **Limpar o cache da CDN** no hPanel.
4. **Rodar o verificador** no computador:
   ```
   php ferramentas/verificar-deploy.php https://staging.studiogabrielasantos.com.br
   ```
   (troque pelo endereço que está testando). Ele confere:
   - que os caminhos de desenvolvimento dão 403 ou 404 (`nucleo/`, `parciais/`, `dados/`, `ferramentas/`, `tests/`, `acervo/`, `.git/`, arquivos `.md`, `package.json`);
   - que scripts dentro de `conteudo/` não rodam;
   - os redirecionamentos 301 (`/index.php` para `/`, `/resultados.php` para `/resultados.html`);
   - os cabeçalhos de segurança;
   - que todo vídeo tem `preload="none"` e poster, e que cada poster abre;
   - que o `/painel/` tem noindex e proteção (CSP);
   - que o vídeo aceita Range (o iPhone precisa disso).

   Tem que terminar com `RESULTADO: OK`. Qualquer `[FALHA]` é motivo para parar e voltar atrás (R5). Avisos `[aviso]` num endereço local são normais; no staging não deve haver avisos de cabeçalho.
5. **No iPhone** (com a Gabriela, se der):
   - a home abre e os vídeos mostram a imagem de capa (nada de tela preta);
   - o comparador de antes e depois funciona com o dedo;
   - entrar no painel, trocar um texto e ver a mudança no site;
   - o botão **Desfazer** funciona.
6. **Lighthouse** mobile na home (Chrome, aba anônima, F12, Lighthouse, Celular). Metas: Desempenho 90 ou mais, SEO 95 ou mais, Acessibilidade 90 ou mais, Boas práticas 95 ou mais.

Só depois de tudo isso aprovado, repita no site de verdade.

## R5. Voltar atrás (rollback)

- **Guarde sempre o zip anterior** (a pasta `publicar/` do computador). Não apague o último zip que funcionou.
- **Voltar o código**: extraia o zip anterior por cima, no mesmo lugar. A mídia e o conteúdo dela não são tocados.
- **Voltar o conteúdo** (texto e fotos do site):
  - com o painel funcionando: Versões, escolha a versão e restaure (dá para desfazer);
  - com o painel fora do ar: no Gerenciador de Arquivos, copie `painel-dados/historico/NNNN.json` (o número mais alto que estava bom) por cima de `painel-dados/site.json`.
- **Último recurso**: o backup diário do hPanel.

## O que NÃO vai no zip (de propósito)

`acervo/`, `tests/`, `ferramentas/`, `node_modules/`, `.git`, `.local/`, os arquivos `.md`, `package.json` e tudo que está em `conteudo/midia/` (só os dois `.htaccess` de `conteudo/` vão).

## Resumo de uma linha

Backup, montar o zip, extrair por cima, limpar a CDN, rodar o verificador, testar no iPhone. Nada de deploy que substitui tudo.
