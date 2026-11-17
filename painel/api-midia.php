<?php
// API de mídia do painel (Lote 2B). POST com `acao`:
//   foto | video_iniciar | video_estado | video_parte | video_concluir | video_cancelar | poster
// Resposta: JSON {ok:true, ...} ou {ok:false, erro:<codigo>}. As frases em português ficam em painel/midia.js.
define('RAIZ', dirname(__DIR__));
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/seguranca.php';
require_once RAIZ . '/nucleo/midia.php';

cabecalhos_painel();
if (!config()['configurado']) {
    responder_json(['ok' => false, 'erro' => 'nao_configurado'], 503);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder_json(['ok' => false, 'erro' => 'metodo'], 405);
}
sessao_iniciar();
exigir_login_api();
csrf_verificar();

// Se a memória acabar no meio de uma foto, ainda devolve JSON em vez de uma página de erro
register_shutdown_function(static function (): void {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR], true) && !headers_sent()) {
        if (ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => stripos($e['message'], 'memory') !== false ? 'foto_enorme' : 'gravacao']);
    }
});

// Pedido maior que post_max_size chega vazio
if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'json') === false) {
    responder_json(['ok' => false, 'erro' => 'limite_servidor'], 413);
}

$entrada = $_POST;
if (stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') === 0) {
    $j = json_decode((string) file_get_contents('php://input'), true);
    $entrada = is_array($j) ? $j : [];
}
$acao = (string) ($entrada['acao'] ?? '');

/** Resposta padrão: erro vira ok:false com o código; sucesso vira ok:true com os dados. */
function resposta_midia(array $r): never
{
    if (isset($r['erro'])) {
        $status = match ($r['erro']) {
            'envio_nao_encontrado' => 404,
            'gravacao' => 500,
            default => 422,
        };
        responder_json(['ok' => false] + $r, $status);
    }
    responder_json(['ok' => true] + $r);
}

/** O arquivo recebido pelo PHP, ou null. */
function arquivo_recebido(string $campo): ?array
{
    $f = $_FILES[$campo] ?? null;
    if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $f['tmp_name'])) {
        return null;
    }
    return $f;
}

set_time_limit(180);

switch ($acao) {
    case 'foto':
        $f = arquivo_recebido('arquivo');
        if ($f === null) {
            resposta_midia(['erro' => 'foto_ilegivel']);
        }
        $maximo = (int) ($entrada['maximo'] ?? 1600) === 800 ? 800 : 1600;
        resposta_midia(midia_foto_salvar((string) $f['tmp_name'], (string) $f['name'], $maximo));

    case 'video_iniciar':
        resposta_midia(midia_video_iniciar((int) ($entrada['tamanho'] ?? 0), (string) ($entrada['nome'] ?? '')));

    case 'video_estado':
        resposta_midia(midia_video_estado((string) ($entrada['envio'] ?? '')));

    case 'video_parte':
        $f = arquivo_recebido('arquivo');
        if ($f === null) {
            resposta_midia(['erro' => 'parte_invalida']);
        }
        resposta_midia(midia_video_parte((string) ($entrada['envio'] ?? ''), (int) ($entrada['indice'] ?? -1), (string) $f['tmp_name']));

    case 'video_concluir':
        resposta_midia(midia_video_concluir((string) ($entrada['envio'] ?? '')));

    case 'video_cancelar':
        midia_envio_apagar((string) ($entrada['envio'] ?? ''));
        resposta_midia([]);

    case 'video_descartar':
        resposta_midia(midia_descartar((string) ($entrada['arquivo'] ?? '')));

    case 'poster':
        $f = arquivo_recebido('arquivo');
        if ($f === null) {
            resposta_midia(['erro' => 'poster_falhou']);
        }
        resposta_midia(midia_poster_salvar((string) $f['tmp_name']));

    default:
        responder_json(['ok' => false, 'erro' => 'acao_invalida'], 400);
}
