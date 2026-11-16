<?php
// Otimiza fotos, vídeos, logo e hero do site. Só roda em desenvolvimento (linha de comando).
// Precisa de ffmpeg e ffprobe no PATH. Não usa nenhuma biblioteca externa.
//
// Uso:
//   php ferramentas/otimizar-assets.php --etapa=logo-hero     (T03)
//   php ferramentas/otimizar-assets.php --etapa=fotos         (T01)
//   php ferramentas/otimizar-assets.php --etapa=videos        (L01)
//   php ferramentas/otimizar-assets.php --etapa=relatorio     (grava .auditoria/assets-depois.md)
//   php ferramentas/otimizar-assets.php                        (todas, na ordem)
//   php ferramentas/otimizar-assets.php --verificar            (confere o resultado, não altera nada)
//
// Os originais ficam em acervo/originais/ (movidos com git mv, nunca apagados).
// A ferramenta pode rodar de novo: sempre parte do original guardado no acervo.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const LIMITE_FOTO = 250000;      // bytes
const LIMITE_LOGO_HERO = 40000;  // bytes
const LIMITE_APRESENTACAO = 8000000;

$raiz = dirname(__DIR__);
chdir($raiz);

$opcoes = getopt('', ['etapa::', 'verificar']);
$etapa = $opcoes['etapa'] ?? 'tudo';

function executar(string $cmd, ?int &$codigo = null): string
{
    $saida = [];
    exec($cmd . ' 2>&1', $saida, $codigo);
    return implode("\n", $saida);
}

function ffmpeg(array $args): void
{
    $cmd = 'ffmpeg -y -v error -nostdin';
    foreach ($args as $a) {
        $cmd .= ' ' . escapeshellarg((string) $a);
    }
    $saida = executar($cmd, $codigo);
    if ($codigo !== 0) {
        fwrite(STDERR, "ffmpeg falhou: $cmd\n$saida\n");
        exit(1);
    }
}

function sonda(string $arquivo): array
{
    $cmd = 'ffprobe -v error -select_streams v:0 -show_entries stream=codec_name,width,height,bit_rate:stream_side_data=rotation:format=duration,size,bit_rate -of json '
        . escapeshellarg($arquivo);
    $j = json_decode(executar($cmd), true) ?: [];
    $s = $j['streams'][0] ?? [];
    $rot = 0;
    foreach ($s['side_data_list'] ?? [] as $sd) {
        if (isset($sd['rotation'])) {
            $rot = (int) $sd['rotation'];
        }
    }
    $w = (int) ($s['width'] ?? 0);
    $h = (int) ($s['height'] ?? 0);
    if (abs($rot) === 90 || abs($rot) === 270) {
        [$w, $h] = [$h, $w];
    }
    return [
        'codec' => $s['codec_name'] ?? '',
        'largura' => $w,
        'altura' => $h,
        'rotacao' => $rot,
        'bitrate' => (int) ($s['bit_rate'] ?? ($j['format']['bit_rate'] ?? 0)),
        'bytes' => (int) ($j['format']['size'] ?? @filesize($arquivo)),
        'duracao' => (float) ($j['format']['duration'] ?? 0),
    ];
}

function dimensoes(string $arquivo): array
{
    $s = sonda($arquivo);
    return [$s['largura'], $s['altura']];
}

// Guarda o original em acervo/originais/ (git mv) e devolve o caminho dele.
// Se já foi guardado antes, só devolve o caminho.
function guardar_original(string $rel): string
{
    $destino = 'acervo/originais/' . preg_replace('#^assets/#', '', $rel);
    if (is_file($rel) && !is_file($destino)) {
        if (!is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0775, true);
        }
        $saida = executar('git mv ' . escapeshellarg($rel) . ' ' . escapeshellarg($destino), $codigo);
        if ($codigo !== 0) {
            rename($rel, $destino);
        }
    }
    if (!is_file($destino)) {
        fwrite(STDERR, "Não achei o original de $rel\n");
        exit(1);
    }
    return $destino;
}

