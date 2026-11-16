<?php defined('RAIZ') || exit;
/** Barra do topo. Variáveis: $c (conteúdo), $pagina ('home' ou 'resultados'). */
$logo_texto = $c['marca']['logo_texto'];
$logo_icone = $c['marca']['logo_icone'];
$wa = link_whatsapp($c['contato']);
$menu = [['#home', 'Início'], ['#sobre', 'Sobre'], ['#procedimentos', 'Procedimentos'], ['#resultados', 'Resultados'], ['#cursos', 'Cursos'], ['#depoimentos', 'Depoimentos'], ['#contato', 'Contato']];
$fecha = 'abrirMenu(false);';
if ($pagina === 'home'): ?>
<div style="font-family: 'Mulish', sans-serif; color: #3A2A22; background: #FBF3EE; overflow-x: clip;">

  <header id="site-header" style="position: sticky; top: 0; z-index: 50; backdrop-filter: blur(8px); border-bottom: 1px solid #5C4A40; display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; padding: 14px 24px; background-color: #3A2A22F0; background-image: linear-gradient(135deg, #3A2A22, #5C4A40);" onclick="abrirMenu(false)">
    <a href="#home" onclick="goHome(event)" id="site-logo-wrap" style="position:relative; display:block; justify-self:center;">
      <img id="logo-full" src="<?= e(url_midia($logo_texto['arquivo'])) ?>" alt="<?= e($logo_texto['alt']) ?>" style="height: 108px; width: auto; display: block; opacity:1;">
      <img id="logo-gs" src="<?= e(url_midia($logo_icone['arquivo'])) ?>" alt="" aria-hidden="true" style="height: 108px; width: auto; position:absolute; left:50%; top:0; transform:translateX(-50%); opacity:0;">
    </a>
    <nav style="display:flex; gap: 28px; align-items:center; justify-self: center;" id="desktop-nav">
<?php foreach ($menu as $i => [$ancora, $rotulo]): ?>
      <a href="<?= e($ancora) ?>"<?= $i === 0 ? ' onclick="goHome(event)"' : '' ?> class="nav-link" style="text-decoration: none; color: #FBF3EE; font-weight: 600; font-size: 16px; letter-spacing: 0.02em; transition: color 0.2s ease;"><?= e($rotulo) ?></a>
<?php endforeach; ?>
    </nav>
    <div id="header-right" style="display:flex; align-items:center; justify-content:flex-end; gap:14px; justify-self:center;">
      <a id="desktop-cta" href="<?= e($wa) ?>" target="_blank" rel="noopener" class="btn-fill" style="text-decoration:none; background:#7D5843; color:#FBF3EE; padding:11px 22px; border-radius:10px; font-weight:700; font-size:15px; letter-spacing:0.02em; white-space:nowrap; transition: background 0.2s ease;">Agendar horário</a>
      <button id="nav-toggle" type="button" onclick="event.stopPropagation(); alternarMenu()" aria-label="Abrir menu" aria-expanded="false" aria-controls="mobile-nav-wrap" style="width:40px; height:40px; background:transparent; border:1.5px solid #F3E4DA; border-radius:10px; cursor:pointer; flex-direction:column; align-items:center; justify-content:center; gap:4px; flex-shrink:0;">
        <span style="width:18px; height:1.5px; background:#F3E4DA; display:block;"></span>
        <span style="width:18px; height:1.5px; background:#F3E4DA; display:block;"></span>
        <span style="width:18px; height:1.5px; background:#F3E4DA; display:block;"></span>
      </button>
    </div>

    <nav id="mobile-nav-wrap" onclick="event.stopPropagation()" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:60; background:#7D5843; border-bottom:1px solid #5C4A40; border-radius: 0 0 20px 20px; flex-direction:column; padding: 8px 24px 20px;">
<?php foreach ($menu as $i => [$ancora, $rotulo]): ?>
      <a href="<?= e($ancora) ?>" onclick="<?= $i === 0 ? 'goHomeMobile(event)' : $fecha ?>" style="text-decoration:none; color:#FBF3EE; font-weight:600; font-size:16px; padding:12px 0; border-bottom:1px solid #5C4A40;"><?= e($rotulo) ?></a>
<?php endforeach; ?>
      <a href="<?= e($wa) ?>" target="_blank" rel="noopener" onclick="<?= $fecha ?>" style="text-decoration:none; text-align:center; background:#FBF3EE; color:#7D5843; font-weight:700; font-size:16px; padding:12px 0; margin-top:12px; border-radius:10px;">Agendar horário</a>
    </nav>
  </header>
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
