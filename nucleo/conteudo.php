<?php
defined('RAIZ') || exit;

/**
 * Conteúdo do site: padrão (dados/site.padrao.json) + o que a dona editou (dados_dir/site.json).
 * Esquema descrito em MELHORIAS.md, seção 4.
 */

const ESQUEMA_VERSAO = 1;
const HISTORICO_MAX = 20;
const ENQUADRAMENTO_MIN = 100.0;
const ENQUADRAMENTO_MAX = 400.0;

function conteudo_cache_limpar(): void
{
    unset($GLOBALS['__conteudo_cache'], $GLOBALS['__conteudo_padrao']);
}

function conteudo_padrao(): array
{
    if (isset($GLOBALS['__conteudo_padrao'])) {
        return $GLOBALS['__conteudo_padrao'];
    }
    $arquivo = RAIZ . '/dados/site.padrao.json';
    $dados = is_file($arquivo) ? json_decode((string) file_get_contents($arquivo), true) : null;
    if (!is_array($dados)) {
        throw new RuntimeException('dados/site.padrao.json ausente ou inválido');
    }
    return $GLOBALS['__conteudo_padrao'] = $dados;
}

function conteudo_ler(): array
{
    if (isset($GLOBALS['__conteudo_cache'])) {
        return $GLOBALS['__conteudo_cache'];
    }
    $padrao = conteudo_padrao();
    $editado = conteudo_ler_editado();
    if ($editado === null) {
        return $GLOBALS['__conteudo_cache'] = conteudo_migrar($padrao);
    }
    if ((int) ($editado['versao_esquema'] ?? 1) > ESQUEMA_VERSAO) {
        error_log('site.json tem versao_esquema maior que a do código; usando só o padrão.');
        return $GLOBALS['__conteudo_cache'] = conteudo_migrar($padrao);
    }
    $editado = conteudo_migrar($editado);
    return $GLOBALS['__conteudo_cache'] = conteudo_migrar(conteudo_mesclar($padrao, $editado));
}

/** O site.json cru (sem mesclar), ou null se não existe ou está ilegível. */
function conteudo_ler_editado(): ?array
{
    $cfg = config();
    if (empty($cfg['configurado'])) {
        return null;
    }
    $arquivo = $cfg['dados_dir'] . '/site.json';
    if (!is_file($arquivo)) {
        return null;
    }
    $dados = json_decode((string) file_get_contents($arquivo), true);
    if (!is_array($dados)) {
        error_log('site.json ilegível; usando o padrão.');
        return null;
    }
    return $dados;
}

function conteudo_eh_lista(array $a): bool
{
    return $a === [] || array_is_list($a);
}

/** Objetos se mesclam por chave; listas são substituídas inteiras; o padrão só entra se a chave não existe. */
function conteudo_mesclar(array $padrao, array $editado): array
{
    $r = $padrao;
    foreach ($editado as $chave => $valor) {
        if (is_array($valor) && isset($padrao[$chave]) && is_array($padrao[$chave])
            && !conteudo_eh_lista($valor) && !conteudo_eh_lista($padrao[$chave])) {
            $r[$chave] = conteudo_mesclar($padrao[$chave], $valor);
        } else {
            $r[$chave] = $valor;
        }
    }
    return $r;
}

/** Sobe versões antigas do esquema até a atual. Hoje só existe a versão 1. */
function conteudo_migrar(array $c): array
{
    $v = (int) ($c['versao_esquema'] ?? 1);
    if ($v < 1) {
        $v = 1;
    }
    while ($v < ESQUEMA_VERSAO) {
        // Aqui entram os passos "da versão N para N+1" quando o esquema mudar.
        $v++;
    }
    $c['versao_esquema'] = $v;
    $c['revisao'] = (int) ($c['revisao'] ?? 0);
    return $c;
}

// ---------------------------------------------------------------------------
// Enquadramento
// ---------------------------------------------------------------------------

function enquadramento_arredondar(float $v): float
{
    return round($v, 3);
}

