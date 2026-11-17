<?php defined('RAIZ') || exit;
/** Rodapé. Variáveis: $c (conteúdo), $pagina ('home' ou 'resultados'). */
$ct = $c['contato'];
$home = $pagina === 'home';
$prefixo = $home ? '' : 'index.html';
$menu = [['#home', 'Início'], ['#sobre', 'Sobre'], ['#procedimentos', 'Procedimentos'], ['#resultados', 'Resultados'], ['#cursos', 'Cursos'], ['#depoimentos', 'Depoimentos'], ['#contato', 'Contato']];
?>
<?php
    $ic = static fn(string $d): string => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . $d . '</svg>';
    $pino = $ic('<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z" stroke="#E3C9A8" stroke-width="1.4" stroke-linejoin="round"/><circle cx="12" cy="9.5" r="2.4" stroke="#E3C9A8" stroke-width="1.4"/>');
    $relogio = $ic('<circle cx="12" cy="12" r="8.5" stroke="#E3C9A8" stroke-width="1.4"/><path d="M12 7.5V12l3 2" stroke="#E3C9A8" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>');
    $insta = $ic('<rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="#E3C9A8" stroke-width="1.4"/><circle cx="12" cy="12" r="4" stroke="#E3C9A8" stroke-width="1.4"/><circle cx="17.2" cy="6.8" r="1" fill="#E3C9A8"/>');
    $zap = $ic('<path d="M17.5 14.4c-.3-.1-1.7-.8-1.9-.9-.3-.1-.5-.1-.6.1-.2.3-.7.9-.9 1-.2.2-.3.2-.6.1-.3-.1-1.2-.4-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.3.2-.4.1-.2 0-.3 0-.5-.1-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3 4.8 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 1.9-1.4.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3z" stroke="#E3C9A8" stroke-width="1.3" stroke-linejoin="round"/><path d="M12 3.5A8.5 8.5 0 0 0 4.3 16l-1.1 4 4.2-1.1A8.5 8.5 0 1 0 12 3.5z" stroke="#E3C9A8" stroke-width="1.3"/>');
?>
  <footer class="rodape">
    <div class="contem rodape-grade" id="footer-grid">
      <div class="rodape-logo">
        <img src="<?= e(url_midia($c['marca']['logo_texto']['arquivo'])) ?>" alt="<?= e($c['marca']['logo_texto']['alt']) ?>" width="1920" height="1080" loading="lazy">
      </div>
      <div class="rodape-listas">
        <div id="footer-menus">
          <p class="rodape-titulo">Menus</p>
          <div class="rodape-lista">
<?php foreach ($menu as $i => [$ancora, $rotulo]): ?>
            <a href="<?= e($prefixo . $ancora) ?>"<?= $home && $i === 0 ? ' onclick="goHome(event)"' : '' ?>><?= e($rotulo) ?></a>
<?php endforeach; ?>
          </div>
        </div>
        <div id="footer-cursos">
          <p class="rodape-titulo">Cursos</p>
          <div class="rodape-lista">
<?php foreach ($c['home']['cursos']['itens'] as $curso): ?>
            <a href="<?= e($prefixo) ?>#cursos"><?= e($curso['titulo']) ?></a>
<?php endforeach; ?>
          </div>
        </div>
        <div>
          <p class="rodape-titulo">Contato</p>
          <div class="rodape-lista">
            <a href="<?= e(link_whatsapp($ct)) ?>" target="_blank" rel="noopener"><?= $zap ?><?= e($ct['whatsapp_exibicao']) ?></a>
            <a href="https://instagram.com/<?= e($ct['instagram_usuario']) ?>" target="_blank" rel="noopener"><?= $insta ?>@<?= e($ct['instagram_usuario']) ?></a>
            <a href="<?= e($ct['maps_link']) ?>" target="_blank" rel="noopener"><?= $pino ?><?= e($ct['endereco_curto']) ?></a>
            <p class="rodape-linha"><?= $relogio ?><?= e($ct['horario']) ?></p>
          </div>
        </div>
      </div>
    </div>
    <div class="contem rodape-fim"><p>Studio Gabriela Santos</p></div>
  </footer>
</div>
