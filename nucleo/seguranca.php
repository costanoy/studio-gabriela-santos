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

const SESSAO_MAXIMO_SEGUNDOS = 43200; // 12 horas

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
    // Tempo máximo absoluto: mesmo usando sem parar, a sessão acaba 12 horas depois da entrada
    if (!empty($_SESSION['logado']) && isset($_SESSION['inicio']) && $agora - (int) $_SESSION['inicio'] > SESSAO_MAXIMO_SEGUNDOS) {
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
    $ip = tentativas_chave(ip_cliente());
    // Confere e registra a tentativa na MESMA trava, antes de testar a senha:
    // pedidos ao mesmo tempo não conseguem passar do limite. Se a senha estiver certa, a marca sai logo depois.
    $r = tentativas_reservar($ip);
    if (!$r['ok']) {
        return ['ok' => false, 'erro' => 'bloqueado', 'espera_min' => $r['espera_min']];
    }
    $hash = (string) config()['senha_hash'];
    if ($hash === '' || !password_verify($senha, $hash)) {
        return ['ok' => false, 'erro' => 'senha'];
    }
    tentativas_limpar($ip);
    // ID novo no login: um ID roubado antes do login não vale depois dele
    session_regenerate_id(true);
    $_SESSION = ['logado' => true, 'ultimo' => seg_agora(), 'inicio' => seg_agora()];
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
        // Só código do próprio site. Sem scripts ou estilos escritos na página (nada de inline).
        "Content-Security-Policy: default-src 'self'; img-src 'self' blob: data:; media-src 'self' blob:; script-src 'self'; style-src 'self'; frame-ancestors 'none'",
    ];
}

/** Código de proteção da sessão. A página do painel o recebe e o devolve no cabeçalho X-CSRF. */
function csrf_token(): string
{
    sessao_iniciar();
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Barra com 403 todo pedido que muda dados sem o X-CSRF certo. Chame depois de exigir_login_api(). */
function csrf_verificar(): void
{
    $enviado = (string) ($_SERVER['HTTP_X_CSRF'] ?? '');
    sessao_iniciar();
    $esperado = (string) ($_SESSION['csrf'] ?? '');
    if ($esperado === '' || $enviado === '' || !hash_equals($esperado, $enviado)) {
        responder_json(['ok' => false, 'erro' => 'csrf'], 403);
    }
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

// ---------------------------------------------------------------------------
// Limite de tentativas de login e IP do visitante (T10)
// ---------------------------------------------------------------------------

const TENTATIVAS_MAX = 5;
const TENTATIVAS_JANELA = 900; // 15 minutos

/**
 * true se o IP está na lista. Cada item pode ser um IP exato ("10.0.0.1") ou uma faixa CIDR ("10.0.0.0/8", "2001:db8::/32").
 */
function ip_em_lista(string $ip, array $lista): bool
{
    $bin = @inet_pton($ip);
    if ($bin === false) {
        return false;
    }
    foreach ($lista as $item) {
        $item = trim((string) $item);
        if ($item === '') {
            continue;
        }
        if (strpos($item, '/') === false) {
            if ($item === $ip || (@inet_pton($item) !== false && @inet_pton($item) === $bin)) {
                return true;
            }
            continue;
        }
        [$base, $bits] = explode('/', $item, 2);
        $b = @inet_pton($base);
        if ($b === false || strlen($b) !== strlen($bin) || !ctype_digit($bits) || (int) $bits > strlen($b) * 8) {
            continue;
        }
        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        if ($bytes > 0 && substr($bin, 0, $bytes) !== substr($b, 0, $bytes)) {
            continue;
        }
        $resto = $bits % 8;
        if ($resto === 0) {
            return true;
        }
        $mascara = (0xFF << (8 - $resto)) & 0xFF;
        if ((ord($bin[$bytes]) & $mascara) === (ord($b[$bytes]) & $mascara)) {
            return true;
        }
    }
    return false;
}

/**
 * IP de quem fez o pedido. O cabeçalho de proxy (ex.: X-Forwarded-For) só vale quando o pedido
 * veio de um proxy listado em proxies_confiaveis; senão qualquer um poderia inventar o próprio IP.
 */
function ip_cliente(): string
{
    $cfg = config();
    $remoto = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $cab = $cfg['ip_cabecalho'] ?? null;
    if ($cab && ip_em_lista($remoto, $cfg['proxies_confiaveis'])) {
        $valor = (string) ($_SERVER[$cab] ?? '');
        // da direita para a esquerda: o primeiro endereço que não é proxy confiável
        $lista = array_reverse(array_map('trim', explode(',', $valor)));
        foreach ($lista as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP) && !ip_em_lista($ip, $cfg['proxies_confiaveis'])) {
                return $ip;
            }
        }
    }
    return filter_var($remoto, FILTER_VALIDATE_IP) ? $remoto : '0.0.0.0';
}

