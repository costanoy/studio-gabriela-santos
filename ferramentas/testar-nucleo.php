<?php
// Testes do núcleo PHP (sem navegador). Uso: php ferramentas/testar-nucleo.php
// Cada bloco é de um item do Lote 1. Sai com código 1 se algo falhar.
define('RAIZ', dirname(__DIR__));
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';
require_once RAIZ . '/nucleo/seguranca.php';
ob_start(); // sessões mandam cabeçalhos: nada sai antes do fim

$ok = 0;
$falhas = [];
function t(bool $cond, string $nome): void
{
    global $ok, $falhas;
    if ($cond) {
        $ok++;
        echo "  ok   $nome\n";
    } else {
        $falhas[] = $nome;
        echo "  FALHA $nome\n";
    }
}

function renderizar(string $pagina, array $c): string
{
    ob_start();
    parcial('head', ['c' => $c, 'pagina' => $pagina]);
    parcial('cabecalho', ['c' => $c, 'pagina' => $pagina]);
    parcial('rodape', ['c' => $c, 'pagina' => $pagina]);
    return (string) ob_get_clean();
}

echo "L15 mesclagem\n";
$padrao = conteudo_padrao();
t(($padrao['versao_esquema'] ?? 0) === 1, 'padrão tem versao_esquema 1');
$m = conteudo_mesclar(['a' => ['x' => 1, 'y' => 2], 'l' => [1, 2, 3], 'so_padrao' => 'p'], ['a' => ['x' => 9], 'l' => [7]]);
t($m['a'] === ['x' => 9, 'y' => 2], 'objetos mesclam por chave');
t($m['l'] === [7], 'listas do editado substituem inteiras');
t($m['so_padrao'] === 'p', 'chave ausente no editado vem do padrão');

echo "\nL15 escape\n";
t(e('<b>"a" & \'b\'</b>') === '&lt;b&gt;&quot;a&quot; &amp; &#039;b&#039;&lt;/b&gt;', 'e() escapa < > " & \'');
t(e(null) === '', 'e(null) devolve vazio');
$ld = jsonld(['x' => '</script><script>alert(1)</script>']);
t(stripos($ld, '</script') === false, 'jsonld não deixa </script> passar');
t(json_decode($ld, true)['x'] === '</script><script>alert(1)</script>', 'jsonld continua JSON válido');

$c = $padrao;
$c['contato']['endereco_curto'] = '<b>Rua "X" & Y</b>';
$html = renderizar('home', $c);
t(strpos($html, '<b>Rua') === false && strpos($html, '&lt;b&gt;Rua') !== false, 'texto do JSON sai escapado no rodapé');

echo "\nL15 resultados\n";
$h = resultados_home($padrao);
t(count($h) === 5, 'home mostra 5 comparações');
t(count(array_filter($h, fn($i) => $i['tipo'] === 'comparacao')) === 5, 'home só tem comparações');
$g = resultados_por_categoria($padrao);
t(count($g) === 5, 'resultados tem 5 categorias');
t(array_sum(array_map(fn($x) => count($x['itens']), $g)) === 11, 'resultados tem 11 itens');
t(array_column($g, 'colunas') === [3, 2, 2, 2, 2] || array_column($g, 'colunas') === [3, 2, 2, 2, 2], 'colunas = min(itens, 3)');

$c2 = $padrao;
foreach ($c2['resultados']['itens'] as &$i) {
    if ($i['categoria'] === $c2['resultados']['categorias'][1]['id']) {
        $i['em_resultados'] = false;
    }
}
unset($i);
t(count(resultados_por_categoria($c2)) === 4, 'categoria sem itens visíveis some');

$c3 = $padrao;
$c3['resultados']['itens'][0]['na_home'] = false;
t(count(resultados_home($c3)) === 4, 'item com na_home falso sai da home');

echo "\nL07 contato em um só lugar\n";
$c4 = $padrao;
$c4['contato']['horario'] = 'Horário de teste 9h';
$c4['contato']['whatsapp'] = '5511999990000';
$h4 = renderizar('home', $c4);
t(substr_count($h4, 'Horário de teste 9h') === 1, 'horário do JSON aparece no rodapé');
t(strpos($h4, '5562984141116') === false && strpos($h4, '5511999990000') !== false, 'link do WhatsApp usa contato.whatsapp');
t(strpos($h4, '8h às 17h') === false, 'nenhum horário escrito à mão sobra');

