<?php
defined('RAIZ') || define('RAIZ', __DIR__);
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';

$c = conteudo_ler();
$ct = $c['contato'];
$h = $c['home'];
$hero = $h['hero'];
$sobre = $h['sobre'];
$foto = $sobre['foto'];
$vars = ['c' => $c, 'pagina' => 'home'];
$wa = link_whatsapp($ct);
$seta_esq = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
$seta_dir = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
$estrela = '<svg width="16" height="16" viewBox="0 0 24 24" fill="#996C55" aria-hidden="true"><path d="M12 2.5l2.9 6.4 6.9.7-5.2 4.8 1.5 6.9-6.1-3.6-6.1 3.6 1.5-6.9-5.2-4.8 6.9-.7z"></path></svg>';
$ic = static fn(string $d): string => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . $d . '</svg>';
$icone_pino = $ic('<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z" stroke="#E3C9A8" stroke-width="1.4" stroke-linejoin="round"></path><circle cx="12" cy="9.5" r="2.4" stroke="#E3C9A8" stroke-width="1.4"></circle>');
$icone_relogio = $ic('<circle cx="12" cy="12" r="8.5" stroke="#E3C9A8" stroke-width="1.4"></circle><path d="M12 7.5V12l3 2" stroke="#E3C9A8" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>');
$icone_insta = $ic('<rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="#E3C9A8" stroke-width="1.4"></rect><circle cx="12" cy="12" r="4" stroke="#E3C9A8" stroke-width="1.4"></circle><circle cx="17.2" cy="6.8" r="1" fill="#E3C9A8"></circle>');
$seta_ver_mais = '<svg width="22" height="12" viewBox="0 0 22 12" fill="none" aria-hidden="true"><path d="M1 6h20M16 1l5 5-5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
$vl = (int) ($sobre['video']['largura'] ?? 0) ?: 576;
$va = (int) ($sobre['video']['altura'] ?? 0) ?: 1024;

parcial('head', $vars);
parcial('estilo-home');
?>
</head>
<body>
<?php parcial('cabecalho', $vars); ?>
<main id="conteudo">

  <section id="home" class="hero">
    <img class="marca-dagua" id="hero-bg-img" src="<?= e(url_midia($hero['imagem']['arquivo'])) ?>" alt="" aria-hidden="true" width="<?= (int) $hero['imagem']['largura'] ?>" height="<?= (int) $hero['imagem']['altura'] ?>" fetchpriority="high">
    <div class="hero-int" id="hero-content">
      <div class="hero-texto">
        <?php $chamada = (string) ($hero['chamada'] ?? 'Estética corporal e facial em Goianésia'); ?>
        <h1><?php if ($chamada !== ''): ?><span class="hero-chamada"><?= e($chamada) ?></span> <?php endif; ?><?= e($hero['titulo']) ?></h1>
        <p class="hero-apoio"><?= e($hero['texto']) ?></p>
        <div class="hero-acoes">
          <a class="btn btn-claro" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= e($hero['botao_principal']) ?></a>
          <a class="btn btn-contorno-claro" href="#procedimentos"><?= e($hero['botao_secundario']) ?></a>
        </div>
      </div>
    </div>
    <span class="hero-linha" aria-hidden="true"></span>
  </section>

  <section class="boas">
    <div class="contem boas-int">
      <div class="boas-txt">
<?php foreach ($h['boas_vindas']['paragrafos'] as $i => $p): ?>
        <p class="<?= $i === 0 ? 'boas-frase' : 'boas-corrido' ?>"><?= e($p) ?></p>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <section id="sobre" class="sobre">
    <div class="contem sobre-grade">
      <div class="sobre-cab">
        <p class="rotulo"><?= e($sobre['rotulo']) ?></p>
        <h2><?= e($sobre['titulo']) ?></h2>
      </div>
      <div class="retrato-col reveal" id="sobre-retrato">
        <div class="retrato-b">
          <img id="sobre-foto" src="<?= e(url_midia($foto['arquivo'])) ?>" alt="<?= e($foto['alt']) ?>" width="<?= (int) $foto['largura'] ?>" height="<?= (int) $foto['altura'] ?>" loading="lazy">
        </div>
      </div>
      <div class="sobre-txt reveal" id="sobre-texto">
<?php foreach ($sobre['paragrafos'] as $i => $p): ?>
        <p class="<?= $i === 0 ? 'sobre-destaque' : 'sobre-corrido' ?>"><?= e($p) ?></p>
<?php endforeach; ?>
        <div class="numeros" id="sobre-stats" aria-label="Destaques">