function tentativas_arquivo(): string
{
    return config()['dados_dir'] . '/tentativas.json';
}

/** Lê, muda e grava o arquivo de tentativas com trava. $f recebe a lista e devolve a nova. */
function tentativas_mexer(callable $f): array
{
    $dir = config()['dados_dir'];
    if ($dir === '') {
        return [];
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $h = fopen(tentativas_arquivo(), 'c+');
    if (!$h) {
        return [];
    }
    flock($h, LOCK_EX);
    $dados = json_decode((string) stream_get_contents($h), true);
    $dados = is_array($dados) ? $dados : [];
    $limite = seg_agora() - TENTATIVAS_JANELA;
    foreach ($dados as $ip => $horas) {
        $dados[$ip] = array_values(array_filter((array) $horas, static fn($t) => (int) $t > $limite));
        if (!$dados[$ip]) {
            unset($dados[$ip]);
        }
    }
    $dados = $f($dados);
    ftruncate($h, 0);
    rewind($h);
    fwrite($h, json_encode($dados));
    fflush($h);
    flock($h, LOCK_UN);
    fclose($h);
    return $dados;
}

/**
 * Chave de contagem das tentativas. IPv4 vale por inteiro; IPv6 vale pela rede /64
 * (quem recebe um /64 inteiro troca de endereço à vontade, então contar o endereço não adianta).
 */
function tentativas_chave(string $ip): string
{
    $bin = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? @inet_pton($ip) : false;
    if ($bin === false || strlen($bin) !== 16) {
        return $ip;
    }
    // IPv4 escrito como IPv6 (::ffff:a.b.c.d) conta como o IPv4
    if (substr($bin, 0, 12) === "\0\0\0\0\0\0\0\0\0\0\xff\xff") {
        return (string) inet_ntop(substr($bin, 12));
    }
    return bin2hex(substr($bin, 0, 8)) . '::/64';
}

/**
 * Confere e já registra uma tentativa, tudo dentro da mesma trava do arquivo.
 * @return array{ok:bool,espera_min:int}
 */
function tentativas_reservar(string $chave): array
{
    $bloqueado = false;
    $dados = tentativas_mexer(static function ($d) use ($chave, &$bloqueado) {
        if (count($d[$chave] ?? []) >= TENTATIVAS_MAX) {
            $bloqueado = true;
            return $d;
        }
        $d[$chave][] = seg_agora();
        return $d;
    });
    if (!$bloqueado) {
        return ['ok' => true, 'espera_min' => 0];
    }
    $horas = $dados[$chave] ?? [];
    sort($horas);
    $libera = (int) $horas[count($horas) - TENTATIVAS_MAX] + TENTATIVAS_JANELA;
    return ['ok' => false, 'espera_min' => max(1, (int) ceil(($libera - seg_agora()) / 60))];
}

/** true se este IP ainda pode tentar entrar (menos de 5 erros nos últimos 15 minutos). */
function tentativas_ok(string $ip): bool
{
    $dados = tentativas_mexer(static fn($d) => $d);
    return count($dados[$ip] ?? []) < TENTATIVAS_MAX;
}

function tentativas_registrar(string $ip): void
{
    tentativas_mexer(static function ($d) use ($ip) {
        $d[$ip][] = seg_agora();
        return $d;
    });
}

function tentativas_limpar(string $ip): void
{
    tentativas_mexer(static function ($d) use ($ip) {
        unset($d[$ip]);
        return $d;
    });
}

/** Minutos até o IP poder tentar de novo. */
function tentativas_espera_min(string $ip): int
{
    $dados = tentativas_mexer(static fn($d) => $d);
    $horas = $dados[$ip] ?? [];
    if (count($horas) < TENTATIVAS_MAX) {
        return 0;
    }
    sort($horas);
    $libera = (int) $horas[count($horas) - TENTATIVAS_MAX] + TENTATIVAS_JANELA;
    return max(1, (int) ceil(($libera - seg_agora()) / 60));
}
