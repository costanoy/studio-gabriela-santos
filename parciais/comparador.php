<?php defined('RAIZ') || exit;
/** Quadro de comparação antes/depois (só o quadro; o chamador faz o envoltório e a legenda). Variável: $item. */
$quadro = static function (array $lado): string {
    $enq = $lado['enquadramento'] ?? [];
    if (!empty($enq['automatico']) || !isset($enq['width'], $enq['height'], $enq['left'], $enq['top'])) {
        $enq = enquadramento_auto((int) ($lado['largura'] ?? 0), (int) ($lado['altura'] ?? 0));
    }
    return estilo_enquadramento($enq);
};
?>
          <div style="position:relative; width:100%; aspect-ratio:3/4; border-radius:20px; overflow:hidden;">
            <img src="<?= e(url_midia($item['depois']['arquivo'])) ?>" alt="<?= e($item['depois']['alt']) ?>" loading="lazy" style="position:absolute; <?= e($quadro($item['depois'])) ?>; transform:translate(-50%,-50%);">
            <span style="position:absolute; bottom:44px; right:10px; z-index:1; background:rgba(153,108,85,0.9); color:#FBF3EE; font-size:11px; letter-spacing:0.1em; text-transform:uppercase; padding:5px 10px; font-weight:700;">Depois</span>
            <div class="ba-clip" style="position:absolute; inset:0; clip-path: inset(0 50% 0 0);">
              <img src="<?= e(url_midia($item['antes']['arquivo'])) ?>" alt="<?= e($item['antes']['alt']) ?>" loading="lazy" style="position:absolute; <?= e($quadro($item['antes'])) ?>; transform:translate(-50%,-50%);">
              <span style="position:absolute; bottom:44px; left:10px; background:rgba(58,42,34,0.75); color:#FBF3EE; font-size:11px; letter-spacing:0.1em; text-transform:uppercase; padding:5px 10px; font-weight:700;">Antes</span>
            </div>
            <div class="ba-line" style="position:absolute; top:0; bottom:36px; left:50%; width:2px; background:#FBF3EE; pointer-events:none; z-index:2;"></div>
            <input type="range" min="0" max="100" value="50" oninput="baSlide(this)" style="position:absolute; left:14px; right:14px; bottom:12px; width:calc(100% - 28px); accent-color:#996C55; z-index:3;">
          </div>
