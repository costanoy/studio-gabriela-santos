<?php
defined('RAIZ') || exit;

/**
 * Mídia enviada pelo painel (Lote 2B): fotos, vídeos em partes e capas.
 *
 * Códigos de erro devolvidos em ['erro' => codigo] (as frases ficam em painel/midia.js):
 *   tipo            o arquivo não é foto nem vídeo aceito
 *   heic            foto HEIC/HEIF crua (o GD não lê)
 *   foto_ilegivel   a foto está corrompida ou não abre
 *   foto_pesada     a foto tem mais de 30 MB
 *   foto_enorme     a foto passa de 40 megapixels (ou não cabe na memória)
 *   gravacao        não foi possível gravar no disco
 * Outros códigos (vídeo) ficam mais abaixo.
 *
 * Nada aqui confia na extensão nem no nome enviado: o tipo vem do conteúdo (finfo).
 */

const MIDIA_FOTO_MP_MAX = 40000000;          // 40 megapixels, lidos do cabeçalho antes de abrir no GD
const MIDIA_FOTO_BYTES_MAX = 30 * 1024 * 1024;
const MIDIA_FOTO_ALVO_BYTES = 250 * 1024;

function midia_erro(string $codigo, array $extra = []): array
{
    return ['erro' => $codigo] + $extra;
}

/** Limite de memória do PHP em bytes (PHP_INT_MAX se for ilimitado). */
function midia_memoria_limite(): int
{
    $v = trim((string) ini_get('memory_limit'));
    if ($v === '' || $v === '-1') {
        return PHP_INT_MAX;
    }
    return midia_bytes_ini($v);
}

/** "256M" -> bytes. */
function midia_bytes_ini(string $v): int
{
    $v = trim($v);
    $n = (float) $v;
    switch (strtoupper(substr($v, -1))) {
        case 'G':
            $n *= 1024;
            // no break
        case 'M':
            $n *= 1024;
            // no break
        case 'K':
            $n *= 1024;
    }
    return (int) $n;
}

/** Pasta do ano dentro da mídia: [caminho no disco, caminho público relativo]. */
function midia_pasta_ano(): array
{
    $cfg = config();
    $ano = date('Y');
    return [$cfg['midia_dir'] . '/' . $ano, $cfg['midia_url'] . '/' . $ano];
}

/** Grava o conteúdo num arquivo novo de conteudo/midia/AAAA/. Devolve o caminho público ou null. */
function midia_gravar(string $bytes, string $extensao, string $sufixo = '', ?string $base = null): ?string
{
    [$dir, $url] = midia_pasta_ano();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return null;
    }
    $base ??= bin2hex(random_bytes(8));
    $nome = $base . $sufixo . '.' . $extensao;
    if (@file_put_contents($dir . '/' . $nome, $bytes, LOCK_EX) === false) {
        @unlink($dir . '/' . $nome);
        return null;
    }
    @chmod($dir . '/' . $nome, 0644);
    return $url . '/' . $nome;
}

/** Tipo real pelo conteúdo. */
function midia_tipo_real(string $arquivo): string
{
    $f = finfo_open(FILEINFO_MIME_TYPE);
    $t = $f ? (string) finfo_file($f, $arquivo) : '';
    if ($f) {
        finfo_close($f);
    }
    return strtolower($t);
}

