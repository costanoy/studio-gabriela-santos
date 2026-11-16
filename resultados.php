<?php
defined('RAIZ') || define('RAIZ', __DIR__);
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';

$c = conteudo_ler();
$vars = ['c' => $c, 'pagina' => 'resultados'];

parcial('head', $vars);
parcial('estilo-resultados');
?>
</head>
<body>
<?php parcial('cabecalho', $vars); ?>
<main id="conteudo">

  <section style="padding: 72px 5% 40px; text-align:center;">
    <p style="font-size:13px; letter-spacing:0.15em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0 0 14px;">Transformações reais</p>
    <h1 style="font-family:'Marcellus', serif; font-size: clamp(32px, 4vw, 48px); margin:0; color:#3A2A22;">Todos os resultados</h1>
  </section>

<?php foreach (resultados_por_categoria($c) as $grupo): ?>
  <section style="padding: 0 5% 90px;">
    <div style="max-width:1280px; margin:0 auto;">
      <h2 style="font-family:'Marcellus', serif; font-size: clamp(24px, 2.6vw, 32px); margin:0 0 32px; color:#3A2A22; border-bottom: 1px solid #E7CDBE; padding-bottom:16px;"><?= e($grupo['categoria']['nome']) ?></h2>
      <div class="rc-grid" style="display:grid; grid-template-columns: repeat(<?= (int) $grupo['colunas'] ?>, minmax(240px, 340px)); justify-content:center; gap: 32px;">
<?php foreach ($grupo['itens'] as $item): ?>
<?php if ($item['tipo'] === 'comparacao'): ?>
        <div>
<?php parcial('comparador', ['item' => $item]); ?>
        </div>
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
</main>
<?php parcial('rodape', $vars); ?>
<?php parcial('script-resultados'); ?>

</body></html>
