<?php defined('RAIZ') || exit; ?>
<script>
function tocarVideo(btn) {
  const v = btn.parentElement.querySelector('video');
  if (v) v.play();
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
function goHomeMobile(e) {
  e.preventDefault();
  const wrap = document.getElementById('mobile-nav-wrap');
  if (wrap) wrap.style.display = 'none';
  window.scrollTo({ top: 0, behavior: 'smooth' });
  history.replaceState(null, '', '#home');
}
const __carouselIdx = {};
function scrollByCard(gridId, dir) {
  const grid = document.getElementById(gridId);
  if (!grid) return;
  const cards = Array.from(grid.children);
  if (!cards.length) return;
  const curIdx = __carouselIdx[gridId] ?? 0;
  const targetIdx = Math.min(cards.length - 1, Math.max(0, curIdx + dir));
  __carouselIdx[gridId] = targetIdx;
  grid.scrollTo({ left: cards[targetIdx].offsetLeft, behavior: 'smooth' });
}
['procedimentos-grid', 'resultados-grid', 'depoimentos-grid'].forEach((gridId) => {
  const grid = document.getElementById(gridId);
  if (!grid) return;
  grid.addEventListener('scrollend', () => {
    const cards = Array.from(grid.children);
    if (!cards.length) return;
    const cur = grid.scrollLeft;
    let idx = 0, best = Infinity;
    cards.forEach((c, i) => { const d = Math.abs(c.offsetLeft - cur); if (d < best) { best = d; idx = i; } });
    __carouselIdx[gridId] = idx;
  }, { passive: true });
});
function toggleCurso(id, btn) {
  const el = document.getElementById(id);
  const open = el.style.display === 'grid';
  el.style.display = open ? 'none' : 'grid';
  btn.textContent = open ? 'Ver descrição completa' : 'Fechar descrição';
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
  const wrap = document.getElementById('mobile-nav-wrap');
  if (wrap) wrap.style.display = 'none';
});
(function () {
  const grid = document.getElementById('procedimentos-grid');
  if (!grid) return;
  let isDown = false, startX = 0, startScroll = 0;
  grid.addEventListener('mousedown', (e) => { isDown = true; grid.style.cursor = 'grabbing'; grid.style.scrollSnapType = 'none'; startX = e.pageX; startScroll = grid.scrollLeft; });
  window.addEventListener('mouseup', () => { if (!isDown) return; isDown = false; grid.style.cursor = 'grab'; grid.style.scrollSnapType = 'x proximity'; });
  grid.addEventListener('mousemove', (e) => { if (!isDown) return; e.preventDefault(); grid.scrollLeft = startScroll - (e.pageX - startX); });
})();
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach((el) => revealObserver.observe(el));
</script>
