<?php defined('RAIZ') || exit;
/** Quadro de comparação antes/depois (só o quadro; o chamador faz o envoltório e a legenda). Variável: $item. */
$quadro = static function (array $lado): string {
    $enq = $lado['enquadramento'] ?? [];
    if (!empty($enq['automatico']) || !isset($enq['width'], $enq['height'], $enq['left'], $enq['top'])) {
        $enq = enquadramento_auto((int) ($lado['largura'] ?? 0), (int) ($lado['altura'] ?? 0));
    }
    return estilo_enquadramento($enq);
};
$carga = !empty($prioridade) ? 'fetchpriority="high"' : 'loading="lazy"';
$nome_comparador = 'Comparar antes e depois' . (!empty($item['titulo']) ? ': ' . $item['titulo'] : '');
?>
<?php if (!empty($novo)): ?>
          <div class="ba">
            <img draggable="false" src="<?= e(url_midia($item['depois']['arquivo'])) ?>" alt="<?= e($item['depois']['alt']) ?>" <?= $carga ?> style="<?= e($quadro($item['depois'])) ?>">
            <span class="ba-etq ba-depois">Depois</span>
            <div class="ba-clip">
              <img draggable="false" src="<?= e(url_midia($item['antes']['arquivo'])) ?>" alt="<?= e($item['antes']['alt']) ?>" <?= $carga ?> style="<?= e($quadro($item['antes'])) ?>">
              <span class="ba-etq ba-antes">Antes</span>
            </div>
            <div class="ba-line"><span class="ba-alca" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M9 7l-5 5 5 5M15 7l5 5-5 5" stroke="#7D5843" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div>
            <input type="range" min="0" max="100" value="50" oninput="baSlide(this)" aria-label="<?= e($nome_comparador) ?>">
          </div>
<?php return; endif; ?>
          <div class="ba" style="position:relative; width:100%; aspect-ratio:3/4; border-radius:20px; overflow:hidden; touch-action:pan-y; cursor:ew-resize; user-select:none; -webkit-user-select:none;">
            <img draggable="false" src="<?= e(url_midia($item['depois']['arquivo'])) ?>" alt="<?= e($item['depois']['alt']) ?>" <?= $carga ?> style="position:absolute; <?= e($quadro($item['depois'])) ?>; transform:translate(-50%,-50%);">
            <span class="ba-depois" style="position:absolute; bottom:44px; right:10px; z-index:1; background:rgba(125,88,67,0.92); color:#FBF3EE; font-size:11px; letter-spacing:0.1em; text-transform:uppercase; padding:5px 10px; font-weight:700;">Depois</span>
            <div class="ba-clip" style="position:absolute; inset:0; clip-path: inset(0 50% 0 0);">
              <img draggable="false" src="<?= e(url_midia($item['antes']['arquivo'])) ?>" alt="<?= e($item['antes']['alt']) ?>" <?= $carga ?> style="position:absolute; <?= e($quadro($item['antes'])) ?>; transform:translate(-50%,-50%);">
              <span class="ba-antes" style="position:absolute; bottom:44px; left:10px; background:rgba(58,42,34,0.75); color:#FBF3EE; font-size:11px; letter-spacing:0.1em; text-transform:uppercase; padding:5px 10px; font-weight:700;">Antes</span>
            </div>
            <div class="ba-line" style="position:absolute; top:0; bottom:36px; left:50%; width:2px; background:#FBF3EE; pointer-events:none; z-index:2;"><span class="ba-alca" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M9 7l-5 5 5 5M15 7l5 5-5 5" stroke="#996C55" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div>
            <input type="range" min="0" max="100" value="50" oninput="baSlide(this)" aria-label="<?= e($nome_comparador) ?>" style="position:absolute; left:14px; right:14px; bottom:0; height:44px; margin:0; width:calc(100% - 28px); accent-color:#996C55; z-index:3; touch-action:pan-y;">
          </div>
