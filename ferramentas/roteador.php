<?php
// Roteador da prévia local (php -S). Imita o que o .htaccess faz na Hostinger.
// Uso: node ferramentas/previa.mjs   (ou: npm run previa)

$raiz = dirname(__DIR__);
$caminho = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$caminho = str_replace('\\', '/', $caminho);

if (str_contains($caminho, '..') || str_contains($caminho, "\0")) {
    http_response_code(400);
    exit('Pedido inválido.');
}

function r_404(): never
{
    http_response_code(404);
    $pagina = dirname(__DIR__) . '/404.php';
    if (is_file($pagina)) {
        define('RAIZ', dirname(__DIR__));
        require $pagina;
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>Página não encontrada</title><p>Página não encontrada.</p>';
    }
    exit;
}

function r_executar(string $arquivo): never
{
    $_SERVER['SCRIPT_NAME'] = '/' . ltrim(substr($arquivo, strlen(dirname(__DIR__))), '/');
    $_SERVER['SCRIPT_FILENAME'] = $arquivo;
    chdir(dirname($arquivo));
    require $arquivo;
    exit;
}

// Páginas públicas
if ($caminho === '/' || $caminho === '/index.html') {
    r_executar($raiz . '/index.php');
}
if ($caminho === '/resultados.html') {
    r_executar($raiz . '/resultados.php');
}
if ($caminho === '/index.php') {
    header('Location: /', true, 301);
    exit;
}
if ($caminho === '/resultados.php') {
    header('Location: /resultados.html', true, 301);
    exit;
}
if ($caminho === '/painel' || $caminho === '/painel/') {
    if ($caminho === '/painel') {
        header('Location: /painel/', true, 301);
        exit;
    }
    if (is_file($raiz . '/painel/index.php')) {
        r_executar($raiz . '/painel/index.php');
    }
    r_404();
}

// Pastas e arquivos que nunca saem do disco
$bloqueados = ['nucleo', 'parciais', 'dados', 'ferramentas', 'tests', 'acervo', '.local', '.git', 'node_modules', 'publicar', '.claude'];
$primeira = explode('/', ltrim($caminho, '/'))[0] ?? '';
if (in_array($primeira, $bloqueados, true)) {
    r_404();
}
if (preg_match('#(^|/)(package(-lock)?\.json|playwright\.config\.js|\.htaccess|\.user\.ini|painel-config[^/]*\.php)$#i', $caminho)) {
    r_404();
}
if (preg_match('#\.md$#i', $caminho) && $primeira !== '.auditoria') {
    r_404();
}

$arquivo = $raiz . $caminho;

// Scripts em conteudo/ nunca executam
if ($primeira === 'conteudo' && preg_match('#\.(php\d?|phtml|phar|pl|py|cgi|sh)$#i', $caminho)) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Em conteudo/ só saem webp, jpg, png e mp4. Todo o resto (html, svg, js, sem extensão) dá 403
if ($primeira === 'conteudo' && !preg_match('#\.(webp|jpe?g|png|mp4)$#i', $caminho)) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Prévia das direções visuais: só direcao-a.php e direcao-b.php executam (nunca os arquivos _*.php). Não vai para a Hostinger.
if (preg_match('#^/prototipos/direcao-[ab]\.php$#', $caminho) && is_file($arquivo)) {
    r_executar($arquivo);
}
// PHP só executa dentro de painel/
if (preg_match('#^/painel/.+\.php$#', $caminho) && is_file($arquivo)) {
    r_executar($arquivo);
}
if (preg_match('#\.php$#i', $caminho)) {
    r_404();
}

if (!is_file($arquivo)) {
    r_404();
}

$tipos = [
    'webp' => 'image/webp', 'mp4' => 'video/mp4', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png', 'svg' => 'image/svg+xml', 'woff2' => 'font/woff2', 'js' => 'text/javascript; charset=utf-8',
    'css' => 'text/css; charset=utf-8', 'json' => 'application/json; charset=utf-8', 'txt' => 'text/plain; charset=utf-8',
    'xml' => 'application/xml; charset=utf-8', 'html' => 'text/html; charset=utf-8', 'ico' => 'image/x-icon',
];
$ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
$tipo = $tipos[$ext] ?? 'application/octet-stream';
$tamanho = filesize($arquivo);

header('Content-Type: ' . $tipo);
header('Accept-Ranges: bytes');
header('X-Content-Type-Options: nosniff');

$ini = 0;
$fim = $tamanho - 1;
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m) && ($m[1] !== '' || $m[2] !== '')) {
    if ($m[1] === '') {
        $ini = max(0, $tamanho - (int) $m[2]);
    } else {
        $ini = (int) $m[1];
        if ($m[2] !== '') {
            $fim = min($fim, (int) $m[2]);
        }
    }
    if ($ini > $fim || $ini >= $tamanho) {
        http_response_code(416);
        header('Content-Range: bytes */' . $tamanho);
        exit;
    }
    http_response_code(206);
    header("Content-Range: bytes $ini-$fim/$tamanho");
}
header('Content-Length: ' . ($fim - $ini + 1));

$f = fopen($arquivo, 'rb');
fseek($f, $ini);
$restante = $fim - $ini + 1;
while ($restante > 0 && !feof($f)) {
    $parte = fread($f, min(65536, $restante));
    if ($parte === false || $parte === '') {
        break;
    }
    echo $parte;
    $restante -= strlen($parte);
}
fclose($f);
