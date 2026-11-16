<?php
// Testes do núcleo de mídia (Lote 2B), sem navegador.
//   php -d extension_dir=<pasta do php>/ext -d extension=gd -d extension=exif ferramentas/testar-midia.php [midia]
// (testar-nucleo.php é do Lote 1; este arquivo é o grupo "midia" separado para não mexer nele.)
//
// Modos internos usados pelos testes de navegador:
//   --sessao     cria uma sessão logada na config de .local/ e imprime {nome, id, csrf} em JSON
//   --interno-24mp <pasta>   gera uma foto de 24 MP e processa (roda com memory_limit=256M)
define('RAIZ', dirname(__DIR__));
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';
require_once RAIZ . '/nucleo/seguranca.php';
require_once RAIZ . '/nucleo/midia.php';
ob_start();

// ---------------------------------------------------------------------------
// Modos internos
// ---------------------------------------------------------------------------
if (in_array('--sessao', $argv, true)) {
    $cfg = include RAIZ . '/.local/painel-config.php';
    config_definir($cfg);
    sessao_iniciar();
    $_SESSION = ['logado' => true, 'ultimo' => time()];
    $token = csrf_token();
    $id = session_id();
    session_write_close();
    ob_end_clean();
    echo json_encode(['nome' => sessao_nome(), 'id' => $id, 'csrf' => $token]);
    exit(0);
}

function pasta_nova(): string
{
    $d = str_replace('\\', '/', sys_get_temp_dir()) . '/sgs-midia-teste-' . bin2hex(random_bytes(4));
    mkdir($d . '/dados/envios', 0775, true);
    mkdir($d . '/midia', 0775, true);
    return $d;
}

function limpar_pasta(string $d): void
{
    foreach (glob($d . '/{,.}*', GLOB_BRACE) ?: [] as $f) {
        if (in_array(basename($f), ['.', '..'], true)) {
            continue;
        }
        is_dir($f) ? limpar_pasta($f) : @unlink($f);
    }
    @rmdir($d);
}

function configurar(string $tmp): void
{
    config_definir(['senha_hash' => 'x', 'dados_dir' => $tmp . '/dados', 'midia_dir' => $tmp . '/midia', 'midia_url' => 'conteudo/midia']);
}

function caminho_saida(string $tmp, string $arquivo): string
{
    return $tmp . '/midia/' . substr($arquivo, strlen('conteudo/midia/'));
}

$pos = array_search('--interno-24mp', $argv, true);
if ($pos !== false) {
    $tmp = (string) $argv[$pos + 1];
    configurar($tmp);
    $im = imagecreatetruecolor(5712, 4284);
    imagefill($im, 0, 0, imagecolorallocate($im, 200, 180, 160));
    imagefilledellipse($im, 2800, 2100, 3000, 3000, imagecolorallocate($im, 90, 60, 40));
    imagejpeg($im, $tmp . '/24mp.jpg', 60);
    imagedestroy($im);
    $r = midia_foto_salvar($tmp . '/24mp.jpg', '24mp.jpg');
    ob_end_clean();
    echo json_encode($r + ['pico' => memory_get_peak_usage(true)]);
    exit(0);
}

// ---------------------------------------------------------------------------
// Estrutura dos testes
// ---------------------------------------------------------------------------
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

$fix = RAIZ . '/tests/fixtures/midia';
$tmp = pasta_nova();
configurar($tmp);

/** Copia o exemplo para a pasta de teste e processa (os testes nunca mexem no arquivo original). */
function foto(string $nome, int $maximo = 1600): array
{
    global $fix, $tmp;
    $copia = $tmp . '/entrada-' . bin2hex(random_bytes(3)) . '-' . $nome;
    copy("$fix/$nome", $copia);
    return midia_foto_salvar($copia, $nome, $maximo);
}

