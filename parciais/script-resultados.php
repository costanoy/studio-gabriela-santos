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
