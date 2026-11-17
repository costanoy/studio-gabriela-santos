<?php
// Prévia da Direção A "Retrato". Só cabeçalho, topo, boas-vindas e Sobre.
require_once __DIR__ . '/_comum.php';
$hero = $h['hero'];
$sobre = $h['sobre'];
$foto = $sobre['foto'];
proto_head('Direção A — Retrato (prévia)', 'direcao-a.css', '<link rel="preload" as="image" href="' . e(pm($foto['arquivo'])) . '" fetchpriority="high">' . "\n");
?>
<body>
<?php proto_cabecalho($c, $wa); ?>
<main id="conteudo">

  <section id="home" class="hero">
    <div class="hero-int contem">
      <div class="hero-texto">
        <h1><?= e($hero['titulo']) ?></h1>
        <p class="hero-apoio"><?= e($hero['texto']) ?></p>
        <div class="hero-acoes">
          <a class="btn btn-claro" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= e($hero['botao_principal']) ?></a>
          <a class="btn-texto hero-sec" href="#procedimentos"><span class="t"><?= e($hero['botao_secundario']) ?></span><svg width="18" height="12" viewBox="0 0 18 12" fill="none" aria-hidden="true"><path d="M1 6h16M12 1l5 5-5 5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
      </div>
      <div class="hero-figura">
        <div class="brilho" aria-hidden="true"><img id="hero-bg-img" src="<?= e(pm($hero['imagem']['arquivo'])) ?>" alt="" width="<?= (int) $hero['imagem']['largura'] ?>" height="<?= (int) $hero['imagem']['altura'] ?>" loading="eager" decoding="async"></div>
        <div class="retrato">
          <img src="<?= e(pm($foto['arquivo'])) ?>" alt="<?= e($foto['alt']) ?>" width="<?= (int) $foto['largura'] ?>" height="<?= (int) $foto['altura'] ?>" fetchpriority="high">
        </div>
      </div>
      <span class="hero-linha" aria-hidden="true"></span>
    </div>
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
      <div class="sobre-esq reveal">
        <p class="rotulo"><?= e($sobre['rotulo']) ?></p>
        <h2><?= e($sobre['titulo']) ?></h2>
        <div class="numeros" aria-label="Destaques">
<?php foreach ($sobre['numeros'] as $n): ?>
          <div class="numero"><b><?= e($n['valor']) ?></b><span><?= e($n['legenda']) ?></span></div>
<?php endforeach; ?>
        </div>
      </div>
      <div class="sobre-dir reveal">
<?php foreach ($sobre['paragrafos'] as $i => $p): ?>
        <p class="<?= $i === 0 ? 'sobre-destaque' : 'sobre-corrido' ?>"><?= e($p) ?></p>
<?php endforeach; ?>
        <div class="numeros numeros-celular">
<?php foreach ($sobre['numeros'] as $n): ?>
          <div class="numero"><b><?= e($n['valor']) ?></b><span><?= e($n['legenda']) ?></span></div>
<?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

<?php proto_faixa_video($sobre, 'a'); ?>

</main>
<?php proto_script(); ?>
</body></html>
