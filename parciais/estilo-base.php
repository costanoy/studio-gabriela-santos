<?php defined('RAIZ') || exit;
/** Base visual do GUIA-VISUAL.md (variante B "Monograma"): fontes, cores, botões, cabeçalho, menu, rótulo, vídeo, comparador, rodapé e entrada de seção. Licença das fontes: assets/fonts/OFL.txt. */
?>
<style>
@font-face { font-family: 'Cormorant Garamond'; font-style: normal; font-weight: 500; font-display: swap; src: url("assets/fonts/cormorant-garamond-500-latin.woff2") format('woff2'); }
@font-face { font-family: 'Cormorant Garamond'; font-style: italic; font-weight: 500; font-display: swap; src: url("assets/fonts/cormorant-garamond-500-italic-latin.woff2") format('woff2'); }
@font-face { font-family: 'Mulish'; font-style: normal; font-weight: 400 700; font-display: swap; src: url("assets/fonts/23e9ba1c.woff2") format('woff2'); }
@font-face { font-family: 'Cormorant Reserva'; src: local('Georgia'); size-adjust: 86%; ascent-override: 92%; descent-override: 29%; line-gap-override: 0%; }
:root {
  --marrom: #7D5843; --marrom-fundo: #6B4A39; --marrom-claro: #996C55;
  --cafe: #3A2A22; --cafe-escuro: #2A1D17; --texto: #5C4A40;
  --creme: #FBF3EE; --areia: #F3E4DA; --linha: #E7CDBE; --marfim: #FFFBF7; --champanhe: #E3C9A8;
  --degrade: linear-gradient(160deg, #7D5843 0%, #74513E 55%, #6B4A39 100%);
  --serifa: 'Cormorant Garamond', 'Cormorant Reserva', Georgia, serif;
  --sem-serifa: 'Mulish', system-ui, sans-serif;
  --margem: 24px; --cab-h: 72px; --logo-h: 56px; --cab-rolou-h: 60px; --logo-rolou-h: 40px;
  --curva: cubic-bezier(.22,.61,.36,1);
}
@media (min-width: 601px) { :root { --margem: 40px; } }
@media (min-width: 901px) { :root { --cab-h: 96px; --logo-h: 76px; --cab-rolou-h: 64px; --logo-rolou-h: 44px; } }
@media (min-width: 1101px) { :root { --margem: 64px; } }
*, *::before, *::after { box-sizing: border-box; }
html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
body { margin: 0; background: var(--creme); color: var(--texto); font-family: var(--sem-serifa); font-size: 16px; line-height: 1.7; font-synthesis: none; overflow-x: clip; }
img, video { display: block; max-width: 100%; }
h1, h2, h3, h4, p { margin: 0; }
h1, h2, h3, h4 { font-family: var(--serifa); font-weight: 500; color: var(--cafe); text-wrap: balance; }
a { color: inherit; }
::selection { background: var(--linha); }
:focus-visible { outline: 2px solid var(--marrom); outline-offset: 3px; }
.contem { width: min(1240px, 100% - 2 * var(--margem)); margin-inline: auto; }
#home, #sobre, #procedimentos, #resultados, #cursos, #depoimentos, #contato { scroll-margin-top: 0; }

/* Botões: pílula */
.btn {
  display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
  min-height: 52px; padding: 0 32px; border-radius: 999px; border: 1px solid transparent;
  font: 700 13px/1 var(--sem-serifa); letter-spacing: .14em; text-transform: uppercase; text-align: center;
  cursor: pointer; transition: background-color .2s ease, color .2s ease, border-color .2s ease;
}
.btn:focus-visible, .btn-texto:focus-visible { outline: 2px solid currentColor; outline-offset: 3px; }
.btn-claro { background: var(--creme); color: var(--marrom-fundo); }
.btn-claro:hover, .btn-claro:focus-visible { background: var(--champanhe); }
.btn-contorno-claro { border-color: var(--areia); color: var(--areia); background: transparent; }
.btn-contorno-claro:hover, .btn-contorno-claro:focus-visible { background: rgba(243,228,218,.14); }
.btn-marrom { background: var(--marrom); color: var(--creme); }
.btn-marrom:hover, .btn-marrom:focus-visible { background: var(--marrom-fundo); }
.btn-contorno { border-color: var(--marrom); color: var(--marrom); background: transparent; }
.btn-contorno:hover, .btn-contorno:focus-visible { background: var(--areia); }
.btn-texto {
  display: inline-flex; align-items: center; justify-content: center; gap: 12px; min-height: 44px; text-decoration: none; color: var(--marrom);
  font: 700 13px/1 var(--sem-serifa); letter-spacing: .14em; text-transform: uppercase;
}
.btn-texto .t { padding-bottom: 6px; border-bottom: 1px solid currentColor; }
.btn-texto svg { transition: transform .2s ease; }
.btn-texto:hover svg { transform: translateX(4px); }

/* Cabeçalho: bloco de altura zero colado no alto; o topo marrom passa por baixo, sem salto ao rolar */
.cab-wrap { position: sticky; top: 0; height: 0; z-index: 50; }
.cab {
  position: absolute; inset: 0 0 auto 0; height: var(--cab-h); background: transparent; border-bottom: 1px solid transparent;
  transition: height .25s ease, background-color .25s ease, border-color .25s ease;
}
/* Véu leve sobre o topo marrom: dá leitura aos links do menu mesmo antes de rolar */
.cab { background-image: linear-gradient(180deg, rgba(42,29,23,.45), rgba(42,29,23,0)); }
.cab.scrolled, .cab.menu-aberto { background-image: none;} 
.cab.scrolled, .cab.menu-aberto { background: rgba(58,42,34,.94); border-bottom-color: rgba(227,201,168,.18); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); }
.cab.scrolled { height: var(--cab-rolou-h); }
.cab-int { height: 100%; width: min(1240px, 100% - 2 * var(--margem)); margin-inline: auto; display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; column-gap: 16px; }
.cab-logo { position: relative; display: grid; grid-column: 1; justify-self: start; }
.cab-logo img { grid-area: 1 / 1; height: var(--logo-h); width: auto; transition: height .25s ease, opacity .25s ease; }
#logo-gs { opacity: 0; }
.cab.scrolled .cab-logo img { height: var(--logo-rolou-h); }
.cab.scrolled #logo-full { opacity: 0; }
.cab.scrolled #logo-gs { opacity: 1; }
.cab-menu { display: none; justify-self: center; gap: clamp(16px, 2.2vw, 32px); }
.cab-menu a { position: relative; display: inline-flex; align-items: center; min-height: 44px; text-decoration: none; color: var(--creme); font: 600 13px/1 var(--sem-serifa); letter-spacing: .12em; text-transform: uppercase; white-space: nowrap; }
.cab-menu a::after { content: ''; position: absolute; left: 0; right: 0; top: calc(50% + 14px); height: 1px; background: var(--champanhe); transform: scaleX(0); transform-origin: left; transition: transform .25s ease; }
.cab-menu a:hover::after, .cab-menu a:focus-visible::after { transform: scaleX(1); }
.cab :focus-visible, .menu-celular :focus-visible, .rodape :focus-visible, .hero :focus-visible, .proc :focus-visible, .contato :focus-visible { outline-color: var(--areia); }
.cab-cta { display: none; min-height: 44px; padding: 0 24px; }
.cab-toggle { grid-column: 4; position: relative; width: 44px; height: 44px; padding: 0; border: 0; background: transparent; cursor: pointer; color: var(--creme); }
.cab-toggle span { position: absolute; left: 11px; width: 22px; height: 1.5px; background: var(--creme); top: 50%; transition: transform .2s ease; margin-top: -.75px; }
.cab-toggle span:first-child { transform: translateY(-3.5px); }
.cab-toggle span:last-child { transform: translateY(3.5px); }
.cab.menu-aberto .cab-toggle span:first-child { transform: rotate(45deg); }
.cab.menu-aberto .cab-toggle span:last-child { transform: rotate(-45deg); }
@media (min-width: 1101px) {
  .cab-int { grid-template-columns: auto 1fr auto; }
  .cab-menu { display: flex; grid-column: 2; }
  .cab-cta { display: inline-flex; grid-column: 3; }
  .cab-toggle, .menu-celular { display: none !important; }
}
.menu-celular { display: none; position: absolute; top: 100%; left: 0; right: 0; flex-direction: column; background: var(--cafe-escuro); padding: 8px 24px 32px; }
.cab.menu-aberto .menu-celular { display: flex; animation: menu-desce .25s ease both; }
.menu-celular > a:not(.btn) { display: flex; align-items: center; height: 56px; text-decoration: none; color: var(--creme); font: 500 28px/1 var(--serifa); border-bottom: 1px solid rgba(227,201,168,.18); }
.menu-celular .btn { margin-top: 24px; width: 100%; }
@keyframes menu-desce { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }

