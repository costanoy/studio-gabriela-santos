<?php
// Confere que o site renderizado do JSON é igual ao site antigo (commit 3c32a3b).
//
//   php ferramentas/verificar-migracao.php
//
// 1. Os 20 enquadramentos (5 comparadores x 2 lados x home e resultados) do HTML antigo
//    saem iguais, como string, no site.padrao.json e no style renderizado.
// 2. (depois do L15) o HTML renderizado é igual ao antigo, ignorando espaços.
// 3. (depois do L07) telefone, horário e endereço ficam em um só lugar e saem 7 links wa.me.

if (PHP_SAPI !== 'cli') {
    exit;
}

$raiz = dirname(__DIR__);
$falhas = [];
$ok = 0;

function conferir(bool $cond, string $msg): void
{
    global $falhas, $ok;
    if ($cond) {
        $ok++;
        echo "  ok   $msg\n";
    } else {
        $falhas[] = $msg;
        echo "  FALHA $msg\n";
    }
}

function html_antigo(string $raiz, string $arquivo): string
{
    $cmd = 'git -C ' . escapeshellarg($raiz) . ' show 3c32a3b:' . escapeshellarg($arquivo);
    $saida = shell_exec($cmd);
    return is_string($saida) ? $saida : '';
}

/** Lista "w/h/l/t" (strings) das fotos com enquadramento, na ordem do documento. */
function enquadramentos_de(string $html): array
{
    $r = [];
    $re = '/<img [^>]*style="position:\s*absolute;\s*width:\s*([0-9.]+)%;\s*height:\s*([0-9.]+)%;\s*left:\s*([0-9.]+)%;\s*top:\s*([0-9.]+)%;\s*transform:\s*translate\(-50%,\s*-50%\);?"/';
    if (preg_match_all($re, $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $x) {
            $r[] = $x[1] . '/' . $x[2] . '/' . $x[3] . '/' . $x[4];
        }
    }
    return $r;
}

function numero_texto($v): string
{
    return rtrim(rtrim(number_format((float) $v, 6, '.', ''), '0'), '.');
}

/** Enquadramentos esperados, na ordem do documento, a partir do JSON. */
function enquadramentos_do_json(array $c, bool $home): array
{
    $itens = array_values(array_filter($c['resultados']['itens'], fn($i) => $i['tipo'] === 'comparacao' && ($home ? !empty($i['na_home']) : !empty($i['em_resultados']))));
    if ($home) {
        usort($itens, fn($a, $b) => $a['ordem_home'] <=> $b['ordem_home']);
    }
    $cats = [];
    foreach ($c['resultados']['categorias'] as $cat) {
        $cats[$cat['id']] = $cat['ordem'];
    }
    if (!$home) {
        usort($itens, fn($a, $b) => [$cats[$a['categoria']], $a['ordem']] <=> [$cats[$b['categoria']], $b['ordem']]);
    }
    $r = [];
    foreach ($itens as $i) {
        foreach (['depois', 'antes'] as $lado) {
            $e = $i[$lado]['enquadramento'];
            $r[] = numero_texto($e['width']) . '/' . numero_texto($e['height']) . '/' . numero_texto($e['left']) . '/' . numero_texto($e['top']);
        }
    }
    return $r;
}

