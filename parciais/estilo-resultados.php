<?php defined('RAIZ') || exit;
/** Estilo da página de resultados (variante B "Monograma"). O que é comum às duas páginas está em estilo-base.php. */
?>
<style>
/* Cabeçalho sempre sólido: logo à esquerda e "Voltar" à direita no desktop; no celular, "Voltar" à esquerda */
.cab-res .cab-int { grid-template-columns: auto 1fr; }
.cab-res .cab-logo { grid-column: 2; justify-self: end; }
.cab-voltar { grid-column: 1; grid-row: 1; justify-self: start; min-width: 44px; }
.btn-texto-claro { color: var(--areia); }
.btn-texto-claro:hover svg { transform: translateX(-4px); }
@media (min-width: 901px) {
  .cab-res .cab-logo { grid-column: 1; justify-self: start; }
  .cab-voltar { grid-column: 2; justify-self: end; }
}

/* Abertura */
.abertura { padding: calc(var(--cab-rolou-h) + 48px) 0 40px; }
.abertura h1 { margin-top: 16px; font-size: 44px; line-height: 1.05; letter-spacing: -.015em; }
@media (min-width: 901px) {
  .abertura { padding: 112px 0 64px; }
  .abertura h1 { font-size: clamp(44px, 5vw, 72px); line-height: 1; }
}

/* Atalhos de categoria: pílulas; no celular a fileira rola na horizontal */
.atalhos { display: flex; gap: 12px; margin: 32px calc(-1 * var(--margem)) 0; padding: 0 var(--margem) 4px; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
.atalhos::-webkit-scrollbar { display: none; }
.atalho { flex: none; display: inline-flex; align-items: center; min-height: 44px; padding: 0 20px; border: 1px solid var(--linha); border-radius: 999px; text-decoration: none; color: var(--marrom); font: 600 14px/1 var(--sem-serifa); white-space: nowrap; transition: background-color .2s ease; }
.atalho:hover, .atalho:focus-visible { background: var(--areia); }
@media (min-width: 901px) {
  .atalhos { flex-wrap: wrap; margin: 40px 0 0; padding: 0; overflow: visible; }
}

/* Categorias */
.categorias { counter-reset: cat; }
.cat { counter-increment: cat; scroll-margin-top: 96px; padding-bottom: 80px; }
.cat-titulo { display: flex; align-items: baseline; gap: 16px; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid var(--linha); font-size: 32px; line-height: 1.1; }
.cat-titulo::before { content: counter(cat, decimal-leading-zero); flex: none; font: italic 500 32px/1 var(--serifa); color: var(--marrom-claro); }
.cat-grade { display: flex; flex-direction: column; align-items: center; gap: 40px; }
.cat-item { margin: 0; width: min(360px, 100%); }
.res-legenda { margin-top: 16px; font: 500 22px/1.25 var(--serifa); color: var(--cafe); text-align: left; min-height: 1.25em; }
@media (min-width: 901px) {
  .cat { padding-bottom: 120px; }
  .cat-titulo { font-size: 44px; }
  .cat-titulo::before { font-size: 44px; }
  .cat-grade { flex-direction: row; flex-wrap: wrap; align-items: flex-start; gap: 56px 24px; justify-content: flex-start; }
  .cat-item { width: min(400px, calc((100% - (var(--col, 3) - 1) * 24px) / var(--col, 3))); }
}

/* Vídeo e foto avulsa */
.item-midia { position: relative; aspect-ratio: 3 / 4; border-radius: 4px; overflow: hidden; background: var(--cafe-escuro); }
.item-midia video, .item-midia img { width: 100%; height: 100%; object-fit: cover; background: var(--cafe-escuro); }
.item-midia:has(img) { background: var(--areia); }
.item-midia img { background: transparent; }
.cat-item.item-midia { margin-bottom: calc(16px + 27.5px); }
.item-etq { left: 12px; pointer-events: none; }

/* Convite final */
.convite { background: var(--degrade); padding: 80px 0; text-align: center; }
.convite p { max-width: 20ch; margin: 0 auto; font: 500 30px/1.15 var(--serifa); color: var(--creme); text-wrap: balance; }
.convite .btn { margin-top: 32px; }
@media (min-width: 901px) {
  .convite { padding: 120px 0; }
  .convite p { font-size: 44px; }
}
</style>