echo "\nT06 canonical, sitemap e 404\n";
preg_match('#<link rel="canonical" href="([^"]+)"#', renderizar('home', $padrao), $mh);
preg_match('#<link rel="canonical" href="([^"]+)"#', renderizar('resultados', $padrao), $mr);
t(($mh[1] ?? '') === 'https://studiogabrielasantos.com.br/', 'canonical da home é a raiz');
t(($mr[1] ?? '') === 'https://studiogabrielasantos.com.br/resultados.html', 'canonical de resultados é /resultados.html');
$sitemap = (string) file_get_contents(RAIZ . '/sitemap.xml');
t(strpos($sitemap, '<loc>' . $mh[1] . '</loc>') !== false && strpos($sitemap, '<loc>' . $mr[1] . '</loc>') !== false, 'sitemap tem as mesmas duas URLs dos canonical');
t(substr_count($sitemap, '<loc>') === 2, 'sitemap tem só 2 URLs');
$htaccess = (string) file_get_contents(RAIZ . '/.htaccess');
t(strpos($htaccess, 'ErrorDocument 404 /404.php') !== false, '.htaccess aponta o 404 para 404.php');
t(strpos($htaccess, 'index\.(html|php)$ / [R=301') !== false && strpos($htaccess, 'resultados\.php$ /resultados.html [R=301') !== false, '.htaccess: /index.php e /resultados.php dão 301');
t(is_file(RAIZ . '/404.php'), '404.php existe');

echo "\nT05 .htaccess e .user.ini\n";
t(strpos($htaccess, 'Options -Indexes') !== false, 'Options -Indexes');
t(strpos($htaccess, 'RewriteCond %{HTTP_HOST} ^www\.') !== false && strpos($htaccess, 'https://studiogabrielasantos.com.br%{REQUEST_URI} [R=301') !== false, 'www e http vão para https sem www (301)');
t(strpos($htaccess, 'index\.(html|php)$ / [R=301') !== false, '/index.html e /index.php dão 301 para /');
t(strpos($htaccess, 'X-Content-Type-Options "nosniff"') !== false && strpos($htaccess, 'Referrer-Policy') !== false && strpos($htaccess, 'X-Frame-Options') !== false, 'cabeçalhos de segurança');
t(strpos($htaccess, 'max-age=2592000') !== false && strpos($htaccess, '"no-cache"') !== false, 'Cache-Control por tipo (mídia 30 dias, páginas no-cache)');
t(preg_match('/\(nucleo\|parciais\|dados\|ferramentas/', $htaccess) === 1, 'pastas de código e dados bloqueadas na web');
$ini = (string) file_get_contents(RAIZ . '/.user.ini');
t(strpos($ini, 'upload_max_filesize=64M') !== false && strpos($ini, 'post_max_size=70M') !== false && strpos($ini, 'memory_limit=256M') !== false && strpos($ini, 'max_execution_time=120') !== false, '.user.ini com os 4 limites');

echo "\nT14 conteudo/ sem execução e dados fora do public_html\n";
$hc = (string) file_get_contents(RAIZ . '/conteudo/.htaccess');
$hm = (string) file_get_contents(RAIZ . '/conteudo/midia/.htaccess');
t(strpos($hc, 'Require all denied') !== false && strpos($hc, 'php_flag engine off') !== false && strpos($hc, 'RemoveHandler') !== false, 'conteudo/.htaccess nega scripts e desliga o PHP');
t(strpos($hm, 'webp|jpe?g|png|mp4') !== false && strpos($hm, 'Require all denied') !== false, 'conteudo/midia/.htaccess só libera webp, jpg, png e mp4');
t(strpos($hm, 'Options -ExecCGI -Indexes') !== false, 'conteudo/midia/.htaccess sem CGI e sem lista de arquivos');
$ig = (string) shell_exec('git -C ' . escapeshellarg(RAIZ) . ' check-ignore conteudo/midia/.htaccess conteudo/midia/foto.webp 2>&1');
t(strpos($ig, 'foto.webp') !== false && strpos($ig, 'midia/.htaccess') === false, '.gitignore deixa o .htaccess da mídia entrar e a mídia enviada fica de fora');
config_definir(['senha_hash' => 'x', 'dados_dir' => sys_get_temp_dir() . '/dados-teste']);
t(strpos(str_replace('\\', '/', config()['dados_dir']), str_replace('\\', '/', RAIZ)) !== 0, 'dados_dir de teste fica fora da pasta do site');
config_definir(null);
t(config()['dados_dir'] === '' || strpos(config()['dados_dir'], RAIZ) !== 0, 'padrão não coloca dados dentro do site');
$cfg_php = (string) file_get_contents(RAIZ . '/nucleo/config.php');
t(strpos($cfg_php, "dirname(RAIZ) . '/painel-config.php'") !== false, 'config de produção fica uma pasta acima do site');

