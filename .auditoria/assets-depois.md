# Assets: antes e depois (T01, T03, T04, L01)

Gerado por `php ferramentas/otimizar-assets.php --etapa=relatorio`. Originais em `acervo/originais/`.

| Arquivo | Antes (bytes) | Antes (px) | Depois | Depois (bytes) | Depois (px) |
|---|---:|---|---|---:|---|
| apresentacao.mp4 | 14.217.443 | 576x1024 | assets/apresentacao.mp4 | 7.918.561 | 576x1024 |
| hero-bg.png | 90.563 | 1920x1080 | assets/hero-bg.webp | 22.102 | 1600x900 |
| logo-gs.png | 141.289 | 1920x1080 | assets/logo-gs.webp | 10.522 | 384x216 |
| results/corrente-1.mp4 | 2.146.201 | 576x1024 | assets/results/corrente-1.mp4 | 1.284.536 | 576x1024 |
| results/drenagem-antes1.jpeg | 260.026 | 1180x1600 | assets/results/drenagem-antes1.webp | 78.524 | 800x1084 |
| results/drenagem-antes2.jpeg | 160.846 | 1149x1600 | assets/results/drenagem-antes2.webp | 57.458 | 800x1114 |
| results/drenagem-depois1.jpeg | 257.944 | 1179x1600 | assets/results/drenagem-depois1.webp | 72.936 | 800x1086 |
| results/drenagem-depois2.jpeg | 181.795 | 1028x1600 | assets/results/drenagem-depois2.webp | 80.086 | 800x1246 |
| results/limpeza-antes1.jpeg | 133.913 | 1284x1123 | assets/results/limpeza-antes1.webp | 32.012 | 800x700 |
| results/limpeza-antes2.jpeg | 151.975 | 1284x1059 | assets/results/limpeza-antes2.webp | 37.062 | 800x660 |
| results/limpeza-depois1.jpeg | 124.255 | 1284x1107 | assets/results/limpeza-depois1.webp | 29.010 | 800x690 |
| results/limpeza-depois2.jpeg | 148.597 | 1284x1056 | assets/results/limpeza-depois2.webp | 37.320 | 800x658 |
| results/liposhape-antes1.jpeg | 177.064 | 1600x1537 | assets/results/liposhape-antes1.webp | 25.148 | 800x768 |
| results/liposhape-depois1.jpeg | 199.987 | 1506x1600 | assets/results/liposhape-depois1.webp | 34.806 | 800x850 |
| results/liposhape-pos.mp4 | 1.882.503 | 576x1024 | assets/results/liposhape-pos.mp4 | 1.143.688 | 576x1024 |
| results/ultrassom-antes1.jpeg | 88.075 | 1284x1116 | assets/results/ultrassom-antes1.webp | 42.764 | 1284x1116 |
| img/39883127.jpg | 44.992 | 903x1600 | assets/img/39883127.jpg | 0 | 0x0 |
| img/5c2dbddb.jpg | 62.846 | 910x1600 | assets/img/5c2dbddb.jpg | 0 | 0x0 |
| img/99c58818.jpg | 73.330 | 899x1599 | assets/img/99c58818.jpg | 0 | 0x0 |
| img/bcea0fb6.jpg | 90.694 | 905x1600 | assets/img/bcea0fb6.jpg | 0 | 0x0 |
| img/d96ec399.jpg | 43.480 | 1080x1064 | assets/img/d96ec399.jpg | 0 | 0x0 |
| **Total** | **20.677.818** | | | **10.906.535** | |

## Pôsteres novos (frame 0, WebP até 720 px)

- `assets/posteres/apresentacao.webp`: 16.686 bytes, 576x1024
- `assets/posteres/corrente-1.webp`: 18.130 bytes, 576x1024
- `assets/posteres/corrente-2.webp`: 29.558 bytes, 576x1024
- `assets/posteres/liposhape-depois3.webp`: 22.204 bytes, 464x832
- `assets/posteres/liposhape-pos.webp`: 10.928 bytes, 576x1024
- `assets/posteres/ultrassom-depois1.webp`: 8.002 bytes, 480x480

## Vídeos mantidos sem recodificar (já H.264, até 1080 px, abaixo de 2,5 Mb/s, sem rotação)

- `assets/results/corrente-1.mp4`: h264, 576x1024, 972 kb/s, 1.284.536 bytes (recodificado: fixa a rotação nos pixels)
- `assets/results/corrente-2.mp4`: h264, 576x1024, 1617 kb/s, 2.361.157 bytes
- `assets/results/liposhape-depois3.mp4`: h264, 464x832, 1625 kb/s, 2.177.389 bytes
- `assets/results/liposhape-pos.mp4`: h264, 576x1024, 994 kb/s, 1.143.688 bytes (recodificado: fixa a rotação nos pixels)
- `assets/results/ultrassom-depois1.mp4`: h264, 480x480, 1638 kb/s, 1.462.646 bytes
- `assets/apresentacao.mp4`: h264, 576x1024, 658 kb/s, 7.918.561 bytes (recodificado)

## Não usados (já estavam em `acervo/`, nada foi apagado)

- `acervo/hero-bg.webp` (2.566 bytes)
- `acervo/logo.png` (4.811 bytes)
- `acervo/results/corrente-3.mp4` (2.502.091 bytes)
- `acervo/results/liposhape-antes3.jpeg` (119.731 bytes)
- `acervo/results/liposhape-depois2.mp4` (1.776.007 bytes)
