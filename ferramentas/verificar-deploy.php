<?php
// Verificador pós-publicação (R4). Só lê; não altera nada no site.
//
//   php ferramentas/verificar-deploy.php https://studiogabrielasantos.com.br
//   php ferramentas/verificar-deploy.php http://localhost:8790      (prévia local)
//
// Confere: 403/404 nos caminhos de desenvolvimento, os 301, os cabeçalhos de segurança,
// preload e poster em todo <video> (e que cada poster responde 200), noindex e CSP no /painel/
// e Range/206 num mp4. Sai com código 0 se tudo passou e 1 se algo falhou.
// Só PHP nativo (curl).

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!function_exists('curl_init')) {
    fwrite(STDERR, "A extensão curl do PHP não está ativa. Rode com: php -d extension=curl ferramentas/verificar-deploy.php <url>\n");
    exit(2);
}

$base = rtrim($argv[1] ?? '', '/');
if (!preg_match('#^https?://[^/\s]+$#i', $base)) {
    fwrite(STDERR, "Uso: php ferramentas/verificar-deploy.php <url-do-site>   (ex.: https://studiogabrielasantos.com.br)\n");
    exit(2);
}
$host = strtolower(parse_url($base, PHP_URL_HOST));
$local = in_array($host, ['localhost', '127.0.0.1', '[::1]'], true) || str_ends_with($host, '.localhost') || str_ends_with($host, '.test');

$falhas = 0;
$avisos = 0;
function ok(string $msg): void
{
    echo "  [ok]    $msg\n";
}
function erro(string $msg): void
{
    global $falhas;
    $falhas++;
    echo "  [FALHA] $msg\n";
}
function aviso(string $msg): void
{
    global $avisos;
    $avisos++;
    echo "  [aviso] $msg\n";
}
function checar(bool $cond, string $certo, string $errado): void
{
    $cond ? ok($certo) : erro($errado);
}
function titulo(string $t): void
{
    echo "\n$t\n";
}

/** GET (ou HEAD) sem seguir redirecionamento. Devolve status, cabeçalhos (minúsculos) e corpo. */
function pedir(string $url, array $cabecalhos = [], bool $corpo = true, ?string $post = null): array
{
    $ch = curl_init($url);
    $h = [];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $cabecalhos,
        CURLOPT_USERAGENT => 'verificar-deploy/1.0',
        CURLOPT_HEADERFUNCTION => function ($c, $linha) use (&$h) {
            if (str_contains($linha, ':')) {
                [$k, $v] = explode(':', $linha, 2);
                $k = strtolower(trim($k));
                $h[$k] = isset($h[$k]) ? $h[$k] . ', ' . trim($v) : trim($v);
            } elseif (str_starts_with($linha, 'HTTP/')) {
                $h = [];
            }
            return strlen($linha);
        },
    ]);
    if (!$corpo) {
        curl_setopt($ch, CURLOPT_NOBODY, true);
    }
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $texto = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['status' => $status, 'h' => $h, 'corpo' => is_string($texto) ? $texto : '', 'erro' => $err];
}

// ---------------------------------------------------------------------------
titulo("Site: $base" . ($local ? ' (prévia local)' : ''));
$home = pedir("$base/");
if ($home['status'] !== 200) {
    erro('a página inicial não respondeu 200 (veio ' . $home['status'] . ($home['erro'] ? ', ' . $home['erro'] : '') . '). Parando aqui.');
    echo "\nRESULTADO: FALHOU\n";
    exit(1);
}
ok('página inicial responde 200');