// Troca "arquivo" (e poster, largura, altura do mesmo bloco) no JSON por texto,
// para não mexer na formatação do resto do arquivo.
function json_trocar(string $antigo, string $novo, ?int $largura, ?int $altura, ?string $poster = null): void
{
    $caminho = 'dados/site.padrao.json';
    $t = file_get_contents($caminho);
    $marca = '"arquivo": "' . $antigo . '"';
    $pos = strpos($t, $marca);
    if ($pos === false) {
        $marca = '"arquivo": "' . $novo . '"';
        $pos = strpos($t, $marca);
        if ($pos === false) {
            fwrite(STDERR, "Caminho $antigo não está no JSON\n");
            exit(1);
        }
    }
    $fim = strpos($t, '}', $pos);
    $bloco = substr($t, $pos, $fim - $pos);
    $bloco = str_replace('"arquivo": "' . $antigo . '"', '"arquivo": "' . $novo . '"', $bloco);
    if ($poster !== null) {
        $bloco = preg_replace('#"poster": "[^"]*"#', '"poster": "' . $poster . '"', $bloco, 1);
    }
    if ($largura !== null) {
        $bloco = preg_replace('#"largura": \d+#', '"largura": ' . $largura, $bloco, 1);
        $bloco = preg_replace('#"altura": \d+#', '"altura": ' . $altura, $bloco, 1);
    }
    file_put_contents($caminho, substr($t, 0, $pos) . $bloco . substr($t, $fim));
}

function webp(string $entrada, string $saida, string $filtro, int $qualidade, int $limite = 0): void
{
    for ($q = $qualidade; $q >= 40; $q -= 5) {
        ffmpeg(['-i', $entrada, '-vf', $filtro, '-frames:v', 1, '-c:v', 'libwebp', '-quality', $q, '-map_metadata', '-1', $saida]);
        if ($limite === 0 || filesize($saida) <= $limite) {
            return;
        }
    }
}

function registrar(string $nome, string $antes, string $depois): void
{
    global $relatorio;
    $relatorio[$nome] = [$antes, $depois];
}

function estado(string $arquivo): array
{
    $bytes = is_file($arquivo) ? filesize($arquivo) : 0;
    [$w, $h] = is_file($arquivo) ? dimensoes($arquivo) : [0, 0];
    return ['bytes' => $bytes, 'largura' => $w, 'altura' => $h];
}

// ---------------------------------------------------------------- etapas

function etapa_logo_hero(): void
{
    // Logo: mesma proporção 16:9 (o CSS usa height + width:auto), 384 px de largura = 2x os 192 exibidos.
    $orig = guardar_original('assets/logo-gs.png');
    webp($orig, 'assets/logo-gs.webp', 'scale=384:-2', 85, LIMITE_LOGO_HERO);
    [$w, $h] = dimensoes('assets/logo-gs.webp');
    json_trocar('assets/logo-gs.png', 'assets/logo-gs.webp', $w, $h);

    // Hero: 1600 px; se passar de 40 KB, 1280 px.
    $orig = guardar_original('assets/hero-bg.png');
    foreach ([1600, 1280] as $largura) {
        webp($orig, 'assets/hero-bg.webp', "scale=$largura:-2", 80, LIMITE_LOGO_HERO);
        if (filesize('assets/hero-bg.webp') <= LIMITE_LOGO_HERO) {
            break;
        }
    }
    [$w, $h] = dimensoes('assets/hero-bg.webp');
    json_trocar('assets/hero-bg.png', 'assets/hero-bg.webp', $w, $h);
}

