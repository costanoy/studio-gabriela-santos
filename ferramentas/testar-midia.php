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

// ---------------------------------------------------------------------------
// Vídeo
// ---------------------------------------------------------------------------

/** Envia $arquivo em partes pelas funções do núcleo. $so_ate: para depois dessa quantidade de partes (simula queda). */
function enviar_video(string $arquivo, ?string $envio = null, ?int $so_ate = null, array $pular = []): array
{
    global $tmp;
    if ($envio === null) {
        $ini = midia_video_iniciar(filesize($arquivo), basename($arquivo));
        if (isset($ini['erro'])) {
            return $ini;
        }
    } else {
        $ini = midia_video_estado($envio) + ['envio' => $envio];
    }
    $h = fopen($arquivo, 'rb');
    $enviadas = 0;
    for ($i = 0; $i < $ini['total']; $i++) {
        if (in_array($i, $pular, true)) {
            continue;
        }
        if ($so_ate !== null && $enviadas >= $so_ate) {
            break;
        }
        fseek($h, $i * $ini['parte']);
        $p = $tmp . '/parte-' . $i . '.bin';
        file_put_contents($p, (string) fread($h, $ini['parte']));
        $r = midia_video_parte($ini['envio'], $i, $p);
        if (isset($r['erro'])) {
            fclose($h);
            return $r;
        }
        $enviadas++;
    }
    fclose($h);
    return ['envio' => $ini['envio'], 'total' => $ini['total'], 'parte' => $ini['parte']];
}

function enviar_e_concluir(string $arquivo): array
{
    $e = enviar_video($arquivo);
    return isset($e['erro']) ? $e : midia_video_concluir($e['envio']);
}

$vfix = fn(string $n) => "$fix/$n";
echo "\nL10 vídeo do iPhone\n";
$ok264 = enviar_e_concluir($vfix('video-h264-com-local.mp4'));
t(isset($ok264['arquivo']) && preg_match('#^conteudo/midia/\d{4}/[0-9a-f]{16}\.mp4$#', $ok264['arquivo']) === 1, 'vídeo H.264 pequeno é aceito e ganha nome aleatório');
t(($ok264['largura'] ?? 0) === 320 && ($ok264['altura'] ?? 0) === 240, 'devolve as dimensões do vídeo (320x240)');
$hevc = enviar_e_concluir($vfix('video-hevc.mp4'));
t(($hevc['erro'] ?? '') === 'hevc', 'vídeo HEVC é recusado com o código hevc');
$k4 = enviar_e_concluir($vfix('video-4k.mp4'));
t(($k4['erro'] ?? '') === 'grande_demais', 'vídeo 4K é recusado com o código grande_demais');
$gir = enviar_e_concluir($vfix('video-1080-girado.mp4'));
t(($gir['largura'] ?? 0) === 1080 && ($gir['altura'] ?? 0) === 1920, 'vídeo 1920x1080 com giro de 90 graus é lido como 1080x1920 e passa');
$pe = enviar_e_concluir($vfix('video-1080-em-pe.mp4'));
t(($pe['largura'] ?? 0) === 1080 && ($pe['altura'] ?? 0) === 1920, 'vídeo em pé de 1080x1920 passa');
$au = enviar_e_concluir($vfix('audio-sem-video.mp4'));
t(($au['erro'] ?? '') === 'video_ilegivel', 'MP4 só com áudio é recusado');
file_put_contents($tmp . '/falso.mp4', file_get_contents($vfix('foto.png')) . str_repeat('x', 2000));
$png_como_video = enviar_e_concluir($tmp . '/falso.mp4');
t(($png_como_video['erro'] ?? '') === 'video_formato', 'PNG mandado como vídeo é recusado (video_formato)');
$pesado = midia_video_iniciar(300 * 1024 * 1024, 'grande.mov');
t(($pesado['erro'] ?? '') === 'video_pesado' && ($pesado['tamanho_mb'] ?? 0) === 300 && ($pesado['limite_mb'] ?? 0) === 250, 'vídeo de 300 MB é recusado já no começo, com os números para a frase');
t(count(glob($tmp . '/dados/envios/*')) === 0, 'envios recusados não deixam pasta de trabalho para trás');

