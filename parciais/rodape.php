<?php defined('RAIZ') || exit;
/** Rodapé. Variáveis: $c (conteúdo), $pagina ('home' ou 'resultados'). */
$ct = $c['contato'];
$home = $pagina === 'home';
$prefixo = $home ? '' : 'index.html';
$tam = $home ? '15.5px' : '14.5px';
$menu = [['#home', 'Início'], ['#sobre', 'Sobre'], ['#procedimentos', 'Procedimentos'], ['#resultados', 'Resultados'], ['#cursos', 'Cursos'], ['#depoimentos', 'Depoimentos'], ['#contato', 'Contato']];
?>
  <footer style="background-image: linear-gradient(135deg, #3A2A22, #5C4A40); padding: 64px 5% 32px; box-sizing:border-box;">
    <div style="max-width:1000px; margin:0 auto; display:grid; grid-template-columns: 1.2fr 1fr 1fr 1fr; gap:48px;" id="footer-grid">
      <div style="display:flex; justify-content:center;">
        <img src="<?= e(url_midia($c['marca']['logo_texto']['arquivo'])) ?>" alt="<?= e($c['marca']['logo_texto']['alt']) ?>" style="height: 220px; width: auto; display: block; margin-bottom: 16px; margin-top: -20px">
      </div>
      <div id="footer-menus" style="margin-left: 24px;">
        <p style="font-size:12.5px; letter-spacing:0.1em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0 0 16px;">MENUS<br></p>
        <div style="display:flex; flex-direction:column; gap:12px;">
<?php foreach ($menu as $i => [$ancora, $rotulo]): ?>
          <a href="<?= e($prefixo . $ancora) ?>"<?= ($home && $i === 0) ? ' onclick="goHome(event)"' : '' ?> style="text-decoration:none; color:#F3E4DA; font-size:<?= $tam ?>;"><?= e($rotulo) ?></a>
<?php endforeach; ?>
        </div>
      </div>
      <div id="footer-cursos">
        <p style="font-size:12.5px; letter-spacing:0.1em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0 0 16px;">Cursos</p>
        <div style="display:flex; flex-direction:column; gap:12px;">
<?php foreach ($c['home']['cursos']['itens'] as $curso): ?>
          <a href="<?= e($prefixo) ?>#cursos" style="text-decoration:none; color:#F3E4DA; font-size:<?= $tam ?>;"><?= e($curso['titulo']) ?></a>
<?php endforeach; ?>
        </div>
      </div>
      <div>
        <p style="font-size:12.5px; letter-spacing:0.1em; text-transform:uppercase; color:#996C55; font-weight:700; margin:0 0 16px;">Contato</p>
        <div style="display:flex; flex-direction:column; gap:14px;">
          <a href="<?= e(link_whatsapp($ct)) ?>" target="_blank" rel="noopener" style="text-decoration:none; color:#F3E4DA; font-size:<?= $tam ?>; display:flex; align-items:center; gap:10px;">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><path d="M17.5 14.4c-.3-.1-1.7-.8-1.9-.9-.3-.1-.5-.1-.6.1-.2.3-.7.9-.9 1-.2.2-.3.2-.6.1-.3-.1-1.2-.4-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.3.2-.4.1-.2 0-.3 0-.5-.1-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3 4.8 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 1.9-1.4.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3z" stroke="#996C55" stroke-width="1.3" stroke-linejoin="round"></path><path d="M12 3.5A8.5 8.5 0 0 0 4.3 16l-1.1 4 4.2-1.1A8.5 8.5 0 1 0 12 3.5z" stroke="#996C55" stroke-width="1.3"></path></svg>
            <?= e($ct['whatsapp_exibicao']) ?>

          </a>
          <a href="https://instagram.com/<?= e($ct['instagram_usuario']) ?>" target="_blank" rel="noopener" style="text-decoration:none; color:#F3E4DA; font-size:<?= $tam ?>; display:flex; align-items:center; gap:10px;">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="#996C55" stroke-width="1.3"></rect><circle cx="12" cy="12" r="4" stroke="#996C55" stroke-width="1.3"></circle><circle cx="17.2" cy="6.8" r="1" fill="#996C55"></circle></svg>
            @<?= e($ct['instagram_usuario']) ?>

          </a>
          <a href="<?= e($ct['maps_link']) ?>" target="_blank" rel="noopener" style="text-decoration:none; color:#D8C4B8; font-size:<?= $tam ?>; line-height:1.6; display:flex; align-items:center; gap:10px;">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z" stroke="#996C55" stroke-width="1.3" stroke-linejoin="round"></path><circle cx="12" cy="9.5" r="2.4" stroke="#996C55" stroke-width="1.3"></circle></svg>
            <?= e($ct['endereco_curto']) ?>

          </a>
          <p style="margin:0; color:#D8C4B8; font-size:<?= $tam ?>; display:flex; align-items:center; gap:10px;">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><circle cx="12" cy="12" r="8.5" stroke="#996C55" stroke-width="1.3"></circle><path d="M12 7.5V12l3 2" stroke="#996C55" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            <?= e($ct['horario']) ?>
          </p>
        </div>
      </div>
    </div>
    <p style="text-align:center; color:#8A7368; font-size:13px; margin: 48px 0 0; padding-top:24px; border-top:1px solid #5C4A40;">Studio Gabriela Santos</p>
  </footer>

</div>
