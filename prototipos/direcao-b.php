<?php
// Prévia da Direção B "Monograma". Só cabeçalho, topo, boas-vindas e Sobre.
require_once __DIR__ . '/_comum.php';
$hero = $h['hero'];
$sobre = $h['sobre'];
$foto = $sobre['foto'];
proto_head('Direção B — Monograma (prévia)', 'direcao-b.css');
?>
<body>
<?php proto_cabecalho($c, $wa); ?>
<main id="conteudo">

  <section id="home" class="hero">
    <img class="marca-dagua" id="hero-bg-img" src="<?= e(pm($hero['imagem']['arquivo'])) ?>" alt="" aria-hidden="true" width="<?= (int) $hero['imagem']['largura'] ?>" height="<?= (int) $hero['imagem']['altura'] ?>" fetchpriority="high">
    <div class="hero-int">
      <div class="hero-texto">
        <h1><?= e($hero['titulo']) ?></h1>
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
      <div class="retrato-col reveal">
        <div class="retrato-b">
          <img src="<?= e(pm($foto['arquivo'])) ?>" alt="<?= e($foto['alt']) ?>" width="<?= (int) $foto['largura'] ?>" height="<?= (int) $foto['altura'] ?>" loading="lazy">
        </div>
      </div>
      <div class="sobre-txt reveal">
        <p class="rotulo"><?= e($sobre['rotulo']) ?></p>
        <h2><?= e($sobre['titulo']) ?></h2>
<?php foreach ($sobre['paragrafos'] as $i => $p): ?>
        <p class="<?= $i === 0 ? 'sobre-destaque' : 'sobre-corrido' ?>"><?= e($p) ?></p>
<?php endforeach; ?>
        <div class="numeros" aria-label="Destaques">
<?php foreach ($sobre['numeros'] as $n): ?>
          <div class="numero"><b><?= e($n['valor']) ?></b><span><?= e($n['legenda']) ?></span></div>
<?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

<?php proto_faixa_video($sobre, 'b'); ?>

</main>
<?php proto_script(); ?>
</body></html>
