<?php defined('RAIZ') || exit;
/** Vídeo da página de resultados. Variáveis: $item (tipo video), $categoria (nome da categoria). */
?>
        <div style="position:relative; border-radius:20px; overflow:hidden; aspect-ratio:3/4;">
          <video aria-label="<?= e($categoria) ?> — resultado em vídeo" src="<?= e(url_midia($item['arquivo'])) ?>" poster="<?= e(url_midia($item['poster'])) ?>" controls="" muted="" playsinline="" preload="metadata" style="width:100%; height:100%; object-fit:cover; background:#000;"></video>
          <span style="position:absolute; bottom:64px; left:10px; background:rgba(153,108,85,0.9); color:#FBF3EE; font-size:11px; letter-spacing:0.1em; text-transform:uppercase; padding:5px 10px; font-weight:700; pointer-events:none;"><?= e($item['rotulo']) ?></span>
          <div onclick="toggleVideo(this)" style="position:absolute; top:0; left:0; right:0; bottom:54px; cursor:pointer;"></div>
        </div>
