# Medição de performance — DEPOIS (Lote 3, Onda 3)

Data: 2026-11-16. Prévia local (`PORTA=8790 npm run previa`, PHP embutido, sem gzip e sem CDN), `npx -y lighthouse` (sem adicionar dependência), emulação padrão de celular (Moto G Power, 4G simulado), categorias performance, seo, accessibility, best-practices.
Relatórios completos: `lh-depois-index-mobile.json` e `lh-depois-resultados-mobile.json` (nesta pasta, ignorados pelo git). O "antes" vem de `performance-antes.md` (`lh-index-mobile.json`, `lh-resultados-mobile.json`).
Limite desta medição: é localhost. O LCP real depende da Hostinger e da CDN; repita o Lighthouse no staging (PUBLICACAO.md, R4 passo 6).

## Orçamento x antes x depois (celular)

| Métrica | Limite | index antes | index depois | resultados antes | resultados depois |
|---|---|---|---|---|---|
| Performance | ≥ 90 | 91 | **97 OK** | 31 FALHA | **98 OK** |
| SEO | ≥ 95 | 100 | **100 OK** | 100 | **100 OK** |
| Acessibilidade | ≥ 90 | 87 FALHA | **100 OK** | 85 FALHA | **100 OK** |
| Boas práticas | ≥ 95 | 100 | **100 OK** | 100 | **100 OK** |
| LCP | ≤ 2,5 s | 3,5 s FALHA | **2,49 s OK (margem de 0,007 s)** | 6,6 s FALHA | **2,42 s OK** |
| CLS | ≤ 0,1 | 0 | **0 OK** | 0,006 | **0 OK** |
| TBT | sem limite | 70 ms | 0 ms | 10.090 ms | 0 ms |
| FCP | sem limite | 1,4 s | 1,3 s | 3,6 s | 0,8 s |
| Peso transferido, 1ª carga (Lighthouse) | ≤ 1,5 MB | 436 KB | **266 KB OK** | 4.834 KB FALHA | **714 KB OK** |
| HTML da página (sem gzip) | ≤ 120 KB | 74.502 B | **82.587 B OK** | ~32 KB | **40.232 B OK** |

## Estouros de orçamento (P0)

Nenhum estouro nas páginas públicas. Observações:
- **LCP da home: 2,493 s contra o limite de 2,5 s.** Passa, mas sem folga. Na Hostinger (rede real, CDN) pode variar para os dois lados; conferir no staging. Se passar de 2,5 s lá, é P0.
- O HTML da home cresceu de 74,5 KB para 82,6 KB (continua ≤120 KB) porque agora é gerado pelo PHP a partir do JSON.

## Peso sem dar play e bytes de mp4

Medido com Chrome (Playwright, viewport 390x844, CDP `Network.loadingFinished`), sem tocar em nenhum vídeo:

| Página | Peso 1ª carga | Requisições | Bytes de mp4 na 1ª carga | Peso depois de rolar a página inteira | Pedidos de mp4 depois de rolar |
|---|---|---|---|---|---|
| index | 272.416 B (266 KB) | 15 | **0** | 484.738 B (473 KB) | **0** |
| resultados | 457.304 B (447 KB) | 21 | **0** | 774.605 B (756 KB) | **0** |

Confirmado: nenhum byte de mp4 é baixado antes do play, nem rolando a página toda (todo `<video>` tem `preload="none"` e poster; o `verificar-deploy.php` confere isso). Antes, a página de resultados baixava ~4,8 MB só com os metadados e inícios dos vídeos.

## Demais itens do orçamento

| Item | Limite | Medido | Situação |
|---|---|---|---|
| JavaScript do site público | ≤ 30 KB, sem framework | 6,6 KB (home) e 2,4 KB (resultados), inline, sem framework | OK |
| JavaScript do painel | ≤ 150 KB | painel.js 69,3 KB + midia.js 26,1 KB = 95,4 KB | OK |
| Fotos | ≤ 1600 px e ≤ 250 KB | maior lado 1600 px; maior arquivo 80 KB (drenagem-depois2.webp) | OK (as duas fotos de 260 KB do "antes" foram convertidas para WebP) |
| Vídeos | H.264, ≤ 1080p, preload none/metadata, poster | todos com `preload="none"`, `playsinline` e poster que responde 200; mp4 aceita Range (206) | OK |
| Painel não carrega nada no site público | — | o site não referencia arquivos de `painel/` | OK |
| Dependências novas | nenhuma | nenhuma (Lighthouse só via `npx`) | OK |

## Não medido

- Lighthouse desktop (não pedido neste lote).
- Lighthouse e peso na Hostinger/staging (com gzip, CDN e HTTPS reais).
- Reprodução dos vídeos no iPhone real (checklist manual do PUBLICACAO.md).
