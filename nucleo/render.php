<?php
defined('RAIZ') || exit;

/** Funções de saída do site público. Tudo que vem do JSON passa por e() antes de ir para o HTML. */

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Número na menor forma exata: 45.888 sai "45.888"; 100 sai "100". */
function num(float $v): string
{
    $antes = ini_set('serialize_precision', '-1');
    $texto = json_encode($v + 0.0);
    if ($antes !== false) {
        ini_set('serialize_precision', (string) $antes);
    }
    return (string) $texto;
}

/** Caminho relativo da mídia, sem query string nem barra inicial. */
function url_midia(string $arquivo): string
{
    $arquivo = preg_replace('/[?#].*$/', '', $arquivo);
    return ltrim((string) $arquivo, '/');
}

function link_whatsapp(array $contato, ?string $mensagem = null): string
{
    $texto = $mensagem ?? (string) ($contato['mensagem_padrao'] ?? '');
    // rawurlencode escapa ! ' ( ) *, que o encodeURIComponent do site antigo deixava como estão.
    $codificado = strtr(rawurlencode($texto), ['%21' => '!', '%27' => "'", '%28' => '(', '%29' => ')', '%2A' => '*']);
    return 'https://wa.me/' . $contato['whatsapp'] . '?text=' . $codificado;
}

/** Posição e tamanho da foto no quadro 3:4 (centro em %, com translate(-50%,-50%)). */
function estilo_enquadramento(array $e): string
{
    return 'width:' . num((float) $e['width']) . '%;height:' . num((float) $e['height'])
        . '%;left:' . num((float) $e['left']) . '%;top:' . num((float) $e['top']) . '%';
}

function parcial(string $nome, array $vars = []): void
{
    if (!preg_match('/^[a-z0-9-]+$/', $nome)) {
        throw new InvalidArgumentException('Nome de parcial inválido');
    }
    $arquivo = RAIZ . '/parciais/' . $nome . '.php';
    if (!is_file($arquivo)) {
        throw new InvalidArgumentException('Parcial não existe: ' . $nome);
    }
    (static function (string $__arquivo, array $__vars): void {
        extract($__vars, EXTR_SKIP);
        include $__arquivo;
    })($arquivo, $vars);
}