/* Rótulo: serifa itálica precedida de uma linha */
.rotulo { display: flex; align-items: center; gap: 12px; color: var(--marrom); font: italic 500 19px/1.2 var(--serifa); }
.rotulo::before { content: ''; width: 32px; height: 1px; background: currentColor; flex: none; }
.rotulo-claro { color: var(--champanhe); }
@media (min-width: 901px) { .rotulo { font-size: 22px; } }

/* Vídeo */
.video-moldura { position: relative; width: 78%; max-width: 300px; isolation: isolate; }
.video-moldura::before { content: ''; position: absolute; inset: 0; z-index: -1; transform: translate(-10px, -10px); border: 1px solid var(--champanhe); border-radius: 4px; }
.video-moldura video { width: 100%; height: auto; aspect-ratio: 576 / 1024; object-fit: cover; border-radius: 4px; background: var(--cafe-escuro); }
.video-play { position: absolute; top: 50%; left: 50%; width: 72px; height: 72px; margin: -36px 0 0 -36px; border-radius: 50%; border: 1px solid var(--creme); background: rgba(42,29,23,.55); padding: 0 0 0 4px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform .2s ease, background-color .2s ease; }
.video-play:hover { transform: scale(1.06); background: rgba(42,29,23,.75); }
.video-play[hidden] { display: none; }
.video-play:focus-visible { outline: 2px solid var(--creme); outline-offset: 3px; }
@media (min-width: 901px) { .video-moldura::before { transform: translate(-12px, -12px); } }

