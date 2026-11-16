# Medição de performance — ANTES (Agente Medidor)

Data: 2026-11-16. Lighthouse via npx (Chrome headless), categorias performance, seo, accessibility, best-practices.
Local: http://localhost:8765 (site novo, ainda não publicado). Real (somente leitura): https://studiogabrielasantos.com.br/ (versão antiga na Hostinger).
Relatórios JSON completos: lh-index-mobile.json, lh-index-desktop.json, lh-resultados-mobile.json, lh-resultados-desktop.json, lh-real-mobile.json (nesta pasta).

## Orçamento x medido

| Métrica | Limite | index (celular) | resultados (celular) | index (desktop) | resultados (desktop) | Real atual (celular) |
|---|---|---|---|---|---|---|
| Performance | ≥ 90 | 91 OK | 31 FALHA | 100 | 84 | 36 |
| SEO | ≥ 95 | 100 OK | 100 OK | 100 | 100 | 100 |
| Acessibilidade | ≥ 90 | 87 FALHA | 85 FALHA | 87 | 85 | 87 |
| Boas práticas | ≥ 95 | 100 OK | 100 OK | 96 OK | 100 OK | 0 |
| LCP | ≤ 2,5 s | 3,5 s FALHA | 6,6 s FALHA | 0,6 s OK | 1,5 s OK | 81,2 s |
| CLS | ≤ 0,1 | 0 OK | 0,006 OK | 0 OK | 0 OK | 0 |
| TBT | (sem limite) | 70 ms | 10.090 ms | 0 | 260 ms | 790 ms |
| FCP | — | 1,4 s | 3,6 s | 0,6 s | 0,3 s | 80,7 s |
| Peso total transferido | ≤ 1,5 MB (1ª carga) | 436 KB OK | 4.834 KB FALHA | 368 KB OK | 4.856 KB FALHA | 15.950 KB |
| HTML (transferido) | ≤ 120 KB | 73 KB OK (74.502 bytes) | 32 KB OK | 73 KB | 32 KB | 15.950 KB FALHA |
| JavaScript | ≤ 30 KB, sem frameworks | 0 KB externo (2 blocos inline; sem framework) OK | idem | idem | idem | não medido |

## Maiores recursos

- index (celular): logo-gs.png 138 KB; hero-bg.png 89 KB; HTML 73 KB; logo.svg 37 KB; sobre-foto.webp 35 KB; fontes woff2 44 KB.
- index (desktop): liposhape-depois1.jpeg 195 KB; HTML 73 KB; sobre-foto.webp 35 KB.
- resultados (celular): corrente-2.mp4 576 KB; ultrassom-depois1.mp4 512 KB; corrente-1.mp4 512 KB; liposhape-depois3.mp4 448 KB; liposhape-pos.mp4 448 KB. Ou seja, a página de resultados baixa ~4,8 MB sem dar play (metadados e início de cada vídeo, mais as fotos).
- Real atual: documento "/" de 15,9 MB (quase tudo é HTML, com fontes e imagens embutidas em base64 e blobs). Explica LCP de 81 s e Boas práticas 0.

## Imagens (assets/ — dimensões via ffprobe)

Todas com lado maior ≤ 1600 px (OK). Acima do limite de 250 KB:
- assets/results/drenagem-antes1.jpeg — 1180x1600, 260.026 bytes (FALHA, limite ~256.000 bytes)
- assets/results/drenagem-depois1.jpeg — 1179x1600, 257.944 bytes (FALHA)
Demais fotos entre 56 KB e 200 KB (OK). Maiores: liposhape-depois1 200 KB, drenagem-antes2 161 KB.
Outros arquivos: hero-bg.png 90 KB (1920x1080, há hero-bg.webp 2,5 KB, 367x201 — conferir uso), logo-gs.png 141 KB (1920x1080), logo.svg 37 KB, og-image.jpg 59 KB (1200x630), sobre-foto.webp 36 KB (690x920). Fontes: 9 arquivos woff2, 9 a 30 KB cada.

