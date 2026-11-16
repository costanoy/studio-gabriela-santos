<?php
defined('RAIZ') || define('RAIZ', __DIR__);
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';

$c = conteudo_ler();
$ct = $c['contato'];
$h = $c['home'];
$vars = ['c' => $c, 'pagina' => 'home'];
$wa = link_whatsapp($ct);
$seta_esq = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M15 6l-6 6 6 6" stroke="#996C55" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
$seta_dir = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="#996C55" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
$seta_esq_marrom = str_replace('stroke="#996C55"', 'stroke="#FBF3EE"', $seta_esq);
$seta_dir_marrom = str_replace('stroke="#996C55"', 'stroke="#FBF3EE"', $seta_dir);
$estrela = '<svg width="18" height="18" viewBox="0 0 24 24" fill="#996C55"><path d="M12 2.5l2.9 6.4 6.9.7-5.2 4.8 1.5 6.9-6.1-3.6-6.1 3.6 1.5-6.9-5.2-4.8 6.9-.7z"></path></svg>';
$icone_pino = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z" stroke="#996C55" stroke-width="1.6" stroke-linejoin="round"></path><circle cx="12" cy="9.5" r="2.4" stroke="#996C55" stroke-width="1.6"></circle></svg>';
$icone_relogio = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><circle cx="12" cy="12" r="8.5" stroke="#996C55" stroke-width="1.6"></circle><path d="M12 7.5V12l3 2" stroke="#996C55" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
$icone_insta = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="#996C55" stroke-width="1.6"></rect><circle cx="12" cy="12" r="4" stroke="#996C55" stroke-width="1.6"></circle><circle cx="17.2" cy="6.8" r="1" fill="#996C55"></circle></svg>';
$seta_ver_mais = static fn(int $t): string => '<svg width="' . $t . '" height="' . $t . '" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="#FBF3EE" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';

