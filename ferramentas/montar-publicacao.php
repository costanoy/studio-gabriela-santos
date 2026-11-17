<?php
// Monta o pacote de publicação por LISTA BRANCA (R1).
//
//   php ferramentas/montar-publicacao.php
//
// Gera publicar/site/ e publicar/site-AAAAMMDD-HHMM.zip (os dois estão no .gitignore).
// Só entra o que está na lista abaixo. Se aparecer qualquer outro arquivo dentro das pastas
// da lista (um .md, um .json, um backup, uma foto esquecida), o script falha e não gera nada.
// conteudo/midia/* (as fotos e vídeos da dona) e acervo/ NUNCA entram.
// Publicar = extrair o zip por cima no Gerenciador de Arquivos. Veja PUBLICACAO.md.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__);

// Arquivos soltos que entram.
$arquivos = [
    'index.php', 'resultados.php', '404.php', '.htaccess', '.user.ini', 'robots.txt', 'sitemap.xml',
    'dados/site.padrao.json', 'conteudo/.htaccess', 'conteudo/midia/.htaccess',
];

// Pastas inteiras que entram, e só com estas extensões (nome sem ponto, em minúsculas).
// '.htaccess' vale como nome de arquivo inteiro.
$pastas = [
    'assets'   => ['webp', 'jpg', 'jpeg', 'png', 'svg', 'woff2', 'mp4', 'ofl.txt'],
    'nucleo'   => ['php'],
    'parciais' => ['php'],
    'painel'   => ['php', 'js', 'css', '.htaccess'],
];

// Nomes que nunca podem entrar, em lugar nenhum do pacote.
$proibidos = '#(^|/)(painel-config[^/]*|senha[^/]*|\.env[^/]*|[^/]*\.(pem|key|md|log|bak|sql|zip))$#i';

function falhar(array $problemas): never
{
    fwrite(STDERR, "\nPACOTE RECUSADO. Arquivos fora da lista branca ou com problema:\n");
    foreach ($problemas as $p) {
        fwrite(STDERR, "  - $p\n");
    }
    fwrite(STDERR, "\nNada foi gerado. Tire ou mova esses arquivos (ou ajuste a lista em ferramentas/montar-publicacao.php) e rode de novo.\n");
    exit(1);
}

function formatar(int $bytes): string
{
    return $bytes >= 1048576 ? number_format($bytes / 1048576, 2, ',', '.') . ' MB' : number_format($bytes / 1024, 1, ',', '.') . ' KB';
}

$lista = [];     // caminho relativo => caminho absoluto
$problemas = [];

foreach ($arquivos as $rel) {
    if (!is_file("$raiz/$rel")) {
        $problemas[] = "$rel (faltando)";
    } else {
        $lista[$rel] = "$raiz/$rel";
    }
}

foreach ($pastas as $pasta => $extensoes) {
    if (!is_dir("$raiz/$pasta")) {
        $problemas[] = "$pasta/ (pasta faltando)";
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$raiz/$pasta", FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) {
            continue;
        }
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($raiz) + 1));
        $nome = $f->getFilename();
        $ext = strtolower($f->getExtension());
        if (!in_array($ext, $extensoes, true) && !in_array(strtolower($nome), $extensoes, true)) {
            $problemas[] = "$rel (extensão ou nome não permitido em $pasta/)";
            continue;
        }
        $lista[$rel] = $f->getPathname();
    }
}

foreach (array_keys($lista) as $rel) {
    if (preg_match($proibidos, $rel)) {
        $problemas[] = "$rel (nome proibido: segredo, anotação ou backup)";
    }
    if (preg_match('#^(acervo|\.local|\.git|\.auditoria|tests|ferramentas|node_modules|publicar)/#', $rel)) {
        $problemas[] = "$rel (pasta de desenvolvimento)";
    }
    if (str_starts_with($rel, 'conteudo/') && !in_array($rel, ['conteudo/.htaccess', 'conteudo/midia/.htaccess'], true)) {
        $problemas[] = "$rel (conteudo/ só entra com os dois .htaccess)";
    }
}

if ($problemas) {
    falhar(array_values(array_unique($problemas)));
}

ksort($lista);

// Monta publicar/site/
$saida = "$raiz/publicar";
$site = "$saida/site";
function apagar_pasta(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}
apagar_pasta($site);
if (!is_dir($saida) && !mkdir($saida, 0775, true)) {
    fwrite(STDERR, "Não consegui criar $saida\n");
    exit(1);
}

$total = 0;
foreach ($lista as $rel => $origem) {
    $destino = "$site/$rel";
    if (!is_dir(dirname($destino))) {
        mkdir(dirname($destino), 0775, true);
    }
    if (!copy($origem, $destino)) {
        fwrite(STDERR, "Não consegui copiar $rel\n");
        exit(1);
    }
    $total += filesize($origem);
}

// Zip
if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "A extensão zip do PHP não está ativa. Rode com: php -d extension=zip ferramentas/montar-publicacao.php\n");
    exit(1);
}
$zipNome = 'site-' . date('Ymd-Hi') . '.zip';
$zipCaminho = "$saida/$zipNome";
$zip = new ZipArchive();
if ($zip->open($zipCaminho, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Não consegui criar $zipCaminho\n");
    exit(1);
}
foreach ($lista as $rel => $origem) {
    $zip->addFile($origem, $rel);
}
$zip->close();

// Confere o zip por dentro: só o que está na lista.
$z = new ZipArchive();
$z->open($zipCaminho);
$dentro = [];
for ($i = 0; $i < $z->numFiles; $i++) {
    $dentro[] = $z->getNameIndex($i);
}
$z->close();
sort($dentro);
$esperado = array_keys($lista);
sort($esperado);
if ($dentro !== $esperado) {
    falhar(['o zip gerado não bate com a lista branca']);
}

echo "Arquivos do pacote (" . count($lista) . "):\n";
foreach ($lista as $rel => $origem) {
    printf("  %10s  %s\n", formatar(filesize($origem)), $rel);
}
echo "\nTotal sem compactar: " . formatar($total) . "\n";
echo 'Zip: publicar/' . $zipNome . ' (' . formatar(filesize($zipCaminho)) . ")\n";
echo "Pasta: publicar/site/\n";
echo "OK. conteudo/midia/* e acervo/ ficaram de fora.\n";
exit(0);