/** JSON para dentro de <script type="application/ld+json">: < e > saem escapados, então </script> não fecha o bloco. */
function jsonld(array $dados): string
{
    return (string) json_encode($dados, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function url_base(array $c): string
{
    return rtrim((string) ($c['seo']['url_base'] ?? 'https://studiogabrielasantos.com.br'), '/');
}

/** Horário no formato schema.org ("Mo-Fr 08:00-17:00, Sa 08:00-12:00") em OpeningHoursSpecification. Vazio se não der para derivar. */
function jsonld_horarios(string $texto): array
{
    $dias = ['Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday', 'Th' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'Su' => 'Sunday'];
    $chaves = array_keys($dias);
    $saida = [];
    foreach (explode(',', $texto) as $parte) {
        if (!preg_match('/^\s*(Mo|Tu|We|Th|Fr|Sa|Su)(?:-(Mo|Tu|We|Th|Fr|Sa|Su))?\s+(\d\d:\d\d)-(\d\d:\d\d)\s*$/', $parte, $m)) {
            return [];
        }
        $ini = array_search($m[1], $chaves, true);
        $fim = $m[2] !== '' ? array_search($m[2], $chaves, true) : $ini;
        if ($fim < $ini) {
            return [];
        }
        $lista = [];
        for ($d = $ini; $d <= $fim; $d++) {
            $lista[] = $dias[$chaves[$d]];
        }
        $saida[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $lista,
            'opens' => $m[3],
            'closes' => $m[4],
        ];
    }
    return $saida;
}

function jsonld_negocio(array $c): array
{
    $base = url_base($c);
    $ct = $c['contato'];
    $seo = $c['seo'];
    $endereco = [
        '@type' => 'PostalAddress',
        'streetAddress' => $ct['endereco_schema']['rua'],
        'addressLocality' => $ct['endereco_schema']['cidade'],
        'addressRegion' => $ct['endereco_schema']['uf'],
    ];
    if (trim((string) ($ct['cep'] ?? '')) !== '') {
        $endereco['postalCode'] = $ct['cep'];
    }
    $endereco['addressCountry'] = $ct['endereco_schema']['pais'];

    $perfis = ['https://instagram.com/' . $ct['instagram_usuario']];
    foreach ((array) ($ct['perfis'] ?? []) as $url) {
        $perfis[] = $url;
    }
    $vistos = [];
    $perfis = array_values(array_filter($perfis, static function (string $u) use (&$vistos): bool {
        $k = strtolower(rtrim(preg_replace('#^https://(www\.)?#i', '', $u), '/'));
        if (isset($vistos[$k])) {
            return false;
        }
        return $vistos[$k] = true;
    }));

    $dados = [
        '@context' => 'https://schema.org',
        '@type' => 'BeautySalon',
        '@id' => $base . '/#negocio',
        'name' => 'Studio Gabriela Santos',
        'description' => $seo['descricao'],
        'url' => $base . '/',
        'logo' => $base . '/assets/logo.svg',
        'image' => [
            $base . '/' . url_midia($seo['og_imagem']),
            $base . '/' . url_midia($c['home']['sobre']['foto']['arquivo']),
        ],
        'address' => $endereco,
        'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $ct['geo']['lat'], 'longitude' => $ct['geo']['lng']],
        'telephone' => '+' . $ct['whatsapp'],
    ];
    $horarios = jsonld_horarios((string) $ct['horario_schema']);
    if ($horarios) {
        $dados['openingHoursSpecification'] = $horarios;
    } else {
        $dados['openingHours'] = $ct['horario_schema'];
    }
    if (trim((string) ($seo['faixa_preco'] ?? '')) !== '') {
        $dados['priceRange'] = $seo['faixa_preco'];
    }
    $dados['sameAs'] = $perfis;
    $dados['hasMap'] = $ct['maps_link'];
    $dados['areaServed'] = $ct['endereco_schema']['cidade'] . ', ' . $ct['endereco_schema']['uf'];
    $dados['founder'] = ['@type' => 'Person', 'name' => 'Gabriela Santos'];
    return $dados;
}

function jsonld_breadcrumb(): array
{
    $base = url_base(conteudo_ler());
    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => $base . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Resultados', 'item' => $base . '/resultados.html'],
        ],
    ];
}

/** Comparadores da home: só tipo comparacao com na_home, por ordem_home, no máximo 5. */
function resultados_home(array $c): array
{
    $itens = array_values(array_filter(
        $c['resultados']['itens'] ?? [],
        static fn($i) => ($i['tipo'] ?? '') === 'comparacao' && !empty($i['na_home'])
    ));
    usort($itens, static fn($a, $b) => ($a['ordem_home'] ?? 0) <=> ($b['ordem_home'] ?? 0));
    return array_slice($itens, 0, 5);
}

/** Página de resultados: categorias por ordem, itens por ordem, colunas = min(n, 3); categoria vazia some. */
function resultados_por_categoria(array $c): array
{
    $cats = $c['resultados']['categorias'] ?? [];
    usort($cats, static fn($a, $b) => ($a['ordem'] ?? 0) <=> ($b['ordem'] ?? 0));
    $saida = [];
    foreach ($cats as $cat) {
        $itens = array_values(array_filter(
            $c['resultados']['itens'] ?? [],
            static fn($i) => ($i['categoria'] ?? '') === $cat['id'] && !empty($i['em_resultados'])
        ));
        if (!$itens) {
            continue;
        }
        usort($itens, static fn($a, $b) => ($a['ordem'] ?? 0) <=> ($b['ordem'] ?? 0));
        $saida[] = ['categoria' => $cat, 'itens' => $itens, 'colunas' => min(count($itens), 3)];
    }
    return $saida;
}