/** Orientação EXIF (1 a 8) lida direto dos bytes do JPEG, sem depender da extensão exif. */
function midia_jpeg_orientacao(string $arquivo): int
{
    $h = @fopen($arquivo, 'rb');
    if (!$h) {
        return 1;
    }
    $cabeca = (string) fread($h, 131072);
    fclose($h);
    if (strlen($cabeca) < 12 || substr($cabeca, 0, 2) !== "\xFF\xD8") {
        return 1;
    }
    $i = 2;
    $n = strlen($cabeca);
    while ($i + 4 <= $n) {
        if ($cabeca[$i] !== "\xFF") {
            break;
        }
        $marca = ord($cabeca[$i + 1]);
        if ($marca === 0xD8 || ($marca >= 0xD0 && $marca <= 0xD7) || $marca === 0x01) {
            $i += 2;
            continue;
        }
        if ($marca === 0xDA || $marca === 0xD9) {
            break;
        }
        $tam = (ord($cabeca[$i + 2]) << 8) | ord($cabeca[$i + 3]);
        if ($tam < 2) {
            break;
        }
        if ($marca === 0xE1 && substr($cabeca, $i + 4, 6) === "Exif\0\0") {
            $t = substr($cabeca, $i + 10, $tam - 8);
            $o = midia_tiff_orientacao($t);
            if ($o > 0) {
                return $o;
            }
        }
        $i += 2 + $tam;
    }
    return 1;
}

function midia_tiff_orientacao(string $t): int
{
    if (strlen($t) < 14) {
        return 0;
    }
    $le = substr($t, 0, 2) === 'II';
    if (!$le && substr($t, 0, 2) !== 'MM') {
        return 0;
    }
    $u16 = static fn(int $p): int => $p + 2 > strlen($t) ? 0 : unpack($le ? 'v' : 'n', substr($t, $p, 2))[1];
    $u32 = static fn(int $p): int => $p + 4 > strlen($t) ? 0 : unpack($le ? 'V' : 'N', substr($t, $p, 4))[1];
    $ifd = $u32(4);
    if ($ifd < 8 || $ifd + 2 > strlen($t)) {
        return 0;
    }
    $qtd = min($u16($ifd), 64);
    for ($k = 0; $k < $qtd; $k++) {
        $p = $ifd + 2 + $k * 12;
        if ($u16($p) === 0x0112) {
            $v = $u16($p + 8);
            return $v >= 1 && $v <= 8 ? $v : 0;
        }
    }
    return 0;
}