parcial('head', $vars);
parcial('estilo-home');
?>
</head>
<body>
<?php parcial('cabecalho', $vars); ?>

  <section id="home" style="position:relative; min-height: 92vh; display:flex; align-items:center; padding: 0 5%; box-sizing:border-box;">
    <div style="position:absolute; inset:0; z-index:0; overflow:hidden; background:#996C55;">
      <img id="hero-bg-img" fetchpriority="high" src="<?= e(url_midia($h['hero']['imagem']['arquivo'])) ?>" alt="" style="position:absolute; right:-2%; top:0; width:92%; height:100%; object-fit:contain; object-position:right center;">
      <div style="position:absolute; inset:0; background: linear-gradient(115deg, rgba(58,42,34,0.8) 0%, rgba(58,42,34,0.5) 42%, rgba(58,42,34,0.18) 75%, rgba(58,42,34,0.06) 100%); pointer-events:none;"></div>
      <div style="position:absolute; inset:0; background: radial-gradient(ellipse at 30% 45%, rgba(0,0,0,0) 50%, rgba(0,0,0,0.2) 100%); pointer-events:none;"></div>
    </div>
    <div id="hero-content" style="position:relative; z-index:1; max-width: 620px; padding: 100px 0;">
      <h1 style="font-family:'Marcellus', serif; font-size: clamp(40px, 5vw, 64px); line-height:1.08; color:#FBF3EE; margin: 0 0 24px;"><?= e($h['hero']['titulo']) ?></h1>
      <p style="font-size: 20px; line-height:1.6; color:#F3E4DA; margin: 0 0 36px; max-width: 480px;"><?= e($h['hero']['texto']) ?></p>
      <div style="display:flex; gap:16px; flex-wrap:wrap;">
        <a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="btn-fill btn-lift" style="text-decoration:none; background:#996C55; color:#FBF3EE; padding:16px 32px; border-radius:10px; font-weight:700; font-size:16px; letter-spacing:0.02em; transition: background 0.2s ease, transform 0.2s ease;"><?= e($h['hero']['botao_principal']) ?></a>
        <a href="#procedimentos" class="btn-outline btn-lift" style="text-decoration:none; background:transparent; border:1.5px solid #F3E4DA; color:#F3E4DA; padding:16px 32px; border-radius:10px; font-weight:700; font-size:16px; letter-spacing:0.02em; transition: background 0.2s ease, transform 0.2s ease;"><?= e($h['hero']['botao_secundario']) ?></a>
      </div>
    </div>
  </section>

  <div style="padding: 72px 5% 0; box-sizing:border-box;">
    <div style="max-width:820px; margin:0 auto; background:#F3E4DA; border-radius:20px; padding: 48px 56px; text-align:center;">
      <p style="font-family:'Marcellus', serif; font-size: clamp(20px, 2.2vw, 26px); line-height:1.5; color:#3A2A22; margin:0 0 18px;"><?= e($h['boas_vindas']['paragrafos'][0]) ?></p>
      <p style="font-size:17px; line-height:1.7; color:#5C4A40; margin:0;"><?= e($h['boas_vindas']['paragrafos'][1]) ?></p>
    </div>
  </div>

  <img id="sobre-photo-mobile" src="<?= e(url_midia($h['sobre']['foto']['arquivo'])) ?>" alt="<?= e($h['sobre']['foto']['alt']) ?>" style="display:none; width:100%; max-width:300px; aspect-ratio:4/5; object-fit:cover; border-radius:20px; margin:48px auto 48px; padding:0 5%; box-sizing:border-box;">

  <section id="sobre" class="reveal" style="padding: 110px 5% 40px; display:grid; grid-template-columns: 0.85fr 1fr; gap: 72px; align-items:stretch; max-width:1280px; margin:0 auto; box-sizing:border-box;">
    <img id="sobre-foto" src="<?= e(url_midia($h['sobre']['foto']['arquivo'])) ?>" alt="<?= e($h['sobre']['foto']['alt']) ?>" style="width:100%; aspect-ratio: 4/5; object-fit:cover; border-radius:20px;">
    <div style="display:flex; flex-direction:column; height:100%;">
      <p style="font-size:13px; letter-spacing:0.15em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0 0 14px;"><?= e($h['sobre']['rotulo']) ?></p>
      <h2 style="font-family:'Marcellus', serif; font-size: clamp(30px, 3vw, 42px); margin: 0 0 24px; color:#3A2A22;"><?= e($h['sobre']['titulo']) ?></h2>
<?php foreach ($h['sobre']['paragrafos'] as $p): ?>
      <p style="font-size:17px; line-height:1.8; color:#5C4A40; margin: 0 0 18px;"><?= e($p) ?></p>
<?php endforeach; ?>
      <div id="sobre-stats" style="display:flex; gap:40px; flex-wrap:wrap; margin-top:auto;">
<?php foreach ($h['sobre']['numeros'] as $n): ?>
        <div>
          <p style="font-family:'Marcellus', serif; font-size:30px; margin:0; color:#996C55;"><?= e($n['valor']) ?></p>
          <p style="font-size:14px; margin:6px 0 0; color:#5C4A40;"><?= e($n['legenda']) ?></p>
        </div>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <div style="padding: 40px 5% 110px; display:grid; grid-template-columns: 1fr 0.85fr; gap: 72px; align-items:center; max-width:1280px; margin:0 auto; box-sizing:border-box;" id="sobre-video-block">
    <div>
<?php $vp = $h['sobre']['video_paragrafos']; foreach ($vp as $i => $p): ?>
      <p style="font-size:17px; line-height:1.8; color:#5C4A40; margin: 0 0 <?= $i === count($vp) - 1 ? '24' : '18' ?>px;"><?= e($p) ?></p>
