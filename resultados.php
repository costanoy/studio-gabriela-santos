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
    <p style="font-size:13px; letter-spacing:0.15em; text-transform:uppercase; color:#7D5843; font-weight:700; margin:0 0 14px;">Transformações reais</p>
    <h1 style="font-family:'Marcellus', serif; font-size: clamp(32px, 4vw, 48px); margin:0; color:#3A2A22;">Todos os resultados</h1>
  </section>

<?php $primeira_foto = false; ?>
<?php foreach (resultados_por_categoria($c) as $grupo): ?>
  <section style="padding: 0 5% 90px;">
    <div style="max-width:1280px; margin:0 auto;">
      <h2 style="font-family:'Marcellus', serif; font-size: clamp(24px, 2.6vw, 32px); margin:0 0 32px; color:#3A2A22; border-bottom: 1px solid #E7CDBE; padding-bottom:16px;"><?= e($grupo['categoria']['nome']) ?></h2>
      <div class="rc-grid" style="display:grid; grid-template-columns: repeat(<?= (int) $grupo['colunas'] ?>, minmax(240px, 340px)); justify-content:center; gap: 32px;">
<?php foreach ($grupo['itens'] as $item): ?>
<?php if ($item['tipo'] === 'comparacao'): ?>
        <div>
<?php parcial('comparador', ['item' => $item, 'prioridade' => !$primeira_foto]); $primeira_foto = true; ?>
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
<?php
$cta_texto = trim((string) ($c['resultados']['cta_texto'] ?? '')) ?: 'Quer um resultado assim? Chame a gente no WhatsApp.';
?>
  <section style="padding: 0 5% 90px; text-align:center;">
    <div style="max-width:640px; margin:0 auto;">
      <p style="font-family:'Marcellus', serif; font-size: clamp(22px, 2.4vw, 30px); line-height:1.3; color:#3A2A22; margin:0 0 24px;"><?= e($cta_texto) ?></p>
      <a href="<?= e(link_whatsapp($c['contato'])) ?>" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; justify-content:center; min-height:48px; box-sizing:border-box; text-decoration:none; background:#7D5843; color:#FBF3EE; padding:13px 28px; border-radius:10px; font-weight:700; font-size:16px;">Chamar no WhatsApp</a>
    </div>
  </section>
</main>
<?php parcial('rodape', $vars); ?>
<?php parcial('script-resultados'); ?>

</body></html>
