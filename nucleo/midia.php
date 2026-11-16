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

// ===========================================================================
// Vídeo (L10, T13, T15)
//
// Códigos de erro do vídeo:
//   hevc            vídeo H.265 (iPhone "Alta eficiência"), que não toca em todo lugar
//   grande_demais   vídeo maior que Full HD (lado curto > 1080 ou lado longo > 1920, já considerando a rotação)
//   video_pesado    arquivo com mais de 250 MB (vem com tamanho_mb e limite_mb)
//   video_formato   não é MP4/MOV (o conteúdo não tem a caixa "ftyp")
//   video_codec     tem vídeo, mas não é H.264
//   video_ilegivel  estrutura quebrada ou sem trilha de vídeo
//   envio_nao_encontrado / parte_invalida / partes_faltando / sem_espaco: envio em partes
// ===========================================================================

const MIDIA_VIDEO_BYTES_MAX = 250 * 1024 * 1024;
const MIDIA_PARTE_MAX = 5 * 1024 * 1024;
const MIDIA_ENVIO_VALIDADE = 86400;

/** Pasta de trabalho dos envios em partes (fora do public_html). */
function midia_envios_dir(): string
{
    return config()['dados_dir'] . '/envios';
}

function midia_envio_id_valido(string $id): bool
{
    return preg_match('/^[0-9a-f]{24}$/', $id) === 1;
}

/** Apaga envios parados há mais de um dia. */
function midia_envios_limpar(): void
{
    foreach (glob(midia_envios_dir() . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        if (filemtime($d) < time() - MIDIA_ENVIO_VALIDADE) {
            midia_envio_apagar(basename($d));
        }
    }
}

function midia_envio_apagar(string $id): void
{
    if (!midia_envio_id_valido($id)) {
        return;
    }
    $d = midia_envios_dir() . '/' . $id;
    foreach (glob($d . '/*') ?: [] as $f) {
        @unlink($f);
    }
    @rmdir($d);
}

function midia_envio_ler(string $id): ?array
{
    if (!midia_envio_id_valido($id)) {
        return null;
    }
    $j = @file_get_contents(midia_envios_dir() . '/' . $id . '/envio.json');
    $m = $j === false ? null : json_decode($j, true);
    return is_array($m) ? $m : null;
}

/**
 * Começa um envio em partes. A parte tem no máximo 5 MB e nunca passa de 90% do limite do servidor,
 * para caber no upload_max_filesize e no post_max_size.
 */
function midia_video_iniciar(int $tamanho, string $nome_original = ''): array
{
    if ($tamanho < 1024) {
        return midia_erro('video_ilegivel');
    }
    if ($tamanho > MIDIA_VIDEO_BYTES_MAX) {
        return midia_erro('video_pesado', ['tamanho_mb' => (int) round($tamanho / 1048576), 'limite_mb' => 250]);
    }
    $lim = min(midia_bytes_ini((string) ini_get('upload_max_filesize')) ?: PHP_INT_MAX, midia_bytes_ini((string) ini_get('post_max_size')) ?: PHP_INT_MAX);
    $parte = (int) min(MIDIA_PARTE_MAX, $lim * 0.9);
    if ($parte < 256 * 1024) {
        $parte = 256 * 1024;
    }
    midia_envios_limpar();
    $livre = @disk_free_space(config()['midia_dir']);
    if ($livre !== false && $livre < $tamanho * 2.2) {
        return midia_erro('sem_espaco');
    }
    $id = bin2hex(random_bytes(12));
    $dir = midia_envios_dir() . '/' . $id;
    if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return midia_erro('gravacao');
    }
    $total = (int) ceil($tamanho / $parte);
    $meta = ['tamanho' => $tamanho, 'parte' => $parte, 'total' => $total];
    if (@file_put_contents($dir . '/envio.json', json_encode($meta), LOCK_EX) === false) {
        midia_envio_apagar($id);
        return midia_erro('gravacao');
    }
    return ['envio' => $id, 'parte' => $parte, 'total' => $total];
}