<?php endforeach; ?>
      <p style="font-size:20px; font-weight:700; color:#996C55; margin:0; display:flex; align-items:center; gap:8px;"><?= e($h['sobre']['saiba_mais']) ?> <span id="saiba-arrow" style="display:inline-flex; align-items:center;">→</span></p>
    </div>
    <div style="width:100%; position:relative; display:flex; justify-content:center;">
      <video id="apresentacao-video" src="<?= e(url_midia($h['sobre']['video']['arquivo'])) ?>" controls="" playsinline="" preload="metadata" poster="<?= e(url_midia($h['sobre']['video']['poster'])) ?>" style="max-width:100%; max-height:480px; width:auto; aspect-ratio: 2/3; object-fit:cover; border-radius:20px; display:block; background:#000;"></video>
      <div onclick="toggleApresentacaoVideo()" style="position:absolute; top:0; left:0; right:0; bottom:44px; cursor:pointer;"></div>
    </div>
  </div>

  <section id="procedimentos" class="reveal" style="padding: 110px 5%; background:#996C55; box-sizing:border-box;">
    <div style="max-width:1280px; margin:0 auto;">
      <p style="font-size:13px; letter-spacing:0.15em; text-transform:uppercase; color:#E7CDBE; font-weight:700; margin:0 0 14px; text-align:center;"><?= e($h['procedimentos']['rotulo']) ?></p>
      <h2 style="font-family:'Marcellus', serif; font-size: clamp(30px, 3vw, 42px); margin: 0 0 56px; color:#FBF3EE; text-align:center;"><?= e($h['procedimentos']['titulo']) ?></h2>
      <div style="position:relative;">
      <div id="procedimentos-grid" style="display:flex; overflow-x:auto; scroll-snap-type:x proximity; -webkit-overflow-scrolling:touch; gap: 16px; padding-bottom:8px; user-select:none; cursor:grab;">
<?php foreach ($h['procedimentos']['itens'] as $proc): ?>
        <div style="background:#85604B; border-radius:20px; padding: 40px 32px; min-height:230px; box-sizing:border-box; flex:0 0 300px; scroll-snap-align:start;">
          <h3 style="font-family:'Marcellus', serif; font-size:22px; margin:0 0 12px; color:#FBF3EE;"><?= e($proc['titulo']) ?></h3>
          <p style="font-size:16px; line-height:1.7; color:#D8C4B8; margin:0;"><?= e($proc['texto']) ?></p>
        </div>
<?php endforeach; ?>
      </div>
      <button id="procedimentos-arrow-left" type="button" onclick="scrollByCard('procedimentos-grid', -1)" aria-label="Ver procedimentos anteriores" style="display:flex; position:absolute; left:-56px; top:50%; transform:translateY(-50%); width:44px; height:44px; border-radius:50%; background:#FBF3EE; border:none; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,0.25); cursor:pointer; z-index:2;">
        <?= $seta_esq ?>

      </button>
      <button id="procedimentos-arrow-right" type="button" onclick="scrollByCard('procedimentos-grid', 1)" aria-label="Ver mais procedimentos" style="display:flex; position:absolute; right:-56px; top:50%; transform:translateY(-50%); width:44px; height:44px; border-radius:50%; background:#FBF3EE; border:none; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,0.25); cursor:pointer; z-index:2;">
        <?= $seta_dir ?>

      </button>
      </div>
    </div>
  </section>

  <section id="resultados" class="reveal" style="padding: 110px 5%; box-sizing:border-box;">
    <div style="max-width:1280px; margin:0 auto;">
      <p style="font-size:13px; letter-spacing:0.15em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0 0 14px; text-align:center;"><?= e($h['resultados']['rotulo']) ?></p>
      <h2 style="font-family:'Marcellus', serif; font-size: clamp(30px, 3vw, 42px); margin: 0 0 56px; color:#3A2A22; text-align:center;"><?= e($h['resultados']['titulo']) ?></h2>
      <div style="position:relative;">
      <div id="resultados-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px;">
<?php foreach (resultados_home($c) as $item): ?>

        <div style="position:relative;">
<?php parcial('comparador', ['item' => $item]); ?>
          <p style="font-size:16px; color:#5C4A40; margin:16px 0 0; text-align:center;"><?= e($item['titulo']) ?></p>
        </div>
