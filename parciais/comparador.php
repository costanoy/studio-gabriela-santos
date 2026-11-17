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