function pixel_saida(string $arquivo, int $x, int $y): array
{
    global $tmp;
    $im = imagecreatefromstring((string) file_get_contents(caminho_saida($tmp, $arquivo)));
    $c = imagecolorat($im, $x, $y);
    imagedestroy($im);
    return [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
}

function azul(array $p): bool { return $p[2] > 150 && $p[0] < 90; }
function vermelho(array $p): bool { return $p[0] > 150 && $p[1] < 90 && $p[2] < 90; }

/** Procura caixas de metadados dentro de um WebP/JPEG/PNG gravado. */
function tem_metadados(string $bytes): bool
{
    if (substr($bytes, 0, 4) === 'RIFF') {
        return (bool) preg_match('/EXIF|XMP |ICCP/', substr($bytes, 12, 400)) || strpos($bytes, 'Exif') !== false;
    }
    return strpos($bytes, 'Exif') !== false || strpos($bytes, 'Apple') !== false || strpos($bytes, 'http://ns.adobe.com') !== false;
}

echo "L12 fotos: orientação, tipos e HEIC\n";
$r = foto('jpeg-orientacao6-gps.jpg');
t(isset($r['arquivo']) && !isset($r['erro']), 'JPEG com orientação 6 é aceito');
t(($r['largura'] ?? 0) === 300 && ($r['altura'] ?? 0) === 400, 'a foto deitada com orientação 6 sai em pé (300x400)');
t(preg_match('#^conteudo/midia/\d{4}/[0-9a-f]{16}\.(webp|jpg)$#', $r['arquivo'] ?? '') === 1, 'nome aleatório de 16 hex em conteudo/midia/AAAA/');
t(azul(pixel_saida($r['arquivo'], 295, 200)), 'o topo da foto original (barra azul) foi para a direita');
t(vermelho(pixel_saida($r['arquivo'], 8, 8)), 'o quadrado vermelho foi para o canto de cima, à esquerda');
$em_pe = foto('jpeg-em-pe-sem-exif.jpg');
t(($em_pe['largura'] ?? 0) === 300 && ($em_pe['altura'] ?? 0) === 400, 'foto que já está em pé não gira de novo');
t(azul(pixel_saida($em_pe['arquivo'], 150, 8)), 'foto em pé mantém a barra azul no topo');
$png = foto('foto.png');
t(isset($png['arquivo']) && substr($png['arquivo'], -5) === '.webp', 'PNG vira WebP');
$h = foto('foto-iphone.heic');
t(($h['erro'] ?? '') === 'heic', 'HEIC cru é recusado com o código heic');
$p = foto('php-disfarcado.jpg');
t(($p['erro'] ?? '') === 'tipo', '.php renomeado para .jpg é recusado (tipo)');
t(count(glob($tmp . '/midia/*/*')) === 3, 'nenhum arquivo foi gravado pelas fotos recusadas');
$vazio = midia_foto_salvar($tmp . '/nao-existe.jpg', 'x.jpg');
t(($vazio['erro'] ?? '') === 'foto_ilegivel', 'arquivo que não existe dá foto_ilegivel');

echo "\nL12 redução\n";
$im = imagecreatetruecolor(3200, 2400);
imagefill($im, 0, 0, imagecolorallocate($im, 120, 90, 70));
imagejpeg($im, $tmp . '/grande.jpg', 85);
imagedestroy($im);
$g = midia_foto_salvar($tmp . '/grande.jpg', 'grande.jpg');
t(($g['largura'] ?? 0) === 1600 && ($g['altura'] ?? 0) === 1200, 'foto de 3200x2400 sai com 1600 px no lado maior');
$im = imagecreatetruecolor(3200, 2400);
imagefill($im, 0, 0, imagecolorallocate($im, 120, 90, 70));
imagejpeg($im, $tmp . '/grande2.jpg', 85);
imagedestroy($im);
$g8 = midia_foto_salvar($tmp . '/grande2.jpg', 'grande.jpg', 800);
t(($g8['largura'] ?? 0) === 800 && ($g8['altura'] ?? 0) === 600, 'modo comparador: 800 px no lado maior');
$im = imagecreatetruecolor(400, 300);
imagefill($im, 0, 0, imagecolorallocate($im, 1, 2, 3));
imagejpeg($im, $tmp . '/pequena.jpg', 85);
imagedestroy($im);
$pq = midia_foto_salvar($tmp . '/pequena.jpg', 'pequena.jpg');
t(($pq['largura'] ?? 0) === 400, 'foto pequena não é ampliada');

echo "\nT12 GD seguro: limites e sem metadados\n";
// PNG falso só com o cabeçalho de 7000x6000 (42 MP): o getimagesize lê o cabeçalho e a foto é recusada antes do GD
$ihdr = pack('NNCCCCC', 7000, 6000, 8, 2, 0, 0, 0);
$png42 = "\x89PNG\r\n\x1a\n" . pack('N', 13) . 'IHDR' . $ihdr . pack('N', crc32('IHDR' . $ihdr)) . pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));
file_put_contents($tmp . '/42mp.png', $png42);
$antes = memory_get_peak_usage(true);
$e42 = midia_foto_salvar($tmp . '/42mp.png', '42mp.png');
t(($e42['erro'] ?? '') === 'foto_enorme', 'foto de 42 MP é recusada (foto_enorme)');
t(memory_get_peak_usage(true) - $antes < 8 * 1024 * 1024, 'a recusa de 42 MP não gastou memória (o GD nem abriu o arquivo)');
// 24 MP real, com memory_limit de 256M, num processo separado
$cmd = escapeshellarg(PHP_BINARY) . ' -d extension_dir=' . escapeshellarg(ini_get('extension_dir')) . ' -d extension=gd -d memory_limit=256M '
    . escapeshellarg(__FILE__) . ' --interno-24mp ' . escapeshellarg($tmp);