<?php endforeach; ?>

        <div id="ver-mais-cell" style="position:relative; height:100%;">
          <div style="position:relative; width:100%; aspect-ratio:3/4;">
            <a href="resultados.html" class="btn-fill" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: flex; align-items: center; gap: 10px; text-decoration: none; color: #FBF3EE; font-weight: 700; font-size: 34px; white-space: nowrap; background: #996C55; border-radius: 14px; padding: 10px 22px; transition: background 0.2s ease;">
              <?= e($h['resultados']['texto_ver_mais']) ?>

              <?= $seta_ver_mais(24) ?>

            </a>
          </div>
        </div>

      </div>
      <button class="mobile-scroll-arrow" onclick="scrollByCard('resultados-grid', -1)" aria-label="Ver resultados anteriores" style="display:none; position:absolute; left:-6px; top:50%; transform:translateY(-50%); width:44px; height:44px; border-radius:50%; background:#996C55; border:none; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,0.25); cursor:pointer; z-index:2;">
        <?= $seta_esq_marrom ?>

      </button>
      <button class="mobile-scroll-arrow" onclick="scrollByCard('resultados-grid', 1)" aria-label="Ver mais resultados" style="display:none; position:absolute; right:-6px; top:50%; transform:translateY(-50%); width:44px; height:44px; border-radius:50%; background:#996C55; border:none; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,0.25); cursor:pointer; z-index:2;">
        <?= $seta_dir_marrom ?>

      </button>
      </div>
      <a id="ver-mais-mobile" href="resultados.html" style="display:none; margin-top:48px; align-items:center; justify-content:center; gap:10px; text-decoration:none; color:#FBF3EE; font-weight:700; font-size:18px; background:#996C55; border-radius:14px; padding:14px 22px;">
        <?= e($h['resultados']['texto_ver_mais']) ?>

        <?= $seta_ver_mais(20) ?>

      </a>
    </div>
  </section>

  <section id="cursos" class="reveal" style="padding: 110px 5% 130px; background:#F3E4DA; box-sizing:border-box;">
    <div style="max-width:1080px; margin:0 auto;">
      <p style="font-size:13px; letter-spacing:0.15em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0 0 14px; text-align:center;"><?= e($h['cursos']['rotulo']) ?></p>
      <h2 style="font-family:'Marcellus', serif; font-size: clamp(30px, 3vw, 42px); margin: 0 0 16px; color:#3A2A22; text-align:center;"><?= e($h['cursos']['titulo']) ?></h2>
      <p style="font-size:17px; color:#5C4A40; text-align:center; max-width:560px; margin: 0 auto 60px;"><?= e($h['cursos']['descricao']) ?></p>

      <div style="display:flex; flex-direction:column; gap:28px;">
<?php foreach ($h['cursos']['itens'] as $curso): ?>
        <div style="background:#FBF3EE; border-radius:20px; padding: 40px 44px; box-shadow: 0 2px 24px rgba(58,42,34,0.06);">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:24px; flex-wrap:wrap;">
            <div>
              <h3 style="font-family:'Marcellus', serif; font-size:26px; margin:0 0 8px; color:#3A2A22;"><?= e($curso['titulo']) ?></h3>
              <p style="font-size:15px; color:#996C55; font-weight:700; margin:0 0 16px; letter-spacing:0.02em;"><?= e($curso['carga_horaria']) ?></p>
              <p style="font-size:16px; line-height:1.7; color:#5C4A40; margin:0; max-width:560px;"><?= e($curso['descricao']) ?></p>
            </div>
            <div style="display:flex; flex-direction:column; gap:10px; min-width:220px;">
              <a href="<?= e(link_whatsapp($ct, $curso['mensagem_whatsapp'])) ?>" target="_blank" rel="noopener" class="btn-fill" style="text-decoration:none; text-align:center; background:#996C55; color:#FBF3EE; padding:13px 24px; border-radius:10px; font-weight:700; font-size:15px; transition: background 0.2s ease;">Chamar no WhatsApp</a>
              <button onclick="toggleCurso('<?= e($curso['id']) ?>', this)" class="curso-outline" style="cursor:pointer; background:transparent; border:1.5px solid #996C55; color:#996C55; padding:13px 24px; border-radius:10px; font-weight:700; font-size:15px; font-family:inherit; transition: background 0.2s ease;">Ver descrição completa</button>
            </div>
          </div>
          <div id="<?= e($curso['id']) ?>" style="display:none; margin-top:32px; padding-top:32px; border-top:1px solid #E7CDBE; grid-template-columns: 1fr 1fr; gap:40px;">
            <div>
              <h4 style="font-family:'Marcellus', serif; font-size:16px; margin:0 0 14px; color:#3A2A22;">Conteúdo programático</h4>
              <ul style="margin:0; padding-left:18px; color:#5C4A40; font-size:15.5px; line-height:1.9;">
