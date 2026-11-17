<?php defined('RAIZ') || exit;
/** Barra do topo. Variáveis: $c (conteúdo), $pagina ('home' ou 'resultados'). */
$logo_texto = $c['marca']['logo_texto'];
$logo_icone = $c['marca']['logo_icone'];
$wa = link_whatsapp($c['contato']);
$menu = [['#home', 'Início'], ['#sobre', 'Sobre'], ['#procedimentos', 'Procedimentos'], ['#resultados', 'Resultados'], ['#cursos', 'Cursos'], ['#depoimentos', 'Depoimentos'], ['#contato', 'Contato']];
$fecha = 'abrirMenu(false);';
if ($pagina === 'home'): ?>
<div class="pagina">

  <div class="cab-wrap">
  <header id="site-header" class="cab">
    <div class="cab-int">
      <a href="#home" class="cab-logo" id="site-logo-wrap" onclick="goHome(event)">
        <img id="logo-full" src="<?= e(url_midia($logo_texto['arquivo'])) ?>" alt="<?= e($logo_texto['alt']) ?>" width="1920" height="1080">
        <img id="logo-gs" src="<?= e(url_midia($logo_icone['arquivo'])) ?>" alt="" aria-hidden="true" width="<?= (int) $logo_icone['largura'] ?>" height="<?= (int) $logo_icone['altura'] ?>">
      </a>
      <nav id="desktop-nav" class="cab-menu" aria-label="Menu principal">
<?php foreach ($menu as $i => [$ancora, $rotulo]): ?>
        <a href="<?= e($ancora) ?>"<?= $i === 0 ? ' onclick="goHome(event)"' : '' ?>><?= e($rotulo) ?></a>
<?php endforeach; ?>
      </nav>
      <a id="desktop-cta" class="btn btn-contorno-claro cab-cta" href="<?= e($wa) ?>" target="_blank" rel="noopener">Agendar horário</a>
      <button id="nav-toggle" class="cab-toggle" type="button" onclick="alternarMenu()" aria-label="Abrir menu" aria-expanded="false" aria-controls="mobile-nav-wrap"><span></span><span></span></button>
    </div>
    <nav id="mobile-nav-wrap" class="menu-celular" aria-label="Menu do celular">
<?php foreach ($menu as $i => [$ancora, $rotulo]): ?>
      <a href="<?= e($ancora) ?>" onclick="<?= $i === 0 ? 'goHomeMobile(event)' : $fecha ?>"><?= e($rotulo) ?></a>
<?php endforeach; ?>
      <a class="btn btn-claro" href="<?= e($wa) ?>" target="_blank" rel="noopener" onclick="<?= $fecha ?>">Agendar horário</a>
    </nav>
  </header>
  </div>
<?php else: ?>
<div class="pagina">

  <div class="cab-wrap">
  <header id="site-header" class="cab scrolled cab-res">
    <div class="cab-int">
      <a href="index.html" class="cab-logo" id="site-logo-wrap">
        <img id="logo-full" src="<?= e(url_midia($logo_texto['arquivo'])) ?>" alt="<?= e($logo_texto['alt']) ?>" width="1920" height="1080">
        <img id="logo-gs" src="<?= e(url_midia($logo_icone['arquivo'])) ?>" alt="" aria-hidden="true" width="<?= (int) $logo_icone['largura'] ?>" height="<?= (int) $logo_icone['altura'] ?>">
      </a>
      <a id="rc-back-link" class="btn-texto btn-texto-claro cab-voltar" href="index.html"><svg width="22" height="12" viewBox="0 0 22 12" fill="none" aria-hidden="true"><path d="M21 6H1M6 1L1 6l5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"></path></svg><span class="t">Voltar</span></a>
    </div>
  </header>
  </div>
<?php endif; ?>
