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
<div style="font-family: 'Mulish', sans-serif; color: #3A2A22; background: #FBF3EE;">

  <header id="site-header" style="position: sticky; top: 0; z-index: 50; background-color: #3A2A22F0; background-image: linear-gradient(135deg, #3A2A22, #5C4A40); backdrop-filter: blur(8px); border-bottom: 1px solid #5C4A40; display:grid; grid-template-columns: 1fr auto 1fr; align-items:center; padding: 14px 5%;">
    <div></div>
    <a href="index.html" style="position:relative; justify-self:center; grid-column:2; display:block;">
      <img id="logo-full" src="<?= e(url_midia($logo_texto['arquivo'])) ?>" alt="<?= e($logo_texto['alt']) ?>" style="height: 100px; width: auto; display:block; opacity:1;">
      <img id="logo-gs" src="<?= e(url_midia($logo_icone['arquivo'])) ?>" alt="" aria-hidden="true" style="height: 100px; width: auto; position:absolute; left:50%; top:0; transform:translateX(-50%); opacity:0;">
    </a>
    <a id="rc-back-link" href="index.html" style="position:absolute; right:5%; top:50%; transform:translateY(-50%); text-decoration:none; color:#F3E4DA; font-weight:700; font-size:14.5px; display:flex; align-items:center; gap:8px;">Voltar</a>
  </header>
<?php endif; ?>