// ---------------------------------------------------------------------------
titulo('1. Caminhos de desenvolvimento (precisam dar 403 ou 404)');
$caminhos = [
    'nucleo/', 'nucleo/config.php', 'parciais/head.php', 'dados/site.padrao.json', 'ferramentas/criar-senha.php',
    'ferramentas/verificar-deploy.php', 'tests/site.spec.js', 'acervo/', 'acervo/originais/', '.local/senha-teste.txt',
    '.git/config', '.git/HEAD', 'MELHORIAS.md', 'CLAUDE.md', 'PUBLICACAO.md', 'package.json', 'package-lock.json',
    'playwright.config.js', '.htaccess', '.user.ini', 'painel-config.php', 'publicar/', 'node_modules/',
];
if (!$local) {
    $caminhos[] = '.auditoria/orcamento.md'; // na prévia local os testes leem a pasta .auditoria
}
foreach ($caminhos as $c) {
    $r = pedir("$base/$c", [], false);
    checar(in_array($r['status'], [403, 404], true), "/$c -> {$r['status']}", "/$c respondeu {$r['status']} (esperado 403 ou 404)");
}
// Script dentro de conteudo/ nunca executa nem é servido.
foreach (['conteudo/teste.php', 'conteudo/midia/teste.php', 'conteudo/midia/teste.phtml', 'conteudo/site.json', 'conteudo/midia/'] as $c) {
    $r = pedir("$base/$c", [], false);
    checar(in_array($r['status'], [403, 404], true), "/$c -> {$r['status']}", "/$c respondeu {$r['status']} (esperado 403 ou 404)");
}
$r = pedir("$base/painel/api.php", ['Content-Type: application/json'], true, '{"acao":"ler"}');
checar(in_array($r['status'], [401, 403], true), "/painel/api.php sem login -> {$r['status']}", "/painel/api.php sem login respondeu {$r['status']} (esperado 401 ou 403)");

// ---------------------------------------------------------------------------
titulo('2. Redirecionamentos 301');
foreach (['/index.php' => '/', '/resultados.php' => '/resultados.html'] as $de => $para) {
    $r = pedir($base . $de, [], false);
    $loc = $r['h']['location'] ?? '';
    $caminho = parse_url($loc, PHP_URL_PATH) ?: $loc;
    checar($r['status'] === 301 && $caminho === $para, "$de -> 301 $para", "$de respondeu {$r['status']} para '$loc' (esperado 301 para $para)");
}
$r = pedir("$base/resultados.html", [], false);
checar($r['status'] === 200, '/resultados.html responde 200', "/resultados.html respondeu {$r['status']}");

// ---------------------------------------------------------------------------
titulo('3. Cabeçalhos de segurança nas páginas públicas');
$esperados = [
    'x-content-type-options' => 'nosniff',
    'referrer-policy' => null,
    'x-frame-options' => null,
    'permissions-policy' => null,
];
foreach (['/' => $home, '/resultados.html' => $r] as $nome => $resp) {
    foreach ($esperados as $cab => $valor) {
        $tem = $resp['h'][$cab] ?? null;
        $bom = $tem !== null && ($valor === null || stripos($tem, $valor) !== false);
        if ($bom) {
            ok("$nome: $cab: $tem");
        } elseif ($local) {
            aviso("$nome: sem $cab (na prévia local esse cabeçalho vem do .htaccess, que só roda na Hostinger; confira no staging)");
        } else {
            erro("$nome: falta o cabeçalho $cab");
        }
    }
}
if (!$local) {
    $cache = $home['h']['cache-control'] ?? '';
    checar(stripos($cache, 'no-cache') !== false || stripos($cache, 'no-store') !== false || stripos($cache, 'max-age=0') !== false,
        "/: Cache-Control: $cache", "/: Cache-Control '$cache' pode deixar a CDN guardar HTML velho (esperado no-cache)");
}

// ---------------------------------------------------------------------------
titulo('4. Vídeos: preload, poster e poster respondendo 200');
$videos = [];
foreach (['/' => $home['corpo'], '/resultados.html' => $r['corpo']] as $pagina => $html) {
    if (!preg_match_all('#<video\b[^>]*>#is', $html, $m)) {
        continue;
    }
    foreach ($m[0] as $tag) {
        $atr = function (string $n) use ($tag): ?string {
            return preg_match('#\s' . $n . '\s*=\s*"([^"]*)"#i', $tag, $x) ? html_entity_decode($x[1]) : null;
        };
        $videos[] = ['pagina' => $pagina, 'src' => $atr('src'), 'poster' => $atr('poster'), 'preload' => $atr('preload'), 'tag' => $tag];
    }
}
checar(count($videos) > 0, count($videos) . ' vídeo(s) encontrado(s) nas páginas', 'nenhum <video> encontrado nas páginas');
$primeiroMp4 = null;
foreach ($videos as $v) {
    $rotulo = "{$v['pagina']} " . ($v['src'] ?? '(sem src)');
    $primeiroMp4 ??= $v['src'];
    checar($v['preload'] === 'none', "$rotulo: preload=\"none\"", "$rotulo: preload é '" . ($v['preload'] ?? 'ausente') . "' (esperado none)");
    checar(stripos($v['tag'], 'playsinline') !== false, "$rotulo: playsinline", "$rotulo: falta playsinline");
    if (!$v['poster']) {
        erro("$rotulo: sem poster");
        continue;
    }
    $url = preg_match('#^https?://#i', $v['poster']) ? $v['poster'] : $base . '/' . ltrim($v['poster'], '/');
    $p = pedir($url, [], false);
    $tipo = $p['h']['content-type'] ?? '';
    checar($p['status'] === 200 && stripos($tipo, 'image/') === 0, "$rotulo: poster {$v['poster']} -> 200 ($tipo)", "$rotulo: poster {$v['poster']} respondeu {$p['status']} ($tipo)");
}

