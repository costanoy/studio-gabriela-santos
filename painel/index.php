<?php
/**
 * Painel da dona do studio. Esta página só monta a "casca" (login ou aplicativo).
 * Todo o resto é desenhado por painel.js, que só usa textContent (nada de HTML vindo dos dados).
 * A política de segurança do painel (CSP) bloqueia script e estilo escritos dentro da página.
 */
define('RAIZ', dirname(__DIR__));
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';
require_once RAIZ . '/nucleo/seguranca.php';

cabecalhos_painel();
header('Content-Type: text/html; charset=utf-8');

/** Cabeça comum das páginas do painel. $extra são tags já escapadas que entram antes do painel.js. */
function painel_cabeca(string $titulo, string $extra = ''): void
{
    $v = static fn(string $f): string => is_file(__DIR__ . '/' . $f) ? (string) filemtime(__DIR__ . '/' . $f) : '0';
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title><?= e($titulo) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="light">
<meta name="theme-color" content="#7D5843">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Painel">
<link rel="apple-touch-icon" href="../assets/favicon.png">
<link rel="stylesheet" href="painel.css?v=<?= e($v('painel.css')) ?>">
<?= $extra ?>
<script src="painel.js?v=<?= e($v('painel.js')) ?>" defer></script>
</head>
    <?php
}

/** Página simples com uma mensagem (usada quando o painel não está pronto). */
function painel_aviso(string $titulo, string $texto): never
{
    painel_cabeca($titulo . ' — Painel');
    ?>
<body class="login">
<main class="caixa-login">
  <h1><?= e($titulo) ?></h1>
  <p><?= e($texto) ?></p>
</main>
</body>
</html>
    <?php
    exit;
}

$cfg = config();
if (!$cfg['configurado']) {
    painel_aviso('Painel ainda não configurado', 'Peça ao Vinicius para terminar a configuração do painel. Seu site continua funcionando normalmente.');
}

sessao_iniciar();

// ---------------------------------------------------------------------------
// Entrar (formulário comum; funciona até sem JavaScript)
// ---------------------------------------------------------------------------
$mensagem_login = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $enviado = (string) ($_POST['csrf'] ?? '');
    if ($enviado === '' || !hash_equals(csrf_token(), $enviado)) {
        $mensagem_login = 'A página ficou aberta por muito tempo. Digite a senha de novo.';
    } else {
        $r = sessao_entrar((string) ($_POST['senha'] ?? ''));
        if (!empty($r['ok'])) {
            header('Location: ./', true, 303);
            exit;
        }
        if (($r['erro'] ?? '') === 'bloqueado') {
            $min = (int) ($r['espera_min'] ?? 15);
            $mensagem_login = 'Muitas tentativas. Espere ' . $min . ' ' . ($min === 1 ? 'minuto' : 'minutos') . ' ou fale com o Vinicius.';
        } else {
            $ja = count(tentativas_mexer(static fn($d) => $d)[tentativas_chave(ip_cliente())] ?? []);
            $restam = max(0, TENTATIVAS_MAX - $ja);
            $mensagem_login = $restam > 0
                ? 'Senha incorreta. Você ainda pode tentar ' . $restam . ($restam === 1 ? ' vez.' : ' vezes.')
                : 'Muitas tentativas. Espere 15 minutos ou fale com o Vinicius.';
        }
    }
}

if (!sessao_logada()) {
    painel_cabeca('Entrar — Painel do Studio Gabriela Santos');
    ?>
<body class="login">
<main class="caixa-login">
  <h1>Painel do site</h1>
  <p class="dica">Studio Gabriela Santos</p>
  <?php if ($mensagem_login !== ''): ?>
  <p class="alerta" role="alert"><span class="alerta-icone" aria-hidden="true">!</span><span id="msg-login"><?= e($mensagem_login) ?></span></p>
  <?php endif; ?>
  <form method="post" action="./" autocomplete="on">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label for="senha">Sua senha</label>
    <input id="senha" name="senha" type="password" autocomplete="current-password" required>
    <button type="button" id="ver-senha" class="botao botao-leve" aria-pressed="false">Mostrar senha</button>
    <button type="submit" class="botao botao-principal">Entrar</button>
  </form>
  <p class="rascunho-aviso" id="aviso-rascunho" hidden>Por segurança, entre de novo. Suas mudanças foram guardadas.</p>
</main>
</body>
</html>
    <?php
    exit;
}

// ---------------------------------------------------------------------------
// Aplicativo (já logada). O código de proteção vai numa meta, não em script escrito na página.
// ---------------------------------------------------------------------------
$vm = is_file(__DIR__ . '/midia.js') ? (string) filemtime(__DIR__ . '/midia.js') : '0';
painel_cabeca(
    'Painel — Studio Gabriela Santos',
    '<meta name="csrf" content="' . e(csrf_token()) . '">' . "\n"
    . '<script src="midia.js?v=' . e($vm) . '" defer></script>' . "\n"
);
?>
<body class="app">
<div id="app" class="app-raiz"><p class="carregando">Abrindo o painel…</p></div>
<noscript><p class="alerta">Para usar o painel, ative o JavaScript do navegador.</p></noscript>
</body>
</html>
