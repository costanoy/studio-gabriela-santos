<?php
defined('RAIZ') || define('RAIZ', __DIR__);
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';

$c = conteudo_ler();
$vars = ['c' => $c, 'pagina' => 'resultados'];
$grupos = resultados_por_categoria($c);
$cta_texto = trim((string) ($c['resultados']['cta_texto'] ?? '')) ?: 'Quer um resultado assim? Chame a gente no WhatsApp.';
$ancora = static fn(array $g, int $n): string => 'cat-' . (preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($g['categoria']['id'] ?? '')) ?: (string) ($n + 1));

parcial('head', $vars);
parcial('estilo-base');
parcial('estilo-resultados');
?>
</head>
<body>
<?php parcial('cabecalho', $vars); ?>
<main id="conteudo">

  <section class="abertura">
    <div class="contem">
      <p class="rotulo">Transformações reais</p>
      <h1>Todos os resultados</h1>
<?php if (count($grupos) > 1): ?>
      <nav class="atalhos" aria-label="Categorias de resultados">
<?php foreach ($grupos as $n => $grupo): ?>
        <a class="atalho" href="#<?= e($ancora($grupo, $n)) ?>"><?= e($grupo['categoria']['nome']) ?></a>
<?php endforeach; ?>
      </nav>
<?php endif; ?>
    </div>
  </section>

  <div class="categorias">
<?php $primeira_foto = false; ?>
<?php foreach ($grupos as $n => $grupo): ?>
    <section id="<?= e($ancora($grupo, $n)) ?>" class="cat<?= $n > 0 ? ' reveal' : '' ?>">
      <div class="contem">
        <h2 class="cat-titulo"><?= e($grupo['categoria']['nome']) ?></h2>
        <div class="cat-grade rc-grid" style="--col:<?= (int) $grupo['colunas'] ?>;">
<?php foreach ($grupo['itens'] as $item): ?>
<?php if ($item['tipo'] === 'comparacao'): ?>
          <figure class="cat-item">
<?php parcial('comparador', ['item' => $item, 'prioridade' => !$primeira_foto]); $primeira_foto = true; ?>
<?php if (!empty($item['titulo'])): ?>
            <figcaption class="res-legenda"><?= e($item['titulo']) ?></figcaption>
<?php endif; ?>
          </figure>
<?php elseif ($item['tipo'] === 'video'): ?>
<?php parcial('video', ['item' => $item, 'categoria' => $grupo['categoria']['nome']]); ?>
<?php else: ?>
<?php parcial('foto', ['item' => $item]); ?>
<?php endif; ?>
<?php endforeach; ?>
        </div>
      </div>
    </section>
<?php endforeach; ?>
  </div>

  <section class="convite reveal">
    <div class="contem">
      <p><?= e($cta_texto) ?></p>
      <a class="btn btn-claro" href="<?= e(link_whatsapp($c['contato'])) ?>" target="_blank" rel="noopener">Chamar no WhatsApp</a>
    </div>
  </section>
</main>
<?php parcial('rodape', $vars); ?>
<?php parcial('script-resultados'); ?>

</body></html>
