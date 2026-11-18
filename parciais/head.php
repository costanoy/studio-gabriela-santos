<?php defined('RAIZ') || exit;
/** Cabeçalho do documento até os preloads de fonte. Variáveis: $c (conteúdo), $pagina ('home' ou 'resultados'). */
$home = $pagina === 'home';
$seo = $c['seo'];
$base = url_base($c);
$url = $base . ($home ? '/' : '/resultados.html');
$titulo = $home ? $seo['titulo'] : $seo['titulo_resultados'];
$descricao = $home ? $seo['descricao'] : $seo['descricao_resultados'];
$og_titulo = $home ? $seo['og_titulo'] : $seo['og_titulo_resultados'];
$og_descricao = $home ? $seo['og_descricao'] : $seo['og_descricao_resultados'];
$og_imagem = $base . '/' . url_midia($seo['og_imagem']);
?>
<!DOCTYPE html>
<html lang="pt-BR"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?></title>
<meta name="description" content="<?= e($descricao) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="theme-color" content="#996C55">
<link rel="canonical" href="<?= e($url) ?>">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" href="/assets/favicon.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Studio Gabriela Santos">
<meta property="og:url" content="<?= e($url) ?>">
<meta property="og:title" content="<?= e($og_titulo) ?>">
<meta property="og:description" content="<?= e($og_descricao) ?>">
<meta property="og:image" content="<?= e($og_imagem) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= e($seo['og_imagem_alt']) ?>">
<meta property="og:locale" content="pt_BR">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($og_titulo) ?>">
<meta name="twitter:description" content="<?= e($og_descricao) ?>">
<meta name="twitter:image:alt" content="<?= e($seo['og_imagem_alt']) ?>">
<meta name="twitter:image" content="<?= e($og_imagem) ?>">
<script>document.documentElement.classList.add('js');</script>
<script type="application/ld+json">
<?= jsonld($home ? jsonld_negocio($c) : jsonld_breadcrumb()) ?>

</script>
<link rel="preload" href="assets/fonts/cormorant-garamond-500-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="assets/fonts/23e9ba1c.woff2" as="font" type="font/woff2" crossorigin>