echo "\nT09 sessão do painel\n";
$tmp = sys_get_temp_dir() . '/sgs-teste-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);
$senha_teste = bin2hex(random_bytes(12));
$cfg_teste = ['senha_hash' => password_hash($senha_teste, PASSWORD_DEFAULT), 'dados_dir' => $tmp, 'cookie_secure' => false, 'sessao_minutos' => 120];
config_definir($cfg_teste);
$GLOBALS['__nucleo_teste'] = ['sem_exit' => true, 'agora' => 1000000];

t(config()['sessao_minutos'] === 120, 'sessão expira com 120 minutos sem uso');
sessao_iniciar();
$cp = session_get_cookie_params();
t($cp['httponly'] === true, 'cookie HttpOnly');
t($cp['samesite'] === 'Strict', 'cookie SameSite=Strict');
t($cp['path'] === '/' && $cp['lifetime'] === 0, 'cookie de sessão (some ao fechar o navegador)');
t(ini_get('session.use_strict_mode') === '1', 'use_strict_mode ligado');
t(str_replace('\\', '/', session_save_path()) === str_replace('\\', '/', $tmp) . '/sessoes', 'sessões guardadas em dados_dir/sessoes');
t(session_name() === 'sgs', 'nome do cookie sem __Host- quando não é https (prévia)');
t(sessao_logada() === false, 'sem login, não está logada');

$id_antes = session_id();
$r = sessao_entrar('senha-errada');
t($r['ok'] === false && $r['erro'] === 'senha' && !sessao_logada(), 'senha errada não entra');
$r = sessao_entrar($senha_teste);
t($r === ['ok' => true] && sessao_logada(), 'senha certa entra');
t(session_id() !== $id_antes, 'ID de sessão novo no login');
$id_logado = session_id();
session_write_close();

// 119 min depois ainda vale, 121 min depois acabou
$GLOBALS['__nucleo_teste']['agora'] = 1000000 + 119 * 60;
session_id($id_logado);
sessao_iniciar();
t(sessao_logada(), 'com 119 min sem uso ainda está logada');
session_write_close();
$GLOBALS['__nucleo_teste']['agora'] = 1000000 + 119 * 60 + 121 * 60;
session_id($id_logado);
sessao_iniciar();
t(!sessao_logada(), 'com 121 min sem uso a sessão acabou');
t(session_id() !== $id_logado, 'a sessão vencida troca de ID');
try {
    exigir_login_api();
    t(false, 'exigir_login_api responde 401 sem sessão');
} catch (RespostaJson $x) {
    t($x->status === 401 && ($x->dados['erro'] ?? '') === 'sessao_expirada', 'exigir_login_api responde 401 com erro sessao_expirada');
}
sessao_entrar($senha_teste);
sessao_sair();
t(session_status() === PHP_SESSION_NONE, 'sair encerra a sessão');
$cab = implode("\n", cabecalhos_painel_lista());
t(strpos($cab, 'X-Robots-Tag: noindex') !== false && strpos($cab, 'Cache-Control: no-store') !== false, '/painel/ com noindex e no-store');
config_definir(array_merge($cfg_teste, ['cookie_secure' => true]));
t(sessao_nome() === '__Host-sgs', 'com https o cookie usa o prefixo __Host- e Secure');
config_definir(['senha_hash' => '', 'dados_dir' => $tmp]);
t(sessao_entrar('qualquer')['ok'] === false, 'sem senha configurada ninguém entra');

