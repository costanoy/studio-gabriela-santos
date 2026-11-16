# Orçamento de performance — Studio Gabriela Santos (site web)

Plataforma: site institucional estático/PHP leve na Hostinger, acessado principalmente pelo celular (Instagram/WhatsApp → site).

| Métrica | Limite |
|---|---|
| Lighthouse Performance (celular) | ≥ 90 |
| Lighthouse SEO / Acessibilidade / Boas práticas | ≥ 95 / ≥ 90 / ≥ 95 |
| LCP (4G simulado) | ≤ 2,5 s |
| CLS | ≤ 0,1 |
| HTML da página inicial | ≤ 120 KB (sem gzip) |
| Peso total no primeiro carregamento (sem rolar, sem dar play) | ≤ 1,5 MB |
| JavaScript do site público | ≤ 30 KB, sem frameworks |
| Fotos | ≤ 1600 px no lado maior, WebP/JPEG ≤ 250 KB cada |
| Vídeos | H.264 MP4, ≤ 1080p, nunca baixados antes do play (preload="none" ou "metadata") e sempre com poster (primeiro frame) |
| Painel administrativo | Não carrega nada no site público; JS do painel ≤ 150 KB |
| Dependências novas | Nenhuma no site público; nenhuma de runtime no servidor além do PHP nativo (GD) |
