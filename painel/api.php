<?php
/**
 * API do painel. Só aceita POST com JSON {acao: ...}, sessão ativa e o cabeçalho X-CSRF.
 * Ações: ler, salvar, versoes, restaurar, lixeira, lixeira_restaurar, sair, diagnostico_ip.
 */
define('RAIZ', dirname(__DIR__));
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';
require_once RAIZ . '/nucleo/seguranca.php';

sessao_iniciar();
cabecalhos_painel();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder_json(['ok' => false, 'erro' => 'metodo'], 405);
}
exigir_login_api();
csrf_verificar();

$bruto = file_get_contents('php://input', false, null, 0, 4000000);
$corpo = is_string($bruto) ? json_decode($bruto, true) : null;
if (!is_array($corpo)) {
    responder_json(['ok' => false, 'erro' => 'pedido'], 400);
}

/** Resposta padrão de salvar/restaurar: 200 com o conteúdo novo, 409 (conflito) ou 422 (campos inválidos). */
function api_resultado_salvar(array $r): never
{
    if (!empty($r['ok'])) {
        lixeira_esvaziar(30);
        responder_json(['ok' => true, 'revisao' => (int) $r['revisao'], 'conteudo' => conteudo_ler()]);
    }
    $erro = (string) ($r['erro'] ?? 'servidor');
    if ($erro === 'conflito') {
        responder_json(['ok' => false, 'erro' => 'conflito'], 409);
    }
    if ($erro === 'nao_encontrada') {
        responder_json(['ok' => false, 'erro' => 'nao_encontrada'], 404);
    }
    if ($erro === 'invalido') {
        responder_json(['ok' => false, 'erro' => 'invalido', 'detalhes' => $r['detalhes'] ?? []], 422);
    }
    responder_json(['ok' => false, 'erro' => 'servidor'], 500);
}

$acao = (string) ($corpo['acao'] ?? '');

switch ($acao) {
    case 'ler':
        $c = conteudo_ler();
        responder_json(['ok' => true, 'conteudo' => $c, 'revisao' => (int) ($c['revisao'] ?? 0)]);

    case 'salvar':
        $c = $corpo['conteudo'] ?? null;
        $rev = $corpo['revisao'] ?? null;
        if (!is_array($c) || !is_int($rev)) {
            responder_json(['ok' => false, 'erro' => 'pedido'], 400);
        }
        $resumo = mb_substr(trim(strip_tags((string) ($corpo['resumo'] ?? ''))), 0, 140);
        $c['atualizado_em'] = date('c');
        api_resultado_salvar(conteudo_salvar($c, $rev, $resumo !== '' ? $resumo : 'Salvou mudanças'));

    case 'versoes':
        $lista = conteudo_versoes();
        // A "versão 0" é o conteúdo original do site, sempre disponível
        $lista[] = ['n' => 0, 'data' => '', 'resumo' => 'Conteúdo original do site'];
        responder_json(['ok' => true, 'versoes' => $lista]);

    case 'restaurar':
        $n = $corpo['n'] ?? null;
        $rev = $corpo['revisao'] ?? null;
        if (!is_int($n) || $n < 0 || !is_int($rev)) {
            responder_json(['ok' => false, 'erro' => 'pedido'], 400);
        }
        if ($n === 0) {
            api_resultado_salvar(conteudo_salvar(conteudo_padrao(), $rev, 'Voltou ao conteúdo original do site'));
        }
        api_resultado_salvar(conteudo_restaurar($n, $rev));

    case 'lixeira':
        $itens = array_map(static fn(array $m): array => [
            'id' => (string) $m['id'],
            'nome' => (string) ($m['nome'] ?? ''),
            'data' => (string) ($m['data'] ?? ''),
            'tamanho' => (int) ($m['tamanho'] ?? 0),
        ], lixeira_listar());
        responder_json(['ok' => true, 'itens' => $itens]);

    case 'lixeira_restaurar':
        $id = $corpo['id'] ?? null;
        if (!is_string($id)) {
            responder_json(['ok' => false, 'erro' => 'pedido'], 400);
        }
        if (!lixeira_restaurar($id)) {
            responder_json(['ok' => false, 'erro' => 'nao_restaurou'], 409);
        }
        responder_json(['ok' => true]);

    case 'sair':
        sessao_sair();
        responder_json(['ok' => true]);

    case 'diagnostico_ip':
        $cfg = config();
        $cab = $cfg['ip_cabecalho'] ?? null;
        responder_json([
            'ok' => true,
            'ip_cliente' => ip_cliente(),
            'remote_addr' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'cabecalho' => $cab,
            'valor_cabecalho' => $cab ? (string) ($_SERVER[$cab] ?? '') : '',
            'proxies_confiaveis' => array_values($cfg['proxies_confiaveis']),
            'remoto_e_proxy_confiavel' => ip_em_lista((string) ($_SERVER['REMOTE_ADDR'] ?? ''), $cfg['proxies_confiaveis']),
            'ambiente' => (string) $cfg['ambiente'],
        ]);

    default:
        responder_json(['ok' => false, 'erro' => 'pedido'], 400);
}