// ---------------------------------------------------------------------------
titulo('5. Painel (/painel/)');
$pn = pedir("$base/painel/");
checar($pn['status'] === 200, '/painel/ responde 200', "/painel/ respondeu {$pn['status']}");
$robots = ($pn['h']['x-robots-tag'] ?? '') . ' ' . (preg_match('#<meta[^>]+name="robots"[^>]+content="([^"]*)"#i', $pn['corpo'], $mm) ? $mm[1] : '');
checar(stripos($robots, 'noindex') !== false, "noindex presente ($robots)", 'o /painel/ não tem noindex');
$csp = $pn['h']['content-security-policy'] ?? '';
checar($csp !== '', 'Content-Security-Policy presente', 'o /painel/ não manda Content-Security-Policy');
if ($csp !== '') {
    checar(str_contains($csp, "script-src 'self'") && !str_contains($csp, 'unsafe-inline') && !str_contains($csp, 'unsafe-eval'),
        "CSP sem inline nem eval (script-src 'self')", "CSP fraca: $csp");
    checar(str_contains($csp, "frame-ancestors 'none'"), "CSP com frame-ancestors 'none'", "CSP sem frame-ancestors 'none'");
}
checar(stripos($pn['h']['x-content-type-options'] ?? '', 'nosniff') !== false, 'X-Content-Type-Options: nosniff', 'o /painel/ não manda X-Content-Type-Options');
$cc = $pn['h']['cache-control'] ?? '';
checar(stripos($cc, 'no-store') !== false, "Cache-Control: $cc", "/painel/ com Cache-Control '$cc' (esperado no-store)");
$cookie = $pn['h']['set-cookie'] ?? '';
if ($cookie !== '') {
    checar(stripos($cookie, 'HttpOnly') !== false, 'cookie de sessão com HttpOnly', 'cookie de sessão sem HttpOnly');
    if (!$local) {
        checar(stripos($cookie, 'Secure') !== false, 'cookie de sessão com Secure', 'cookie de sessão sem Secure');
    }
    checar(stripos($cookie, 'SameSite=Strict') !== false, 'cookie de sessão com SameSite=Strict', 'cookie de sessão sem SameSite=Strict');
}

// ---------------------------------------------------------------------------
titulo('6. Range / 206 num mp4');
if ($primeiroMp4 === null) {
    erro('sem mp4 para testar');
} else {
    $url = preg_match('#^https?://#i', $primeiroMp4) ? $primeiroMp4 : $base . '/' . ltrim($primeiroMp4, '/');
    $g = pedir($url, ['Range: bytes=0-99']);
    $cr = $g['h']['content-range'] ?? '';
    checar($g['status'] === 206 && preg_match('#^bytes 0-99/\d+$#', $cr) && strlen($g['corpo']) === 100,
        "$primeiroMp4 com Range -> 206 ($cr, 100 bytes)", "$primeiroMp4 com Range respondeu {$g['status']} ('$cr', " . strlen($g['corpo']) . ' bytes); o iPhone precisa de 206');
    checar(stripos($g['h']['accept-ranges'] ?? '', 'bytes') !== false, 'Accept-Ranges: bytes', 'falta Accept-Ranges: bytes');
}

echo "\n";
if ($falhas > 0) {
    echo "RESULTADO: FALHOU ($falhas falha(s), $avisos aviso(s))\n";
    exit(1);
}
echo "RESULTADO: OK ($avisos aviso(s))\n";
exit(0);