echo "\nT10 limite de tentativas e IP atrás da CDN\n";
$cfg_t10 = array_merge($cfg_teste, ['ip_cabecalho' => 'HTTP_X_FORWARDED_FOR', 'proxies_confiaveis' => ['10.0.0.1']]);
config_definir($cfg_t10);
$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.7';
t(ip_cliente() === '203.0.113.9', 'cabeçalho de proxy é ignorado se REMOTE_ADDR não é proxy confiável');
$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
t(ip_cliente() === '198.51.100.7', 'REMOTE_ADDR confiável: usa o IP do cabeçalho');
$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 198.51.100.7, 10.0.0.1';
t(ip_cliente() === '198.51.100.7', 'lista do cabeçalho: lê da direita e pula proxy confiável (IP inventado à esquerda não vale)');
$_SERVER['HTTP_X_FORWARDED_FOR'] = 'lixo';
t(ip_cliente() === '10.0.0.1', 'cabeçalho inválido volta para REMOTE_ADDR');

$_SERVER['REMOTE_ADDR'] = '203.0.113.50';
unset($_SERVER['HTTP_X_FORWARDED_FOR']);
$GLOBALS['__nucleo_teste']['agora'] = 5000000;
for ($i = 1; $i <= 5; $i++) {
    $r = sessao_entrar('errada' . $i);
    t($r['ok'] === false && $r['erro'] === 'senha', "tentativa errada $i volta senha incorreta");
}
$r = sessao_entrar($senha_teste);
t($r['ok'] === false && $r['erro'] === 'bloqueado' && $r['espera_min'] >= 1 && $r['espera_min'] <= 15, 'a 6ª tentativa em 15 min é bloqueada, mesmo com a senha certa');
$_SERVER['REMOTE_ADDR'] = '203.0.113.51';
t(sessao_entrar($senha_teste)['ok'] === true, 'outro IP não é afetado pelo bloqueio');
sessao_sair();
$_SERVER['REMOTE_ADDR'] = '203.0.113.50';
$GLOBALS['__nucleo_teste']['agora'] = 5000000 + 901;
t(sessao_entrar($senha_teste)['ok'] === true, 'passados 15 min o IP volta a poder entrar');
sessao_sair();
$GLOBALS['__nucleo_teste']['agora'] = 6000000;
for ($i = 0; $i < 4; $i++) {
    sessao_entrar('errada');
}
t(sessao_entrar($senha_teste)['ok'] === true, 'com 4 erros ainda entra');
sessao_sair();
for ($i = 0; $i < 4; $i++) {
    sessao_entrar('errada');
}
t(sessao_entrar($senha_teste)['ok'] === true, 'sucesso zera a contagem: mais 4 erros depois ainda não bloqueiam');
sessao_sair();
sessao_entrar('errada');
t(strpos((string) file_get_contents($tmp . '/tentativas.json'), '203.0.113.50') !== false, 'tentativas ficam em dados_dir/tentativas.json');
unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR']);
config_definir(null);

echo "\nT11 CSRF, CSP e escape\n";
config_definir($cfg_teste);
$GLOBALS['__nucleo_teste'] = ['sem_exit' => true, 'agora' => 7000000];
$_SERVER['REMOTE_ADDR'] = '203.0.113.77';
sessao_entrar($senha_teste);
$token = csrf_token();
t(strlen($token) === 64 && csrf_token() === $token, 'csrf_token gera 64 caracteres e é estável na sessão');
unset($_SERVER['HTTP_X_CSRF']);
try {
    csrf_verificar();
    t(false, 'POST sem X-CSRF dá 403');
} catch (RespostaJson $x) {
    t($x->status === 403, 'POST sem X-CSRF dá 403');
}
$_SERVER['HTTP_X_CSRF'] = 'errado';
try {
    csrf_verificar();
    t(false, 'POST com X-CSRF errado dá 403');
} catch (RespostaJson $x) {
    t($x->status === 403, 'POST com X-CSRF errado dá 403');
}
$_SERVER['HTTP_X_CSRF'] = $token;
$passou = true;
try {
    csrf_verificar();
} catch (RespostaJson $x) {
    $passou = false;
}
t($passou, 'POST com o X-CSRF certo passa');
$cab = implode("\n", cabecalhos_painel_lista());
t(strpos($cab, "script-src 'self'") !== false && strpos($cab, "frame-ancestors 'none'") !== false && strpos($cab, "style-src 'self'") !== false && strpos($cab, "img-src 'self' blob: data:") !== false && strpos($cab, "media-src 'self' blob:") !== false, 'CSP do painel sem inline e sem iframe');
sessao_sair();
unset($_SERVER['HTTP_X_CSRF'], $_SERVER['REMOTE_ADDR']);

