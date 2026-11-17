<?php defined('RAIZ') || exit;
/** Estilo da home (direção B "Monograma"): base compartilhada mais as seções da home. */
parcial('estilo-base');
?>
<style>
/* Topo: só tipografia, centralizado */
.hero { position: relative; background: var(--degrade); overflow: hidden; min-height: max(86svh, 600px); display: flex; align-items: center; justify-content: center; text-align: center; }
.hero-int { position: relative; z-index: 1; width: 100%; padding: 104px var(--margem) 64px; }
.marca-dagua { position: absolute; left: 50%; top: 50%; z-index: 0; width: 130vw; max-width: none; height: auto; transform: translate(-50%, -50%); opacity: .25; pointer-events: none; -webkit-mask-image: radial-gradient(closest-side, #000 55%, transparent 100%); mask-image: radial-gradient(closest-side, #000 55%, transparent 100%); }
.hero h1 { font-size: 52px; line-height: 1; letter-spacing: -.015em; color: var(--creme); }
.hero-apoio { margin: 20px auto 0; max-width: 30ch; font-size: 17px; line-height: 1.6; color: var(--areia); }
.hero-acoes { margin-top: 32px; display: flex; flex-direction: column; gap: 12px; }
.hero-acoes .btn { width: 100%; }
.hero-apoio, .hero-acoes { animation: surgir .6s var(--curva) both; }
.hero-apoio { animation-delay: .15s; }
.hero-acoes { animation-delay: .25s; }
@keyframes surgir { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
.hero-linha { position: absolute; left: 50%; bottom: 0; z-index: 1; width: 1px; height: 48px; background: var(--champanhe); }

/* Boas-vindas */
.boas { padding: 80px 0 64px; }
.boas-int { text-align: center; }
.boas-frase { position: relative; padding-top: 72px; font: italic 500 26px/1.3 var(--serifa); letter-spacing: -.005em; color: var(--cafe); text-wrap: balance; }
.boas-frase::before { content: ''; position: absolute; left: 50%; top: 0; width: 1px; height: 48px; background: var(--marrom-claro); }
.boas-corrido { margin: 24px auto 0; max-width: 52ch; font-size: 16px; line-height: 1.7; color: var(--texto); text-wrap: pretty; }

/* Sobre */
.sobre { padding: 80px 0; }
.sobre h2 { margin: 16px 0 0; font-size: 36px; line-height: 1.1; letter-spacing: -.01em; }
.retrato-b { position: relative; isolation: isolate; width: 72%; max-width: 280px; margin: 32px auto 40px; }
.retrato-b::before { content: ''; position: absolute; inset: 0; z-index: -1; transform: translate(-10px, -10px); border: 1px solid var(--champanhe); border-radius: 999px 999px 4px 4px; }
.retrato-b img { width: 100%; height: auto; aspect-ratio: 3 / 4; object-fit: cover; object-position: center top; border-radius: 999px 999px 4px 4px; background: var(--cafe-escuro); box-shadow: 0 30px 60px -30px rgba(42,29,23,.45); }
.sobre-destaque { margin-top: 24px; font-size: 19px; line-height: 1.6; color: var(--cafe); }
.sobre-corrido { margin-top: 16px; font-size: 16px; line-height: 1.7; color: var(--texto); }
.sobre-corrido, .sobre-destaque { max-width: 62ch; text-wrap: pretty; }
.numeros { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 24px; margin-top: 40px; }
.numero { border-top: 1px solid var(--linha); padding-top: 16px; }
.numero b { display: block; font: 500 30px/1.1 var(--serifa); color: var(--marrom); }
.numero span { display: block; margin-top: 4px; font-size: 14px; line-height: 1.5; color: var(--texto); }

/* Faixa do vídeo */
.faixa-video { background: var(--areia); padding: 64px 0; }
.faixa-grade { display: block; }
.faixa-texto p { font-size: 16px; line-height: 1.7; color: var(--texto); max-width: 62ch; text-wrap: pretty; }
.faixa-texto p + p { margin-top: 16px; }
.faixa-texto .saiba { display: flex; align-items: center; gap: 16px; margin-top: 24px; color: var(--marrom); font: italic 500 24px/1.2 var(--serifa); }
.seta-lado { display: none; }
.faixa-video-col { margin-top: 32px; display: flex; justify-content: center; }

/* Procedimentos: lista numerada */
.proc { background: var(--degrade); padding: 96px 0; }
.proc h2 { margin-top: 16px; font-size: 36px; line-height: 1.1; letter-spacing: -.01em; color: var(--creme); }
.proc-lista { list-style: none; counter-reset: proc; margin: 48px 0 0; padding: 0; display: grid; grid-template-columns: 1fr; }
.proc-item { counter-increment: proc; position: relative; padding: 28px 0 28px 64px; border-top: 1px solid rgba(227,201,168,.28); }
.proc-item::before { content: counter(proc, decimal-leading-zero); position: absolute; left: 0; top: 26px; font: 500 28px/1 var(--serifa); color: var(--champanhe); }
.proc-item h3 { font-size: 26px; line-height: 1.15; color: var(--creme); }
.proc-item p { margin-top: 8px; font-size: 16px; line-height: 1.7; color: var(--areia); max-width: 52ch; text-wrap: pretty; }
.reveal-item { transition-delay: calc(min(var(--i, 0), 5) * 80ms) !important; }

/* Resultados */
.res { padding: 96px 0; }
.res-topo h2, .cursos-topo h2, .dep-topo h2, .contato h2 { margin-top: 16px; font-size: 36px; line-height: 1.1; letter-spacing: -.01em; }
.res-ver-desktop { display: none; }
#resultados-grid { margin-top: 48px; display: flex; gap: 16px; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; scrollbar-width: none; position: relative; }
#resultados-grid::-webkit-scrollbar, #depoimentos-grid::-webkit-scrollbar { display: none; }
#resultados-grid > * { flex: 0 0 82%; scroll-snap-align: start; }
.res-legenda { margin-top: 16px; font: 500 22px/1.25 var(--serifa); color: var(--cafe); text-align: left; }
.carrossel-ctl { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 24px; }
.carrossel-ctl button { width: 44px; height: 44px; padding: 0; border-radius: 50%; border: 1px solid var(--marrom); background: transparent; color: var(--marrom); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background-color .2s ease; }
.carrossel-ctl button:hover { background: var(--areia); }
.carrossel-contador { min-width: 72px; text-align: center; font-size: 15px; font-weight: 700; color: var(--cafe); }
#ver-mais-mobile { width: 100%; margin-top: 32px; gap: 12px; }

/* Cursos */
.cursos { background: var(--areia); padding: 96px 0; }
.cursos-desc { margin-top: 16px; max-width: 56ch; font-size: 16px; line-height: 1.7; color: var(--texto); }
.cursos-lista { margin-top: 48px; display: flex; flex-direction: column; gap: 24px; }
.curso { background: var(--marfim); border: 1px solid var(--linha); border-radius: 4px; padding: 32px 24px; }
.curso-cab { display: flex; flex-direction: column; gap: 24px; }
.curso h3 { font-size: 30px; line-height: 1.1; }
.curso-carga { margin-top: 8px; font-size: 13px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--marrom); }
.curso-desc { margin-top: 16px; max-width: 56ch; font-size: 16px; line-height: 1.7; color: var(--texto); }
.curso-acoes { display: flex; flex-direction: column; gap: 12px; }
.curso-detalhe { margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--linha); grid-template-columns: 1fr; gap: 32px; }
.curso-detalhe h4 { font-size: 22px; color: var(--cafe); }
.curso-detalhe ul { margin: 12px 0 0; padding-left: 18px; color: var(--texto); font-size: 15.5px; line-height: 1.9; }

/* Depoimentos */
.dep { padding: 96px 0; }
#depoimentos-grid { margin-top: 48px; display: flex; gap: 32px; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; scrollbar-width: none; position: relative; }
#depoimentos-grid > * { flex: 0 0 84%; scroll-snap-align: start; }
.dep-item { display: flex; flex-direction: column; border-top: 1px solid var(--linha); padding-top: 24px; }
.dep-estrelas { display: flex; gap: 4px; margin-bottom: 16px; }
.dep-texto { font: italic 500 22px/1.4 var(--serifa); color: var(--cafe); text-wrap: pretty; }
.dep-autor { margin-top: auto; padding-top: 24px; }
.dep-autor b { display: block; font-size: 15.5px; color: var(--cafe); }
.dep-autor span { display: block; font-size: 14px; color: var(--marrom); }

/* Contato: faixa marrom de fechamento */
.contato { background: var(--degrade); padding: 96px 0; color: var(--areia); }
.contato h2 { color: var(--creme); }
.contato-grade { display: grid; grid-template-columns: 1fr; gap: 48px; }
.contato .btn-principal { margin-top: 32px; }
.contato-dados { margin-top: 40px; }
.contato-dado { display: flex; align-items: flex-start; gap: 16px; padding: 20px 0; border-top: 1px solid rgba(227,201,168,.28); }
.contato-dado svg { flex: none; margin-top: 4px; }
.contato-dado h3 { font: 700 12px/1.2 var(--sem-serifa); letter-spacing: .14em; text-transform: uppercase; color: var(--champanhe); }
.contato-dado p, .contato-dado a { display: block; margin-top: 6px; font-size: 16px; line-height: 1.6; color: var(--areia); text-decoration: none; }
.contato-dado a:hover { color: var(--creme); }
.contato-avaliacao { margin-top: 8px; }
.contato-mapa { position: relative; width: 100%; min-height: 360px; border-radius: 4px; overflow: hidden; background: var(--cafe-escuro); }
.contato-mapa iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
.contato-mapa .btn { position: absolute; left: 16px; bottom: 16px; min-height: 44px; padding: 0 20px; }

/* Desktop */
@media (min-width: 901px) {
  .hero { min-height: min(88vh, 800px); }
  .hero-int { width: min(1240px, 100% - 2 * var(--margem)); padding: 96px 0 112px; }
  .hero-texto { max-width: 920px; margin: 0 auto; }
  .marca-dagua { width: auto; height: 110%; opacity: .3; }
  .hero h1 { font-size: clamp(52px, 7.8vw, 112px); line-height: .96; margin-inline: auto; max-width: 14ch; }
  .hero-apoio { margin-top: 28px; max-width: 46ch; font-size: 19px; }
  .hero-acoes { margin-top: 40px; flex-direction: row; justify-content: center; gap: 16px; }
  .hero-acoes .btn { width: auto; }
  .hero-linha { height: 72px; }

  .boas { padding: 144px 0 112px; }
  .boas-int { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: 24px; }
  .boas-txt { grid-column: 3 / 11; }
  .boas-frase { font-size: clamp(26px, 2.8vw, 40px); line-height: 1.25; }
  .boas-corrido { font-size: 17px; line-height: 1.75; }

  .sobre { padding: 144px 0 120px; }
  .sobre-grade { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: 24px; align-items: start; }
  .retrato-col { grid-column: 1 / 6; grid-row: 1; position: sticky; top: 120px; }
  .retrato-b { width: min(503px, 100%); max-width: none; margin: 0; }
  .retrato-b::before { transform: translate(-14px, -14px); }
  .sobre-txt { grid-column: 7 / 13; grid-row: 1; }
  .sobre h2 { font-size: clamp(36px, 3.9vw, 56px); line-height: 1.05; }
  .sobre-txt .sobre-destaque { margin-top: 32px; font-size: 22px; line-height: 1.55; }
  .sobre-txt .sobre-corrido { font-size: 17px; line-height: 1.75; margin-top: 24px; }
  .sobre-txt .numeros { display: flex; gap: 48px; margin-top: 56px; }
  .numero { flex: 1 1 0; }
  .numero b { font-size: 36px; }

  .faixa-video { padding: 112px 0; }
  .faixa-texto p { font-size: 17px; line-height: 1.75; }
  .faixa-texto .saiba { font-size: 28px; }
  .seta-lado { display: block; flex: none; transform: scaleX(-1); }
  .seta-baixo { display: none; }
  .faixa-grade { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: 24px; align-items: center; }
  .faixa-texto { grid-column: 2 / 7; grid-row: 1; }
  .faixa-video-col { grid-column: 8 / 12; grid-row: 1; margin: 0; justify-content: flex-start; }
  .video-moldura { width: auto; max-width: 340px; }
  .video-moldura video { width: auto; height: 600px; max-width: 100%; }

  .proc { padding: 144px 0 120px; }
  .proc h2, .res-topo h2, .cursos-topo h2, .dep-topo h2, .contato h2 { font-size: clamp(36px, 3.9vw, 56px); line-height: 1.05; }
  .proc-lista { margin-top: 72px; grid-template-columns: 1fr 1fr; column-gap: 96px; }
  .proc-item { padding: 36px 0 36px 80px; }
  .proc-item::before { top: 34px; font-size: 36px; }
  .proc-item h3 { font-size: 30px; }
  .proc-item p { font-size: 17px; }

  .res { padding: 144px 0 120px; }
  .res-topo { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: 24px; align-items: end; }
  .res-topo > div { grid-column: 1 / 10; }
  .res-ver-desktop { display: inline-flex; grid-column: 10 / 13; justify-self: end; }
  #resultados-grid { margin-top: 64px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 48px 24px; overflow: visible; }
  #resultados-grid > * { flex: none; }
  #resultados .carrossel-ctl, #ver-mais-mobile { display: none; }

  .cursos { padding: 144px 0 120px; }
  .cursos-topo { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: 24px; }
  .cursos-topo > div { grid-column: 1 / 8; }
  .cursos-desc { font-size: 17px; }
  .cursos-lista { margin-top: 72px; gap: 32px; }
  .curso { padding: 48px; }
  .curso-cab { flex-direction: row; justify-content: space-between; align-items: flex-start; gap: 48px; }
  .curso-acoes { min-width: 260px; }
  .curso-detalhe { gap: 48px; }
  .curso-detalhe.aberto, .curso-detalhe[style*="grid"] { grid-template-columns: 1fr 1fr; }

  .dep { padding: 144px 0 120px; }
  #depoimentos-grid { margin-top: 64px; gap: 48px; }
  #depoimentos-grid > * { flex: 0 0 calc((100% - 96px) / 3); }

  .contato { padding: 144px 0 128px; }
  .contato-grade { grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: 24px; align-items: center; }
  .contato-info { grid-column: 1 / 7; }
  .contato-mapa { grid-column: 8 / 13; min-height: 520px; height: 100%; }
}
</style>