function etapa_fotos(): void
{
    $fotos = [
        'drenagem-antes1', 'drenagem-antes2', 'drenagem-depois1', 'drenagem-depois2',
        'limpeza-antes1', 'limpeza-antes2', 'limpeza-depois1', 'limpeza-depois2',
        'liposhape-antes1', 'liposhape-depois1',
    ];
    foreach ($fotos as $nome) {
        $orig = guardar_original("assets/results/$nome.jpeg");
        webp($orig, "assets/results/$nome.webp", 'scale=800:-2', 75, LIMITE_FOTO);
        [$w, $h] = dimensoes("assets/results/$nome.webp");
        json_trocar("assets/results/$nome.jpeg", "assets/results/$nome.webp", $w, $h);
    }
    // Foto única do ultrassom: no máximo 1600 px e 250 KB (a de hoje já tem 1284 px).
    $orig = guardar_original('assets/results/ultrassom-antes1.jpeg');
    webp($orig, 'assets/results/ultrassom-antes1.webp', "scale='min(1600,iw)':-2", 80, LIMITE_FOTO);
    [$w, $h] = dimensoes('assets/results/ultrassom-antes1.webp');
    json_trocar('assets/results/ultrassom-antes1.jpeg', 'assets/results/ultrassom-antes1.webp', $w, $h);
}

function recodificar(string $entrada, string $saida, int $video_kbps, int $maxrate_kbps, int $audio_kbps): void
{
    ffmpeg([
        '-i', $entrada,
        '-vf', "scale='if(gt(iw,ih),min(1080,iw),-2)':'if(gt(iw,ih),-2,min(1080,ih))'",
        '-c:v', 'libx264', '-profile:v', 'high', '-pix_fmt', 'yuv420p',
        '-b:v', $video_kbps . 'k', '-maxrate', $maxrate_kbps . 'k', '-bufsize', ($maxrate_kbps * 2) . 'k',
        '-preset', 'slow',
        '-c:a', 'aac', '-b:a', $audio_kbps . 'k',
        '-movflags', '+faststart', '-map_metadata', '-1', '-map_chapters', '-1',
        $saida,
    ]);
}

function etapa_videos(): void
{
    if (!is_dir('assets/posteres')) {
        mkdir('assets/posteres', 0775, true);
    }

    // Apresentação: alvo abaixo de 8 MB, 576x1024. Começa nos parâmetros do plano e baixa se precisar.
    $orig = guardar_original('assets/apresentacao.mp4');
    foreach ([[800, 1000, 96], [680, 850, 80], [600, 750, 64]] as [$v, $m, $a]) {
        recodificar($orig, 'assets/apresentacao.mp4', $v, $m, $a);
        if (filesize('assets/apresentacao.mp4') < LIMITE_APRESENTACAO) {
            break;
        }
    }

    // Vídeos de resultados: só recodifica HEVC, acima de 1080 px, bitrate acima de 2,5 Mb/s ou com rotação.
    $videos = ['corrente-1', 'corrente-2', 'liposhape-depois3', 'liposhape-pos', 'ultrassom-depois1'];
    foreach ($videos as $nome) {
        $rel = "assets/results/$nome.mp4";
        $s = sonda(is_file("acervo/originais/results/$nome.mp4") ? "acervo/originais/results/$nome.mp4" : $rel);
        $precisa = $s['codec'] !== 'h264' || max($s['largura'], $s['altura']) > 1080 || $s['bitrate'] > 2500000 || $s['rotacao'] !== 0;
        if ($precisa) {
            $orig = guardar_original($rel);
            recodificar($orig, $rel, 1000, 1400, 96);
        }
        [$w, $h] = dimensoes($rel);
        ffmpeg(['-i', $rel, '-frames:v', 1, '-vf', "scale='min(720,iw)':-2", '-c:v', 'libwebp', '-quality', 78, '-map_metadata', '-1', "assets/posteres/$nome.webp"]);
        json_trocar($rel, $rel, $w, $h, "assets/posteres/$nome.webp");
    }

    ffmpeg(['-i', 'assets/apresentacao.mp4', '-frames:v', 1, '-vf', "scale='min(720,iw)':-2", '-c:v', 'libwebp', '-quality', 78, '-map_metadata', '-1', 'assets/posteres/apresentacao.webp']);
    [$w, $h] = dimensoes('assets/apresentacao.mp4');
    json_trocar('assets/apresentacao.mp4', 'assets/apresentacao.mp4', $w, $h, 'assets/posteres/apresentacao.webp');

    // Pôsteres antigos (aleatórios) vão para o acervo.
    foreach (glob('assets/img/*.jpg') ?: [] as $velho) {
        guardar_original($velho);
    }
}