<?php foreach ($sobre['numeros'] as $n): ?>
          <div class="numero"><b><?= e($n['valor']) ?></b><span><?= e($n['legenda']) ?></span></div>
<?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <div class="faixa-video" id="sobre-video-block">
    <div class="contem faixa-grade">
      <div class="faixa-texto">
<?php foreach ($sobre['video_paragrafos'] as $p): ?>
        <p><?= e($p) ?></p>
<?php endforeach; ?>
        <p class="saiba"><?= e($sobre['saiba_mais']) ?>
          <svg class="seta-lado" width="34" height="14" viewBox="0 0 34 14" fill="none" aria-hidden="true"><path d="M33 7H1M7 1L1 7l6 6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          <svg class="seta-baixo" width="14" height="30" viewBox="0 0 14 30" fill="none" aria-hidden="true"><path d="M7 1v28M1 23l6 6 6-6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </p>
      </div>
      <div class="faixa-video-col">
        <div class="video-moldura">
          <video id="apresentacao-video" src="<?= e(url_midia($sobre['video']['arquivo'])) ?>" playsinline preload="none" poster="<?= e(url_midia($sobre['video']['poster'])) ?>" width="<?= $vl ?>" height="<?= $va ?>" style="aspect-ratio: <?= $vl ?>/<?= $va ?>; scroll-margin-top: 150px;"></video>
          <button type="button" class="video-play" onclick="tocarVideo(this)" aria-label="Reproduzir vídeo"><svg width="24" height="24" viewBox="0 0 24 24" fill="#FBF3EE" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"></path></svg></button>
        </div>
      </div>
    </div>
  </div>

  <section id="procedimentos" class="proc">
    <div class="contem">
      <div class="reveal" id="procedimentos-topo">
        <p class="rotulo rotulo-claro"><?= e($h['procedimentos']['rotulo']) ?></p>
        <h2><?= e($h['procedimentos']['titulo']) ?></h2>
      </div>
      <ol class="proc-lista" id="procedimentos-grid">
<?php foreach ($h['procedimentos']['itens'] as $i => $proc): ?>
        <li class="proc-item reveal-item" style="--i:<?= $i ?>">
          <h3><?= e($proc['titulo']) ?></h3>
          <p><?= e($proc['texto']) ?></p>
        </li>
<?php endforeach; ?>
      </ol>
    </div>
  </section>

  <section id="resultados" class="res reveal">
    <div class="contem">
      <div class="res-topo">
        <div>
          <p class="rotulo"><?= e($h['resultados']['rotulo']) ?></p>
          <h2><?= e($h['resultados']['titulo']) ?></h2>
        </div>
        <a id="ver-mais-desktop" class="btn-texto res-ver-desktop" href="resultados.html"><span class="t"><?= e($h['resultados']['texto_ver_mais']) ?></span><?= $seta_ver_mais ?></a>
      </div>
      <div class="carrossel">
        <div id="resultados-grid">
<?php foreach (resultados_home($c) as $item): ?>
          <figure style="margin:0;">
<?php parcial('comparador', ['item' => $item]); ?>
            <figcaption class="res-legenda"><?= e($item['titulo']) ?></figcaption>
          </figure>
<?php endforeach; ?>
        </div>
        <div class="carrossel-ctl">
          <button type="button" class="mobile-scroll-arrow" onclick="scrollByCard('resultados-grid', -1)" aria-label="Ver resultados anteriores"><?= $seta_esq ?></button>
          <span class="carrossel-contador" data-grid="resultados-grid"></span>
          <button type="button" class="mobile-scroll-arrow" onclick="scrollByCard('resultados-grid', 1)" aria-label="Ver mais resultados"><?= $seta_dir ?></button>
        </div>
      </div>
      <a id="ver-mais-mobile" class="btn btn-marrom" href="resultados.html"><?= e($h['resultados']['texto_ver_mais']) ?><?= $seta_ver_mais ?></a>
    </div>
  </section>

  <section id="cursos" class="cursos reveal">
    <div class="contem">
      <div class="cursos-topo">
        <div>
          <p class="rotulo"><?= e($h['cursos']['rotulo']) ?></p>
          <h2><?= e($h['cursos']['titulo']) ?></h2>
          <p class="cursos-desc"><?= e($h['cursos']['descricao']) ?></p>
        </div>
      </div>
      <div class="cursos-lista">
