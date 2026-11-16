<?php
defined('RAIZ') || exit;

/**
 * Configuração do painel. Fica FORA do public_html (produção) ou em .local/ (prévia local).
 * Sem o arquivo, o site público mostra o conteúdo padrão e o painel avisa que não está configurado.
 */

/** Só para testes: troca a configuração lida do disco. Passe null para voltar ao normal. */
function config_definir(?array $dados): void
{
    $GLOBALS['__config_teste'] = $dados;
}

function config_arquivo(): string
{
    if (PHP_SAPI === 'cli-server') {
        return RAIZ . '/.local/painel-config.php';
    }
    return dirname(RAIZ) . '/painel-config.php';
}

function config(): array
{
    $padrao = [
        'configurado' => false,
        'senha_hash' => '',
        'dados_dir' => '',
        'midia_dir' => RAIZ . '/conteudo/midia',
        'midia_url' => 'conteudo/midia',
        'ambiente' => 'producao',
        'cookie_secure' => true,
        'ip_cabecalho' => null,
        'proxies_confiaveis' => [],
        'sessao_minutos' => 120,
    ];

    if (array_key_exists('__config_teste', $GLOBALS) && $GLOBALS['__config_teste'] !== null) {
        return array_replace($padrao, $GLOBALS['__config_teste'], ['configurado' => true]);
    }

    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $arquivo = config_arquivo();
    $lido = is_file($arquivo) ? (static function (string $f) {
        return include $f;
    })($arquivo) : null;

    if (!is_array($lido) || empty($lido['senha_hash']) || empty($lido['dados_dir'])) {
        return $cache = $padrao;
    }

    $cfg = array_replace($padrao, $lido, ['configurado' => true]);
    $cfg['dados_dir'] = rtrim(str_replace('\\', '/', (string) $cfg['dados_dir']), '/');
    $cfg['midia_dir'] = rtrim(str_replace('\\', '/', (string) $cfg['midia_dir']), '/');
    $cfg['midia_url'] = trim((string) $cfg['midia_url'], '/');
    $cfg['sessao_minutos'] = max(5, (int) $cfg['sessao_minutos']);
    $cfg['proxies_confiaveis'] = array_values((array) $cfg['proxies_confiaveis']);
    return $cache = $cfg;
}