function normalizar(string $html): string
{
    $html = preg_replace('/\s+/', ' ', $html);
    $html = preg_replace('/>\s+</', '><', $html);
    $html = preg_replace('/;\s+/', ';', $html);
    $html = preg_replace('/\s*;\s*"/', '"', $html);
    $html = preg_replace('/:\s+/', ':', $html);
    // JSON-LD: só o conteúdo importa, não os espaços entre as chaves
    $html = preg_replace_callback('#(<script type="application/ld\+json">)\s*(.*?)\s*(</script>)#s', static function ($m) {
        $j = json_decode($m[2], true);
        return $m[1] . ($j === null ? $m[2] : json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . $m[3];
    }, $html);
    $html = str_replace(['=""', ' >'], ['', '>'], $html);
    return trim($html);
}

echo "Migração: HTML antigo (3c32a3b) contra o JSON\n";

$jsonArq = $raiz . '/dados/site.padrao.json';
conferir(is_file($jsonArq), 'dados/site.padrao.json existe');
$c = json_decode((string) @file_get_contents($jsonArq), true);
conferir(is_array($c) && ($c['versao_esquema'] ?? 0) === 1, 'JSON válido, versao_esquema 1');
if (!is_array($c)) {
    exit(1);
}

$antigoHome = html_antigo($raiz, 'index.html');
$antigoRes = html_antigo($raiz, 'resultados.html');
conferir($antigoHome !== '' && $antigoRes !== '', 'HTML antigo lido do Git');

$eHomeAntigo = enquadramentos_de($antigoHome);
$eResAntigo = enquadramentos_de($antigoRes);
conferir(count($eHomeAntigo) === 10, 'home antiga: 10 enquadramentos (' . count($eHomeAntigo) . ')');
conferir(count($eResAntigo) === 10, 'resultados antigo: 10 enquadramentos (' . count($eResAntigo) . ')');
conferir($eHomeAntigo === $eResAntigo, 'home e resultados antigos usam os mesmos 10 enquadramentos');

$eJsonHome = enquadramentos_do_json($c, true);
$eJsonRes = enquadramentos_do_json($c, false);
conferir($eJsonHome === $eHomeAntigo, 'JSON (home) igual ao HTML antigo, 10 de 10');
conferir($eJsonRes === $eResAntigo, 'JSON (resultados) igual ao HTML antigo, 10 de 10');
if ($eJsonHome !== $eHomeAntigo) {
    echo "    esperado: " . implode(' | ', $eHomeAntigo) . "\n    json:     " . implode(' | ', $eJsonHome) . "\n";
}

// Enquadramentos guardados como número, sem arredondar
foreach ($c['resultados']['itens'] as $i) {
    if ($i['tipo'] === 'comparacao') {
        foreach (['antes', 'depois'] as $lado) {
            $e = $i[$lado]['enquadramento'] ?? null;
            conferir(is_array($e) && isset($e['width'], $e['height'], $e['left'], $e['top']) && ($e['automatico'] ?? null) === false, $i['id'] . ' ' . $lado . ': enquadramento manual completo');
        }
    }
}

if (is_file($raiz . '/nucleo/render.php')) {
    echo "\nRenderização: PHP contra HTML antigo\n";
    define('RAIZ', $raiz);
    require_once $raiz . '/nucleo/config.php';
    require_once $raiz . '/nucleo/conteudo.php';
    require_once $raiz . '/nucleo/render.php';

    $paginas = [];
    foreach (['index' => $antigoHome, 'resultados' => $antigoRes] as $nome => $antigo) {
        $_SERVER['REQUEST_URI'] = $nome === 'index' ? '/' : '/resultados.html';
        ob_start();
        include $raiz . '/' . $nome . '.php';
        $novo = (string) ob_get_clean();
        $paginas[$nome] = $novo;

        $eNovo = enquadramentos_de($novo);
        $eAnt = enquadramentos_de($antigo);
        conferir($eNovo === $eAnt && count($eNovo) === 10, "$nome.php: os 10 enquadramentos saem iguais como string");
        if ($eNovo !== $eAnt) {
            echo "    antigo: " . implode(' | ', $eAnt) . "\n    novo:   " . implode(' | ', $eNovo) . "\n";
        }

        preg_match_all('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $novo, $ld);
        $validos = $ld[1] !== [];
        foreach ($ld[1] as $j) {
            if (json_decode($j, true) === null) {
                $validos = false;
            }
        }
        conferir($validos, "$nome.php: todos os ld+json passam em JSON.parse (" . count($ld[1]) . ')');

        preg_match_all('/\sid="([^"]+)"/', $antigo, $a);
        preg_match_all('/\sid="([^"]+)"/', $novo, $b);
        conferir(array_values(array_unique($a[1])) === array_values(array_unique($b[1])), "$nome.php: mesmos ids na mesma ordem");
        $oc_antigo = preg_match_all('/onclick="([^"]*)"/', $antigo, $oa) ? $oa[1] : [];
        $oc_novo = preg_match_all('/onclick="([^"]*)"/', $novo, $on) ? $on[1] : [];
        conferir($oc_antigo === $oc_novo, "$nome.php: mesmos onclick (" . count($oc_novo) . ')');

        // Única diferença aceita na renderização: "Corrente Fortalecedora" tem 2 itens e
        // passa de 3 para 2 colunas (colunas = min(itens, 3), regra do plano).
        if ($nome === 'resultados') {
            $pos = strrpos($antigo, 'repeat(3, minmax(240px, 340px))');
            if ($pos !== false) {
                $antigo = substr_replace($antigo, 'repeat(2, minmax(240px, 340px))', $pos, strlen('repeat(3, minmax(240px, 340px))'));
            }
        }
        // Depois do L07 o rodapé usa o horário único do JSON ("das 8h às 17h").
        if (strpos((string) @file_get_contents($raiz . '/parciais/rodape.php'), "['horario']") !== false) {
            $antigo = str_replace('Segunda à sexta, 8h às 17h', $c['contato']['horario'], $antigo);
        }
        $iguais = normalizar($novo) === normalizar($antigo);
        echo '  info ' . $nome . '.php: HTML normalizado ' . ($iguais ? 'idêntico ao antigo' : 'tem diferenças (veja abaixo)') . "\n";
        if (!$iguais && getenv('DIFERENCAS')) {
            file_put_contents(sys_get_temp_dir() . "/$nome-antigo.html", str_replace('><', ">\n<", normalizar($antigo)));
            file_put_contents(sys_get_temp_dir() . "/$nome-novo.html", str_replace('><', ">\n<", normalizar($novo)));
            echo '       arquivos em ' . sys_get_temp_dir() . "/$nome-antigo.html e $nome-novo.html\n";
        }
        $GLOBALS['igual_' . $nome] = $iguais;
    }

    // Tudo o mais deve ser idêntico ao HTML antigo. Diferenças aceitas (aplicadas no antigo acima):
    // Corrente Fortalecedora com 2 colunas e, depois do L07, o horário único do rodapé.
    conferir($GLOBALS['igual_index'], 'index.php: HTML idêntico ao antigo (normalizado)');
    conferir($GLOBALS['igual_resultados'], 'resultados.php: HTML idêntico ao antigo (normalizado)');

    // L07: telefone, horário e endereço em um só lugar
    $home = $paginas['index'];
    $n_wa = preg_match_all('#https://wa\.me/5562984141116\?text=#', $home);
    $n_wa_res = preg_match_all('#https://wa\.me/5562984141116\?text=#', $paginas['resultados']);
    echo "\nContato (L07)\n";
    conferir($n_wa >= 6, "links wa.me na home gerados de contato ($n_wa)");
    conferir($n_wa + $n_wa_res >= 7, "7 ou mais links wa.me no total ($n_wa + $n_wa_res)");
    $txt = $c['contato'];
    $hor = [];
    foreach (['index' => $home, 'resultados' => $paginas['resultados']] as $nome => $h) {
        $t = html_entity_decode(strip_tags($h), ENT_QUOTES, 'UTF-8');
        preg_match_all('/Segunda à sexta[^\n<]*/u', $t, $mm);
        foreach ($mm[0] as $f) {
            $hor[$nome . ':' . trim($f)] = true;
        }
    }
    $formas = array_unique(array_map(fn($k) => substr($k, strpos($k, ':') + 1), array_keys($hor)));
    $unificado = count($formas) === 1 && $formas[0] === $txt['horario'];
    if (!$unificado) {
        echo '  info formas do horário: ' . json_encode(array_values($formas), JSON_UNESCAPED_UNICODE) . "\n";
    }
    conferir(true, 'horário: ' . ($unificado ? 'uma forma só, igual ao JSON' : 'ainda com formas diferentes (L07 pendente)'));
    $L07 = file_exists($raiz . '/parciais/rodape.php') && strpos((string) file_get_contents($raiz . '/parciais/rodape.php'), "['horario']") !== false;
    if ($L07) {
        conferir($unificado, 'L07: horário igual em todas as ocorrências');
        $tel = preg_match_all('/\(62\) 98414-1116/', $home . $paginas['resultados']);
        conferir($tel >= 1, 'telefone exibido vem de contato.whatsapp_exibicao');
        $arqs = array_merge(glob($raiz . '/parciais/*.php'), [$raiz . '/index.php', $raiz . '/resultados.php']);
        $literais = [];
        foreach ($arqs as $a) {
            $s = (string) file_get_contents($a);
            if (preg_match('/5562984141116|98414-1116|Segunda à sexta|Palmeiras 1/u', $s)) {
                $literais[] = basename($a);
            }
        }
        conferir($literais === [], 'nenhum parcial ou página tem telefone, horário ou endereço escrito à mão' . ($literais ? ' (' . implode(', ', $literais) . ')' : ''));
        $rua = substr_count($home, $txt['endereco_curto']);
        conferir($rua >= 2, 'endereço curto sai do JSON (' . $rua . 'x na home)');
    }
}

echo "\n";
if ($falhas) {
    echo count($falhas) . " falha(s), $ok ok.\n";
    exit(1);
}
echo "Tudo certo: $ok verificações.\n";
exit(0);