/** Enquadramento automático: foto inteira cobrindo o quadro 3:4. */
function enquadramento_auto(int $largura, int $altura): array
{
    if ($largura <= 0 || $altura <= 0) {
        return ['width' => 100, 'height' => 100, 'left' => 50, 'top' => 50, 'automatico' => true];
    }
    $r = $largura / $altura;
    if ($r > 0.75) {
        $h = 100.0;
        $w = 100.0 * $r / 0.75;
    } else {
        $w = 100.0;
        $h = 75.0 / $r;
    }
    return [
        'width' => enquadramento_arredondar($w),
        'height' => enquadramento_arredondar($h),
        'left' => 50,
        'top' => 50,
        'automatico' => true,
    ];
}

/** Segura o enquadramento nos limites: 100 a 400 e a foto sempre cobrindo o quadro. */
function enquadramento_limitar(array $e): array
{
    $w = min(ENQUADRAMENTO_MAX, max(ENQUADRAMENTO_MIN, (float) ($e['width'] ?? 100)));
    $h = min(ENQUADRAMENTO_MAX, max(ENQUADRAMENTO_MIN, (float) ($e['height'] ?? 100)));
    $folga_x = ($w - 100) / 2;
    $folga_y = ($h - 100) / 2;
    $left = min(50 + $folga_x, max(50 - $folga_x, (float) ($e['left'] ?? 50)));
    $top = min(50 + $folga_y, max(50 - $folga_y, (float) ($e['top'] ?? 50)));
    return [
        'width' => enquadramento_arredondar($w),
        'height' => enquadramento_arredondar($h),
        'left' => enquadramento_arredondar($left),
        'top' => enquadramento_arredondar($top),
        'automatico' => !empty($e['automatico']),
    ];
}

// ---------------------------------------------------------------------------
// Mídias usadas
// ---------------------------------------------------------------------------

/** Todos os arquivos de mídia que o conteúdo usa (fotos, vídeos, pôsteres, imagens da marca). */
function conteudo_midias_usadas(array $c): array
{
    $achados = [];
    $andar = static function ($no, string $chave = '') use (&$andar, &$achados): void {
        if (is_array($no)) {
            foreach ($no as $k => $v) {
                $andar($v, (string) $k);
            }
            return;
        }
        if (is_string($no) && $no !== '' && in_array($chave, ['arquivo', 'poster', 'og_imagem'], true)) {
            $achados[$no] = true;
        }
    };
    $andar($c);
    return array_keys($achados);
}

// ---------------------------------------------------------------------------
// Validação
// ---------------------------------------------------------------------------

const LIMITE_TITULO = 80;
const LIMITE_ROTULO = 40;
const LIMITE_PARAGRAFO = 600;
const LIMITE_DEPOIMENTO = 400;

