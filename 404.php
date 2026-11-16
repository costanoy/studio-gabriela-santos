<?php
defined('RAIZ') || define('RAIZ', __DIR__);
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';

http_response_code(404);
header('X-Robots-Tag: noindex');
header('Content-Type: text/html; charset=utf-8');
$c = conteudo_ler();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Página não encontrada | Studio Gabriela Santos</title>
<link rel="icon" href="/assets/favicon.png">
<style>
  body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #FBF3EE; color: #3A2A22; font-family: Georgia, 'Times New Roman', serif; text-align: center; padding: 24px; box-sizing: border-box; }
  main { max-width: 480px; }
  img { height: 120px; width: auto; margin-bottom: 24px; }
  h1 { font-size: 28px; margin: 0 0 12px; }
  p { font-family: Arial, sans-serif; font-size: 17px; line-height: 1.6; color: #5C4A40; margin: 0 0 28px; }
  a.botao { display: inline-block; background: #996C55; color: #FBF3EE; text-decoration: none; padding: 14px 28px; border-radius: 10px; font-family: Arial, sans-serif; font-weight: 700; }
</style>
</head>
<body>
<main>
  <img src="/<?= e(url_midia($c['marca']['logo_texto']['arquivo'])) ?>" alt="<?= e($c['marca']['logo_texto']['alt']) ?>">
  <h1>Página não encontrada</h1>
  <p>O endereço que você abriu não existe ou foi trocado. Volte para o início do site.</p>
  <a class="botao" href="/">Ir para o início</a>
</main>
</body>
</html>
