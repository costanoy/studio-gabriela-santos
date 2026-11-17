<?php defined('RAIZ') || exit;
/** Foto avulsa da página de resultados. Variável: $item (tipo foto). */
$x = (float) ($item['posicao_x'] ?? 0);
$posicao = 'calc(50% ' . ($x < 0 ? '-' : '+') . ' ' . num(abs($x)) . '%) 50%';
$ajuste = in_array($item['ajuste'] ?? '', ['contain', 'cover'], true) ? $item['ajuste'] : 'contain';
?>
          <div class="cat-item item-midia">
            <img src="<?= e(url_midia($item['arquivo'])) ?>" alt="<?= e($item['alt']) ?>" loading="lazy" style="object-fit:<?= e($ajuste) ?>; object-position: <?= e($posicao) ?>;">
            <span class="ba-etq item-etq"><?= e($item['rotulo']) ?></span>
          </div>
