# MELHORIAS — Plano de execução (Fase 4)

Branch base: `melhorias-claude`. Cada lote roda num worktree próprio (`git worktree add ../sgs-lote-XX -b lote-XX melhorias-claude`). Quem junta os lotes na `melhorias-claude` é o orquestrador. **Merge em `main` e publicação no domínio só com aprovação do Vinicius.**

Regras que valem para todos os lotes (CLAUDE.md):
- Sem chave ou senha no código.
- Só PHP nativo (GD) e JS puro.
- Painel em português simples.
- Um commit por item, mensagem `ID: o que mudou`, com push.

Setup de cada worktree:
```
npm ci
php ferramentas/criar-senha.php --local
```
- `criar-senha.php` existe a partir do Lote 1.
- Gere `.auditoria/frame0-apresentacao.png` a partir do vídeo **original**:
  - `ffmpeg -y -i <original>/apresentacao.mp4 -frames:v 1 .auditoria/frame0-apresentacao.png`
  - O original fica em `assets/apresentacao.mp4` antes do 2C e em `acervo/originais/apresentacao.mp4` depois.

## 1. Itens priorizados (45 = 19 P0 · 19 P1 · 7 P2)

Os testes citados são os nomes em `tests/bugs.spec.js` (`-g "<ID>"`). Testes novos: `testar-nucleo` = `php ferramentas/testar-nucleo.php`; `painel.spec`, `midia.spec` e `integracao.spec` em `tests/`.

### P0
| ID | Lote | Critério de pronto |
|---|---|---|
| L01 | 2D+2C | bugs.spec L01 verde: pôster = frame 0 (diferença ≤5), aspect-ratio `576/1024` com JS desligado |
| L02 | 2D | bugs.spec L02 verde: 0 byte de mp4 sem play; resultados ≤1,5 MB |
| L07 | 1 | bugs.spec L07 verde. `verificar-migracao` confirma telefone, horário e endereço em um só lugar do JSON e 7 links wa.me gerados de `contato` |
| L08 | 1 | `verificar-migracao`: os 20 enquadramentos do HTML antigo (`git show 3c32a3b:resultados.html` e `:index.html`) saem iguais, como string, no `style` renderizado |
| L10 | 2B | midia.spec: `.mov` HEVC recusado com frase P4; H.264 4K recusado; vídeo com rotação salva largura e altura de exibição |
| L13 | 2A | painel.spec: 21 salvamentos deixam 20 versões; "Desfazer" restaura; mídia vai para a lixeira só quando nenhuma versão a usa; a lixeira restaura |
| L15 | 1 | site.spec com o mesmo resultado da linha de base. Os `<script type=ld+json>` passam em JSON.parse. testar-nucleo: texto com `</script>`, `<b>`, `"` e `&` sai escapado |
| S1 | 2D | bugs.spec S1 verde. Lighthouse A11y ≥95 nas duas páginas |
| P1 | 2A+2B, fecha no 3 | integracao.spec (iphone-chromium): adicionar foto, trocar vídeo e apagar aparecem no site depois de Salvar |
| P2 | 2A | painel.spec: arrasto e zoom mudam o enquadramento; "Voltar ao automático" = `enquadramento_auto()`; o site mostra os mesmos % (computed style) |
| T01 | 2C+2D | bugs.spec T01 verde (nenhuma foto >250 KB, largura natural adaptada, 1º comparador sem lazy e com fetchpriority=high) |
| T02 | 2D | = L02 |
| T03 | 2C | bugs.spec T03 verde (logo e hero ≤40.000 B; logo ≤2,5× a largura exibida); LCP ≤2,5 s no Lighthouse mobile |
| T07 | 3 | `montar-publicacao.php` falha se o pacote tiver arquivo fora da lista branca. `verificar-deploy.php <url>` dá 403/404 em `/package.json /tests/ /.auditoria/ /CLAUDE.md /MELHORIAS.md /node_modules/ /nucleo/config.php /dados/site.padrao.json /.git/ /acervo/ /ferramentas/` |
| T08 | 1+3 | Dados fora do `public_html` (testar-nucleo). No staging: salvar pelo painel → publicar de novo → o conteúdo e a mídia continuam (item marcado no checklist) |
| T09 | 1 | testar-nucleo e painel.spec: cookie HttpOnly + SameSite=Strict (+Secure fora da prévia); ID de sessão novo no login; expira com 120 min sem uso; `/painel/` com `noindex` e `no-store` |
| T10 | 1 | testar-nucleo: a 6ª tentativa em 15 min é bloqueada; `ip_cliente()` só usa cabeçalho de proxy se REMOTE_ADDR estiver em `proxies_confiaveis`. No staging, o diagnóstico (2A) mostra qual IP aparece |
| T12 | 2B | midia.spec: foto >40 MP recusada antes do GD; 24 MP passa com memory 256M; a saída não tem EXIF nem GPS (`exif_read_data` vazio) |
| T13 | 2B | midia.spec: depois de enviado, `ffprobe -show_format` do mp4 não tem `location` nem `©xyz` |