## Vídeos (ffprobe)

| Arquivo | Resolução | Codec | Bitrate (vídeo) | Duração | Tamanho | Limite |
|---|---|---|---|---|---|---|
| assets/apresentacao.mp4 (index) | 576x1024 | H.264 Baseline | 1,28 Mb/s | 84,6 s | 14,2 MB | FALHA de tamanho (grande demais; ver nota) |
| results/corrente-1.mp4 | 1024x576 | H.264 Baseline | 1,63 Mb/s | 10,5 s | 2,15 MB | OK 1080p, H.264 |
| results/corrente-2.mp4 | 576x1024 | H.264 Baseline | 1,62 Mb/s | 11,7 s | 2,36 MB | OK |
| results/corrente-3.mp4 | 480x800 | H.264 Baseline | 1,62 Mb/s | 11,9 s | 2,50 MB | OK (sem uso no HTML) |
| results/liposhape-depois2.mp4 | 1024x576 | H.264 Baseline | 1,64 Mb/s | 8,7 s | 1,78 MB | OK (sem uso no HTML) |
| results/liposhape-depois3.mp4 | 464x832 | H.264 Baseline | 1,63 Mb/s | 10,7 s | 2,18 MB | OK |
| results/liposhape-pos.mp4 | 1024x576 | H.264 Baseline | 1,64 Mb/s | 9,2 s | 1,88 MB | OK |
| results/ultrassom-depois1.mp4 | 480x480 | H.264 Baseline | 1,64 Mb/s | 7,1 s | 1,46 MB | OK |

Todos H.264, até 1024 px, sem nenhum acima de 1080p. Nota: apresentacao.mp4 tem 14,2 MB; o limite do orçamento não fixa tamanho de vídeo, mas nunca deve ser baixado antes do play, então o impacto real depende do preload.

## Tags <video>

- index.html (apresentacao.mp4): poster = assets/sobre-foto.webp; preload="metadata". Poster presente, mas NÃO é o primeiro frame do vídeo: a comparação em escala de cinza 32x32 dá diferença média de 42,9/255 (outra imagem). Ponto a corrigir: gerar poster a partir do frame 0.
- resultados.html (5 vídeos: liposhape-depois3, liposhape-pos, ultrassom-depois1, corrente-1, corrente-2): poster presente em todos; preload="metadata" em todos (aceito pelo orçamento, que permite "none" ou "metadata"). Frame 0 extraído com ffmpeg confere com o poster: diferença média 1,6 a 3,3 /255. Pode-se considerar OK.
- Observação: o poster em si evita a tela preta no iPhone com preload="metadata" (o iOS não carrega o primeiro frame sozinho), mas só funciona enquanto o poster existir e for o primeiro frame.
- Frames extraídos e comparados: frame0-*.png nesta pasta.

## Outras observações

- index.html: 11 imagens com loading="lazy" em cada página; 2 <script> inline, nenhum externo (Google Maps é iframe, não script).
- Acessibilidade 85-87 abaixo de 90 nas duas páginas: causa não investigada em detalhe (ver lh-*.json, auditorias com score < 1). Não foram tentados ajustes, conforme pedido.
- Versão real atual: LCP, boas práticas e HTML de 15,9 MB indicam que a versão antiga é pesada. Só leitura, nada alterado.

## Resumo de falhas vs orçamento (versão nova, local)

1. Performance da página de resultados (31 celular) e LCP 6,6 s no celular.
2. Peso transferido da página de resultados: 4,8 MB sem dar play (limite 1,5 MB).
3. Acessibilidade 85-87 (limite 90) nas duas páginas.
4. LCP da index no celular 3,5 s (limite 2,5 s).
5. Duas fotos drenagem acima de 250 KB.
6. apresentacao.mp4 poster não é o primeiro frame.