/** Aplica a orientação EXIF numa imagem já reduzida. Devolve a imagem nova (a antiga é liberada). */
function midia_aplicar_orientacao(\GdImage $img, int $ori): \GdImage
{
    if ($ori === 2 || $ori === 4 || $ori === 5 || $ori === 7) {
        imageflip($img, $ori === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
    }
    $graus = match ($ori) {
        3 => 180,
        5, 8 => 90,    // imagerotate gira no sentido anti-horário
        6, 7 => 270,
        default => 0,
    };
    if ($graus === 0) {
        return $img;
    }
    $fundo = imagecolorallocatealpha($img, 0, 0, 0, 127);
    $novo = imagerotate($img, $graus, $fundo);
    if ($novo === false) {
        return $img;
    }
    imagealphablending($novo, false);
    imagesavealpha($novo, true);
    imagedestroy($img);
    return $novo;
}

/** Codifica em WebP (ou JPEG se o GD não tiver WebP). Devolve [bytes, extensão]. */
function midia_codificar(\GdImage $img, int $qualidade): array
{
    ob_start();
    if (function_exists('imagewebp')) {
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagewebp($img, null, $qualidade);
        return [(string) ob_get_clean(), 'webp'];
    }
    // JPEG não tem transparência: achata sobre branco
    $l = imagesx($img);
    $a = imagesy($img);
    $chapa = imagecreatetruecolor($l, $a);
    imagefill($chapa, 0, 0, imagecolorallocate($chapa, 255, 255, 255));
    imagealphablending($chapa, true);
    imagecopy($chapa, $img, 0, 0, 0, 0, $l, $a);
    imagejpeg($chapa, null, $qualidade);
    imagedestroy($chapa);
    return [(string) ob_get_clean(), 'jpg'];
}

/**
 * Processa uma foto enviada e grava em conteudo/midia/AAAA/.
 * Tudo é refeito pelo GD: a saída não carrega EXIF, GPS nem qualquer metadado.
 *
 * @param int $maximo maior lado em pixels (1600 para exibição, 800 para comparador)
 * @return array{arquivo:string,largura:int,altura:int}|array{erro:string}
 */
function midia_foto_salvar(string $tmp, string $nome_original, int $maximo = 1600): array
{
    $maximo = max(200, min(1600, $maximo));
    if (!is_file($tmp) || filesize($tmp) === 0) {
        return midia_erro('foto_ilegivel');
    }
    if (filesize($tmp) > MIDIA_FOTO_BYTES_MAX) {
        return midia_erro('foto_pesada');
    }

    $mime = midia_tipo_real($tmp);
    if ($mime === 'image/heic' || $mime === 'image/heif' || $mime === 'image/heic-sequence' || $mime === 'image/heif-sequence') {
        return midia_erro('heic');
    }
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return midia_erro('tipo');
    }

    // Só o cabeçalho: nada de GD antes de saber o tamanho
    $info = @getimagesize($tmp);
    if ($info === false || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
        return midia_erro('foto_ilegivel');
    }
    [$w, $h] = $info;
    if ($w * $h > MIDIA_FOTO_MP_MAX) {
        return midia_erro('foto_enorme');
    }
    // O GD guarda 4 bytes por pixel. Se não cabe na memória, recusa em vez de dar erro 500.
    $precisa = (int) ($w * $h * 4 * 1.15) + 24 * 1024 * 1024;
    if ($precisa + memory_get_usage() > midia_memoria_limite()) {
        return midia_erro('foto_enorme');
    }

    $ori = $mime === 'image/jpeg' ? midia_jpeg_orientacao($tmp) : 1;

    $origem = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/png' => @imagecreatefrompng($tmp),
        default => @imagecreatefromwebp($tmp),
    };
    if (!$origem) {
        return midia_erro('foto_ilegivel');
    }
    if (!imageistruecolor($origem)) {
        imagepalettetotruecolor($origem);
    }

    // O tamanho é medido já com a rotação (foto de pé tem o lado maior na altura)
    $troca = $ori >= 5;
    $exib_l = $troca ? $h : $w;
    $exib_a = $troca ? $w : $h;
    $escala = min(1.0, $maximo / max($exib_l, $exib_a));
    $tl = max(1, (int) round($w * $escala));
    $ta = max(1, (int) round($h * $escala));

    $img = imagecreatetruecolor($tl, $ta);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 255, 255, 255, 127));
    imagecopyresampled($img, $origem, 0, 0, 0, 0, $tl, $ta, $w, $h);
    imagedestroy($origem);

    if ($ori > 1) {
        $img = midia_aplicar_orientacao($img, $ori);
    }

    $bytes = '';
    $ext = 'webp';
    foreach ([80, 72, 64, 56] as $q) {
        [$bytes, $ext] = midia_codificar($img, $q);
        if (strlen($bytes) <= MIDIA_FOTO_ALVO_BYTES) {
            break;
        }
    }
    // Ainda grande: encolhe aos poucos (até 600 px no lado maior)
    while (strlen($bytes) > MIDIA_FOTO_ALVO_BYTES && max(imagesx($img), imagesy($img)) > 600) {
        $nl = (int) round(imagesx($img) * 0.85);
        $na = (int) round(imagesy($img) * 0.85);
        $menor = imagescale($img, $nl, $na, IMG_BICUBIC);
        if ($menor === false) {
            break;
        }
        imagedestroy($img);
        $img = $menor;
        [$bytes, $ext] = midia_codificar($img, 64);
    }
    $largura = imagesx($img);
    $altura = imagesy($img);
    imagedestroy($img);

    $arquivo = midia_gravar($bytes, $ext);
    if ($arquivo === null) {
        return midia_erro('gravacao');
    }
    return ['arquivo' => $arquivo, 'largura' => $largura, 'altura' => $altura];
}
