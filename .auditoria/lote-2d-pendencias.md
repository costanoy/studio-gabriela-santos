# Lote 2D: pendencias

- L05 depoimentos: o teste pede pelo menos 5 depoimentos, mas `dados/site.json` so tem 4. Nao da para passar sem mais um depoimento no conteudo (arquivo do lote 2C / da dona). O carrossel em si funciona (procedimentos e resultados passam).
- L03 no iphone-webkit: o canto do video fica coberto pelo cabecalho fixo quando o WebKit centraliza o video na tela. Foi mitigado com `scroll-margin-top:150px` (resolve no Chromium). Se ainda falhar no WebKit, e efeito do teste, nao do toque real.
- T01 (peso das fotos: drenagem-antes1/depois1 acima de 250 KB, fotos de comparadores acima de 1000 px) e L01 (poster): pertencem ao lote 2C, nao foram mexidos aqui.
- S9: usa `resultados.cta_texto`; se o campo nao existir no JSON, o modelo usa o texto padrao "Quer um resultado assim? Chame a gente no WhatsApp."