$xss = '<script>alert(1)</script><img src=x onerror=alert(2)>';
$cx = $padrao;
$cx['contato']['endereco_curto'] = $xss;
$cx['contato']['horario'] = $xss;
$cx['seo']['titulo'] = $xss;
$cx['seo']['descricao'] = $xss;
$hx = renderizar('home', $cx);
t(strpos($hx, '<script>alert(1)') === false && strpos($hx, '<img src=x') === false, 'payload XSS nos textos sai escapado, sem tag ativa');
t(strpos($hx, '&lt;script&gt;alert(1)') !== false, 'o payload aparece como texto escapado');
config_definir(null);

echo "\nL14 revisão, conflito, histórico e lixeira\n";
$midia_t = $tmp . '/site/conteudo/midia';
mkdir($midia_t, 0700, true);
config_definir(array_merge($cfg_teste, ['midia_dir' => $midia_t, 'midia_url' => 'conteudo/midia']));
$GLOBALS['__nucleo_teste'] = ['sem_exit' => true, 'agora' => 8000000];
conteudo_cache_limpar();
$base = conteudo_padrao();
t((int) (conteudo_ler()['revisao'] ?? -1) === 0, 'sem site.json a revisão é 0');
$r = conteudo_salvar($base, 0, 'primeiro');
t($r === ['ok' => true, 'revisao' => 1], 'salvar com a revisão certa devolve ok e revisao 1');
t(is_file($tmp . '/site.json') && (int) json_decode((string) file_get_contents($tmp . '/site.json'), true)['revisao'] === 1, 'site.json gravado com revisao 1');
t(conteudo_ler()['revisao'] === 1, 'conteudo_ler enxerga a revisão nova');
$r = conteudo_salvar($base, 0, 'de outra tela');
t($r === ['ok' => false, 'erro' => 'conflito'], 'salvar com revisão velha dá conflito (a API responde 409)');
t((int) json_decode((string) file_get_contents($tmp . '/site.json'), true)['revisao'] === 1, 'o conflito não grava nada');
$ruim = $base;
$ruim['resultados']['itens'][0]['ordem'] = -5;
$r = conteudo_salvar($ruim, 1, 'ruim');
t($r['ok'] === false && $r['erro'] === 'invalido' && !empty($r['detalhes']), 'conteúdo inválido volta invalido com detalhes');
t(is_file($tmp . '/historico/0001.json') && json_decode((string) file_get_contents($tmp . '/historico/0001.json'), true)['resumo'] === 'primeiro', 'versão guardada em historico/NNNN.json com resumo');

// mídia: a.webp entra na versão 2 e sai na 3; só vai para a lixeira quando nenhuma das 20 versões a usa
@mkdir($midia_t . '/2026', 0700, true);
file_put_contents($midia_t . '/2026/aaaaaaaaaaaaaaaa.webp', 'x');
$ca = $base;
$ca['resultados']['itens'][0]['arquivo'] = 'conteudo/midia/2026/aaaaaaaaaaaaaaaa.webp';
$r = conteudo_salvar($ca, 1, 'com a.webp');
t($r['ok'] && $r['revisao'] === 2, 'salvar versão com mídia nova');
$r = conteudo_salvar($base, 2, 'sem a.webp');
t(!is_file($midia_t . '/2026/aaaaaaaaaaaaaaaa.webp') && is_file($tmp . '/midia-privada/2026/aaaaaaaaaaaaaaaa.webp') && !lixeira_listar(), 'mídia que o site não usa mais sai do public_html e fica na área privada, não na lixeira');
$rev = 3;
for ($i = 0; $i < 25; $i++) {
    $b = $base;
    $b['seo']['titulo'] = 'Título ' . $i;
    $r = conteudo_salvar($b, $rev, 'ciclo ' . $i);
    $rev = $r['revisao'];
}
t(count(conteudo_versoes()) === 20, 'histórico mantém só as 20 últimas versões');
t(conteudo_versoes()[0]['n'] === $rev, 'a versão mais nova vem primeiro');
t(!is_file($midia_t . '/2026/aaaaaaaaaaaaaaaa.webp') && !is_file($tmp . '/midia-privada/2026/aaaaaaaaaaaaaaaa.webp'), 'mídia que nenhuma versão usa mais sai da pasta de mídia e da área privada');
$lx = lixeira_listar();
t(count($lx) === 1 && $lx[0]['arquivo'] === 'conteudo/midia/2026/aaaaaaaaaaaaaaaa.webp' && preg_match('/^\d{8}-\d{6}-[0-9a-f]+$/', $lx[0]['id']) === 1, 'a mídia está na lixeira com meta (id Ymd-His-hex)');
t(is_file($tmp . '/lixeira/' . $lx[0]['id'] . '/meta.json'), 'lixeira/<id>/meta.json existe');
t(lixeira_mover('assets/hero-bg.webp') === null && is_file(RAIZ . '/assets/hero-bg.webp'), 'assets/ nunca vai para a lixeira');
t(lixeira_mover('conteudo/midia/../../index.php') === null && is_file(RAIZ . '/index.php'), 'caminho com .. é recusado');
t(lixeira_restaurar($lx[0]['id']) === true && is_file($midia_t . '/2026/aaaaaaaaaaaaaaaa.webp') && !lixeira_listar(), 'lixeira_restaurar devolve a mídia ao lugar');
lixeira_mover('conteudo/midia/2026/aaaaaaaaaaaaaaaa.webp');
$GLOBALS['__nucleo_teste']['agora'] += 29 * 86400;
t(lixeira_esvaziar(30) === 0 && count(lixeira_listar()) === 1, 'lixeira guarda o que tem menos de 30 dias');
$GLOBALS['__nucleo_teste']['agora'] += 2 * 86400;
t(lixeira_esvaziar(30) === 1 && !lixeira_listar(), 'lixeira_esvaziar(30) apaga o que passou de 30 dias');

