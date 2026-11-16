<?php defined('RAIZ') || exit;
/** Foto avulsa da página de resultados. Variável: $item (tipo foto). */
$x = (float) ($item['posicao_x'] ?? 0);
$posicao = 'calc(50% ' . ($x < 0 ? '-' : '+') . ' ' . num(abs($x)) . '%) 50%';
$ajuste = in_array($item['ajuste'] ?? '', ['contain', 'cover'], true) ? $item['ajuste'] : 'contain';
?>
        <div style="position:relative; border-radius:20px; overflow:hidden; aspect-ratio:3/4; background:#000;">
          <img src="<?= e(url_midia($item['arquivo'])) ?>" alt="<?= e($item['alt']) ?>" loading="lazy" style="width:100%; height:100%; object-fit:<?= e($ajuste) ?>; object-position: <?= e($posicao) ?>;">
          <span style="position:absolute; bottom:44px; left:10px; background:rgba(58,42,34,0.75); color:#FBF3EE; font-size:11px; letter-spacing:0.1em; text-transform:uppercase; padding:5px 10px; font-weight:700;"><?= e($item['rotulo']) ?></span>
        </div>
