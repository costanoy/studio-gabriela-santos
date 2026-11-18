<?php
defined('RAIZ') || define('RAIZ', __DIR__);
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';

/** Sitemap gerado do conteúdo: 2 páginas, data da última gravação e as fotos públicas de cada uma. */

$c = conteudo_ler();
$base = url_base($c);

$lastmod = null;
$cfg = config();
if (!empty($cfg['configurado']) && is_file($cfg['dados_dir'] . '/site.json')) {
    $lastmod = date('Y-m-d', (int) filemtime($cfg['dados_dir'] . '/site.json'));
} elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', (string) ($c['atualizado_em'] ?? ''), $m)) {
    $lastmod = $m[0];
} else {
    $lastmod = date('Y-m-d', (int) filemtime(RAIZ . '/dados/site.padrao.json'));
}

$eh_imagem = static fn($a): bool => is_string($a) && preg_match('/\.(webp|jpe?g|png)(\?.*)?$/i', $a) === 1;

$home = [$c['home']['hero']['imagem']['arquivo'] ?? null, $c['home']['sobre']['foto']['arquivo'] ?? null, $c['home']['sobre']['video']['poster'] ?? null];
foreach (resultados_home($c) as $i) {
    $home[] = $i['antes']['arquivo'] ?? null;
    $home[] = $i['depois']['arquivo'] ?? null;
}
$res = [];
foreach (resultados_por_categoria($c) as $g) {
    foreach ($g['itens'] as $i) {
        if (($i['tipo'] ?? '') === 'comparacao') {
            $res[] = $i['antes']['arquivo'] ?? null;
            $res[] = $i['depois']['arquivo'] ?? null;
        } elseif (($i['tipo'] ?? '') === 'video') {
            $res[] = $i['poster'] ?? null;
        } else {
            $res[] = $i['arquivo'] ?? null;
        }
    }
}

$paginas = [[$base . '/', '1.0', $home], [$base . '/resultados.html', '0.8', $res]];

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: no-cache');
$x = static fn(string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
foreach ($paginas as [$loc, $prioridade, $fotos]) {
    echo "  <url>\n    <loc>" . $x($loc) . "</loc>\n    <lastmod>$lastmod</lastmod>\n    <priority>$prioridade</priority>\n";
    $vistos = [];
    foreach ($fotos as $f) {
        if (!$eh_imagem($f) || isset($vistos[$f])) {
            continue;
        }
        $vistos[$f] = true;
        echo '    <image:image><image:loc>' . $x($base . '/' . url_midia($f)) . "</image:loc></image:image>\n";
    }
    echo "  </url>\n";
}
echo "</urlset>\n";