/** Quais partes já chegaram (para retomar depois de uma queda). */
function midia_video_estado(string $id): array
{
    $m = midia_envio_ler($id);
    if ($m === null) {
        return midia_erro('envio_nao_encontrado');
    }
    $tem = [];
    for ($i = 0; $i < $m['total']; $i++) {
        $f = midia_envios_dir() . '/' . $id . '/' . sprintf('%05d', $i) . '.parte';
        if (is_file($f) && filesize($f) === midia_parte_tamanho($m, $i)) {
            $tem[] = $i;
        }
    }
    return ['envio' => $id, 'parte' => $m['parte'], 'total' => $m['total'], 'recebidas' => $tem];
}

function midia_parte_tamanho(array $m, int $i): int
{
    return $i < $m['total'] - 1 ? $m['parte'] : $m['tamanho'] - $m['parte'] * ($m['total'] - 1);
}

/** Guarda a parte número $i (começa em 0). Mandar a mesma parte de novo só a substitui. */
function midia_video_parte(string $id, int $i, string $tmp): array
{
    $m = midia_envio_ler($id);
    if ($m === null) {
        return midia_erro('envio_nao_encontrado');
    }
    if ($i < 0 || $i >= $m['total'] || !is_file($tmp) || filesize($tmp) !== midia_parte_tamanho($m, $i)) {
        return midia_erro('parte_invalida');
    }
    $dir = midia_envios_dir() . '/' . $id;
    $alvo = $dir . '/' . sprintf('%05d', $i) . '.parte';
    $ok = is_uploaded_file($tmp) ? @move_uploaded_file($tmp, $alvo) : @rename($tmp, $alvo);
    if (!$ok) {
        return midia_erro('gravacao');
    }
    @touch($dir);
    return ['parte' => $i];
}

function midia_u32(string $s, int $p): int
{
    return $p + 4 > strlen($s) ? 0 : unpack('N', substr($s, $p, 4))[1];
}

/**
 * Lista as caixas MP4 entre $ini e $fim dentro de $s: [tipo, início, tamanho, cabeçalho].
 * Para de olhar se encontrar uma caixa inválida.
 */
function midia_mp4_caixas(string $s, int $ini, int $fim): array
{
    $r = [];
    $p = $ini;
    while ($p + 8 <= $fim) {
        $t = midia_u32($s, $p);
        $tipo = substr($s, $p + 4, 4);
        $h = 8;
        if ($t === 1) {
            $t = midia_u32($s, $p + 12);
            $h = 16;
        } elseif ($t === 0) {
            $t = $fim - $p;
        }
        if ($t < $h || $p + $t > $fim) {
            break;
        }
        $r[] = [$tipo, $p, $t, $h];
        $p += $t;
    }
    return $r;
}

/**
 * Lê o "moov" (trilhas, tamanho, rotação, codec) e, se $limpar, troca por "free" (mesmo tamanho) todas as caixas
 * que podem guardar localização ou dados do aparelho: udta (©xyz), meta (com.apple.quicktime.location) e uuid.
 * Devolve ['moov' => bytes novos, 'info' => ...].
 */