$saida = (string) shell_exec($cmd);
$r24 = json_decode(trim($saida), true) ?: [];
t(isset($r24['arquivo']) && ($r24['largura'] ?? 0) === 1600, 'foto de 24 MP passa com memory_limit de 256M (1600 px)');
t(isset($r24['arquivo']) && filesize(caminho_saida($tmp, $r24['arquivo'])) <= 250 * 1024, 'a foto de 24 MP sai com no máximo 250 KB');
// Sem memória: o limite é conferido antes do GD
$limite_antigo = ini_get('memory_limit');
ini_set('memory_limit', '40M');
$im_mem = imagecreatetruecolor(2000, 1500);
imagejpeg($im_mem, $tmp . '/2000.jpg', 80);
imagedestroy($im_mem);
$e_mem = midia_foto_salvar($tmp . '/2000.jpg', '2000.jpg');
ini_set('memory_limit', $limite_antigo);
t(($e_mem['erro'] ?? '') === 'foto_enorme', 'se a foto não cabe na memória, recusa em vez de dar erro 500');
// Ruído: a foto final fica ≤250 KB
$im = imagecreatetruecolor(1600, 1200);
for ($i = 0; $i < 60000; $i++) {
    imagesetpixel($im, random_int(0, 1599), random_int(0, 1199), imagecolorallocate($im, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
}
for ($y = 0; $y < 1200; $y += 3) {
    imageline($im, 0, $y, 1599, $y + random_int(-30, 30), imagecolorallocate($im, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
}
imagejpeg($im, $tmp . '/ruido.jpg', 95);
imagedestroy($im);
$rr = midia_foto_salvar($tmp . '/ruido.jpg', 'ruido.jpg');
t(isset($rr['arquivo']) && filesize(caminho_saida($tmp, $rr['arquivo'])) <= 250 * 1024, 'foto cheia de detalhes é comprimida até ≤250 KB');
$tudo = '';
foreach (glob($tmp . '/midia/*/*') as $f) {
    $tudo .= (string) file_get_contents($f);
}
t(!tem_metadados($tudo) && strpos($tudo, 'iPhone') === false && strpos($tudo, 'GPS') === false, 'nenhum arquivo gravado tem EXIF, Make, Model nem GPS');
$saida_gps = caminho_saida($tmp, $r['arquivo']);
$ex = function_exists('exif_read_data') ? @exif_read_data($saida_gps) : false;
t($ex === false || (!isset($ex['GPSLatitude']) && !isset($ex['Make']) && !isset($ex['Model']) && !isset($ex['Orientation'])), 'exif_read_data da saída não tem GPS, Make, Model nem Orientation');
t((fileperms($saida_gps) & 0111) === 0, 'o arquivo gravado não é executável');

echo "\n";
limpar_pasta($tmp);
config_definir(null);
if ($falhas) {
    echo count($falhas) . " falha(s), $ok ok.\n";
    exit(1);
}
echo "Tudo certo: $ok verificações.\n";
exit(0);