function relatorio(): void
{
    $linhas = [];
    $pares = [];
    foreach (glob('acervo/originais/{,results/,img/}*.*', GLOB_BRACE) ?: [] as $orig) {
        $rel = 'assets/' . substr($orig, strlen('acervo/originais/'));
        $pares[] = [$orig, $rel];
    }
    $total_antes = 0;
    $total_depois = 0;
    $linhas[] = '# Assets: antes e depois (T01, T03, T04, L01)';
    $linhas[] = '';
    $linhas[] = 'Gerado por `php ferramentas/otimizar-assets.php --etapa=relatorio`. Originais em `acervo/originais/`.';
    $linhas[] = '';
    $linhas[] = '| Arquivo | Antes (bytes) | Antes (px) | Depois | Depois (bytes) | Depois (px) |';
    $linhas[] = '|---|---:|---|---|---:|---|';
    foreach ($pares as [$orig, $rel]) {
        $base = preg_replace('/\.[^.]+$/', '', $rel);
        $novo = $rel;
        if (!is_file($novo)) {
            foreach (['webp', 'mp4'] as $ext) {
                if (is_file("$base.$ext")) {
                    $novo = "$base.$ext";
                    break;
                }
            }
        }
        $a = estado($orig);
        $d = estado($novo);
        $ehPoster = false;
        $total_antes += $a['bytes'];
        $total_depois += $d['bytes'];
        $linhas[] = sprintf('| %s | %s | %dx%d | %s | %s | %dx%d |', substr($orig, strlen('acervo/originais/')), number_format($a['bytes'], 0, ',', '.'), $a['largura'], $a['altura'], $novo, number_format($d['bytes'], 0, ',', '.'), $d['largura'], $d['altura']);
    }
    $linhas[] = sprintf('| **Total** | **%s** | | | **%s** | |', number_format($total_antes, 0, ',', '.'), number_format($total_depois, 0, ',', '.'));
    $linhas[] = '';
    $linhas[] = '## Pôsteres novos (frame 0, WebP até 720 px)';
    $linhas[] = '';
    foreach (glob('assets/posteres/*.webp') ?: [] as $p) {
        $e = estado($p);
        $linhas[] = sprintf('- `%s`: %s bytes, %dx%d', $p, number_format($e['bytes'], 0, ',', '.'), $e['largura'], $e['altura']);
    }
    $linhas[] = '';
    $linhas[] = '## Vídeos mantidos sem recodificar (já H.264, até 1080 px, abaixo de 2,5 Mb/s, sem rotação)';
    $linhas[] = '';
    foreach (glob('assets/results/*.mp4') ?: [] as $v) {
        $s = sonda($v);
        $orig = 'acervo/originais/results/' . basename($v);
        $linhas[] = sprintf('- `%s`: %s, %dx%d, %d kb/s, %s bytes%s', $v, $s['codec'], $s['largura'], $s['altura'], intdiv($s['bitrate'], 1000), number_format($s['bytes'], 0, ',', '.'), is_file($orig) ? ' (recodificado: fixa a rotação nos pixels)' : '');
    }
    $s = sonda('assets/apresentacao.mp4');
    $linhas[] = sprintf('- `assets/apresentacao.mp4`: %s, %dx%d, %d kb/s, %s bytes (recodificado)', $s['codec'], $s['largura'], $s['altura'], intdiv($s['bitrate'], 1000), number_format($s['bytes'], 0, ',', '.'));
    $linhas[] = '';
    $linhas[] = '## Não usados (já estavam em `acervo/`, nada foi apagado)';
    $linhas[] = '';
    foreach (array_merge(glob('acervo/*.*') ?: [], glob('acervo/results/*') ?: []) as $f) {
        $linhas[] = sprintf('- `%s` (%s bytes)', $f, number_format(filesize($f), 0, ',', '.'));
    }
    $linhas[] = '';
    file_put_contents('.auditoria/assets-depois.md', implode("\n", $linhas));
}

