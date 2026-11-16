<?php defined('RAIZ') || exit; ?>
<script>
let __scrolled = false;
window.addEventListener('scroll', function () {
  const y = window.scrollY;
  if (!__scrolled && y > 80) __scrolled = true;
  else if (__scrolled && y < 20) __scrolled = false;
  else return;
  document.getElementById('site-header').classList.toggle('scrolled', __scrolled);
}, { passive: true });
function toggleVideo(overlay) {
  const video = overlay.previousElementSibling && overlay.previousElementSibling.tagName === 'VIDEO'
    ? overlay.previousElementSibling
    : overlay.parentElement.querySelector('video');
  const v = overlay.parentElement.querySelector('video');
  if (v) { v.paused ? v.play() : v.pause(); }
}
function baSlide(input) {
  const wrap = input.closest('div');
  const pct = input.value;
  wrap.querySelector('.ba-clip').style.clipPath = 'inset(0 ' + (100 - pct) + '% 0 0)';
  wrap.querySelector('.ba-line').style.left = pct + '%';
}
</script>
