<?php defined('RAIZ') || exit;
/** Vídeo da página de resultados. Variáveis: $item (tipo video), $categoria (nome da categoria). */
?>
          <div class="cat-item item-midia">
            <video aria-label="<?= e($categoria) ?> — resultado em vídeo" src="<?= e(url_midia($item['arquivo'])) ?>" poster="<?= e(url_midia($item['poster'])) ?>" controls="" muted="" playsinline="" preload="none"></video>
            <span class="ba-etq item-etq"><?= e($item['rotulo']) ?></span>
            <button type="button" class="video-play" onclick="tocarVideo(this)" aria-label="Reproduzir vídeo"><svg width="26" height="26" viewBox="0 0 24 24" fill="#FBF3EE" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"></path></svg></button>
          </div>