function verificar(): int
{
    $erros = [];
    $j = json_decode(file_get_contents('dados/site.padrao.json'), true);
    $caminhos = [];
    $andar = function ($no) use (&$andar, &$caminhos) {
        if (is_array($no)) {
            foreach ($no as $k => $v) {
                if (($k === 'arquivo' || $k === 'poster') && is_string($v)) {
                    $caminhos[] = $v;
                }
                $andar($v);
            }
        }
    };
    $andar($j);
    foreach ($caminhos as $c) {
        if (!is_file($c)) {
            $erros[] = "JSON aponta para arquivo que não existe: $c";
        }
    }
    foreach (glob('assets/results/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $f) {
        if (filesize($f) > LIMITE_FOTO) {
            $erros[] = "$f passa de 250 KB";
        }
    }
    foreach (['assets/logo-gs.webp', 'assets/hero-bg.webp'] as $f) {
        if (!is_file($f) || filesize($f) > LIMITE_LOGO_HERO) {
            $erros[] = "$f ausente ou acima de 40 KB";
        }
    }
    if (is_file('assets/apresentacao.mp4') && filesize('assets/apresentacao.mp4') >= LIMITE_APRESENTACAO) {
        $erros[] = 'apresentacao.mp4 não ficou abaixo de 8 MB';
    }
    foreach (array_merge(['assets/apresentacao.mp4'], glob('assets/results/*.mp4') ?: []) as $v) {
        $s = sonda($v);
        if ($s['codec'] !== 'h264' || max($s['largura'], $s['altura']) > 1080 || $s['rotacao'] !== 0) {
            $erros[] = "$v: codec/tamanho/rotação fora do combinado";
        }
        $tags = executar('ffprobe -v error -show_entries format_tags:stream_tags -of compact ' . escapeshellarg($v));
        if (preg_match('/location|com\.apple|gps/i', $tags)) {
            $erros[] = "$v tem metadado de localização";
        }
        $inicio = file_get_contents($v, false, null, 0, 65536);
        if (strpos($inicio, 'moov') === false) {
            $erros[] = "$v sem faststart (moov não está no começo)";
        }
    }
    // Todo arquivo de assets/ precisa estar citado no JSON ou nos templates (T04).
    $fontes = ['dados/site.padrao.json', 'index.php', 'resultados.php'];
    foreach (glob('parciais/*.php') as $p) {
        $fontes[] = $p;
    }
    $texto = '';
    foreach ($fontes as $f) {
        $texto .= file_get_contents($f) . "\n";
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('assets', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', $f->getPathname());
        if (strpos($texto, $rel) === false) {
            $erros[] = "$rel não é usado por nenhum template nem pelo JSON";
        }
    }
    if ($erros) {
        echo "PROBLEMAS:\n - " . implode("\n - ", $erros) . "\n";
        return 1;
    }
    echo "OK: assets conferidos (tamanhos, vídeos, caminhos e uso).\n";
    return 0;
}

if (isset($opcoes['verificar'])) {
    exit(verificar());
}

$etapas = $etapa === 'tudo' ? ['logo-hero', 'fotos', 'videos', 'relatorio'] : [$etapa];
foreach ($etapas as $e) {
    switch ($e) {
        case 'logo-hero':
            etapa_logo_hero();
            break;
        case 'fotos':
            etapa_fotos();
            break;
        case 'videos':
            etapa_videos();
            break;
        case 'relatorio':
            relatorio();
            break;
        default:
            fwrite(STDERR, "Etapa desconhecida: $e\n");
            exit(2);
    }
    echo "Etapa $e concluída.\n";
}