### P1
| ID | Lote | Critério |
|---|---|---|
| L03 | 2D | bugs.spec L03 verde (o toque no canto chega ao vídeo; no máximo 1 tocando) |
| L04 | 2D | bugs.spec L04 verde (arrasto com mouse e toque >65; faixa ≥44 px; "Depois" escondido em 100) |
| L05 | 2D | bugs.spec L05 verde (índice calculado no clique; onclick `scrollByCard('<grid>', 1)` mantido) |
| L09 | 2A | = P2, rodando no projeto iphone-webkit (gestos via eventos de toque) |
| L11 | 2B | midia.spec (chromium): pôster do navegador comparado ao frame 0 do ffmpeg, diferença ≤5 |
| L12 | 2B | midia.spec: JPEG com orientação 6 sai em pé, ≤1600 px, sem EXIF; HEIC cru recusado com frase P4 |
| L14 | 1+2A | testar-nucleo: salvar com revisão velha → 409. painel.spec: 2 abas, a segunda recebe "O site foi alterado em outra tela". Sessão expirada guarda rascunho no localStorage e oferece de volta depois do login |
| S2 | 2D | bugs.spec S2 verde (#996C55 → #7D5843 em fundo de botão e texto pequeno) |
| S3 | 2D | = L04, mais a alça visível na captura iphone-webkit |
| S4 | 2D | bugs.spec S4 verde |
| S5 | 2D | bugs.spec S5 verde (scroll-margin-top ≈104 px no celular) |
| P3 | 2A | painel.spec: apagar pede confirmação com o nome do item; "Desfazer" fica 10 s na tela; a lixeira restaura |
| P4 | 2A+2B | Cada código de erro da API mostra a frase da tabela P4 (painel.spec e midia.spec checam o texto) |
| P5 | 2A | painel.spec (iphone-webkit): alvos ≥44 px, inputs ≥16 px, sem rolagem horizontal; captura anexada |
| T05 | 1 | `verificar-deploy` na prévia e no staging: 301 de www, http, `/index.html` e `/*.php`; Cache-Control por tipo; `Options -Indexes`; cabeçalhos de segurança |
| T06 | 1 | canonical `/` e `/resultados.html` iguais aos de hoje; sitemap com as mesmas URLs; `/index.php` → 301 `/`; `/resultados.php` → 301 `/resultados.html` |
| T11 | 1+2A | POST sem `X-CSRF` → 403. O painel monta o DOM só com `textContent`. A CSP do painel bloqueia script inline. Payload XSS salvo aparece escapado no site |
| T14 | 1 | `/conteudo/midia/x.php` e `.html`/`.svg` → 403 sem executar. Só webp/jpg/png/mp4 são servidos. Dados fora do `public_html` |
| T15 | 2B | midia.spec: vídeo de 60 MB em partes (tamanho = min(5 MB, limite do servidor −10%)) passa e retoma depois de interrupção |

### P2
| ID | Lote | Critério |
|---|---|---|
| L06 | 2D | bugs.spec L06 verde |
| S6 | 2D | bugs.spec S6 verde. Setas abaixo dos cards com contador "2 de 7" (captura iphone) |
| S7 | 2D | bugs.spec S7 verde |
| S8 | 2D | bugs.spec S8 verde (`.reveal` só com `html.js`; reduced-motion zera a transição) |
| S9 | 2D | Captura de resultados com o botão WhatsApp de agendar depois da última categoria (texto em `resultados.cta_texto`) |
| P6 | 2A (+1 validação) | Contadores no painel. O servidor recusa texto acima do limite (testar-nucleo) |
| T04 | 2C (+1 adapta teste) | bugs.spec T04 verde lendo `dados/site.padrao.json` e os templates. `acervo/` fora do pacote |

## 2. Prontidão para publicar (5 itens)

**R1. Pacote por lista branca (T07).**
- `php ferramentas/montar-publicacao.php` gera `publicar/site/` e `publicar/site-AAAAMMDD-HHMM.zip` (os dois no `.gitignore`).
- Entram só: `index.php resultados.php 404.php .htaccess .user.ini robots.txt sitemap.xml assets/ dados/site.padrao.json nucleo/ parciais/ painel/ conteudo/.htaccess conteudo/midia/.htaccess`.
- `conteudo/midia/*` nunca entra.
- O script falha se aparecer qualquer outro arquivo e imprime a lista e o tamanho.
- O `.htaccess` também nega os caminhos de desenvolvimento (defesa dupla).

**R2. Senha fora do `public_html`.**
1. O Vinicius roda `php ferramentas/criar-senha.php --saida=publicar/painel-config.php`. O script:
   - roda só em CLI
   - pede uma senha de no mínimo 12 caracteres, digitada 2 vezes
   - grava só o hash (`password_hash`)
   - recusa a senha de teste local
2. Ele envia o arquivo pelo Gerenciador de Arquivos para `/home/u837316869/domains/studiogabrielasantos.com.br/painel-config.php`, ao lado de `public_html`.
3. Apaga a cópia local.
4. Cria a pasta `painel-dados/` no mesmo lugar.

**R3. O conteúdo da dona sobrevive.**
- `site.json`, `historico/`, `lixeira/`, sessões e tentativas ficam em `painel-dados/`, fora do `public_html`.
- A mídia fica em `public_html/conteudo/midia/`, que nunca vai no zip.
- Publicar = extrair o zip por cima, o que não apaga arquivos que não estão no zip.
- **Proibido** usar `hosting_deploy`, `hostinger up --yes` ou deploy pelo git, porque substituem o conteúdo.
- Teste obrigatório no staging: subir a mídia → publicar de novo → conferir se continua lá.
- Se falhar: `midia_dir` vai para `painel-dados/midia` e o PHP serve com Range (plano B, ver Riscos).

**R4. Checklist depois de publicar.**
1. Backup: baixar `painel-dados/` e `conteudo/midia/`.
2. Extrair o zip.
3. Limpar o cache da CDN no hPanel.
4. Rodar `php ferramentas/verificar-deploy.php https://<host>`. Ele confere 403/404, os 301, os cabeçalhos, `preload="none"`, que os pôsteres respondem 200 e que o `/painel/` tem noindex.
5. No iPhone:
   - a home abre e os vídeos mostram o pôster (sem preto)
   - o comparador funciona com o dedo
   - login no painel e troca de um texto aparecem no site
   - Desfazer funciona
6. Lighthouse mobile na home.

**R5. Voltar atrás (rollback).**
- Guardar sempre o zip anterior (`publicar/`).
- Para voltar o código: extrair o zip anterior por cima.
- Para voltar o conteúdo: no painel, Versões → restaurar. Se o painel estiver fora do ar, copiar `painel-dados/historico/NNNN.json` por cima de `painel-dados/site.json`.
- Último recurso: backup diário do hPanel.

## 3. Lote de assets (2C)

`ferramentas/otimizar-assets.php` (CLI, chama ffmpeg/ffprobe, roda só em desenvolvimento) faz tudo e grava `.auditoria/assets-depois.md` com o antes e depois em bytes e dimensões.

1. **Guardar os originais:** `git mv` de cada arquivo que será recomprimido para `acervo/originais/`, preservando o histórico.
2. **Vídeo de apresentação:** `assets/apresentacao.mp4`, alvo <8 MB.
   ```
   ffmpeg -i acervo/originais/apresentacao.mp4 -c:v libx264 -profile:v high -pix_fmt yuv420p -b:v 800k -maxrate 1000k -bufsize 2000k -preset slow -c:a aac -b:a 96k -movflags +faststart -map_metadata -1
   ```
   Manter 576×1024.
3. **Vídeos de resultados:**
   - Rodar `ffprobe` em cada um. Recodificar (mesmos parâmetros, ≤1080 px no lado maior) quem for HEVC, maior que 1080 ou com bitrate acima de 2,5 Mb/s.
   - `corrente-1.mp4` (rotação −90): recodificar para fixar a rotação nos pixels e gravar no JSON a largura/altura de exibição.
4. **Pôsteres do frame 0, todos novos:**
   ```
   ffmpeg -i X.mp4 -frames:v 1 -vf "scale='min(720,iw)':-2" -c:v libwebp -quality 78 assets/posteres/<nome>.webp
   ```
   - O ffmpeg já aplica a rotação.
   - Os `assets/img/*.jpg` antigos vão para `acervo/originais/img/`.
5. **Comparadores:** 800 px de largura, WebP q≈75, sem metadados.
   ```
   -vf scale=800:-2 -c:v libwebp -quality 75 -map_metadata -1
   ```
   Saída em `assets/results/<nome>.webp`. Resolve `drenagem-antes1` (260.026 B) e `drenagem-depois1` (257.944 B).
6. **Foto do ultrassom:** WebP com no máximo 1600 px e ≤250 KB.
7. **Logo:** `assets/logo-gs.webp` com 384 px de largura (2× os 192 exibidos), recortando a borda transparente do PNG de 1920×1080, ≤40 KB.
8. **Hero:** `assets/hero-bg.webp` com 1600 px (ou 1280 se passar de 40 KB), feito a partir de `hero-bg.png`. O `hero-bg.webp` atual (2.566 B, não usado) vai antes para `acervo/nao-usados/`.
9. **JSON:** em `dados/site.padrao.json`, trocar só `arquivo`, `poster`, `largura` e `altura`.
   - Os enquadramentos ficam iguais, porque são % do quadro e não dependem da resolução.
10. **Não usados:** `git mv` para `acervo/nao-usados/`, sem apagar:
    - `assets/logo.png`
    - `assets/hero-bg.webp` (o antigo)
    - `assets/results/corrente-3.mp4`
    - `assets/results/liposhape-antes3.jpeg`
    - `assets/results/liposhape-depois2.mp4`
    - Vão para "Sugestões que precisam do Vinicius".

Commits: T03 (logo + hero), T01 (fotos), L01 (pôsteres + apresentação), T04 (acervo).
Testes:
```
npx playwright test tests/bugs.spec.js -g "T01|T03|T04|L01"
php ferramentas/otimizar-assets.php --verificar
```

## 4. Esquema do JSON (`versao_esquema: 1`)

Arquivos:
- `dados/site.padrao.json` (versionado; espelha 1:1 o site atual)
- `painel-dados/site.json` (só no servidor; tem o mesmo formato)

**Regra de mesclagem:**
- Objetos se mesclam recursivamente: o editado vence e o campo que faltar vem do padrão.
- **Listas são substituídas inteiras.** O padrão só entra quando a chave não existe; assim um item apagado não volta.
- `versao_esquema` maior que a do código → usa só o padrão e registra erro.
- `conteudo_migrar()` sobe as versões antigas.

**IDs:** `^[a-z0-9-]{3,40}$`, únicos e nunca reaproveitados. Os migrados têm nome legível (`cmp-liposhape-1`); os novos usam `<tipo>-<8 hex>`.

**Textos:** sem HTML; a quebra de parágrafo vira item de lista. **Mídia:** caminho relativo sem query string (`assets/...` ou `conteudo/midia/AAAA/xxxx.webp`).

```json
{
  "versao_esquema": 1,
  "revisao": 0,
  "atualizado_em": "2026-11-16T00:00:00-03:00",
  "contato": {
    "whatsapp": "5562984141116",
    "whatsapp_exibicao": "(62) 98414-1116",
    "mensagem_padrao": "Olá! Gostaria de agendar um horário no Studio Gabriela Santos.",
    "instagram_usuario": "<igual ao HTML>",
    "endereco_linhas": ["<4 linhas do #contato>"],
    "endereco_curto": "Rua 01, Q.31, L.03 — Palmeiras 1",
    "endereco_schema": {"rua": "Rua 01, Q.31, L.03, Palmeiras 1", "cidade": "Goianésia", "uf": "GO", "pais": "BR"},
    "horario": "Segunda à sexta, das 8h às 17h",
    "horario_schema": "Mo-Fr 08:00-17:00",
    "geo": {"lat": -15.33235, "lng": -49.133907},
    "maps_link": "<igual>",
    "maps_embed": "<igual>",
    "avaliacao_online": "<igual>"
  },
  "seo": {"titulo": "", "descricao": "", "titulo_resultados": "", "descricao_resultados": "", "og_imagem": "assets/og-image.jpg"},
  "marca": {
    "logo_texto": {"arquivo": "assets/logo.svg", "largura": 0, "altura": 0},
    "logo_icone": {"arquivo": "assets/logo-gs.png", "largura": 1920, "altura": 1080, "alt": ""}
  },
  "home": {
    "hero": {"imagem": {"arquivo": "assets/hero-bg.png", "largura": 1920, "altura": 1080}, "titulo": "", "texto": "", "botao_principal": "", "botao_secundario": ""},
    "boas_vindas": {"paragrafos": ["", ""]},
    "sobre": {
      "foto": {"arquivo": "assets/sobre-foto.webp", "largura": 690, "altura": 920, "alt": ""},
      "rotulo": "", "titulo": "", "paragrafos": ["", ""],
      "numeros": [{"id": "num-1", "valor": "", "legenda": ""}],
      "video": {"arquivo": "assets/apresentacao.mp4", "poster": "assets/sobre-foto.webp", "largura": 576, "altura": 1024},
      "video_paragrafos": ["", "", ""],
      "saiba_mais": ""
    },
    "procedimentos": {"rotulo": "", "titulo": "", "itens": [{"id": "proc-1", "titulo": "", "texto": ""}]},
    "resultados": {"rotulo": "Transformações reais", "titulo": "", "texto_ver_mais": ""},
    "cursos": {
      "rotulo": "Formação profissional", "titulo": "", "descricao": "",
      "itens": [{"id": "curso-metodo", "titulo": "", "carga_horaria": "Carga horária: 10 horas", "descricao": "",
                 "mensagem_whatsapp": "Olá! Quero saber mais sobre o curso Método Gabriela Santos.",
                 "conteudo": ["7 itens"], "modulos": ["5 itens"]}]
    },
    "depoimentos": {"rotulo": "O que dizem", "titulo": "Depoimentos de clientes",
                    "itens": [{"id": "dep-1", "texto": "", "autor": "Cliente do studio", "servico": ""}]},
    "contato_secao": {"rotulo": "", "titulo": "Agende seu horário"}
  },
  "resultados": {
    "titulo_pagina": "Todos os resultados",
    "cta_texto": "Quer um resultado assim? Agende pelo WhatsApp",
    "categorias": [{"id": "liposhape", "nome": "<h2 exato>", "ordem": 1}],
    "itens": [
      {"id": "cmp-liposhape-1", "tipo": "comparacao", "categoria": "liposhape", "titulo": "<p da home>",
       "em_resultados": true, "ordem": 1, "na_home": true, "ordem_home": 1,
       "antes":  {"arquivo": "assets/results/liposhape-antes1.jpeg", "largura": 1600, "altura": 1537, "alt": "",
                  "enquadramento": {"width": 138.799, "height": 100, "left": 50, "top": 50, "automatico": false}},
       "depois": {"arquivo": "assets/results/liposhape-depois1.jpeg", "largura": 1506, "altura": 1600, "alt": "",
                  "enquadramento": {"width": 125.5, "height": 100, "left": 45.888, "top": 50, "automatico": false}}},
      {"id": "vid-liposhape-1", "tipo": "video", "categoria": "liposhape", "rotulo": "Resultado",
       "arquivo": "assets/results/liposhape-depois3.mp4", "poster": "assets/img/99c58818.jpg",
       "largura": 0, "altura": 0, "em_resultados": true, "ordem": 2, "na_home": false, "ordem_home": 0},
      {"id": "foto-ultrassom-1", "tipo": "foto", "categoria": "ultrassom", "rotulo": "Antes", "ajuste": "contain",
       "arquivo": "assets/results/ultrassom-antes1.jpeg", "largura": 1284, "altura": 1116, "alt": "",
       "em_resultados": true, "ordem": 1, "na_home": false, "ordem_home": 0}
    ]
  }
}
```

**Regras:**
- `enquadramento`:
  - `left`/`top` = centro da foto em %, com `transform: translate(-50%,-50%)` num quadro 3:4 sem object-fit.
  - Os valores migrados são copiados **sem arredondar**. O editor grava com no máximo 3 casas.
  - **Automático:** r = largura/altura. Se r > 0,75: `height=100` e `width=100·r/0,75`. Senão: `width=100` e `height=75/r`. `left=top=50`.
  - **Limite:** `|left−50| ≤ (width−100)/2` (e o mesmo para `top`), aplicado só quando a dona edita. `width`/`height` entre 100 e 400.
  - Valores atuais:

    | # | Depois (w/h/left/top) | Antes (w/h/left/top) |
    |---|---|---|
    | 1 Liposhape | 125.5/100/45.888/50 | 138.799/100/50/50 |
    | 2 Drenagem | 101.015/102.814/50.508/48.554 | 106.896/108.708/51.524/53.216 |
    | 3 Drenagem | 113.869/132.921/50.062/46.914 | 100/104.439/50/50 |
    | 4 Limpeza | 154.652/100/41.775/50 | 152.449/100/50/50 |
    | 5 Limpeza | 162.121/100/52.661/50 | 179.27/110.892/58.562/44.554 |

- **Foto `ajuste:"contain"` (ultrassom):** é um modelo à parte, `object-fit:contain; object-position: calc(50% - 0.86%) 50%`. Fica guardado em `posicao_x: -0.86` (ajuste em %).
- **Home:** só `tipo:"comparacao"` com `na_home:true`, ordenados por `ordem_home`, no máximo 5 (a 6ª célula é "Ver mais").
  - Hoje: os 5 comparadores, 1 a 5.
  - O executor confere com o HTML antigo; se a home tiver outro conjunto, vale o HTML.
- **Resultados:** categorias por `ordem`. Dentro de cada uma, os itens com `em_resultados:true` por `ordem`. Categoria vazia não aparece.
  - Colunas da grade = `min(n,3)`. Corrige o "Corrente", que tem 2 itens e 3 colunas.
  - Conjunto atual:
    - Liposhape: comparador, vídeo `liposhape-depois3`, vídeo `liposhape-pos`
    - Drenagem: 2 comparadores
    - Limpeza: 2 comparadores
    - Ultrassom: foto `ultrassom-antes1` + vídeo `ultrassom-depois1` "Depois"
    - Corrente: vídeos `corrente-1` e `corrente-2`
- **Vídeo:**
  - `aspect-ratio` escrito como `largura/altura`, sem simplificar (576/1024).
  - Sempre `preload="none" playsinline controls`, com `poster` obrigatório.
- **Limites (P6):** usar os da tabela P6 da auditoria. Se faltar algum, usar:

  | Campo | Limite |
  |---|---|
  | titulo | 80 |
  | rotulo | 40 |
  | parágrafo | 600 |
  | depoimento | 400 |
  | itens | procedimentos 12 · depoimentos 12 · cursos 4 · resultados 60 · categorias 10 |

- **`revisao`:** inteiro que sobe a cada salvamento; serve para o 409.

## 5. Interface PHP compartilhada (fixa no Lote 1; a Onda 2 só consome)

Todo arquivo em `nucleo/` começa com `defined('RAIZ') || exit;`. `RAIZ` é definida em `index.php`, `resultados.php`, `404.php` e `painel/*.php`.

**`nucleo/config.php`**
- `config(): array` devolve as chaves `senha_hash, dados_dir, midia_dir, midia_url, ambiente('local'|'staging'|'producao'), cookie_secure, ip_cabecalho(?string), proxies_confiaveis(array), sessao_minutos`.
- Onde procura:
  - Se `PHP_SAPI==='cli-server'`: **só** em `RAIZ/.local/painel-config.php`.
  - Senão: **só** em `dirname(RAIZ)/painel-config.php`.
- Sem config: o site público renderiza o padrão; o painel mostra "Painel ainda não configurado".
- `midia_dir` padrão = `RAIZ/conteudo/midia`; `midia_url` padrão = `conteudo/midia`.

**`nucleo/conteudo.php`**
- `conteudo_padrao(): array`
- `conteudo_ler(): array`: padrão mesclado com o editado e migrado, com cache na requisição.
- `conteudo_mesclar(array $padrao, array $editado): array`
- `conteudo_migrar(array $c): array`
- `conteudo_validar(array $c): array`: lista de `['campo'=>..., 'mensagem'=>pt-BR]`.
- `conteudo_salvar(array $c, int $revisao_esperada, string $resumo): array`
  - Devolve `['ok'=>true,'revisao'=>n]`, `['ok'=>false,'erro'=>'conflito']` ou `['ok'=>false,'erro'=>'invalido','detalhes'=>[...]]`.
  - Usa flock em `dados_dir/trava.lock`, grava `tmp` e faz `rename`.
  - Cria `historico/NNNN.json` e mantém 20.
  - Manda para a lixeira a mídia que nenhuma versão referencia mais.
- `conteudo_versoes(): array`: `[{n, data, resumo}]`.
- `conteudo_restaurar(int $n, int $revisao_esperada): array`: gera uma versão nova.
- `conteudo_midias_usadas(array $c): array`
- `lixeira_mover(string $arquivo_relativo): bool`, `lixeira_listar(): array`, `lixeira_restaurar(string $id): bool`, `lixeira_esvaziar(int $dias = 30): int`
- `enquadramento_auto(int $largura, int $altura): array`
- `enquadramento_limitar(array $e): array`

**`nucleo/render.php`**
- `e(?string $s): string`: `htmlspecialchars(ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')`.
- `num(float $v): string`: `json_encode` com `serialize_precision=-1`, a menor forma exata. 45.888 sai "45.888"; 100 sai "100".
- `url_midia(string $arquivo): string`: caminho relativo, sem query.
- `link_whatsapp(array $contato, ?string $mensagem = null): string`: `https://wa.me/<n>?text=`.rawurlencode.
- `estilo_enquadramento(array $e): string`: `width:..%;height:..%;left:..%;top:..%`.
- `parcial(string $nome, array $vars = []): void`: inclui `parciais/<nome>.php`; o nome é validado com `^[a-z0-9-]+$`.
- `jsonld(array $dados): string`: `json_encode(JSON_HEX_TAG|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)`.
- `jsonld_negocio(array $c): array`, `jsonld_breadcrumb(): array`
- `resultados_home(array $c): array`, `resultados_por_categoria(array $c): array`: `[['categoria'=>..., 'itens'=>[...], 'colunas'=>min(n,3)]]`.

**`nucleo/seguranca.php`**
- `sessao_iniciar(): void`: `session_save_path(dados_dir/sessoes)`, cookie `__Host-` só com Secure, `HttpOnly`, `SameSite=Strict`, `use_strict_mode`, expira por inatividade.
- `sessao_logada(): bool`
- `sessao_entrar(string $senha): array`: `['ok'=>true]` ou `['ok'=>false,'erro'=>'senha'|'bloqueado','espera_min'=>int]`. Em caso de sucesso chama `session_regenerate_id(true)`.
- `sessao_sair(): void`
- `exigir_login_api(): void`: 401 em JSON com `erro:'sessao_expirada'`.
- `csrf_token(): string`, `csrf_verificar(): void`: cabeçalho `X-CSRF` + `hash_equals`; senão 403.
- `ip_cliente(): string`
- `tentativas_ok(string $ip): bool`: 5 a cada 15 min, guardado em `dados_dir/tentativas.json`.
- `responder_json(array $d, int $status = 200): never`
- `cabecalhos_painel(): void`: CSP `default-src 'self'; img-src 'self' blob: data:; media-src 'self' blob:; script-src 'self'; style-src 'self'; frame-ancestors 'none'`, mais `X-Robots-Tag: noindex`, `Cache-Control: no-store` e nosniff.

**`nucleo/midia.php` (Lote 2B)**
- `midia_foto_salvar(string $tmp, string $nome_original): array`
  - Devolve `['arquivo','largura','altura']` ou `['erro'=>codigo]`.
  - Passos:
    - finfo: só jpeg, png ou webp
    - ≤40 MP lido com `getimagesize` antes do GD
    - orientação EXIF
    - GD redimensiona para ≤1600 (comparador: 800)
    - WebP q80, ou JPEG se não houver `imagewebp`
    - sem metadados
    - nome aleatório `bin2hex(random_bytes(8))`
- `midia_video_iniciar(int $tamanho, string $nome): array`: `['envio_id','tamanho_parte']`.
- `midia_video_parte(string $envio_id, int $indice, string $tmp): array`
- `midia_video_concluir(string $envio_id): array`
  - Devolve `['arquivo','largura','altura','duracao']` ou `['erro'=>...]`.
  - Lê os átomos do MP4: `ftyp` mp4/qt; codec `avc1` (`hvc1`/`hev1` → `erro:'hevc'`); `tkhd` para tamanho e rotação; lado maior ≤1080 (senão `erro:'grande_demais'`).
  - Neutraliza `udta`/`meta`/`©xyz` trocando o tipo do átomo por `free`, sem mudar o tamanho.
- `midia_poster_salvar(string $tmp, string $video_arquivo): array`
- Códigos de erro: chaves da tabela P4. As frases ficam em `painel/midia.js` e `painel/painel.js`.

**JS entre 2A e 2B:**
- `painel/midia.js` expõe `window.PainelMidia = { escolherFoto(opcoes) → Promise<{arquivo,largura,altura}>, escolherVideo(opcoes) → Promise<{arquivo,poster,largura,altura}>, mensagemErro(codigo) → string }`.
  - A UI de progresso fica dentro dele.
  - Recebe `{csrf, onProgresso}`.
- `painel/index.php` (2A) carrega `midia.js` antes de `painel.js` com `defer`.

**APIs:**
- `painel/api.php` (2A): POST JSON `{acao}`, com `acao ∈ ler | salvar | versoes | restaurar | lixeira | lixeira_restaurar | sair | diagnostico_ip`.
- `painel/api-midia.php` (2B): POST `acao ∈ foto | video_iniciar | video_parte | video_concluir | poster`.
- Os dois começam com `sessao_iniciar(); exigir_login_api(); csrf_verificar(); cabecalhos_painel();`.

## 6. Ondas e lotes

### Onda 1: Lote 1 "Fundação" (sozinho; os outros esperam o merge)
**Cria:**
- `nucleo/{config,conteudo,render,seguranca}.php`
- `parciais/{head,cabecalho,rodape,comparador,video,foto,estilo-home,estilo-resultados,script-home,script-resultados}.php` (CSS e JS continuam inline, como hoje)
- `index.php`, `resultados.php`, `404.php`, `.htaccess`, `.user.ini` (`upload_max_filesize=64M`, `post_max_size=70M`, `memory_limit=256M`, `max_execution_time=120`), `conteudo/.htaccess`, `conteudo/midia/.htaccess`
- `dados/site.padrao.json`
- `ferramentas/{roteador.php,previa.mjs,criar-senha.php,testar-nucleo.php,verificar-migracao.php}`

**Edita:**
- `.gitignore`: adiciona `.local/`, `publicar/`, `painel-config*.php`, `conteudo/midia/*` e `!conteudo/midia/.htaccess`
- `.claude/launch.json`: `"runtimeExecutable":"npm","runtimeArgs":["run","previa"]`
- `playwright.config.js`: `webServer: {command:'npm run previa', url:'http://localhost:8765/', reuseExistingServer:true}`
- `package.json`: script `"previa":"node ferramentas/previa.mjs"`
- `tests/bugs.spec.js`:
  - T04 passa a ler o JSON e os templates.
  - A largura do T01 usa `naturalWidth` no navegador em vez do SOF, que dava 0 em WebP. Limite inalterado.

**Remove:** `index.html` e `resultados.html`, depois de gravar a linha de base.

**Primeiro passo:** gravar a linha de base do site.spec no commit 3c32a3b, com o servidor antigo, em `.auditoria/linha-de-base.txt`.

**Commits (em ordem):**
1. `infra: prévia PHP com roteador e config local`
2. `L08: site.padrao.json com enquadramentos 1:1`
3. `L15: index.php e resultados.php renderizados do JSON` (inclui a adaptação do T04)
4. `L07: contato e horário em um só lugar`
5. `T06: canonical, 404 e redirecionamentos`
6. `T05: .htaccess de cache, URL única e cabeçalhos`
7. `T14: conteudo/ sem execução e dados fora do public_html`
8. `T09: sessão do painel`
9. `T10: limite de tentativas e IP atrás da CDN`
10. `T11: CSRF e escape`
11. `L14: revisão e 409`
12. `T08: dados do painel fora do public_html`

**Regras para o 1:1:**
- Nenhuma correção visual neste lote; o HTML renderizado deve bater com o antigo.
- Exceção: o L07 unifica o horário.
- Manter todos os ids e seletores que os testes usam: inline `onclick` do `scrollByCard('<grid>', 1)` e `toggleCurso`, o range como filho direto do quadro, o `.ba-clip` com clip-path inline.

**Testes:**
```
php ferramentas/testar-nucleo.php
php ferramentas/verificar-migracao.php
npx playwright test tests/site.spec.js
npx playwright test tests/bugs.spec.js -g "L07|T04"
```
O site.spec deve dar o mesmo resultado da linha de base.

### Onda 2: 4 lotes em paralelo (nenhum arquivo em comum)

**2A Painel**
- Cria: `painel/index.php`, `api.php`, `painel.js`, `painel.css`, `ajuda.php`, `.htaccess`, `tests/painel.spec.js`
- Commits:
  1. `T11: shell do painel com CSP e login`
  2. `P6: edição de textos com limites`
  3. `P2/L09: editor de enquadramento`
  4. `L13: versões e desfazer`
  5. `P3: apagar com confirmação e lixeira`
  6. `L14: aviso de outra tela e rascunho`
  7. `P4: mensagens`
  8. `P5: ajustes iPhone`
  9. `T10: diagnóstico de IP`
- Editor de enquadramento:
  - Pointer Events com 1 dedo arrastando e 2 dedos em pinça.
  - Botões −/+ e as 4 setas como alternativa.
  - Botão "Voltar ao automático".
  - A prévia usa o mesmo CSS do site.
- Envio de mídia nos testes: stub de `PainelMidia`.
- Teste: `npx playwright test tests/painel.spec.js`

**2B Mídia**
- Cria: `nucleo/midia.php`, `painel/api-midia.php`, `painel/midia.js`, `ferramentas/gerar-fixtures.php`, `tests/midia.spec.js`, `tests/fixtures/midia/*`
- Fixtures, cada uma ≤300 KB, geradas por ffmpeg e PHP:
  - h264 com location
  - hevc .mov
  - 4K curto
  - rotação 90
  - JPEG com orientação 6 + GPS (APP1 escrito em bytes)
  - PNG
  - .php renomeado para .jpg
- Commits:
  1. `L12: fotos (orientação, redução no navegador, HEIC)`
  2. `T12: GD seguro e sem EXIF`
  3. `L10: vídeo do iPhone (HEVC, rotação, 4K)`
  4. `T13: remove localização do vídeo`
  5. `T15: envio em partes com retomada`
  6. `L11: pôster do frame 0 no navegador`
  7. `P1: escolher, trocar e apagar mídia`
  8. `P4: frases de erro de mídia`
- Navegador:
  - Fotos: `createImageBitmap` → canvas ≤2400 px (abaixo do limite de canvas do iOS) → JPEG 0,9.
  - Pôster: `currentTime=0.001`, `seeked`, canvas → WebP/JPEG.
- Testes:
  ```
  php ferramentas/testar-nucleo.php midia
  npx playwright test tests/midia.spec.js
  ```
  O L11 roda no projeto chromium.

**2C Assets**
- Ver a seção 3. Arquivos: `assets/**`, `acervo/**`, `dados/site.padrao.json`, `ferramentas/otimizar-assets.php`, `.auditoria/assets-depois.md`.
- **Só ele toca no JSON na Onda 2.**

**2D Site público**
- Edita: `parciais/*.php`, `index.php`, `resultados.php`, `404.php`, `tests/site.spec.js`
- No site.spec: as linhas 267 e 319 trocam `div[onclick]` por `button.video-play`, com a mesma intenção.
- Commits:
  1. `L01: caixa do vídeo com proporção fixa`
  2. `L02: preload none`
  3. `L03: botão de play no centro, sem camada por cima`
  4. `L04: comparador com Pointer Events e nome acessível`
  5. `S3: alça visível`
  6. `L05: índice do carrossel no clique`
  7. `L06: estados do menu e dos cursos`
  8. `S7: foco visível`
  9. `S1: main e contraste do CTA`
  10. `S2: contrastes`
  11. `S4: alvos de 44 px`
  12. `S5: scroll-margin`
  13. `S6: setas abaixo e contador`
  14. `S8: reduced motion`
  15. `S9: chamada no fim de resultados`
  16. `T01: primeiro comparador sem lazy`
- Detalhes:
  - L03: botão central de 64 px; pausar os outros vídeos no play; o botão some com o vídeo tocando.
  - L04: `touch-action: pan-y` e rótulos escondidos abaixo de 15%.
- Testes:
  ```
  npx playwright test tests/bugs.spec.js -g "L0[1-6]|S[1-8]|T01|T02"
  npx playwright test tests/site.spec.js
  ```

**Merge da Onda 2:** o orquestrador junta na `melhorias-claude` na ordem 2D, 2C, 2B, 2A e roda `npx playwright test` completo.

### Onda 3: Lote 3 "Integração e publicação"
- Cria: `ferramentas/montar-publicacao.php`, `ferramentas/verificar-deploy.php`, `tests/integracao.spec.js`, `PUBLICACAO.md` (R1–R5 + checklist + rollback), `.auditoria/performance-depois.md`
- Commits:
  1. `T07: pacote por lista branca`
  2. `R4: verificador pós-deploy`
  3. `P1: teste de ponta a ponta do painel`
  4. `T08: roteiro de staging e sobrevivência do conteúdo`
  5. `medição final`
- Testes:
  ```
  npx playwright test
  php ferramentas/montar-publicacao.php
  php ferramentas/verificar-deploy.php http://localhost:8765
  ```
  Mais Lighthouse mobile (via `npx -y lighthouse`, só como ferramenta) comparado com `orcamento.md`.

### Onda 4: Vinicius (não é executor)
1. Prévia local aprovada.
2. Criar o subdomínio de staging e a config fora do `public_html`.
3. Publicar no staging, rodar o checklist e o teste de sobrevivência.
4. Aprovar a produção.
5. Fazer o merge em main.

## 7. Prévia local completa (site + painel)

```
npm ci && npx playwright install
php ferramentas/criar-senha.php --local
npm run previa
```

**`criar-senha.php --local`:**
- Cria `.local/painel-config.php` com `ambiente: 'local'`, `cookie_secure: false` e `dados_dir: .local/painel-dados`.
- Gera uma senha aleatória e grava em `.local/senha-teste.txt`. Os testes leem esse arquivo; nenhuma senha fica no código.
- Tudo dentro de `.local/` está no `.gitignore`.

**`npm run previa`:**
- `previa.mjs` acha o php no PATH e roda:
  ```
  php -d extension_dir=<pasta-do-php>/ext -d extension=gd -d extension=exif -d upload_max_filesize=8M -d post_max_size=12M -d memory_limit=256M -S 0.0.0.0:8765 ferramentas/roteador.php
  ```
- O php.ini do WinGet tem `extension_dir="ext"`, que é relativo; por isso o caminho é passado inteiro.

**Abrir:** `http://localhost:8765/`, `/resultados.html` e `/painel/`. No iPhone, na mesma rede, usar `http://<ip-do-pc>:8765/`.

**`roteador.php`:**
- `/` e `/index.html` → `index.php`
- `/resultados.html` → `resultados.php`
- `/index.php` → 301 `/`; `/resultados.php` → 301 `/resultados.html`
- `/painel/` → `painel/index.php`
- Só executa PHP em `painel/*.php`
- 404 para `nucleo/ parciais/ dados/ ferramentas/ tests/ acervo/ .local/ .git/ node_modules/ publicar/`, `*.md` (exceto `.auditoria/`, que os testes leem) e `package*.json`
- 403 para script em `conteudo/`
- Serve `.mp4` com **Range/206** (o `php -S` não faz isso e o Safari/WebKit precisa)
- MIME explícito para webp e mp4
- Fora disso, `return false`

## 8. Sugestões que precisam do Vinicius
- **Arquivos não usados.** Ficam em `acervo/nao-usados/`, fora do deploy:
  - `logo.png`
  - `hero-bg.webp` antigo
  - `corrente-3.mp4`
  - `liposhape-antes3.jpeg`
  - `liposhape-depois2.mp4`
  - Apagar de vez? Usar algum (por exemplo, `corrente-3` como 3º vídeo do Corrente)?
- Os originais em `acervo/originais/` continuam no git. Vale manter?
- Comparadores com 800 px: se ficarem moles no iPhone com zoom, subir para 1200 (ainda ≤250 KB?).
- Ativar HSTS depois de 1 semana de HTTPS estável.
- Criar o staging (subdomínio) e confirmar se a conta tem SSH.
- Revisar os textos da página de ajuda do painel e a frase do botão do S9.

## 9. Riscos
- **Deploy que substitui tudo apaga a mídia da dona (T08).** Mitigação: só zip; teste no staging; backup antes.
  - Plano B: `midia_dir` em `painel-dados/midia`, servido por `nucleo/servir-midia.php` com Range e cache longo. Custa um pouco de PHP por pedido.
- **A CDN (hcdn) pode guardar HTML velho** depois que a dona salva. Mitigação: HTML com `Cache-Control: no-cache` e mídia com nome novo a cada envio. Testar no staging; se não resolver, configurar a CDN para não guardar HTML.
- **Diferenças do LiteSpeed no `.htaccess`** (`RemoveHandler`, `php_flag`). Mitigação: `verificar-deploy` no staging.
- **Parser de MP4:** átomos de 64 bits, `moov` no fim e MP4 fragmentado. Mitigação: fixtures de cada caso; o que não for reconhecido é recusado com mensagem, nunca aceito "no escuro".
- **iOS Safari:** limite de memória do canvas e HEIC (o Safari normalmente entrega JPEG no `<input>`). Mitigação: `createImageBitmap` ≤2400 px; HEIC cru é recusado com explicação.
- **Playwright WebKit no Windows** não toca H.264 de forma confiável. Mitigação: testes de vídeo no Chromium e checagem manual no iPhone (R4).
- **flock e sessões em hospedagem compartilhada.** Mitigação: tudo em `painel-dados`; sessão com caminho próprio.
- **A migração 1:1 quebrar seletores dos testes.** Mitigação: linha de base gravada e `verificar-migracao`.

## 10. Fora do escopo
- Redesign, seções novas, blog, vários usuários ou permissões no painel.
- Converter HEVC no servidor (não há ffmpeg na hospedagem); recortar vídeo.
- CDN de imagens; `lastmod` automático no sitemap; HSTS preload; analytics.
- Publicar no domínio e fazer merge em main (é do Vinicius).
- Apagar qualquer asset.
