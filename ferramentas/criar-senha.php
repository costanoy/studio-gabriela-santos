<?php
// Cria a configuração do painel.
//
//   php ferramentas/criar-senha.php --local
//       Gera uma senha aleatória para a prévia, grava .local/painel-config.php e .local/senha-teste.txt.
//
//   php ferramentas/criar-senha.php
//       Pede a senha real (digite e aperte Enter) e grava publicar/painel-config.php.
//       Esse arquivo vai para a pasta ACIMA do public_html, nunca dentro dele.
//
// Nenhuma senha nem hash fica no código. .local/ e publicar/ estão no .gitignore.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__);
$local = in_array('--local', $argv, true);

function gravar_seguro(string $arquivo, string $conteudo): void
{
    $pasta = dirname($arquivo);
    if (!is_dir($pasta) && !mkdir($pasta, 0775, true)) {
        fwrite(STDERR, "Não consegui criar a pasta $pasta\n");
        exit(1);
    }
    file_put_contents($arquivo, $conteudo, LOCK_EX);
    @chmod($arquivo, 0600);
}

if ($local) {
    $letras = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $senha = '';
    for ($i = 0; $i < 16; $i++) {
        $senha .= $letras[random_int(0, strlen($letras) - 1)];
    }
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $dados = str_replace('\\', '/', $raiz) . '/.local/painel-dados';
    foreach (['', '/sessoes', '/historico', '/lixeira', '/envios'] as $sub) {
        if (!is_dir($dados . $sub)) {
            mkdir($dados . $sub, 0775, true);
        }
    }
    $conteudo = "<?php\n// Gerado por ferramentas/criar-senha.php --local. Só para a prévia. Não vai para o Git.\nreturn "
        . var_export([
            'senha_hash' => $hash,
            'dados_dir' => $dados,
            'ambiente' => 'local',
            'cookie_secure' => false,
            'ip_cabecalho' => null,
            'proxies_confiaveis' => [],
            'sessao_minutos' => 120,
        ], true) . ";\n";
    gravar_seguro($raiz . '/.local/painel-config.php', $conteudo);
    gravar_seguro($raiz . '/.local/senha-teste.txt', $senha . "\n");
    echo "Pronto. Configuração em .local/painel-config.php\n";
    echo "A senha de teste está em .local/senha-teste.txt (não vai para o Git).\n";
    exit(0);
}

fwrite(STDOUT, "Digite a senha do painel (mínimo 12 caracteres) e aperte Enter: ");
$senha = rtrim((string) fgets(STDIN), "\r\n");
if (mb_strlen($senha) < 12) {
    fwrite(STDERR, "Senha curta demais. Use pelo menos 12 caracteres.\n");
    exit(1);
}
$hash = password_hash($senha, PASSWORD_DEFAULT);
$conteudo = "<?php\n// Gerado por ferramentas/criar-senha.php. Suba para a pasta ACIMA do public_html.\n"
    . "// Os dados do painel ficam em painel-dados/, ao lado deste arquivo.\n"
    . "return [\n"
    . "    'senha_hash' => " . var_export($hash, true) . ",\n"
    . "    'dados_dir' => __DIR__ . '/painel-dados',\n"
    . "    'ambiente' => 'producao',\n"
    . "    'cookie_secure' => true,\n"
    . "    'ip_cabecalho' => null,          // na Hostinger com CDN: 'HTTP_X_FORWARDED_FOR' (veja o diagnóstico do painel)\n"
    . "    'proxies_confiaveis' => [],      // IPs ou faixas (CIDR) do proxy\n"
    . "    'sessao_minutos' => 120,\n"
    . "];\n";
gravar_seguro($raiz . '/publicar/painel-config.php', $conteudo);
echo "Pronto. Arquivo em publicar/painel-config.php (não vai para o Git).\n";
echo "Envie para a pasta acima do public_html, com o nome painel-config.php.\n";
