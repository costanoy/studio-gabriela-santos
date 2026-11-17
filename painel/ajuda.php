<?php
/**
 * Página de ajuda do painel: passo a passo em português simples. Só para quem já entrou.
 * Não usa script nem estilo escritos na página (a política de segurança do painel bloqueia).
 */
define('RAIZ', dirname(__DIR__));
require_once RAIZ . '/nucleo/config.php';
require_once RAIZ . '/nucleo/conteudo.php';
require_once RAIZ . '/nucleo/render.php';
require_once RAIZ . '/nucleo/seguranca.php';

cabecalhos_painel();
sessao_iniciar();
if (!sessao_logada()) {
    header('Location: ./', true, 303);
    exit;
}
header('Content-Type: text/html; charset=utf-8');

$v = is_file(__DIR__ . '/painel.css') ? (string) filemtime(__DIR__ . '/painel.css') : '0';

/** Passos da ajuda: [título, [passos...]] */
$secoes = [
    ['Trocar um texto do site', [
        'Na tela inicial, toque em Textos.',
        'Toque no grupo que você quer mudar, por exemplo Topo do site.',
        'Apague e escreva o novo texto. O número embaixo mostra quanto ainda cabe.',
        'Toque em Salvar no site. Pronto: o site já mostra o texto novo.',
    ]],
    ['Mudar a ordem das fotos', [
        'Toque em Antes e depois.',
        'Escolha onde as fotos aparecem: Página inicial, Resultados ou Fora do site.',
        'Toque em Subir ou Descer, os botões que ficam na linha de cada foto.',
        'Toque em Salvar no site.',
    ]],
    ['Trocar uma foto ou ajustar o enquadramento', [
        'Toque em Antes e depois e depois em Editar na foto que você quer.',
        'Para ajustar o enquadramento, arraste a foto com um dedo. Use dois dedos para aumentar ou diminuir.',
        'Se preferir, use os botões − e + ou a barra de zoom. Errou? Toque em Desfazer ajuste. Para começar do zero, toque em Voltar ao automático.',
        'Para alinhar com a outra foto, toque em Sobrepor a outra foto. Ela aparece por cima, meio transparente, e a barra Transparência muda o quanto você enxerga.',
        'Para trocar a foto, toque em Trocar esta foto e escolha outra da sua galeria.',
        'Confira como fica no site mais abaixo e toque em Salvar no site.',
    ]],
    ['Apagar e desfazer', [
        'Toque em Editar no item. O botão vermelho Apagar fica no fim da tela. O painel pergunta se você tem certeza e mostra o nome.',
        'Depois de apagar, a lista mostra o aviso 1 item será apagado. O site ainda não mudou. Se desistir, toque em Não apagar.',
        'Para apagar de verdade, toque em Salvar no site. Aparece um aviso com o botão Desfazer, que fica na tela por 10 segundos.',
        'Se passou o tempo, toque em Versões e volte para como o site estava antes.',
        'Fotos e vídeos que saem de todas as versões ficam na Lixeira por 30 dias. Toque em Restaurar para trazer de volta.',
    ]],
    ['Voltar o site para um dia anterior', [
        'Toque em Versões.',
        'Escolha o dia e a hora e toque em Voltar para esta versão.',
        'Confirme. Nada se perde: se mudar de ideia, toque em Desfazer.',
    ]],
    ['Se aparecer um aviso', [
        'O site foi alterado em outra tela: alguém salvou antes de você. Toque em Carregar a versão nova e refaça sua mudança.',
        'Por segurança, entre de novo: o tempo acabou. Suas mudanças ficam guardadas neste aparelho. Entre com a senha e toque em Recuperar mudanças.',
        'A internet caiu: nada foi mudado no site. Confira o sinal e toque em Tentar de novo.',
    ]],
    ['Abrir o painel como um aplicativo no iPhone', [
        'Abra o painel no Safari.',
        'Toque no botão de compartilhar (o quadrado com a seta para cima).',
        'Toque em Adicionar à Tela de Início.',
    ]],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Ajuda — Painel do Studio Gabriela Santos</title>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="light">
<meta name="theme-color" content="#7D5843">
<link rel="apple-touch-icon" href="../assets/favicon.png">
<link rel="stylesheet" href="painel.css?v=<?= e($v) ?>">
</head>
<body class="app">
<div id="ajuda" class="app-raiz">
  <header class="topo">
    <a class="botao" href="./" id="ajuda-voltar">← Voltar</a>
    <h1>Ajuda</h1>
  </header>
  <main id="conteudo-tela">
    <p class="dica">Passo a passo para as tarefas mais comuns. Se ficar com dúvida, fale com o Vinicius.</p>
    <?php foreach ($secoes as [$titulo, $passos]): ?>
    <section class="ajuda-secao">
      <h2><?= e($titulo) ?></h2>
      <ol class="ajuda-passos">
        <?php foreach ($passos as $p): ?>
        <li><?= e($p) ?></li>
        <?php endforeach; ?>
      </ol>
    </section>
    <?php endforeach; ?>
  </main>
</div>
</body>
</html>
