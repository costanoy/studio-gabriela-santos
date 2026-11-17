<?php
// Prévia das direções visuais (A "Retrato" e B "Monograma"). NÃO faz parte do site publicado:
// a pasta prototipos/ não está na lista branca de ferramentas/montar-publicacao.php.
defined('RAIZ') || define('RAIZ', dirname(__DIR__));
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';

$c = conteudo_ler();
$h = $c['home'];
$wa = link_whatsapp($c['contato']);

/** Caminho de mídia a partir da raiz do site (as prévias ficam em /prototipos/). */
function pm(string $arquivo): string
{
    return '/' . url_midia($arquivo);
}

function proto_head(string $titulo, string $css_proprio, string $extra = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="pt-BR"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titulo) ?></title>
<script>document.documentElement.classList.add('js');</script>
<link rel="preload" href="/assets/fonts/cormorant-garamond-500-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/23e9ba1c.woff2" as="font" type="font/woff2" crossorigin>
<?= $extra ?>
<link rel="stylesheet" href="/prototipos/base.css">
<link rel="stylesheet" href="/prototipos/<?= e($css_proprio) ?>">
</head>
<?php
}

function proto_cabecalho(array $c, string $wa): void
{
    $logo_texto = $c['marca']['logo_texto'];
    $logo_icone = $c['marca']['logo_icone'];
    $menu = [['#home', 'Início'], ['#sobre', 'Sobre'], ['#procedimentos', 'Procedimentos'], ['#resultados', 'Resultados'], ['#cursos', 'Cursos'], ['#depoimentos', 'Depoimentos'], ['#contato', 'Contato']];
    ?>
<div class="cab-wrap">
  <header id="site-header" class="cab">
    <div class="cab-int">
      <a href="#home" class="cab-logo" id="site-logo-wrap">
        <img id="logo-full" src="<?= e(pm($logo_texto['arquivo'])) ?>" alt="<?= e($logo_texto['alt']) ?>" width="1920" height="1080">
        <img id="logo-gs" src="<?= e(pm($logo_icone['arquivo'])) ?>" alt="" aria-hidden="true" width="<?= (int) $logo_icone['largura'] ?>" height="<?= (int) $logo_icone['altura'] ?>">
      </a>
      <nav id="desktop-nav" class="cab-menu" aria-label="Menu principal">
<?php foreach ($menu as [$ancora, $rotulo]): ?>
        <a href="<?= e($ancora) ?>"><?= e($rotulo) ?></a>
<?php endforeach; ?>
      </nav>
      <a id="desktop-cta" class="btn btn-contorno-claro cab-cta" href="<?= e($wa) ?>" target="_blank" rel="noopener">Agendar horário</a>
      <button id="nav-toggle" class="cab-toggle" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="mobile-nav-wrap"><span></span><span></span></button>
    </div>
    <nav id="mobile-nav-wrap" class="menu-celular" aria-label="Menu do celular">
<?php foreach ($menu as [$ancora, $rotulo]): ?>
      <a href="<?= e($ancora) ?>"><?= e($rotulo) ?></a>
<?php endforeach; ?>
      <a class="btn btn-claro" href="<?= e($wa) ?>" target="_blank" rel="noopener">Agendar horário</a>
    </nav>
  </header>
</div>
<?php
}

/** Faixa areia com o vídeo e os parágrafos do vídeo. $lado = 'a' (vídeo à esquerda) ou 'b' (vídeo à direita). */
function proto_faixa_video(array $s, string $lado): void
{
    $vl = (int) ($s['video']['largura'] ?? 0) ?: 576;
    $va = (int) ($s['video']['altura'] ?? 0) ?: 1024;
    ?>
  <div class="faixa-video faixa-<?= e($lado) ?>" id="sobre-video-block">
    <div class="contem faixa-grade">
      <div class="faixa-texto">
<?php foreach ($s['video_paragrafos'] as $p): ?>
        <p><?= e($p) ?></p>
<?php endforeach; ?>
        <p class="saiba"><?= e($s['saiba_mais']) ?>
          <svg class="seta-lado" width="34" height="14" viewBox="0 0 34 14" fill="none" aria-hidden="true"><path d="M33 7H1M7 1L1 7l6 6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <svg class="seta-baixo" width="14" height="30" viewBox="0 0 14 30" fill="none" aria-hidden="true"><path d="M7 1v28M1 23l6 6 6-6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </p>
      </div>
      <div class="faixa-video-col">
        <div class="video-moldura">
          <video id="apresentacao-video" src="<?= e(pm($s['video']['arquivo'])) ?>" controls playsinline preload="none" poster="<?= e(pm($s['video']['poster'])) ?>" width="<?= $vl ?>" height="<?= $va ?>"></video>
          <button type="button" class="video-play" aria-label="Reproduzir vídeo"><svg width="24" height="24" viewBox="0 0 24 24" fill="#FBF3EE" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg></button>
        </div>
      </div>
    </div>
  </div>
<?php
}

function proto_script(): void
{
    ?>
<script>
(function () {
  var cab = document.getElementById('site-header');
  var rolou = false;
  function aoRolar() {
    var y = window.scrollY;
    if (!rolou && y > 80) rolou = true; else if (rolou && y < 20) rolou = false;
    cab.classList.toggle('rolou', rolou);
  }
  window.addEventListener('scroll', aoRolar, { passive: true });
  aoRolar();

  var botao = document.getElementById('nav-toggle');
  function menu(abrir) {
    cab.classList.toggle('menu-aberto', abrir);
    botao.setAttribute('aria-expanded', abrir ? 'true' : 'false');
    botao.setAttribute('aria-label', abrir ? 'Fechar menu' : 'Abrir menu');
  }
  botao.addEventListener('click', function () { menu(!cab.classList.contains('menu-aberto')); });
  document.querySelectorAll('#mobile-nav-wrap a').forEach(function (a) { a.addEventListener('click', function () { menu(false); }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { menu(false); botao.focus(); } });
  window.matchMedia('(min-width: 1101px)').addEventListener('change', function (m) { if (m.matches) menu(false); });

  document.querySelectorAll('.video-play').forEach(function (b) {
    b.addEventListener('click', function () { var v = b.parentElement.querySelector('video'); if (v) v.play(); });
  });
  document.addEventListener('play', function (e) {
    var b = e.target.parentElement && e.target.parentElement.querySelector('.video-play'); if (b) b.hidden = true;
  }, true);
  ['pause', 'ended'].forEach(function (n) {
    document.addEventListener(n, function (e) {
      var b = e.target.parentElement && e.target.parentElement.querySelector('.video-play'); if (b) b.hidden = false;
    }, true);
  });

  var itens = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (x) { if (x.isIntersecting) { x.target.classList.add('visible'); io.unobserve(x.target); } });
    }, { threshold: 0.12 });
    itens.forEach(function (i) { io.observe(i); });
  } else {
    itens.forEach(function (i) { i.classList.add('visible'); });
  }
})();
</script>
<?php
}
