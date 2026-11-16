<?php
// Gera os arquivos de exemplo dos testes de mídia em tests/fixtures/midia/.
//   php -d extension_dir=<pasta do php>/ext -d extension=gd ferramentas/gerar-fixtures.php
// Precisa de GD e, para os vídeos, de ffmpeg no PATH. Só roda em linha de comando.
// Cada arquivo fica abaixo de 300 KB. Os arquivos grandes dos testes (24 MP, 60 MB) nascem na hora do teste.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$pasta = dirname(__DIR__) . '/tests/fixtures/midia';
if (!is_dir($pasta)) {
    mkdir($pasta, 0775, true);
}

function gravar(string $nome, string $bytes): void
{
    global $pasta;
    file_put_contents("$pasta/$nome", $bytes);
    printf("  %-34s %7d bytes\n", $nome, strlen($bytes));
}

// ---------------------------------------------------------------------------
// EXIF escrito em bytes: Make, Model, Orientation e GPS (para provar que o servidor tira tudo)
// ---------------------------------------------------------------------------

function exif_app1(int $orientacao): string
{
    $asc = static fn(int $tag, string $v, int $off): string => pack('nnNN', $tag, 2, strlen($v), $off);
    $racional = static fn(array $partes): string => implode('', array_map(static fn($n) => pack('NN', $n, 1), $partes));

    $ifd0_n = 4;
    $ifd0_tam = 2 + $ifd0_n * 12 + 4;
    $dados = 8 + $ifd0_tam;                       // onde começam os dados fora da tabela
    $off_make = $dados;
    $make = "Apple\0";
    $off_model = $off_make + strlen($make);
    $model = "iPhone 13\0";
    $off_gps = $off_model + strlen($model);
    $gps_n = 4;
    $gps_tam = 2 + $gps_n * 12 + 4;
    $off_lat = $off_gps + $gps_tam;
    $off_lon = $off_lat + 24;

    $ifd0 = pack('n', $ifd0_n)
        . $asc(0x010F, $make, $off_make)
        . $asc(0x0110, $model, $off_model)
        . pack('nnNn', 0x0112, 3, 1, $orientacao) . "\0\0"
        . pack('nnNN', 0x8825, 4, 1, $off_gps)
        . pack('N', 0);

    $gps = pack('n', $gps_n)
        . pack('nnN', 0x0001, 2, 2) . "S\0\0\0"
        . pack('nnNN', 0x0002, 5, 3, $off_lat)
        . pack('nnN', 0x0003, 2, 2) . "W\0\0\0"
        . pack('nnNN', 0x0004, 5, 3, $off_lon)
        . pack('N', 0);

    $tiff = "MM\0\x2A" . pack('N', 8) . $ifd0 . $make . $model . $gps . $racional([15, 19, 56]) . $racional([49, 8, 2]);
    $corpo = "Exif\0\0" . $tiff;
    return "\xFF\xE1" . pack('n', strlen($corpo) + 2) . $corpo;
}

/** Insere o APP1 logo depois do SOI de um JPEG. */
function jpeg_com_exif(string $jpeg, int $orientacao): string
{
    return substr($jpeg, 0, 2) . exif_app1($orientacao) . substr($jpeg, 2);
}

/** Foto de teste: barra azul no topo e quadrado vermelho embaixo à esquerda (para saber o lado de cima). */
function imagem_marcada(int $l, int $a)
{
    $im = imagecreatetruecolor($l, $a);
    imagefill($im, 0, 0, imagecolorallocate($im, 235, 235, 235));
    imagefilledrectangle($im, 0, 0, $l - 1, (int) ($a * 0.2), imagecolorallocate($im, 20, 60, 220));
    imagefilledrectangle($im, 0, (int) ($a * 0.7), (int) ($l * 0.3), $a - 1, imagecolorallocate($im, 220, 30, 30));
    return $im;
}

echo "Fotos\n";

