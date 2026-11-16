<?php
defined('RAIZ') || exit;

/**
 * Sessão e segurança do painel. Só o painel usa estas funções; o site público não abre sessão.
 */

/** Lançada no lugar de encerrar o script quando os testes ligam o gancho. */
final class RespostaJson extends RuntimeException
{
    public array $dados;
    public int $status;

    public function __construct(array $dados, int $status)
    {
        parent::__construct('resposta json ' . $status, $status);
        $this->dados = $dados;
        $this->status = $status;
    }
}

/** Hora atual. Os testes podem trocar por $GLOBALS['__nucleo_teste']['agora']. */
function seg_agora(): int
{
    return (int) ($GLOBALS['__nucleo_teste']['agora'] ?? time());
}

function sessao_nome(): string
{
    return config()['cookie_secure'] ? '__Host-sgs' : 'sgs';
}

function sessao_iniciar(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $cfg = config();
    $limite = $cfg['sessao_minutos'] * 60;

    $pasta = $cfg['dados_dir'] !== '' ? $cfg['dados_dir'] . '/sessoes' : sys_get_temp_dir() . '/sgs-sessoes';
    if (!is_dir($pasta)) {
        @mkdir($pasta, 0700, true);
    }
    session_save_path($pasta);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', (string) $limite);
    ini_set('session.cookie_lifetime', '0');
    session_name(sessao_nome());
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (bool) $cfg['cookie_secure'],
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    // Expira por inatividade: sem uso por mais que sessao_minutos, a sessão acaba
    $agora = seg_agora();
    if (isset($_SESSION['ultimo']) && $agora - (int) $_SESSION['ultimo'] > $limite) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    if (!empty($_SESSION['logado'])) {
        $_SESSION['ultimo'] = $agora;
    }
}

function sessao_logada(): bool
{
    sessao_iniciar();
    return !empty($_SESSION['logado']);
}

/** @return array{ok:bool,erro?:string,espera_min?:int} */
function sessao_entrar(string $senha): array
{
    sessao_iniciar();
    $hash = (string) config()['senha_hash'];
    if ($hash === '' || !password_verify($senha, $hash)) {
        return ['ok' => false, 'erro' => 'senha'];
    }
    // ID novo no login: um ID roubado antes do login não vale depois dele
    session_regenerate_id(true);
    $_SESSION = ['logado' => true, 'ultimo' => seg_agora()];
    return ['ok' => true];
}

function sessao_sair(): void
{
    sessao_iniciar();
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $p['path'],
            'secure' => $p['secure'],
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
    session_destroy();
}

function exigir_login_api(): void
{
    if (!sessao_logada()) {
        responder_json(['ok' => false, 'erro' => 'sessao_expirada'], 401);
    }
}

function responder_json(array $d, int $status = 200): never
{
    if (!empty($GLOBALS['__nucleo_teste']['sem_exit'])) {
        throw new RespostaJson($d, $status);
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    exit;
}

/** Linhas de cabeçalho das páginas e das APIs do painel. */
function cabecalhos_painel_lista(): array
{
    return [
        'X-Robots-Tag: noindex, nofollow',
        'Cache-Control: no-store',
        'X-Content-Type-Options: nosniff',
    ];
}

function cabecalhos_painel(): void
{
    if (headers_sent()) {
        return;
    }
    foreach (cabecalhos_painel_lista() as $linha) {
        header($linha);
    }
}
