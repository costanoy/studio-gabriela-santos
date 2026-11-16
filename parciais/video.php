<?php defined('RAIZ') || exit;
/** Vídeo da página de resultados. Variáveis: $item (tipo video), $categoria (nome da categoria). */
?>
        <div style="position:relative; border-radius:20px; overflow:hidden; aspect-ratio:3/4;">
          <video aria-label="<?= e($categoria) ?> — resultado em vídeo" src="<?= e(url_midia($item['arquivo'])) ?>" poster="<?= e(url_midia($item['poster'])) ?>" controls="" muted="" playsinline="" preload="none" style="width:100%; height:100%; object-fit:cover; background:#000;"></video>
          <span style="position:absolute; bottom:64px; left:10px; background:rgba(153,108,85,0.9); color:#FBF3EE; font-size:11px; letter-spacing:0.1em; text-transform:uppercase; padding:5px 10px; font-weight:700; pointer-events:none;"><?= e($item['rotulo']) ?></span>
          <button type="button" class="video-play" onclick="tocarVideo(this)" aria-label="Reproduzir vídeo"><svg width="26" height="26" viewBox="0 0 24 24" fill="#FBF3EE" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"></path></svg></button>
        </div>
