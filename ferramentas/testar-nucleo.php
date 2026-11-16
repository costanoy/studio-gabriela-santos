<?php
// Testes do núcleo PHP (sem navegador). Uso: php ferramentas/testar-nucleo.php
// Cada bloco é de um item do Lote 1. Sai com código 1 se algo falhar.
define('RAIZ', dirname(__DIR__));
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';

$ok = 0;
$falhas = [];
function t(bool $cond, string $nome): void
{
    global $ok, $falhas;
    if ($cond) {
        $ok++;
        echo "  ok   $nome\n";
    } else {
        $falhas[] = $nome;
        echo "  FALHA $nome\n";
    }
}

function renderizar(string $pagina, array $c): string
{
    ob_start();
    parcial('head', ['c' => $c, 'pagina' => $pagina]);
    parcial('cabecalho', ['c' => $c, 'pagina' => $pagina]);
    parcial('rodape', ['c' => $c, 'pagina' => $pagina]);
    return (string) ob_get_clean();
}

echo "L15 mesclagem\n";
$padrao = conteudo_padrao();
t(($padrao['versao_esquema'] ?? 0) === 1, 'padrão tem versao_esquema 1');
$m = conteudo_mesclar(['a' => ['x' => 1, 'y' => 2], 'l' => [1, 2, 3], 'so_padrao' => 'p'], ['a' => ['x' => 9], 'l' => [7]]);
t($m['a'] === ['x' => 9, 'y' => 2], 'objetos mesclam por chave');
t($m['l'] === [7], 'listas do editado substituem inteiras');
t($m['so_padrao'] === 'p', 'chave ausente no editado vem do padrão');

echo "\nL15 escape\n";
t(e('<b>"a" & \'b\'</b>') === '&lt;b&gt;&quot;a&quot; &amp; &#039;b&#039;&lt;/b&gt;', 'e() escapa < > " & \'');
t(e(null) === '', 'e(null) devolve vazio');
$ld = jsonld(['x' => '</script><script>alert(1)</script>']);
t(stripos($ld, '</script') === false, 'jsonld não deixa </script> passar');
t(json_decode($ld, true)['x'] === '</script><script>alert(1)</script>', 'jsonld continua JSON válido');

$c = $padrao;
$c['contato']['endereco_curto'] = '<b>Rua "X" & Y</b>';
$html = renderizar('home', $c);
t(strpos($html, '<b>Rua') === false && strpos($html, '&lt;b&gt;Rua') !== false, 'texto do JSON sai escapado no rodapé');

echo "\nL15 resultados\n";
$h = resultados_home($padrao);
t(count($h) === 5, 'home mostra 5 comparações');
t(count(array_filter($h, fn($i) => $i['tipo'] === 'comparacao')) === 5, 'home só tem comparações');
$g = resultados_por_categoria($padrao);
t(count($g) === 5, 'resultados tem 5 categorias');
t(array_sum(array_map(fn($x) => count($x['itens']), $g)) === 11, 'resultados tem 11 itens');
t(array_column($g, 'colunas') === [3, 2, 2, 2, 2] || array_column($g, 'colunas') === [3, 2, 2, 2, 2], 'colunas = min(itens, 3)');

$c2 = $padrao;
foreach ($c2['resultados']['itens'] as &$i) {
    if ($i['categoria'] === $c2['resultados']['categorias'][1]['id']) {
        $i['em_resultados'] = false;
    }
}
unset($i);
t(count(resultados_por_categoria($c2)) === 4, 'categoria sem itens visíveis some');

$c3 = $padrao;
$c3['resultados']['itens'][0]['na_home'] = false;
t(count(resultados_home($c3)) === 4, 'item com na_home falso sai da home');

echo "\nL07 contato em um só lugar\n";
$c4 = $padrao;
$c4['contato']['horario'] = 'Horário de teste 9h';
$c4['contato']['whatsapp'] = '5511999990000';
$h4 = renderizar('home', $c4);
t(substr_count($h4, 'Horário de teste 9h') === 1, 'horário do JSON aparece no rodapé');
t(strpos($h4, '5562984141116') === false && strpos($h4, '5511999990000') !== false, 'link do WhatsApp usa contato.whatsapp');
t(strpos($h4, '8h às 17h') === false, 'nenhum horário escrito à mão sobra');

echo "\nT06 canonical, sitemap e 404\n";
preg_match('#<link rel="canonical" href="([^"]+)"#', renderizar('home', $padrao), $mh);
preg_match('#<link rel="canonical" href="([^"]+)"#', renderizar('resultados', $padrao), $mr);
t(($mh[1] ?? '') === 'https://studiogabrielasantos.com.br/', 'canonical da home é a raiz');
t(($mr[1] ?? '') === 'https://studiogabrielasantos.com.br/resultados.html', 'canonical de resultados é /resultados.html');
$sitemap = (string) file_get_contents(RAIZ . '/sitemap.xml');
t(strpos($sitemap, '<loc>' . $mh[1] . '</loc>') !== false && strpos($sitemap, '<loc>' . $mr[1] . '</loc>') !== false, 'sitemap tem as mesmas duas URLs dos canonical');
t(substr_count($sitemap, '<loc>') === 2, 'sitemap tem só 2 URLs');
$htaccess = (string) file_get_contents(RAIZ . '/.htaccess');
t(strpos($htaccess, 'ErrorDocument 404 /404.php') !== false, '.htaccess aponta o 404 para 404.php');
t(strpos($htaccess, 'index\.php$ / [R=301') !== false && strpos($htaccess, 'resultados\.php$ /resultados.html [R=301') !== false, '.htaccess: /index.php e /resultados.php dão 301');
t(is_file(RAIZ . '/404.php'), '404.php existe');

echo "\nT05 .htaccess e .user.ini\n";
t(strpos($htaccess, 'Options -Indexes') !== false, 'Options -Indexes');
t(strpos($htaccess, 'RewriteCond %{HTTP_HOST} ^www\.') !== false && strpos($htaccess, 'https://studiogabrielasantos.com.br%{REQUEST_URI} [R=301') !== false, 'www e http vão para https sem www (301)');
t(strpos($htaccess, 'index\.(html|php)$ / [R=301') !== false, '/index.html e /index.php dão 301 para /');
t(strpos($htaccess, 'X-Content-Type-Options "nosniff"') !== false && strpos($htaccess, 'Referrer-Policy') !== false && strpos($htaccess, 'X-Frame-Options') !== false, 'cabeçalhos de segurança');
t(strpos($htaccess, 'max-age=2592000') !== false && strpos($htaccess, '"no-cache"') !== false, 'Cache-Control por tipo (mídia 30 dias, páginas no-cache)');
t(preg_match('/\(nucleo\|parciais\|dados\|ferramentas/', $htaccess) === 1, 'pastas de código e dados bloqueadas na web');
$ini = (string) file_get_contents(RAIZ . '/.user.ini');
t(strpos($ini, 'upload_max_filesize=64M') !== false && strpos($ini, 'post_max_size=70M') !== false && strpos($ini, 'memory_limit=256M') !== false && strpos($ini, 'max_execution_time=120') !== false, '.user.ini com os 4 limites');

echo "\n";
if ($falhas) {
    echo count($falhas) . " falha(s), $ok ok.\n";
    exit(1);
}
echo "Tudo certo: $ok verificações.\n";
exit(0);