// Guardada deitada (400x300) com orientação 6: na tela fica em pé (300x400). Tem GPS, Make e Model.
$im = imagem_marcada(400, 300);
ob_start();
imagejpeg($im, null, 85);
$jpg = (string) ob_get_clean();
imagedestroy($im);
gravar('jpeg-orientacao6-gps.jpg', jpeg_com_exif($jpg, 6));

// Mesma foto sem orientação (já em pé), para provar que não gira duas vezes
$im = imagem_marcada(300, 400);
ob_start();
imagejpeg($im, null, 85);
$jpg2 = (string) ob_get_clean();
imagedestroy($im);
gravar('jpeg-em-pe-sem-exif.jpg', $jpg2);

$im = imagem_marcada(200, 150);
ob_start();
imagepng($im);
gravar('foto.png', (string) ob_get_clean());
imagedestroy($im);

gravar('php-disfarcado.jpg', "<?php echo 'oi'; ?>\n" . str_repeat('x', 400));

// HEIC cru: só a caixa ftyp, o bastante para o finfo reconhecer
gravar('foto-iphone.heic', pack('N', 24) . 'ftypheic' . pack('N', 0) . 'mif1heic' . str_repeat("\0", 200));

echo "Vídeos (ffmpeg)\n";

function ffmpeg(string $args, string $saida): void
{
    global $pasta;
    $cmd = 'ffmpeg -hide_banner -loglevel error -y ' . $args . ' ' . escapeshellarg("$pasta/$saida");
    exec($cmd . ' 2>&1', $linhas, $codigo);
    if ($codigo !== 0) {
        fwrite(STDERR, "ffmpeg falhou em $saida: " . implode("\n", $linhas) . "\n");
        return;
    }
    printf("  %-34s %7d bytes\n", $saida, filesize("$pasta/$saida"));
}

// Fonte com o primeiro quadro bem diferente dos seguintes (para o teste da capa no quadro 0)
$fonte = static fn(string $tam, string $dur = '1') => '-f lavfi -i ' . escapeshellarg("testsrc2=size=$tam:rate=15:duration=$dur");
$h264 = '-c:v libx264 -preset veryfast -crf 30 -pix_fmt yuv420p -g 15';
$local = '-metadata ' . escapeshellarg('location=+15.9365-049.2536/') . ' -metadata ' . escapeshellarg('location-eng=+15.9365-049.2536/')
    . ' -metadata ' . escapeshellarg('com.apple.quicktime.location.ISO6709=+15.9365-049.2536+000.000/')
    . ' -metadata ' . escapeshellarg('com.apple.quicktime.make=Apple') . ' -movflags use_metadata_tags+faststart';

ffmpeg($fonte('320x240') . " $h264 $local", 'video-h264-com-local.mp4');
ffmpeg($fonte('320x240') . ' -c:v libx265 -preset veryfast -crf 32 -pix_fmt yuv420p -tag:v hvc1', 'video-hevc.mp4');
ffmpeg($fonte('3840x2160', '0.4') . ' -c:v libx264 -preset ultrafast -crf 45 -pix_fmt yuv420p', 'video-4k.mp4');
// Gravado deitado (1920x1080) com giro de 90 graus: na tela fica em pé, 1080x1920, e deve passar
ffmpeg('-noautorotate -display_rotation 90 ' . $fonte('1920x1080', '0.4') . ' -c:v libx264 -preset ultrafast -crf 45 -pix_fmt yuv420p', 'video-1080-girado.mp4');
// Em pé de verdade (1080x1920) sem giro
ffmpeg($fonte('1080x1920', '0.4') . ' -c:v libx264 -preset ultrafast -crf 45 -pix_fmt yuv420p', 'video-1080-em-pe.mp4');
// Só áudio num MP4: não é vídeo
ffmpeg('-f lavfi -i sine=frequency=440:duration=1 -c:a aac', 'audio-sem-video.mp4');

echo "Pronto.\n";