<?php foreach ($curso['conteudo'] as $li): ?>
                <li><?= e($li) ?></li>
<?php endforeach; ?>
              </ul>
            </div>
            <div>
              <h4 style="font-family:'Marcellus', serif; font-size:16px; margin:0 0 14px; color:#3A2A22;">Módulos</h4>
              <ul style="margin:0; padding-left:18px; color:#5C4A40; font-size:15.5px; line-height:1.9;">
<?php foreach ($curso['modulos'] as $li): ?>
                <li><?= e($li) ?></li>
<?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <section id="depoimentos" class="reveal" style="padding: 110px 5%; box-sizing:border-box;">
    <div style="max-width:1280px; margin:0 auto;">
      <p style="font-size:13px; letter-spacing:0.15em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0 0 14px; text-align:center;"><?= e($h['depoimentos']['rotulo']) ?></p>
      <h2 style="font-family:'Marcellus', serif; font-size: clamp(30px, 3vw, 42px); margin: 0 0 56px; color:#3A2A22; text-align:center;"><?= e($h['depoimentos']['titulo']) ?></h2>
      <div style="position:relative;">
      <div id="depoimentos-grid" style="display:flex; overflow-x:auto; scroll-snap-type:x proximity; -webkit-overflow-scrolling:touch; gap: 32px; padding-bottom:8px; user-select:none;">
<?php foreach ($h['depoimentos']['itens'] as $dep): ?>
        <div style="background:#F3E4DA; padding: 36px 32px; border-radius:20px; display:flex; flex-direction:column; height:100%; box-sizing:border-box; flex:0 0 340px; scroll-snap-align:start;">
          <div style="display:flex; gap:4px; margin:0 0 18px;">
<?php for ($i = 0; $i < 5; $i++): ?>
            <?= $estrela ?>

<?php endfor; ?>
          </div>
          <p style="font-size:16.5px; line-height:1.8; color:#4A3A32; margin:0 0 24px; font-style:italic;"><?= e($dep['texto']) ?></p>
          <div style="margin-top:auto;">
            <p style="margin:0; font-weight:700; font-size:15.5px; color:#3A2A22;"><?= e($dep['autor']) ?></p>
            <p style="margin:2px 0 0; font-size:14px; color:#996C55;"><?= e($dep['servico']) ?></p>
          </div>
        </div>