$vs = conteudo_versoes();
$alvo = $vs[count($vs) - 1]['n'];
$antes = $rev;
$titulo_alvo = json_decode((string) file_get_contents($tmp . '/historico/' . sprintf('%04d', $alvo) . '.json'), true)['conteudo']['seo']['titulo'];
$r = conteudo_restaurar($alvo, $antes - 1);
t($r === ['ok' => false, 'erro' => 'conflito'], 'restaurar com revisão velha dá conflito');
$r = conteudo_restaurar($alvo, $antes);
t($r['ok'] === true && $r['revisao'] === $antes + 1, 'restaurar vira uma versão nova');
t(conteudo_restaurar(99999, $antes + 1)['erro'] === 'nao_encontrada', 'restaurar versão que não existe');
t(conteudo_ler()['seo']['titulo'] === $titulo_alvo, 'o conteúdo voltou ao da versão escolhida');

// o payload XSS salvo sai escapado no site
$cx2 = conteudo_ler();
$cx2['contato']['endereco_curto'] = $xss;
$r = conteudo_salvar($cx2, $cx2['revisao'], 'xss');
t($r['ok'] === false && $r['erro'] === 'invalido', 'salvar texto com tag HTML é recusado pela validação');
// mesmo que um site.json chegue com tag (editado à mão), o site escapa na saída
$no_disco = json_decode((string) file_get_contents($tmp . '/site.json'), true);
$no_disco['contato']['endereco_curto'] = $xss;
file_put_contents($tmp . '/site.json', json_encode($no_disco));
conteudo_cache_limpar();
$hs = renderizar('home', conteudo_ler());
t(strpos($hs, '<script>alert(1)') === false && strpos($hs, '&lt;script&gt;alert(1)') !== false, 'payload XSS salvo aparece escapado no site');
config_definir(null);
conteudo_cache_limpar();

