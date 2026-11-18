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
        $ok_assets = is_string($v) && preg_match('#^assets/[A-Za-z0-9_./-]+$#', $v) && !str_contains($v, '..') && !str_contains($v, '//') && $v[strlen('assets/')] !== '.';
        if (!$ok_assets && !(is_string($v) && midia_resto($v) !== null)) {
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
    if (isset($h['hero']['chamada'])) $texto($h['hero']['chamada'], 'home.hero.chamada', LIMITE_TITULO, 'A frase acima do título do topo', false);
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
                    // Posição: 0,05 ponto de folga (invisível). O ajuste à mão do site antigo passa em 0,039.
                    $tolp = 0.05;
                    if ($e['width'] < ENQUADRAMENTO_MIN - $tol || $e['width'] > ENQUADRAMENTO_MAX + $tol
                        || $e['height'] < ENQUADRAMENTO_MIN - $tol || $e['height'] > ENQUADRAMENTO_MAX + $tol) {
                        $erro("$p.$lado.enquadramento", 'O zoom da foto precisa ficar entre 100% e 400%.');
                    } elseif (abs($e['left'] - 50) > ($e['width'] - 100) / 2 + $tolp || abs($e['top'] - 50) > ($e['height'] - 100) / 2 + $tolp) {
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

    // Campos que a dona não edita (ficam no arquivo e vão para links, mapa e dados do Google).
    // O servidor confere o tipo de cada um: um valor trocado à mão nunca chega à página inicial.
    $https = static function ($v, string $campo, string $nome, ?string $prefixo = null) use ($erro): void {
        $ok = is_string($v) && strlen($v) <= 400 && preg_match('#^https://[^\s<>"\\\\]+$#', $v) === 1
            && !str_contains($v, "'") && filter_var($v, FILTER_VALIDATE_URL) !== false;
        if ($ok && $prefixo !== null) {
            $ok = strncmp($v, $prefixo, strlen($prefixo)) === 0;
        }
        if (!$ok) {
            $erro($campo, "O endereço de $nome precisa começar com https:// e ser um link válido.");
        }
    };
    $inteiro = static function ($v, string $campo, string $nome, int $min = 0, int $max = 20000) use ($erro): void {
        if (!is_int($v) || $v < $min || $v > $max) {
            $erro($campo, "O número de $nome é inválido.");
        }
    };
    $logico = static function ($v, string $campo, string $nome) use ($erro): void {
        if (!is_bool($v)) {
            $erro($campo, "A opção $nome é inválida.");
        }
    };
    $padrao = static function ($v, string $campo, string $nome, string $regex) use ($erro): void {
        if (!is_string($v) || preg_match($regex, $v) !== 1) {
            $erro($campo, "O valor de $nome é inválido.");
        }
    };

    $padrao($ct['instagram_usuario'] ?? null, 'contato.instagram_usuario', 'o Instagram', '/^[A-Za-z0-9._]{1,30}$/');
    $es = $ct['endereco_schema'] ?? null;
    if (!is_array($es)) {
        $erro('contato.endereco_schema', 'O endereço para o Google está no formato errado.');
    } else {
        $texto($es['rua'] ?? null, 'contato.endereco_schema.rua', 120, 'A rua do endereço do Google');
        $texto($es['cidade'] ?? null, 'contato.endereco_schema.cidade', 80, 'A cidade do endereço do Google');
        $padrao($es['uf'] ?? null, 'contato.endereco_schema.uf', 'o estado', '/^[A-Z]{2}$/');
        $padrao($es['pais'] ?? null, 'contato.endereco_schema.pais', 'o país', '/^[A-Z]{2}$/');
    }
    $padrao($ct['horario_schema'] ?? null, 'contato.horario_schema', 'o horário para o Google', '/^[A-Za-z0-9:, -]{1,80}$/');
    $geo = $ct['geo'] ?? null;
    foreach (['lat' => 90, 'lng' => 180] as $k => $lim) {
        $n = is_array($geo) ? ($geo[$k] ?? null) : null;
        if ((!is_int($n) && !is_float($n)) || abs($n) > $lim) {
            $erro("contato.geo.$k", 'A localização do mapa é inválida.');
        }
    }
    $https($ct['maps_link'] ?? null, 'contato.maps_link', 'o link do mapa');
    $https($ct['maps_embed'] ?? null, 'contato.maps_embed', 'o mapa da página', 'https://www.google.com/maps');
    $https($ct['avaliacao_online'] ?? null, 'contato.avaliacao_online', 'o link de avaliação');
    // Opcionais (só vão para o Google se preenchidos): CEP, outros perfis e faixa de preço.
    $cep = $ct['cep'] ?? '';
    if (!is_string($cep) || ($cep !== '' && preg_match('/^\d{5}-\d{3}$/', $cep) !== 1)) {
        $erro('contato.cep', 'O CEP precisa estar no formato 00000-000.');
    }
    $perfis = $ct['perfis'] ?? [];
    if ($limite_lista($perfis, 'contato.perfis', 10, 'perfis')) {
        foreach ($perfis as $n => $url) {
            $https($url, "contato.perfis.$n", 'o perfil');
        }
    }
    $faixa = $c['seo']['faixa_preco'] ?? '';
    if (!is_string($faixa) || mb_strlen($faixa) > 20 || $faixa !== strip_tags($faixa)) {
        $erro('seo.faixa_preco', 'A faixa de preço aceita até 20 letras (exemplo: $$ ou R$ 80 a R$ 300).');
    }

    $seo = $c['seo'] ?? [];
    foreach (['titulo', 'titulo_resultados', 'og_titulo', 'og_titulo_resultados', 'og_imagem_alt'] as $k) {
        $texto($seo[$k] ?? null, "seo.$k", 200, 'O texto do Google (' . $k . ')');
    }
    foreach (['descricao', 'descricao_resultados', 'og_descricao', 'og_descricao_resultados'] as $k) {
        $texto($seo[$k] ?? null, "seo.$k", 400, 'O texto do Google (' . $k . ')');
    }
    $midia($seo['og_imagem'] ?? null, 'seo.og_imagem', 'a imagem de divulgação');
    $https($seo['url_base'] ?? null, 'seo.url_base', 'o endereço do site');
    $texto($c['atualizado_em'] ?? null, 'atualizado_em', 40, 'A data de atualização');

    foreach (['logo_texto', 'logo_icone'] as $k) {
        $lg = $c['marca'][$k] ?? null;
        if (!is_array($lg)) {
            $erro("marca.$k", 'O logotipo está no formato errado.');
            continue;
        }
        $midia($lg['arquivo'] ?? null, "marca.$k.arquivo", 'o logotipo');
        $inteiro($lg['largura'] ?? null, "marca.$k.largura", 'largura do logotipo');
        $inteiro($lg['altura'] ?? null, "marca.$k.altura", 'altura do logotipo');
        $texto($lg['alt'] ?? null, "marca.$k.alt", 160, 'O texto alternativo do logotipo');
    }
    $inteiro($h['hero']['imagem']['largura'] ?? null, 'home.hero.imagem.largura', 'largura da imagem do topo');
    $inteiro($h['hero']['imagem']['altura'] ?? null, 'home.hero.imagem.altura', 'altura da imagem do topo');
    $texto($h['hero']['botao_principal'] ?? null, 'home.hero.botao_principal', LIMITE_ROTULO, 'O botão principal');
    $texto($h['hero']['botao_secundario'] ?? null, 'home.hero.botao_secundario', LIMITE_ROTULO, 'O botão secundário');
    $inteiro($h['sobre']['foto']['largura'] ?? null, 'home.sobre.foto.largura', 'largura da foto de "Sobre"');
    $inteiro($h['sobre']['foto']['altura'] ?? null, 'home.sobre.foto.altura', 'altura da foto de "Sobre"');
    $texto($h['sobre']['foto']['alt'] ?? null, 'home.sobre.foto.alt', 160, 'O texto alternativo da foto de "Sobre"', false);
    $inteiro($h['sobre']['video']['largura'] ?? null, 'home.sobre.video.largura', 'largura do vídeo de "Sobre"');
    $inteiro($h['sobre']['video']['altura'] ?? null, 'home.sobre.video.altura', 'altura do vídeo de "Sobre"');
    $texto($h['sobre']['saiba_mais'] ?? null, 'home.sobre.saiba_mais', LIMITE_ROTULO, 'O texto do botão "Saiba mais"');
    $texto($h['resultados']['rotulo'] ?? null, 'home.resultados.rotulo', LIMITE_ROTULO, 'O rótulo de "Resultados"');
    $texto($h['resultados']['titulo'] ?? null, 'home.resultados.titulo', LIMITE_TITULO, 'O título de "Resultados"');
    $texto($h['resultados']['texto_ver_mais'] ?? null, 'home.resultados.texto_ver_mais', LIMITE_ROTULO, 'O texto de "Ver mais"');
    $texto($h['depoimentos']['rotulo'] ?? null, 'home.depoimentos.rotulo', LIMITE_ROTULO, 'O rótulo de "Depoimentos"');
    $texto($h['contato_secao']['rotulo'] ?? null, 'home.contato_secao.rotulo', LIMITE_ROTULO, 'O rótulo de "Contato"');
    $texto($h['contato_secao']['titulo'] ?? null, 'home.contato_secao.titulo', LIMITE_TITULO, 'O título de "Contato"');
    $texto($r['cta_texto'] ?? null, 'resultados.cta_texto', LIMITE_TITULO, 'O convite da página de resultados');
    if (is_array($r['categorias'] ?? null) && conteudo_eh_lista($r['categorias'])) {
        foreach ($r['categorias'] as $n => $cat) {
            $inteiro($cat['ordem'] ?? null, "resultados.categorias.$n.ordem", 'ordem da categoria', 0, 1000);
        }
    }
    if (is_array($r['itens'] ?? null) && conteudo_eh_lista($r['itens'])) {
        foreach ($r['itens'] as $n => $x) {
            $p = "resultados.itens.$n";
            if (!is_array($x)) {
                continue;
            }
            $logico($x['em_resultados'] ?? null, "$p.em_resultados", 'de aparecer em resultados');
            $logico($x['na_home'] ?? null, "$p.na_home", 'de aparecer no início');
            if (($x['tipo'] ?? null) === 'comparacao') {
                foreach (['antes', 'depois'] as $lado) {
                    $inteiro($x[$lado]['largura'] ?? null, "$p.$lado.largura", 'largura da foto');
                    $inteiro($x[$lado]['altura'] ?? null, "$p.$lado.altura", 'altura da foto');
                    if (is_array($x[$lado]['enquadramento'] ?? null) && array_key_exists('automatico', $x[$lado]['enquadramento'])) {
                        $logico($x[$lado]['enquadramento']['automatico'], "$p.$lado.enquadramento.automatico", 'de enquadramento automático');
                    }
                }
            }
        }
    }

    return $erros;
}

// ---------------------------------------------------------------------------
// Salvar, histórico e lixeira (L14)
// ---------------------------------------------------------------------------

function conteudo_json(array $dados): string
{
    return json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '{}';
}

/** Grava por um arquivo temporário e troca de nome: ninguém lê um arquivo pela metade. */
function conteudo_gravar_atomico(string $arquivo, string $texto): bool
{
    $tmp = $arquivo . '.tmp' . bin2hex(random_bytes(3));
    if (file_put_contents($tmp, $texto, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $arquivo)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/** Formato único dos arquivos enviados pelo painel: AAAA/<16 letras e números hex>.(webp|jpg|mp4). */
const MIDIA_RESTO_REGEX = '#^\d{4}/[0-9a-f]{16}\.(webp|jpg|mp4)$#';

/** Parte do caminho depois de "conteudo/midia/", ou null se não for um arquivo enviado pelo painel. */
function midia_resto(string $arquivo): ?string
{
    $prefixo = config()['midia_url'] . '/';
    if (strpos($arquivo, $prefixo) !== 0) {
        return null;
    }
    $resto = substr($arquivo, strlen($prefixo));
    return preg_match(MIDIA_RESTO_REGEX, $resto) === 1 ? $resto : null;
}

/** Caminho no disco de uma mídia de conteudo/midia/. Devolve null para qualquer outra coisa (assets/, "..", arquivo oculto etc.). */
function midia_caminho(string $arquivo): ?string
{
    $resto = midia_resto($arquivo);
    return $resto === null ? null : config()['midia_dir'] . '/' . $resto;
}

/** Área privada (fora do public_html): guarda a mídia que o site não usa agora, mas que uma versão antiga ainda pede. */
function midia_privada_dir(): string
{
    return config()['dados_dir'] . '/midia-privada';
}

function midia_privada_caminho(string $arquivo): ?string
{
    $resto = midia_resto($arquivo);
    return $resto === null ? null : midia_privada_dir() . '/' . $resto;
}

/** Move um arquivo, criando a pasta de destino. Se o rename não puder (outro disco), copia e apaga. */
function midia_mover_arquivo(string $de, string $para): bool
{
    $pasta = dirname($para);
    if (!is_dir($pasta) && !@mkdir($pasta, 0700, true) && !is_dir($pasta)) {
        return false;
    }
    if (@rename($de, $para)) {
        return true;
    }
    if (@copy($de, $para)) {
        @unlink($de);
        return true;
    }
    @unlink($para);
    return false;
}

/** Tira a mídia do public_html e guarda na área privada. true se ela ficou (ou já estava) guardada. */
function midia_privada_guardar(string $arquivo): bool
{
    $publico = midia_caminho($arquivo);
    $privado = midia_privada_caminho($arquivo);
    if ($publico === null || $privado === null) {
        return false;
    }
    if (!is_file($publico)) {
        return is_file($privado);
    }
    if (is_file($privado)) {
        @unlink($publico);   // já há cópia privada; a pública não deve continuar no ar
        return !is_file($publico);
    }
    return midia_mover_arquivo($publico, $privado);
}

/** Traz de volta ao site a mídia guardada na área privada. true se o arquivo está no ar depois da chamada. */
function midia_privada_trazer(string $arquivo): bool
{
    $publico = midia_caminho($arquivo);
    $privado = midia_privada_caminho($arquivo);
    if ($publico === null || $privado === null) {
        return false;
    }
    if (is_file($publico)) {
        return true;
    }
    if (!is_file($privado)) {
        return false;
    }
    if (!midia_mover_arquivo($privado, $publico)) {
        return false;
    }
    @chmod($publico, 0644);
    return true;
}

function historico_dir(): string
{
    return config()['dados_dir'] . '/historico';
}

/** @return array<int,string> número da versão => caminho, da mais nova para a mais velha */
function historico_arquivos(): array
{
    $r = [];
    foreach (glob(historico_dir() . '/*.json') ?: [] as $f) {
        if (preg_match('/(\d+)\.json$/', $f, $m)) {
            $r[(int) $m[1]] = $f;
        }
    }
    krsort($r);
    return $r;
}

function historico_ler(string $arquivo): ?array
{
    $d = is_file($arquivo) ? json_decode((string) file_get_contents($arquivo), true) : null;
    return is_array($d) && isset($d['n'], $d['conteudo']) && is_array($d['conteudo']) ? $d : null;
}

/**
 * Salva o conteúdo. $revisao_esperada é a revisão que a tela tinha quando abriu: se alguém salvou
 * no meio, devolve 'conflito' e nada é gravado.
 *
 * @return array{ok:bool,revisao?:int,erro?:string,detalhes?:array}
 */
function conteudo_salvar(array $c, int $revisao_esperada, string $resumo = ''): array
{
    $cfg = config();
    if (empty($cfg['configurado']) || $cfg['dados_dir'] === '') {
        return ['ok' => false, 'erro' => 'invalido', 'detalhes' => [['campo' => '', 'mensagem' => 'O painel ainda não está configurado.']]];
    }
    $detalhes = conteudo_validar($c);
    if ($detalhes) {
        return ['ok' => false, 'erro' => 'invalido', 'detalhes' => $detalhes];
    }

    $dir = $cfg['dados_dir'];
    foreach ([$dir, historico_dir()] as $p) {
        if (!is_dir($p)) {
            @mkdir($p, 0700, true);
        }
    }
    $trava = fopen($dir . '/trava.lock', 'c');
    if (!$trava) {
        return ['ok' => false, 'erro' => 'invalido', 'detalhes' => [['campo' => '', 'mensagem' => 'Não foi possível gravar. Tente de novo.']]];
    }
    flock($trava, LOCK_EX);
    try {
        $atual = conteudo_ler_editado();
        $rev_atual = (int) ($atual['revisao'] ?? 0);
        if ($rev_atual !== $revisao_esperada) {
            return ['ok' => false, 'erro' => 'conflito'];
        }

        // Tudo o que o conteúdo novo usa precisa estar no ar (ao voltar uma versão, sai da área privada)
        $uso = array_flip(conteudo_midias_usadas($c));
        foreach (array_keys($uso) as $arq) {
            midia_privada_trazer((string) $arq);
        }

        $nova = $rev_atual + 1;
        $c['versao_esquema'] = ESQUEMA_VERSAO;
        $c['revisao'] = $nova;

        $hist = [
            'n' => $nova,
            'data' => date('c', seg_agora_conteudo()),
            'resumo' => $resumo,
            'conteudo' => $c,
        ];
        if (!conteudo_gravar_atomico(historico_dir() . '/' . sprintf('%04d', $nova) . '.json', conteudo_json($hist))
            || !conteudo_gravar_atomico($dir . '/site.json', conteudo_json($c))) {
            return ['ok' => false, 'erro' => 'invalido', 'detalhes' => [['campo' => '', 'mensagem' => 'Não foi possível gravar. Tente de novo.']]];
        }

        // Guarda só as últimas 20 versões. A mídia que o site não usa mais sai do public_html na hora:
        // vai para a área privada se alguma versão ainda a pede, ou para a lixeira se nenhuma pede.
        $candidatas = $atual ? conteudo_midias_usadas($atual) : [];
        $restam = [];
        $arquivos = historico_arquivos();
        $i = 0;
        foreach ($arquivos as $n => $f) {
            $i++;
            $h = historico_ler($f);
            if ($i > HISTORICO_MAX) {
                if ($h) {
                    $candidatas = array_merge($candidatas, conteudo_midias_usadas($h['conteudo']));
                }
                @unlink($f);
                continue;
            }
            if ($h) {
                $usadas = conteudo_midias_usadas($h['conteudo']);
                $restam = array_merge($restam, $usadas);
                $candidatas = array_merge($candidatas, $usadas);
            }
        }
        $restam = array_flip($restam);
        foreach (array_unique($candidatas) as $arq) {
            if (isset($uso[$arq]) || midia_caminho($arq) === null) {
                continue;
            }
            if (isset($restam[$arq])) {
                midia_privada_guardar($arq);
            } else {
                lixeira_mover($arq);
            }
        }
    } finally {
        flock($trava, LOCK_UN);
        fclose($trava);
    }
    conteudo_cache_limpar();
    return ['ok' => true, 'revisao' => $nova];
}

/** Hora usada nas datas gravadas (os testes podem fixar). */
function seg_agora_conteudo(): int
{
    return (int) ($GLOBALS['__nucleo_teste']['agora'] ?? time());
}

/** @return array<int,array{n:int,data:string,resumo:string}> da versão mais nova para a mais velha */
function conteudo_versoes(): array
{
    $r = [];
    foreach (historico_arquivos() as $f) {
        $h = historico_ler($f);
        if ($h) {
            $r[] = ['n' => (int) $h['n'], 'data' => (string) ($h['data'] ?? ''), 'resumo' => (string) ($h['resumo'] ?? '')];
        }
    }
    return $r;
}

/** Volta o site para a versão $n. A volta também vira uma versão nova (nada se perde). */
function conteudo_restaurar(int $n, int $revisao_esperada): array
{
    $arquivo = historico_dir() . '/' . sprintf('%04d', $n) . '.json';
    $h = historico_ler($arquivo);
    if (!$h) {
        return ['ok' => false, 'erro' => 'nao_encontrada'];
    }
    return conteudo_salvar($h['conteudo'], $revisao_esperada, 'Voltou para a versão ' . $n);
}

function lixeira_dir(): string
{
    return config()['dados_dir'] . '/lixeira';
}

/**
 * Manda uma mídia de conteudo/midia/ para a lixeira. Nunca mexe em assets/ nem em fora da pasta de mídia.
 * @return string|null id na lixeira, ou null se o arquivo não pode ou não existe
 */
function lixeira_mover(string $arquivo): ?string
{
    $origem = midia_caminho($arquivo);
    if ($origem !== null && !is_file($origem)) {
        $origem = midia_privada_caminho($arquivo);   // pode estar na área privada
    }
    if ($origem === null || !is_file($origem)) {
        return null;
    }
    $id = date('Ymd-His', seg_agora_conteudo()) . '-' . bin2hex(random_bytes(3));
    $pasta = lixeira_dir() . '/' . $id;
    if (!@mkdir($pasta, 0700, true)) {
        return null;
    }
    $tamanho = (int) filesize($origem);
    if (!midia_mover_arquivo($origem, $pasta . '/arquivo')) {
        @rmdir($pasta);
        return null;
    }
    $privado = midia_privada_caminho($arquivo);
    if ($privado !== null && is_file($privado)) {
        @unlink($privado);   // sobrou uma cópia privada: não deixa duplicado fora da lixeira
    }
    $meta = ['id' => $id, 'arquivo' => $arquivo, 'nome' => basename($arquivo), 'data' => date('c', seg_agora_conteudo()), 'tamanho' => $tamanho];
    file_put_contents($pasta . '/meta.json', conteudo_json($meta));
    return $id;
}

/** @return array<int,array> mais novos primeiro */
function lixeira_listar(): array
{
    $r = [];
    foreach (glob(lixeira_dir() . '/*/meta.json') ?: [] as $f) {
        $m = json_decode((string) file_get_contents($f), true);
        if (is_array($m) && isset($m['id'])) {
            $r[] = $m;
        }
    }
    usort($r, static fn($a, $b) => strcmp((string) $b['id'], (string) $a['id']));
    return $r;
}

/** Devolve a mídia ao lugar de origem. Falha se já existe outro arquivo com o mesmo nome. */
function lixeira_restaurar(string $id): bool
{
    if (!preg_match('/^\d{8}-\d{6}-[0-9a-f]+$/', $id)) {
        return false;
    }
    $pasta = lixeira_dir() . '/' . $id;
    $m = is_file($pasta . '/meta.json') ? json_decode((string) file_get_contents($pasta . '/meta.json'), true) : null;
    $destino = is_array($m) ? midia_caminho((string) ($m['arquivo'] ?? '')) : null;
    if ($destino === null || file_exists($destino) || !is_file($pasta . '/arquivo')) {
        return false;
    }
    if (!is_dir(dirname($destino))) {
        @mkdir(dirname($destino), 0755, true);
    }
    if (!@rename($pasta . '/arquivo', $destino)) {
        return false;
    }
    @unlink($pasta . '/meta.json');
    @rmdir($pasta);
    return true;
}

/** Apaga de vez o que está na lixeira há mais de $dias dias. Com 0, esvazia tudo. Devolve quantos itens apagou. */
function lixeira_esvaziar(int $dias = 30): int
{
    $limite = seg_agora_conteudo() - $dias * 86400;
    $n = 0;
    foreach (lixeira_listar() as $m) {
        $quando = strtotime((string) ($m['data'] ?? '')) ?: 0;
        if ($dias > 0 && $quando > $limite) {
            continue;
        }
        $pasta = lixeira_dir() . '/' . $m['id'];
        foreach (glob($pasta . '/*') ?: [] as $f) {
            @unlink($f);
        }
        if (@rmdir($pasta)) {
            $n++;
        }
    }
    return $n;
}
