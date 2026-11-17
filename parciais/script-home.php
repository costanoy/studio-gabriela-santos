<?php defined('RAIZ') || exit; ?>
<script>
function tocarVideo(btn) {
  const v = btn.parentElement.querySelector('video');
  if (v) { v.controls = true; v.play(); btn.hidden = true; }
}
function marcarVideo(v, tocando) {
  const b = v.parentElement.querySelector('.video-play');
  if (b) b.hidden = tocando;
}
document.addEventListener('play', function (e) {
  const v = e.target;
  if (!v || v.tagName !== 'VIDEO') return;
  document.querySelectorAll('video').forEach(function (o) { if (o !== v && !o.paused) o.pause(); });
  marcarVideo(v, true);
}, true);
['pause', 'ended'].forEach(function (nome) {
  document.addEventListener(nome, function (e) {
    if (e.target && e.target.tagName === 'VIDEO') marcarVideo(e.target, false);
  }, true);
});
function goHome(e) {
  e.preventDefault();
  window.scrollTo({ top: 0, behavior: 'smooth' });
  history.replaceState(null, '', '#home');
}
function abrirMenu(abrir) {
  const cab = document.getElementById('site-header');
  const botao = document.getElementById('nav-toggle');
  if (!cab) return;
  cab.classList.toggle('menu-aberto', !!abrir);
  if (botao) {
    botao.setAttribute('aria-expanded', abrir ? 'true' : 'false');
    botao.setAttribute('aria-label', abrir ? 'Fechar menu' : 'Abrir menu');
  }
}
function alternarMenu() {
  const cab = document.getElementById('site-header');
  abrirMenu(!!cab && !cab.classList.contains('menu-aberto'));
}
document.addEventListener('keydown', function (e) {
  const cab = document.getElementById('site-header');
  if (e.key === 'Escape' && cab && cab.classList.contains('menu-aberto')) {
    abrirMenu(false);
    const botao = document.getElementById('nav-toggle');
    if (botao) botao.focus();
  }
});
window.matchMedia('(min-width: 1101px)').addEventListener('change', function (m) {
  if (m.matches) abrirMenu(false);
});
function goHomeMobile(e) {
  e.preventDefault();
  abrirMenu(false);
  window.scrollTo({ top: 0, behavior: 'smooth' });
  history.replaceState(null, '', '#home');
}
function cartaoAtual(grid) {
  const cards = Array.from(grid.children).filter(function (c) { return c.offsetWidth > 0; });
  const centro = cards.length && getComputedStyle(cards[0]).scrollSnapAlign.indexOf('center') === 0;
  const meio = grid.scrollLeft + (centro ? grid.clientWidth / 2 : 0);
  let idx = 0, melhor = Infinity;
  cards.forEach(function (c, i) {
    const d = Math.abs(c.offsetLeft + (centro ? c.offsetWidth / 2 : 0) - meio);
    if (d < melhor) { melhor = d; idx = i; }
  });
  return { cards: cards, idx: idx };
}
function scrollByCard(gridId, dir) {
  const grid = document.getElementById(gridId);
  if (!grid) return;
  const atual = cartaoAtual(grid);
  if (!atual.cards.length) return;
  const alvo = Math.min(atual.cards.length - 1, Math.max(0, atual.idx + dir));
  grid.scrollTo({ left: atual.cards[alvo].offsetLeft, behavior: 'smooth' });
}
function atualizarContadores() {
  document.querySelectorAll('.carrossel-contador').forEach(function (el) {
    const grid = document.getElementById(el.getAttribute('data-grid'));
    if (!grid) return;
    const a = cartaoAtual(grid);
    if (a.cards.length) el.textContent = (a.idx + 1) + ' de ' + a.cards.length;
  });
}
['resultados-grid', 'depoimentos-grid'].forEach(function (id) {
  const g = document.getElementById(id);
  if (g) g.addEventListener('scroll', atualizarContadores, { passive: true });
});
window.addEventListener('resize', atualizarContadores);
window.addEventListener('load', atualizarContadores);
atualizarContadores();
function toggleCurso(id, btn) {
  const el = document.getElementById(id);
  const open = el.style.display === 'grid';
  el.style.display = open ? 'none' : 'grid';
  btn.textContent = open ? 'Ver descrição completa' : 'Fechar descrição';
  btn.setAttribute('aria-expanded', open ? 'false' : 'true');
}
function baSlide(input) {
  const wrap = input.closest('div');
  const pct = Math.max(0, Math.min(100, Number(input.value)));
  wrap.querySelector('.ba-clip').style.clipPath = 'inset(0 ' + (100 - pct) + '% 0 0)';
  wrap.querySelector('.ba-line').style.left = pct + '%';
  const antes = wrap.querySelector('.ba-antes'), depois = wrap.querySelector('.ba-depois');
  if (antes) antes.style.visibility = pct < 15 ? 'hidden' : 'visible';
  if (depois) depois.style.visibility = pct > 85 ? 'hidden' : 'visible';
}
(function () {
  let ativo = null;
  function mover(e) {
    const r = ativo.getBoundingClientRect();
    const input = ativo.querySelector('input[type=range]');
    input.value = Math.round(Math.max(0, Math.min(100, ((e.clientX - r.left) / r.width) * 100)));
    baSlide(input);
  }
  document.addEventListener('pointerdown', function (e) {
    const q = e.target.closest && e.target.closest('.ba');
    if (!q || e.target.tagName === 'INPUT' || (e.pointerType === 'mouse' && e.button !== 0)) return;
    ativo = q;
    try { q.setPointerCapture(e.pointerId); } catch (_) {}
    mover(e);
  });
  document.addEventListener('pointermove', function (e) { if (ativo) mover(e); });
  ['pointerup', 'pointercancel'].forEach(function (n) {
    document.addEventListener(n, function () { ativo = null; });
  });
})();
let __scrolled = false;
window.addEventListener('scroll', function () {
  const y = window.scrollY;
  if (!__scrolled && y > 80) __scrolled = true;
  else if (__scrolled && y < 20) __scrolled = false;
  else return;
  document.getElementById('site-header').classList.toggle('scrolled', __scrolled);
}, { passive: true });
window.addEventListener('hashchange', function () {
  abrirMenu(false);
});
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal, .reveal-item').forEach((el) => revealObserver.observe(el));
</script>