echo "\nP1-1 mídia fora do ar guardada em área privada\n";
config_definir(array_merge($cfg_teste, ['midia_dir' => $midia_t, 'midia_url' => 'conteudo/midia']));
$GLOBALS['__nucleo_teste'] = ['sem_exit' => true, 'agora' => 8500000];
conteudo_cache_limpar();
$FA = 'conteudo/midia/2026/bbbbbbbbbbbbbbbb.webp';
$FB = 'conteudo/midia/2026/cccccccccccccccc.jpg';
file_put_contents($midia_t . '/2026/bbbbbbbbbbbbbbbb.webp', 'foto da dona A');
file_put_contents($midia_t . '/2026/cccccccccccccccc.jpg', 'foto da dona B');
$revp = (int) conteudo_ler()['revisao'];
$cp = conteudo_padrao();
$cp['resultados']['itens'][0]['antes']['arquivo'] = $FA;
$cp['resultados']['itens'][0]['depois']['arquivo'] = $FB;
$r = conteudo_salvar($cp, $revp, 'com A e B');
t($r['ok'] === true && is_file($midia_t . '/2026/bbbbbbbbbbbbbbbb.webp') && is_file($midia_t . '/2026/cccccccccccccccc.jpg'), 'mídia que o conteúdo usa continua no ar');
$rev_com = $r['revisao'];
$cp2 = conteudo_ler();
$cp2['resultados']['itens'][0]['antes']['arquivo'] = 'assets/hero-bg.webp';
$r = conteudo_salvar($cp2, $rev_com, 'sem A');
t($r['ok'] === true && !is_file($midia_t . '/2026/bbbbbbbbbbbbbbbb.webp'), 'foto tirada do site some do public_html logo ao salvar');
t(is_file($tmp . '/midia-privada/2026/bbbbbbbbbbbbbbbb.webp') && file_get_contents($tmp . '/midia-privada/2026/bbbbbbbbbbbbbbbb.webp') === 'foto da dona A', 'a foto fica guardada na área privada, dentro de dados_dir');
t(is_file($midia_t . '/2026/cccccccccccccccc.jpg') && !lixeira_listar(), 'foto que o conteúdo ainda usa fica no ar e nada vai para a lixeira');
$rev_sem = $r['revisao'];
$r = conteudo_restaurar($rev_com, $rev_sem);
t($r['ok'] === true && is_file($midia_t . '/2026/bbbbbbbbbbbbbbbb.webp') && !is_file($tmp . '/midia-privada/2026/bbbbbbbbbbbbbbbb.webp'), 'restaurar uma versão traz de volta a foto de que ela precisa');
t(file_get_contents($midia_t . '/2026/bbbbbbbbbbbbbbbb.webp') === 'foto da dona A', 'a foto voltou com o mesmo conteúdo');
// assets/ originais nunca são movidos
$hero = RAIZ . '/assets/hero-bg.webp';
$hero_antes = is_file($hero) ? md5_file($hero) : null;
$cp3 = conteudo_ler();
$cp3['resultados']['itens'][0]['antes']['arquivo'] = 'assets/hero-bg.webp';
$r = conteudo_salvar($cp3, $r['revisao'], 'tira A');
t(midia_privada_guardar('assets/hero-bg.webp') === false && ($hero_antes === null || (is_file($hero) && md5_file($hero) === $hero_antes)), 'assets/ originais nunca vão para a área privada');
// mídia que nenhuma versão usa vai para a lixeira, mesmo estando na área privada
$revq = $r['revisao'];
for ($i = 0; $i < 22; $i++) {
    $b = conteudo_ler();
    $b['resultados']['itens'][0]['depois']['arquivo'] = 'assets/hero-bg.webp';
    $b['seo']['titulo'] = 'Giro ' . $i;
    $r = conteudo_salvar($b, $revq, 'giro');
    $revq = $r['revisao'];
}
t(!is_file($midia_t . '/2026/cccccccccccccccc.jpg') && !is_file($tmp . '/midia-privada/2026/cccccccccccccccc.jpg') && count(array_filter(lixeira_listar(), fn($m) => $m['arquivo'] === $FB)) === 1, 'foto que nenhuma versão usa mais vai da área privada para a lixeira');
// P2-7: só AAAA/<hex>.(webp|jpg|mp4)
t(midia_caminho('conteudo/midia/.htaccess') === null && midia_caminho('conteudo/midia/2026/.htaccess') === null && midia_caminho('conteudo/midia/a.webp') === null && midia_caminho('conteudo/midia/2026/abc.webp') === null && midia_caminho('conteudo/midia/2026/aaaaaaaaaaaaaaaa.php') === null && midia_caminho('conteudo/midia/2026/aaaaaaaaaaaaaaaa.png') === null, 'midia_caminho recusa arquivo oculto e nome fora do formato');
t(midia_caminho($FA) === $midia_t . '/2026/bbbbbbbbbbbbbbbb.webp' && midia_caminho('conteudo/midia/2026/dddddddddddddddd.mp4') !== null, 'midia_caminho aceita AAAA/<hex>.(webp|jpg|mp4)');
$cv = conteudo_ler();
$cv['resultados']['itens'][0]['antes']['arquivo'] = 'conteudo/midia/.htaccess';
t(count(array_filter(conteudo_validar($cv), fn($e) => $e['campo'] === 'resultados.itens.0.antes.arquivo')) === 1, 'a validação recusa conteudo/midia/.htaccess como arquivo de foto');
$cv['resultados']['itens'][0]['antes']['arquivo'] = 'conteudo/midia/foto-solta.webp';
t(count(array_filter(conteudo_validar($cv), fn($e) => $e['campo'] === 'resultados.itens.0.antes.arquivo')) === 1, 'a validação recusa nome fora do formato');
$cv['resultados']['itens'][0]['antes']['arquivo'] = $FA;
t(!array_filter(conteudo_validar($cv), fn($e) => $e['campo'] === 'resultados.itens.0.antes.arquivo'), 'a validação aceita o formato certo');
config_definir(null);
conteudo_cache_limpar();