function midia_mp4_moov(string $m, bool $limpar): array
{
    $info = ['codec' => '', 'largura' => 0, 'altura' => 0, 'duracao' => 0.0, 'video' => false];
    $apagar = static function (string &$m, int $p, int $t): void {
        // vira "free" e o conteúdo é zerado (só trocar o nome deixaria as coordenadas no arquivo)
        $m = substr_replace($m, 'free' . str_repeat("\0", $t - 8), $p + 4, $t - 4);
    };
    foreach (midia_mp4_caixas($m, 8, strlen($m)) as [$tipo, $p, $t, $h]) {
        if ($tipo === 'udta' || $tipo === 'meta' || $tipo === 'uuid') {
            if ($limpar) {
                $apagar($m, $p, $t);
            }
        } elseif ($tipo === 'mvhd') {
            $v = ord($m[$p + 8]);
            $escala = midia_u32($m, $p + ($v === 1 ? 28 : 20));
            $dur = $v === 1 ? midia_u32($m, $p + 32) * 4294967296 + midia_u32($m, $p + 36) : midia_u32($m, $p + 24);
            $info['duracao'] = $escala > 0 ? round($dur / $escala, 2) : 0.0;
        } elseif ($tipo === 'trak') {
            $trilha = ['video' => false, 'codec' => '', 'l' => 0, 'a' => 0, 'giro' => false];
            foreach (midia_mp4_caixas($m, $p + $h, $p + $t) as [$tt, $tp, $tz, $th]) {
                if ($tt === 'udta' || $tt === 'meta' || $tt === 'uuid') {
                    if ($limpar) {
                        $apagar($m, $tp, $tz);
                    }
                } elseif ($tt === 'tkhd') {
                    $v = ord($m[$tp + 8]);
                    $mx = $tp + 8 + ($v === 1 ? 4 + 8 + 8 + 4 + 4 + 8 : 4 + 4 + 4 + 4 + 4 + 4) + 8 + 2 + 2 + 2 + 2;
                    $a = midia_u32($m, $mx);
                    $trilha['giro'] = $a === 0 && midia_u32($m, $mx + 4) !== 0;
                    $trilha['l'] = (int) (midia_u32($m, $mx + 36) / 65536);
                    $trilha['a'] = (int) (midia_u32($m, $mx + 40) / 65536);
                } elseif ($tt === 'mdia') {
                    foreach (midia_mp4_caixas($m, $tp + $th, $tp + $tz) as [$mt, $mp, $mz, $mh]) {
                        if ($mt === 'hdlr') {
                            $trilha['video'] = substr($m, $mp + 16, 4) === 'vide';
                        } elseif ($mt === 'minf') {
                            foreach (midia_mp4_caixas($m, $mp + $mh, $mp + $mz) as [$nt, $np, $nz, $nh]) {
                                if ($nt !== 'stbl') {
                                    continue;
                                }
                                foreach (midia_mp4_caixas($m, $np + $nh, $np + $nz) as [$st, $sp]) {
                                    if ($st === 'stsd') {
                                        $trilha['codec'] = substr($m, $sp + 20, 4);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($trilha['video'] && !$info['video']) {
                $info['video'] = true;
                $info['codec'] = $trilha['codec'];
                $info['largura'] = $trilha['giro'] ? $trilha['a'] : $trilha['l'];
                $info['altura'] = $trilha['giro'] ? $trilha['l'] : $trilha['a'];
            }
        }
    }
    return ['moov' => $m, 'info' => $info];
}

/**
 * Confere o MP4 em $arquivo e, se $limpar, grava de volta sem os metadados de localização.
 * @return array{info:array}|array{erro:string}
 */
function midia_mp4_analisar(string $arquivo, bool $limpar): array
{
    $tam = filesize($arquivo);
    $f = @fopen($arquivo, $limpar ? 'r+b' : 'rb');
    if (!$f || $tam < 16) {
        return midia_erro('video_ilegivel');
    }
    $p = 0;
    $ftyp = false;
    $info = null;
    $n = 0;
    while ($p + 8 <= $tam && $n++ < 200) {
        fseek($f, $p);
        $c = (string) fread($f, 16);
        $t = midia_u32($c, 0);
        $tipo = substr($c, 4, 4);
        if ($t === 1) {
            $t = midia_u32($c, 12);   // tamanhos acima de 4 GB não interessam
        } elseif ($t === 0) {
            $t = $tam - $p;
        }
        if ($p === 0) {
            if ($tipo !== 'ftyp') {
                fclose($f);
                return midia_erro('video_formato');
            }
            $ftyp = true;
        }
        if ($t < 8 || $p + $t > $tam) {
            fclose($f);
            return midia_erro('video_ilegivel');
        }
        if ($tipo === 'moov') {
            if ($t > 64 * 1024 * 1024) {
                fclose($f);
                return midia_erro('video_ilegivel');
            }
            fseek($f, $p);
            $m = (string) fread($f, $t);
            $r = midia_mp4_moov($m, $limpar);
            $info = $r['info'];
            if ($limpar && $r['moov'] !== $m) {
                fseek($f, $p);
                fwrite($f, $r['moov']);
            }
        } elseif ($limpar && ($tipo === 'udta' || $tipo === 'meta' || $tipo === 'uuid')) {
            fseek($f, $p + 4);
            fwrite($f, 'free' . str_repeat("\0", min($t - 8, 8 * 1024 * 1024)));
        }
        $p += $t;
    }
    fclose($f);
    if (!$ftyp || $info === null || !$info['video']) {
        return midia_erro($ftyp ? 'video_ilegivel' : 'video_formato');
    }
    return ['info' => $info];
}

/** Regras do site para o vídeo: H.264 e no máximo Full HD (lado curto 1080, lado longo 1920). */
function midia_video_regras(array $i): ?array
{
    if (in_array($i['codec'], ['hvc1', 'hev1', 'dvh1', 'dvhe'], true)) {
        return midia_erro('hevc');
    }
    if ($i['codec'] !== 'avc1' && $i['codec'] !== 'avc3') {
        return midia_erro('video_codec');
    }
    if (min($i['largura'], $i['altura']) > 1088 || max($i['largura'], $i['altura']) > 1920) {
        return midia_erro('grande_demais', ['largura' => $i['largura'], 'altura' => $i['altura']]);
    }
    return null;
}

/** Junta as partes, confere o vídeo, tira a localização e grava em conteudo/midia/AAAA/. */
function midia_video_concluir(string $id): array
{
    $m = midia_envio_ler($id);
    if ($m === null) {
        return midia_erro('envio_nao_encontrado');
    }
    $est = midia_video_estado($id);
    if (count($est['recebidas']) !== $m['total']) {
        return midia_erro('partes_faltando', ['faltam' => array_values(array_diff(range(0, $m['total'] - 1), $est['recebidas']))]);
    }
    $dir = midia_envios_dir() . '/' . $id;
    $junto = $dir . '/video.tmp';
    $out = @fopen($junto, 'wb');
    if (!$out) {
        return midia_erro('gravacao');
    }
    for ($i = 0; $i < $m['total']; $i++) {
        $in = fopen($dir . '/' . sprintf('%05d', $i) . '.parte', 'rb');
        stream_copy_to_stream($in, $out);
        fclose($in);
    }
    fclose($out);

    $a = midia_mp4_analisar($junto, true);
    if (isset($a['erro'])) {
        midia_envio_apagar($id);
        return $a;
    }
    $ruim = midia_video_regras($a['info']);
    if ($ruim !== null) {
        midia_envio_apagar($id);
        return $ruim;
    }
    [$pasta, $url] = midia_pasta_ano();
    if (!is_dir($pasta) && !@mkdir($pasta, 0755, true) && !is_dir($pasta)) {
        return midia_erro('gravacao');
    }
    $nome = bin2hex(random_bytes(8)) . '.mp4';
    if (!@rename($junto, $pasta . '/' . $nome)) {
        if (!@copy($junto, $pasta . '/' . $nome)) {
            return midia_erro('gravacao');
        }
    }
    @chmod($pasta . '/' . $nome, 0644);
    midia_envio_apagar($id);
    $i = $a['info'];
    return ['arquivo' => $url . '/' . $nome, 'largura' => $i['largura'], 'altura' => $i['altura'], 'duracao' => $i['duracao']];
}

/** Capa do vídeo: o primeiro quadro, tirado no navegador. Passa pelo mesmo tratamento das fotos. */
function midia_poster_salvar(string $tmp): array
{
    return midia_foto_salvar($tmp, 'poster.jpg', 1600);
}