/* Comparador antes/depois */
.ba { position: relative; width: 100%; aspect-ratio: 3 / 4; border-radius: 4px; overflow: hidden; background: var(--cafe-escuro); touch-action: pan-y; cursor: ew-resize; user-select: none; -webkit-user-select: none; }
.ba img { position: absolute; max-width: none; transform: translate(-50%, -50%); }
.ba-clip { position: absolute; inset: 0; clip-path: inset(0 50% 0 0); }
.ba-etq { position: absolute; top: 12px; z-index: 1; padding: 6px 10px; border-radius: 2px; background: rgba(42,29,23,.72); color: var(--creme); font: 700 11px/1 var(--sem-serifa); letter-spacing: .14em; text-transform: uppercase; }
.ba-antes { left: 12px; }
.ba-depois { right: 12px; }
.ba-line { position: absolute; top: 0; bottom: 44px; left: 50%; width: 1px; background: var(--creme); pointer-events: none; z-index: 2; }
.ba-alca { position: absolute; top: 50%; left: 50%; width: 44px; height: 44px; margin: -22px 0 0 -22px; border-radius: 50%; background: var(--creme); border: 1.5px solid var(--champanhe); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 16px rgba(42,29,23,.3); }
.ba input[type=range] { position: absolute; left: 16px; right: 16px; bottom: 0; width: calc(100% - 32px); height: 44px; margin: 0; z-index: 3; background: transparent; -webkit-appearance: none; appearance: none; touch-action: pan-y; cursor: ew-resize; }
.ba input[type=range]::-webkit-slider-runnable-track { height: 2px; background: rgba(251,243,238,.6); border-radius: 2px; }
.ba input[type=range]::-moz-range-track { height: 2px; background: rgba(251,243,238,.6); border-radius: 2px; }
.ba input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; width: 16px; height: 16px; margin-top: -7px; border-radius: 50%; background: var(--creme); border: 1px solid var(--champanhe); }
.ba input[type=range]::-moz-range-thumb { width: 16px; height: 16px; border-radius: 50%; background: var(--creme); border: 1px solid var(--champanhe); }
.ba input[type=range]:focus-visible { outline: 2px solid var(--creme); outline-offset: 0; border-radius: 4px; }

/* Rodapé */
.rodape { background: var(--cafe-escuro); color: var(--areia); padding: 72px 0 32px; }
.rodape-grade { display: grid; grid-template-columns: 1fr; gap: 40px; }
.rodape-logo img { height: 150px; width: auto; }
.rodape-listas { display: grid; grid-template-columns: 1fr; gap: 40px; }
.rodape-titulo { margin-bottom: 8px; color: var(--champanhe); font: 700 12px/1.2 var(--sem-serifa); letter-spacing: .14em; text-transform: uppercase; }
.rodape-lista { display: flex; flex-direction: column; }
.rodape a, .rodape p.rodape-linha { display: flex; align-items: center; gap: 10px; min-height: 44px; text-decoration: none; color: var(--areia); font-size: 15px; line-height: 1.5; margin: 0; }
.rodape a:hover { color: var(--champanhe); }
.rodape svg { flex: none; }
.rodape-fim { margin-top: 48px; padding-top: 24px; border-top: 1px solid rgba(227,201,168,.18); text-align: center; color: #BFA99B; font-size: 13px; }
@media (min-width: 901px) {
  .rodape { padding: 96px 0 40px; }
  .rodape-grade { grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: 24px; }
  .rodape-logo { grid-column: 1 / 5; }
  .rodape-logo img { height: 200px; }
  .rodape-listas { grid-column: 6 / 13; grid-template-columns: repeat(3, 1fr); column-gap: 24px; }
}

/* Entrada de seção: só opacity e transform */
.js .reveal, .js .reveal-item { opacity: 0; transform: translateY(16px); transition: opacity .7s var(--curva), transform .7s var(--curva); }
.js .reveal.visible, .js .reveal-item.visible { opacity: 1; transform: none; }
@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
  *, *::before, *::after { transition-duration: 0s !important; transition-delay: 0s !important; animation-duration: 0s !important; animation-delay: 0s !important; }
  .js .reveal, .js .reveal-item { opacity: 1; transform: none; }
}
</style>