echo "\nT13 localização do vídeo\n";
$caminho_ok = caminho_saida($tmp, $ok264['arquivo']);
$bruto = (string) file_get_contents($caminho_ok);
t(strpos($bruto, "\xA9xyz") === false && strpos($bruto, 'ISO6709') === false && strpos($bruto, '+15.9365') === false, 'nos bytes do vídeo não há ©xyz, ISO6709 nem coordenadas');
t(strpos($bruto, 'Apple') === false, 'nos bytes do vídeo não sobra a marca do aparelho');
$origem_bruta = (string) file_get_contents($vfix('video-h264-com-local.mp4'));
t(strpos($origem_bruta, 'ISO6709') !== false, 'o exemplo de teste tinha mesmo localização (o teste é válido)');
$ffprobe = trim((string) shell_exec('ffprobe -v error -show_format -show_streams ' . escapeshellarg($caminho_ok) . ' 2>&1'));
if ($ffprobe !== '' && stripos($ffprobe, 'codec_name') !== false) {
    t(preg_match('/TAG:[^=\n]*(location|xyz)/i', $ffprobe) === 0, 'ffprobe -show_format não mostra nenhuma tag location');
    $orig_probe = (string) shell_exec('ffprobe -v error -show_format ' . escapeshellarg($vfix('video-h264-com-local.mp4')) . ' 2>&1');
    t(preg_match('/TAG:location=/', $orig_probe) === 1, 'o ffprobe do exemplo original mostra location (o teste enxerga o problema)');
    t(strpos($ffprobe, 'codec_name=h264') !== false && strpos($ffprobe, 'width=320') !== false, 'o vídeo continua tocável (ffprobe lê h264 320 de largura)');
} else {
    t(false, 'ffprobe precisa estar no PATH para este teste');
}
$d = trim((string) shell_exec('ffmpeg -v error -i ' . escapeshellarg($caminho_ok) . ' -f null - 2>&1'));
t($d === '', 'ffmpeg decodifica o vídeo limpo inteiro sem erro');

echo "\nT15 envio em partes e retomada\n";
$grande = $tmp . '/60mb.mp4';
$base = (string) file_get_contents($vfix('video-1080-em-pe.mp4'));
$resto = 60 * 1024 * 1024 - strlen($base);
$gh = fopen($grande, 'wb');
fwrite($gh, $base . pack('N', $resto) . 'free');
for ($k = 0; $k < $resto - 8; $k += 1048576) {
    fwrite($gh, str_repeat("\0", min(1048576, $resto - 8 - $k)));
}
fclose($gh);
clearstatcache();
t(filesize($grande) === 60 * 1024 * 1024, 'vídeo de teste de 60 MB criado');
$ini = midia_video_iniciar(filesize($grande), '60mb.mp4');
t(isset($ini['envio']) && $ini['parte'] <= 5 * 1024 * 1024 && $ini['total'] === (int) ceil(filesize($grande) / $ini['parte']), 'o servidor define partes de até 5 MB');
$lim = min(midia_bytes_ini((string) ini_get('upload_max_filesize')), midia_bytes_ini((string) ini_get('post_max_size')));
t($ini['parte'] <= $lim * 0.9 + 1, 'a parte cabe com folga de 10% no limite do servidor');
midia_envio_apagar($ini['envio']);
$a = enviar_video($grande, null, 5);
t(isset($a['envio']), 'primeiras partes enviadas e a "conexão cai"');
$est = midia_video_estado($a['envio']);
t(count($est['recebidas']) === 5, 'o estado mostra 5 partes recebidas');
$inc = midia_video_concluir($a['envio']);
t(($inc['erro'] ?? '') === 'partes_faltando', 'concluir com parte faltando devolve partes_faltando');
$errada = $tmp . '/errada.bin';
file_put_contents($errada, 'curta');
$r_errada = midia_video_parte($a['envio'], 9, $errada);
t(($r_errada['erro'] ?? '') === 'parte_invalida', 'parte de tamanho errado devolve parte_invalida');
$r_idx = midia_video_parte($a['envio'], 9999, $errada);
t(($r_idx['erro'] ?? '') === 'parte_invalida', 'número de parte fora do envio devolve parte_invalida');
$b = enviar_video($grande, $a['envio'], null, $est['recebidas']);
t(!isset($b['erro']), 'retomada: só as partes que faltam são enviadas');
$fim = midia_video_concluir($a['envio']);
t(isset($fim['arquivo']), 'depois de retomar, o vídeo de 60 MB conclui');
t(isset($fim['arquivo']) && filesize(caminho_saida($tmp, $fim['arquivo'])) === 60 * 1024 * 1024, 'o arquivo final tem os 60 MB certos');
t(count(glob($tmp . '/dados/envios/*')) === 0, 'a pasta de partes é apagada ao concluir');
$naoexiste = midia_video_estado('0123456789abcdef01234567');
t(($naoexiste['erro'] ?? '') === 'envio_nao_encontrado', 'envio desconhecido devolve envio_nao_encontrado');
t((midia_video_estado('../../etc')['erro'] ?? '') === 'envio_nao_encontrado', 'identificador com ../ é recusado');
$c = enviar_video($vfix('video-h264-com-local.mp4'), null, 0);
midia_envio_apagar($c['envio']);
t(!is_dir($tmp . '/dados/envios/' . $c['envio']), 'cancelar apaga as partes');
$velho = midia_video_iniciar(2 * 1024 * 1024, 'velho.mp4');
touch($tmp . '/dados/envios/' . $velho['envio'], time() - 90000);
midia_video_iniciar(2 * 1024 * 1024, 'novo.mp4');
t(!is_dir($tmp . '/dados/envios/' . $velho['envio']), 'envio parado há mais de um dia é apagado');

echo "\n";
limpar_pasta($tmp);
config_definir(null);
if ($falhas) {
    echo count($falhas) . " falha(s), $ok ok.\n";
    exit(1);
}
echo "Tudo certo: $ok verificações.\n";
exit(0);