<?php endforeach; ?>
      </div>
      <button id="depoimentos-arrow-left" type="button" onclick="scrollByCard('depoimentos-grid', -1)" aria-label="Ver depoimentos anteriores" style="display:flex; position:absolute; left:-56px; top:50%; transform:translateY(-50%); width:44px; height:44px; border-radius:50%; background:#FBF3EE; border:none; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,0.25); cursor:pointer; z-index:2;">
        <?= $seta_esq ?>

      </button>
      <button id="depoimentos-arrow-right" type="button" onclick="scrollByCard('depoimentos-grid', 1)" aria-label="Ver mais depoimentos" style="display:flex; position:absolute; right:-56px; top:50%; transform:translateY(-50%); width:44px; height:44px; border-radius:50%; background:#FBF3EE; border:none; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,0.25); cursor:pointer; z-index:2;">
        <?= $seta_dir ?>

      </button>
      </div>
    </div>
  </section>

  <section id="contato" class="reveal" style="padding: 110px 5% 90px; background:#F3E4DA; box-sizing:border-box;">
    <div style="max-width:1150px; margin:0 auto; display:grid; grid-template-columns: 1fr 1fr; gap:72px; align-items:center;" id="contato-grid">
      <div style="margin-left: 48px;">
        <p style="font-size:13px; letter-spacing:0.15em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0 0 14px;"><?= e($h['contato_secao']['rotulo']) ?><br></p>
        <h2 style="font-family:'Marcellus', serif; font-size: clamp(30px, 3vw, 42px); margin: 0 0 28px; color:#3A2A22;"><?= e($h['contato_secao']['titulo']) ?></h2>
        <a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="btn-fill btn-lift" style="text-decoration:none; display:inline-flex; align-items:center; gap:10px; background:#996C55; color:#FBF3EE; padding:18px 34px; border-radius:10px; font-weight:700; font-size:17px; margin-bottom:44px; transition: background 0.2s ease, transform 0.2s ease;"><?= e($h['hero']['botao_principal']) ?></a>

        <div style="display:flex; flex-direction:column; gap:26px;">
          <div>
            <div style="display:flex; gap:10px; align-items:center; margin:0 0 8px;">
              <?= $icone_pino ?>

              <p style="font-size:12.5px; letter-spacing:0.1em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0;">Endereço</p>
            </div>
            <p style="font-size:16.5px; line-height:1.7; color:#5C4A40; margin:0;"><?= implode('<br>', array_map('e', $ct['endereco_linhas'])) ?></p>
          </div>
          <div>
            <div style="display:flex; gap:10px; align-items:center; margin:0 0 8px;">
              <?= $icone_relogio ?>

              <p style="font-size:12.5px; letter-spacing:0.1em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0;">Horário de atendimento</p>
            </div>
            <p style="font-size:16.5px; line-height:1.7; color:#5C4A40; margin:0;"><?= e($ct['horario']) ?></p>
          </div>
          <div>
            <div style="display:flex; gap:10px; align-items:center; margin:0 0 8px;">
              <?= $icone_insta ?>

              <p style="font-size:12.5px; letter-spacing:0.1em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0;">Instagram</p>
            </div>
            <a href="https://instagram.com/<?= e($ct['instagram_usuario']) ?>" target="_blank" rel="noopener" style="font-size:16.5px; color:#5C4A40; text-decoration:none; font-weight:600;">@<?= e($ct['instagram_usuario']) ?></a>
          </div>
          <a id="avaliacao-online-link" href="<?= e($ct['avaliacao_online']) ?>" target="_blank" rel="noopener" class="btn-outline-brown" style="text-decoration:none; display:inline-flex; align-items:center; gap:10px; background:transparent; border:1.5px solid #996C55; color:#996C55; padding:14px 28px; border-radius:10px; font-weight:700; font-size:16px; align-self:flex-start; transition: background 0.2s ease;">Avaliação Online</a>
        </div>
      </div>
      <div style="position:relative; width:100%; border-radius:20px; overflow:hidden; min-height:360px;">
        <iframe src="<?= e($ct['maps_embed']) ?>" title="Localização do Studio Gabriela Santos" style="position:absolute; inset:0; width:100%; height:100%; border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <a href="<?= e($ct['maps_link']) ?>" target="_blank" rel="noopener" class="btn-fill" style="position:absolute; bottom:16px; left:16px; text-decoration:none; background:#996C55; color:#FBF3EE; padding:11px 20px; border-radius:10px; font-weight:700; font-size:13px; transition: background 0.2s ease;">Abrir no Google Maps</a>
      </div>
    </div>
  </section>

<?php parcial('rodape', $vars); ?>
<?php parcial('script-home'); ?>

</body></html>