echo "\nT08 dados do painel fora do public_html\n";
$raiz_n = rtrim(str_replace('\\', '/', RAIZ), '/') . '/';
$fora = static fn(string $p) => strpos(rtrim(str_replace('\\', '/', $p), '/') . '/', $raiz_n) !== 0;
config_definir(array_merge($cfg_teste, ['midia_dir' => $midia_t, 'midia_url' => 'conteudo/midia']));
$GLOBALS['__nucleo_teste'] = ['sem_exit' => true, 'agora' => 9000000];
conteudo_cache_limpar();
$tit = 'Título que sobrevive ' . bin2hex(random_bytes(2));
$rev8 = (int) json_decode((string) file_get_contents($tmp . '/site.json'), true)['revisao'];
$c8 = conteudo_padrao();
$c8['seo']['titulo'] = $tit;
$r8 = conteudo_salvar($c8, $rev8, 'T08');
t($r8['ok'] === true, 'salvar pelo painel funciona com dados_dir fora do site');
t($fora(config()['dados_dir']), 'dados_dir fica fora da pasta do site');
t($fora($tmp . '/site.json') && is_file($tmp . '/site.json') && is_dir($tmp . '/historico') && is_dir($tmp . '/sessoes'), 'site.json, historico e sessoes ficam em dados_dir');
$dentro = array_merge(glob(RAIZ . '/site.json') ?: [], glob(RAIZ . '/historico') ?: [], glob(RAIZ . '/lixeira') ?: [], glob(RAIZ . '/trava.lock') ?: [], glob(RAIZ . '/tentativas.json') ?: [], glob(RAIZ . '/sessoes') ?: []);
t($dentro === [], 'nada do painel foi escrito dentro da pasta do site');
// "publicar de novo": a pasta do site é trocada inteira; dados_dir e a mídia ficam
$site_velho = $tmp . '/site';
file_put_contents($site_velho . '/index.php', 'versao antiga');
file_put_contents($midia_t . '/foto.webp', 'foto da dona');
foreach (['index.php'] as $f) {
    unlink($site_velho . '/' . $f);
}
file_put_contents($site_velho . '/index.php', 'versao nova');
conteudo_cache_limpar();
t(conteudo_ler()['seo']['titulo'] === $tit, 'depois de publicar de novo o conteúdo salvo continua');
t(is_file($midia_t . '/foto.webp'), 'depois de publicar de novo a mídia continua');
$tracked = (string) shell_exec('git -C ' . escapeshellarg(RAIZ) . ' ls-files 2>&1');
t($tracked !== '' && !preg_match('#(^|/)(site\.json|trava\.lock|tentativas\.json|painel-config[^/]*\.php)$#m', $tracked) && !preg_match('#^(historico|lixeira|sessoes|\.local|publicar)/#m', $tracked), 'o git não rastreia dados do painel, config nem .local/');
config_definir(null);
conteudo_cache_limpar();

// limpa a pasta temporária dos testes
function limpar_pasta(string $d): void
{
    foreach (glob($d . '/{,.}*', GLOB_BRACE) ?: [] as $f) {
        if (in_array(basename($f), ['.', '..'], true)) {
            continue;
        }
        is_dir($f) ? limpar_pasta($f) : @unlink($f);
    }
    @rmdir($d);
}
limpar_pasta($tmp);

echo "\n";
if ($falhas) {
    echo count($falhas) . " falha(s), $ok ok.\n";
    exit(1);
}
echo "Tudo certo: $ok verificações.\n";
exit(0);