function conteudo_validar(array $c): array
{
    $erros = [];
    $erro = static function (string $campo, string $mensagem) use (&$erros): void {
        $erros[] = ['campo' => $campo, 'mensagem' => $mensagem];
    };
    $texto = static function ($v, string $campo, int $max, string $nome, bool $obrigatorio = true) use ($erro): void {
        if (!is_string($v)) {
            $erro($campo, "$nome precisa ser um texto.");
            return;
        }
        if ($obrigatorio && trim($v) === '') {
            $erro($campo, "$nome não pode ficar vazio.");
        }
        if (mb_strlen($v) > $max) {
            $erro($campo, "$nome passou do limite de $max letras (tem " . mb_strlen($v) . ').');
        }
        if ($v !== strip_tags($v)) {
            $erro($campo, "$nome não pode ter código HTML.");
        }
    };
    $midia = static function ($v, string $campo, string $nome) use ($erro): void {
        if (!is_string($v) || !preg_match('#^(assets|conteudo/midia)/[A-Za-z0-9_./-]+$#', $v) || str_contains($v, '..') || str_contains($v, '//')) {
            $erro($campo, "O arquivo de $nome não é válido.");
        }
    };
    $ids = static function (array $lista, string $campo, string $nome) use ($erro): void {
        $vistos = [];
        foreach ($lista as $n => $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : null;
            if (!is_string($id) || !preg_match('/^[a-z0-9-]{3,40}$/', $id)) {
                $erro("$campo.$n.id", "O código interno de $nome é inválido.");
            } elseif (isset($vistos[$id])) {
                $erro("$campo.$n.id", "O código interno de $nome está repetido ($id).");
            }
            $vistos[$id] = true;
        }
    };
    $limite_lista = static function ($lista, string $campo, int $max, string $nome) use ($erro): bool {
        if (!is_array($lista) || !conteudo_eh_lista($lista)) {
            $erro($campo, "A lista de $nome está no formato errado.");
            return false;
        }
        if (count($lista) > $max) {
            $erro($campo, "Só cabem $max $nome (tem " . count($lista) . ').');
        }
        return true;
    };

    if (($c['versao_esquema'] ?? null) !== ESQUEMA_VERSAO) {
        $erro('versao_esquema', 'Versão do conteúdo não reconhecida.');
    }
    if (!is_int($c['revisao'] ?? null) || $c['revisao'] < 0) {
        $erro('revisao', 'Número de revisão inválido.');
    }

    // Contato
    $ct = $c['contato'] ?? [];
    if (!is_string($ct['whatsapp'] ?? null) || !preg_match('/^[0-9]{10,15}$/', $ct['whatsapp'])) {
        $erro('contato.whatsapp', 'O WhatsApp deve ter só números, com DDI e DDD (exemplo: 5562984141116).');
    }
    $texto($ct['whatsapp_exibicao'] ?? null, 'contato.whatsapp_exibicao', 30, 'O telefone exibido');
    $texto($ct['horario'] ?? null, 'contato.horario', 80, 'O horário');
    $texto($ct['mensagem_padrao'] ?? null, 'contato.mensagem_padrao', 200, 'A mensagem do WhatsApp');
    $texto($ct['endereco_curto'] ?? null, 'contato.endereco_curto', 80, 'O endereço curto');
    if (!is_array($ct['endereco_linhas'] ?? null) || !conteudo_eh_lista($ct['endereco_linhas'])) {
        $erro('contato.endereco_linhas', 'O endereço está no formato errado.');
    } else {
        foreach ($ct['endereco_linhas'] as $n => $l) {
            $texto($l, "contato.endereco_linhas.$n", 100, 'A linha do endereço');
        }
    }

    // Início
    $h = $c['home'] ?? [];
    $texto($h['hero']['titulo'] ?? null, 'home.hero.titulo', LIMITE_TITULO, 'O título do topo');
    $texto($h['hero']['texto'] ?? null, 'home.hero.texto', LIMITE_PARAGRAFO, 'O texto do topo');
    $midia($h['hero']['imagem']['arquivo'] ?? null, 'home.hero.imagem.arquivo', 'a imagem do topo');
    foreach (['boas_vindas', 'sobre'] as $sec) {
        $lista = $h[$sec]['paragrafos'] ?? null;
        if ($limite_lista($lista, "home.$sec.paragrafos", 6, 'parágrafos')) {
            foreach ($lista as $n => $p) {
                $texto($p, "home.$sec.paragrafos.$n", LIMITE_PARAGRAFO, 'O parágrafo');
            }
        }
    }
    $texto($h['sobre']['titulo'] ?? null, 'home.sobre.titulo', LIMITE_TITULO, 'O título de "Sobre"');
    $texto($h['sobre']['rotulo'] ?? null, 'home.sobre.rotulo', LIMITE_ROTULO, 'O rótulo de "Sobre"');
    $midia($h['sobre']['foto']['arquivo'] ?? null, 'home.sobre.foto.arquivo', 'a foto de "Sobre"');
    $midia($h['sobre']['video']['arquivo'] ?? null, 'home.sobre.video.arquivo', 'o vídeo de "Sobre"');
    $midia($h['sobre']['video']['poster'] ?? null, 'home.sobre.video.poster', 'a capa do vídeo de "Sobre"');
    if ($limite_lista($h['sobre']['video_paragrafos'] ?? null, 'home.sobre.video_paragrafos', 6, 'parágrafos')) {
        foreach ($h['sobre']['video_paragrafos'] as $n => $p) {
            $texto($p, "home.sobre.video_paragrafos.$n", LIMITE_PARAGRAFO, 'O parágrafo');
        }
    }
    if ($limite_lista($h['sobre']['numeros'] ?? null, 'home.sobre.numeros', 4, 'destaques')) {
        $ids($h['sobre']['numeros'], 'home.sobre.numeros', 'o destaque');
        foreach ($h['sobre']['numeros'] as $n => $x) {
            $texto($x['valor'] ?? null, "home.sobre.numeros.$n.valor", LIMITE_ROTULO, 'O destaque');
            $texto($x['legenda'] ?? null, "home.sobre.numeros.$n.legenda", LIMITE_ROTULO, 'A legenda do destaque');
        }
    }

    // Procedimentos
    $texto($h['procedimentos']['titulo'] ?? null, 'home.procedimentos.titulo', LIMITE_TITULO, 'O título de "Procedimentos"');
    $texto($h['procedimentos']['rotulo'] ?? null, 'home.procedimentos.rotulo', LIMITE_ROTULO, 'O rótulo de "Procedimentos"');
    if ($limite_lista($h['procedimentos']['itens'] ?? null, 'home.procedimentos.itens', 12, 'procedimentos')) {
        $ids($h['procedimentos']['itens'], 'home.procedimentos.itens', 'o procedimento');
        foreach ($h['procedimentos']['itens'] as $n => $x) {
            $texto($x['titulo'] ?? null, "home.procedimentos.itens.$n.titulo", LIMITE_TITULO, 'O título do procedimento');
            $texto($x['texto'] ?? null, "home.procedimentos.itens.$n.texto", LIMITE_PARAGRAFO, 'O texto do procedimento');
        }
    }

    // Cursos
    $texto($h['cursos']['titulo'] ?? null, 'home.cursos.titulo', LIMITE_TITULO, 'O título de "Cursos"');
    $texto($h['cursos']['descricao'] ?? null, 'home.cursos.descricao', LIMITE_PARAGRAFO, 'A descrição de "Cursos"');
    if ($limite_lista($h['cursos']['itens'] ?? null, 'home.cursos.itens', 4, 'cursos')) {
        $ids($h['cursos']['itens'], 'home.cursos.itens', 'o curso');
        foreach ($h['cursos']['itens'] as $n => $x) {
            $texto($x['titulo'] ?? null, "home.cursos.itens.$n.titulo", LIMITE_TITULO, 'O título do curso');
            $texto($x['carga_horaria'] ?? null, "home.cursos.itens.$n.carga_horaria", LIMITE_TITULO, 'A carga horária');
            $texto($x['descricao'] ?? null, "home.cursos.itens.$n.descricao", LIMITE_PARAGRAFO, 'A descrição do curso');
            $texto($x['mensagem_whatsapp'] ?? null, "home.cursos.itens.$n.mensagem_whatsapp", 200, 'A mensagem do curso');
            foreach (['conteudo', 'modulos'] as $lista) {
                if ($limite_lista($x[$lista] ?? null, "home.cursos.itens.$n.$lista", 20, 'itens')) {
                    foreach ($x[$lista] as $m => $t) {
                        $texto($t, "home.cursos.itens.$n.$lista.$m", LIMITE_TITULO * 2, 'O item');
                    }
                }
            }
        }
    }

    // Depoimentos
    $texto($h['depoimentos']['titulo'] ?? null, 'home.depoimentos.titulo', LIMITE_TITULO, 'O título de "Depoimentos"');
    if ($limite_lista($h['depoimentos']['itens'] ?? null, 'home.depoimentos.itens', 12, 'depoimentos')) {
        $ids($h['depoimentos']['itens'], 'home.depoimentos.itens', 'o depoimento');
        foreach ($h['depoimentos']['itens'] as $n => $x) {
            $texto($x['texto'] ?? null, "home.depoimentos.itens.$n.texto", LIMITE_DEPOIMENTO, 'O depoimento');
            $texto($x['autor'] ?? null, "home.depoimentos.itens.$n.autor", LIMITE_ROTULO, 'O nome de quem deu o depoimento');
            $texto($x['servico'] ?? null, "home.depoimentos.itens.$n.servico", LIMITE_ROTULO, 'O serviço do depoimento', false);
        }
    }

    // Resultados
    $r = $c['resultados'] ?? [];
    $texto($r['titulo_pagina'] ?? null, 'resultados.titulo_pagina', LIMITE_TITULO, 'O título da página de resultados');
    $cats = [];
    if ($limite_lista($r['categorias'] ?? null, 'resultados.categorias', 10, 'categorias')) {
        $ids($r['categorias'], 'resultados.categorias', 'a categoria');
        foreach ($r['categorias'] as $n => $cat) {
            $texto($cat['nome'] ?? null, "resultados.categorias.$n.nome", LIMITE_TITULO, 'O nome da categoria');
            if (is_string($cat['id'] ?? null)) {
                $cats[$cat['id']] = true;
            }
        }
    }
    if ($limite_lista($r['itens'] ?? null, 'resultados.itens', 60, 'resultados')) {
        $ids($r['itens'], 'resultados.itens', 'o resultado');
        foreach ($r['itens'] as $n => $x) {
            $p = "resultados.itens.$n";
            $tipo = $x['tipo'] ?? null;
            if (!in_array($tipo, ['comparacao', 'video', 'foto'], true)) {
                $erro("$p.tipo", 'O tipo do resultado é inválido.');
                continue;
            }
            if (!is_string($x['categoria'] ?? null) || !isset($cats[$x['categoria']])) {
                $erro("$p.categoria", 'A categoria do resultado não existe.');
            }
            if ($tipo === 'comparacao') {
                $texto($x['titulo'] ?? null, "$p.titulo", LIMITE_TITULO, 'O título do comparador');
                foreach (['antes', 'depois'] as $lado) {
                    $f = $x[$lado] ?? [];
                    $midia($f['arquivo'] ?? null, "$p.$lado.arquivo", "a foto de $lado");
                    $texto($f['alt'] ?? '', "$p.$lado.alt", 160, 'O texto alternativo', false);
                    $e = $f['enquadramento'] ?? null;
                    if (!is_array($e)) {
                        $erro("$p.$lado.enquadramento", 'Falta o enquadramento da foto.');
                        continue;
                    }
                    foreach (['width', 'height', 'left', 'top'] as $k) {
                        if (!is_int($e[$k] ?? null) && !is_float($e[$k] ?? null)) {
                            $erro("$p.$lado.enquadramento.$k", 'O enquadramento da foto está incompleto.');
                            continue 2;
                        }
                    }
                    $tol = 0.0011;
                    if ($e['width'] < ENQUADRAMENTO_MIN - $tol || $e['width'] > ENQUADRAMENTO_MAX + $tol
                        || $e['height'] < ENQUADRAMENTO_MIN - $tol || $e['height'] > ENQUADRAMENTO_MAX + $tol) {
                        $erro("$p.$lado.enquadramento", 'O zoom da foto precisa ficar entre 100% e 400%.');
                    } elseif (abs($e['left'] - 50) > ($e['width'] - 100) / 2 + $tol || abs($e['top'] - 50) > ($e['height'] - 100) / 2 + $tol) {
                        $erro("$p.$lado.enquadramento", 'A foto saiu do quadro. Volte ao automático ou arraste de volta.');
                    }
                }
            } else {
                $texto($x['rotulo'] ?? null, "$p.rotulo", LIMITE_ROTULO, 'O rótulo');
                $midia($x['arquivo'] ?? null, "$p.arquivo", $tipo === 'video' ? 'o vídeo' : 'a foto');
                if ($tipo === 'video') {
                    $midia($x['poster'] ?? null, "$p.poster", 'a capa do vídeo');
                } else {
                    $texto($x['alt'] ?? '', "$p.alt", 160, 'O texto alternativo', false);
                    if (!in_array($x['ajuste'] ?? 'cover', ['cover', 'contain'], true)) {
                        $erro("$p.ajuste", 'O ajuste da foto é inválido.');
                    }
                }
            }
            foreach (['ordem', 'ordem_home'] as $k) {
                if (!is_int($x[$k] ?? null) || $x[$k] < 0 || $x[$k] > 1000) {
                    $erro("$p.$k", 'A ordem do resultado é inválida.');
                }
            }
        }
    }

    return $erros;
}