<?php foreach ($h['cursos']['itens'] as $curso): ?>
        <div class="curso">
          <div class="curso-cab">
            <div>
              <p class="curso-carga"><?= e($curso['carga_horaria']) ?></p>
              <h3><?= e($curso['titulo']) ?></h3>
              <p class="curso-desc"><?= e($curso['descricao']) ?></p>
            </div>
            <div class="curso-acoes">
              <a href="<?= e(link_whatsapp($ct, $curso['mensagem_whatsapp'])) ?>" target="_blank" rel="noopener" class="btn btn-marrom">Chamar no WhatsApp</a>
              <button type="button" onclick="toggleCurso('<?= e($curso['id']) ?>', this)" aria-expanded="false" aria-controls="<?= e($curso['id']) ?>" class="curso-toggle">Ver descrição completa</button>
            </div>
          </div>
          <div id="<?= e($curso['id']) ?>" class="curso-detalhe" style="display:none;">
            <div>
              <h4>Conteúdo programático</h4>
              <ul>
<?php foreach ($curso['conteudo'] as $li): ?>
                <li><?= e($li) ?></li>
<?php endforeach; ?>
              </ul>
            </div>
            <div>
              <h4>Módulos</h4>
              <ul>
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

  <section id="depoimentos" class="dep reveal">
    <div class="contem">
      <div class="dep-topo">
        <p class="rotulo"><?= e($h['depoimentos']['rotulo']) ?></p>
        <h2><?= e($h['depoimentos']['titulo']) ?></h2>
      </div>
      <div class="carrossel">
        <div id="depoimentos-grid">
<?php foreach ($h['depoimentos']['itens'] as $dep): ?>
          <figure class="dep-item" style="margin:0;">
            <div class="dep-estrelas" aria-hidden="true"><?= str_repeat($estrela, 5) ?></div>
            <blockquote class="dep-texto" style="margin:0;"><?= e($dep['texto']) ?></blockquote>
            <figcaption class="dep-autor"><b><?= e($dep['autor']) ?></b><span><?= e($dep['servico']) ?></span></figcaption>
          </figure>
<?php endforeach; ?>
        </div>
        <div class="carrossel-ctl">
          <button id="depoimentos-arrow-left" type="button" onclick="scrollByCard('depoimentos-grid', -1)" aria-label="Ver depoimentos anteriores"><?= $seta_esq ?></button>
          <span class="carrossel-contador" data-grid="depoimentos-grid"></span>
          <button id="depoimentos-arrow-right" type="button" onclick="scrollByCard('depoimentos-grid', 1)" aria-label="Ver mais depoimentos"><?= $seta_dir ?></button>
        </div>
      </div>
    </div>
  </section>

  <section id="contato" class="contato reveal">
    <div class="contem contato-grade" id="contato-grid">
      <div class="contato-info">
        <p class="rotulo rotulo-claro"><?= e($h['contato_secao']['rotulo']) ?></p>
        <h2><?= e($h['contato_secao']['titulo']) ?></h2>
        <a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="btn btn-claro btn-principal"><?= e($hero['botao_principal']) ?></a>
        <div class="contato-dados">
          <div class="contato-dado">
            <?= $icone_pino ?>
            <div>
              <h3>Endereço</h3>
              <p><?= implode('<br>', array_map('e', $ct['endereco_linhas'])) ?></p>
            </div>
          </div>
          <div class="contato-dado">
            <?= $icone_relogio ?>
            <div>
              <h3>Horário de atendimento</h3>
              <p><?= e($ct['horario']) ?></p>
            </div>
          </div>
          <div class="contato-dado">
            <?= $icone_insta ?>
            <div>
              <h3>Instagram</h3>
              <a href="https://instagram.com/<?= e($ct['instagram_usuario']) ?>" target="_blank" rel="noopener">@<?= e($ct['instagram_usuario']) ?></a>
            </div>
          </div>
        </div>
      </div>
      <div class="contato-lado">
        <div class="contato-mapa">
          <img class="mapa-mono" src="<?= e(url_midia($hero['imagem']['arquivo'])) ?>" alt="" aria-hidden="true" width="<?= (int) $hero['imagem']['largura'] ?>" height="<?= (int) $hero['imagem']['altura'] ?>" loading="lazy">
          <iframe src="<?= e($ct['maps_embed']) ?>" title="Localização do Studio Gabriela Santos" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <div class="contato-acoes">
          <a href="<?= e($ct['maps_link']) ?>" target="_blank" rel="noopener" class="btn btn-contorno-claro">Abrir no Google Maps</a>
          <a id="avaliacao-online-link" class="btn btn-contorno-claro" href="<?= e($ct['avaliacao_online']) ?>" target="_blank" rel="noopener">Avaliação Online</a>
        </div>
      </div>
    </div>
  </section>

</main>
<?php parcial('rodape', $vars); ?>
<?php parcial('script-home'); ?>

</body></html>
