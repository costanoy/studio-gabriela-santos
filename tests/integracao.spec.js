// Teste de ponta a ponta do painel (P1): entra com a senha, envia foto e vídeo REAIS pelo midia.js
// (sem stub), cria o item, salva, confere no site público e desfaz.
// Roda depois dos testes do site (os projetos painel-* dependem deles; ver playwright.config.js)
// e usa a mesma trava e a mesma limpeza de dados que tests/painel.spec.js.
const { test: base, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

const RAIZ = path.resolve(__dirname, '..');
const LOCAL = path.join(RAIZ, '.local');
const DADOS = path.join(LOCAL, 'painel-dados');
const MIDIA = path.join(RAIZ, 'conteudo', 'midia');
const FIX = path.join(__dirname, 'fixtures', 'midia');
const SENHA = fs.readFileSync(path.join(LOCAL, 'senha-teste.txt'), 'utf8').trim();
const TRAVA = path.join(LOCAL, 'painel-teste.lock');

const dormir = (ms) => new Promise((r) => setTimeout(r, ms));

async function pegarTrava() {
  const limite = Date.now() + 150000;
  for (;;) {
    try {
      fs.mkdirSync(TRAVA);
      return;
    } catch (e) {
      if (e.code !== 'EEXIST') throw e;
      try {
        if (Date.now() - fs.statSync(TRAVA).mtimeMs > 100000) fs.rmdirSync(TRAVA);
      } catch (_) { /* outro teste soltou */ }
      if (Date.now() > limite) throw new Error('Não consegui a trava dos testes do painel');
      await dormir(120);
    }
  }
}
function soltarTrava() {
  try { fs.rmdirSync(TRAVA); } catch (_) { /* já solta */ }
}

/** Zera o conteúdo editado, o histórico, a lixeira e as tentativas de login. */
function zerarDados() {
  for (const f of ['site.json', 'tentativas.json']) fs.rmSync(path.join(DADOS, f), { force: true });
  for (const d of ['historico', 'lixeira']) fs.rmSync(path.join(DADOS, d), { recursive: true, force: true });
}

function listarMidia() {
  const saida = [];
  const ande = (dir) => {
    if (!fs.existsSync(dir)) return;
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
      const p = path.join(dir, e.name);
      if (e.isDirectory()) ande(p);
      else if (e.name !== '.htaccess') saida.push(p);
    }
  };
  ande(MIDIA);
  return saida;
}

const test = base.extend({
  dados: [async ({}, use) => {
    await pegarTrava();
    zerarDados();
    const antes = new Set(listarMidia());
    try {
      await use(true);
    } finally {
      // apaga a mídia que o teste enviou (a pasta conteudo/midia não vai para o git)
      for (const f of listarMidia()) if (!antes.has(f)) fs.rmSync(f, { force: true });
      soltarTrava();
    }
  }, { auto: true }],
});
test.setTimeout(120000);

async function entrar(page) {
  await page.goto('/painel/');
  await page.fill('#senha', SENHA);
  await page.click('button[type=submit]');
  await expect(page.locator('#sair')).toBeVisible();
}

async function chamar(page, corpo) {
  return page.evaluate(async (c) => {
    const csrf = document.querySelector('meta[name="csrf"]').content;
    const r = await fetch('api.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': csrf },
      body: JSON.stringify(c),
    });
    const j = await r.json();
    j.status = r.status;
    return j;
  }, corpo);
}

/** Clica no botão do painel e entrega o arquivo ao seletor que o midia.js abre de verdade. */
async function escolherArquivo(page, seletorBotao, arquivo) {
  const [seletor] = await Promise.all([page.waitForEvent('filechooser'), page.click(seletorBotao)]);
  await seletor.setFiles(arquivo);
}

function noDisco(rel) { return path.join(RAIZ, rel); }

// Vídeo: converte para cor com a mesma matriz que o navegador usa (BT.709 em HD, BT.601 abaixo de 720p).
// Sem isso o ffmpeg mede o Y cru e um vídeo HD de cores fortes parece "diferente" da capa, mesmo sendo o quadro 0.
function cinza32(arquivo, ate) {
  let filtro = 'scale=32:32:flags=area,format=gray';
  if (/\.mp4$/i.test(arquivo)) {
    const alt = Number(execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=height', '-of', 'csv=p=0', arquivo]).toString().trim());
    filtro = `scale=32:32:flags=area:in_color_matrix=${alt >= 720 ? 'bt709' : 'bt601'}:in_range=tv:out_range=pc,format=rgb24,format=gray`;
  }
  const args = ['-v', 'error', ...(ate ? ['-ss', String(ate)] : []), '-i', arquivo, '-frames:v', '1', '-vf', filtro, '-f', 'rawvideo', '-'];
  return execFileSync('ffmpeg', args, { maxBuffer: 1 << 20 });
}
function diferenca(a, b) {
  let s = 0;
  for (let i = 0; i < a.length; i++) s += Math.abs(a[i] - b[i]);
  return s / a.length;
}

test.describe('P1 ponta a ponta com o envio de verdade', () => {
  test('foto: envia antes e depois, cria, salva, aparece no site e Desfazer tira', async ({ page }) => {
    await entrar(page);
    await page.click('#cartao-resultados');
    await page.click('#novo-comparacao');
    await page.locator('[data-caminho="titulo"] input').fill('Teste de ponta a ponta');

    await escolherArquivo(page, '#escolher-antes', path.join(FIX, 'jpeg-orientacao6-gps.jpg'));
    await expect(page.locator('#escolher-antes')).toHaveText(/Trocar/, { timeout: 30000 });
    await escolherArquivo(page, '#escolher-depois', path.join(FIX, 'jpeg-em-pe-sem-exif.jpg'));
    await expect(page.locator('#escolher-depois')).toHaveText(/Trocar/, { timeout: 30000 });
    await expect(page.locator('#criar')).toBeEnabled();
    await page.click('#criar');
    await expect(page.locator('#quadro-edicao')).toBeVisible();
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');

    // gravou de verdade, com arquivos novos em conteudo/midia
    const lido = await chamar(page, { acao: 'ler' });
    const novo = lido.conteudo.resultados.itens.find((x) => x.titulo === 'Teste de ponta a ponta');
    expect(novo, 'o item novo foi salvo').toBeTruthy();
    expect(novo.tipo).toBe('comparacao');
    for (const lado of ['antes', 'depois']) {
      expect(novo[lado].arquivo).toMatch(/^conteudo\/midia\/\d{4}\/[0-9a-f]{16}\.(webp|jpg)$/);
      expect(fs.existsSync(noDisco(novo[lado].arquivo)), novo[lado].arquivo).toBe(true);
    }
    // a foto "antes" tinha GPS e giro: sai em pé (300x400) e sem metadados
    expect([novo.antes.largura, novo.antes.altura]).toEqual([300, 400]);
    const bruto = fs.readFileSync(noDisco(novo.antes.arquivo)).toString('latin1');
    for (const marca of ['Exif', 'GPS']) expect(bruto).not.toContain(marca);

    // no site público
    const pub = await page.request.get('/resultados.html');
    expect(pub.status()).toBe(200);
    const html = await pub.text();
    expect(html).toContain(novo.antes.arquivo);
    expect(html).toContain(novo.depois.arquivo);
    for (const lado of ['antes', 'depois']) {
      const img = await page.request.get('/' + novo[lado].arquivo);
      expect(img.status(), lado).toBe(200);
      expect(img.headers()['content-type']).toMatch(/^image\//);
    }

    // Desfazer
    await page.locator('.toast').getByRole('button', { name: 'Desfazer' }).click();
    await expect(page.locator('.toast')).toContainText('Desfeito');
    const depoisDesfazer = await chamar(page, { acao: 'ler' });
    expect(depoisDesfazer.conteudo.resultados.itens.find((x) => x.titulo === 'Teste de ponta a ponta')).toBeUndefined();
    const html2 = await (await page.request.get('/resultados.html')).text();
    expect(html2).not.toContain(novo.antes.arquivo);
    expect(html2).not.toContain(novo.depois.arquivo);
  });

  for (const caso of [
    { arquivo: 'video-h264-com-local.mp4', meio: 0.6, nome: '' },
    { arquivo: 'video-1080-em-pe.mp4', meio: 0.3, nome: ' (1080 em pé)' },
  ]) test('vídeo' + caso.nome + ': envia, o poster é o quadro 0, aparece no site com preload none e Desfazer tira', async ({ page, browserName }) => {
    test.skip(browserName !== 'chromium', 'o WebKit do Playwright no Windows não toca H.264, então a capa não sai; vale a checagem manual no iPhone (PUBLICACAO.md)');
    await entrar(page);
    await page.click('#cartao-resultados');
    await page.click('#novo-video');
    await page.locator('[data-caminho="titulo"] input').fill('Vídeo de ponta a ponta');
    await escolherArquivo(page, '#escolher-video', path.join(FIX, caso.arquivo));
    // o painel mostra a capa e pede para confirmar antes de gastar internet
    await page.getByRole('button', { name: 'Enviar este vídeo' }).click();
    await expect(page.locator('#escolher-video')).toHaveText(/Trocar/, { timeout: 60000 });
    await expect(page.locator('#criar')).toBeEnabled();
    await page.click('#criar');
    await expect(page.locator('#trocar-video')).toBeVisible();
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');

    const lido = await chamar(page, { acao: 'ler' });
    const v = lido.conteudo.resultados.itens.find((x) => x.rotulo === 'Vídeo de ponta a ponta');
    expect(v, 'o vídeo novo foi salvo').toBeTruthy();
    expect(v.tipo).toBe('video');
    expect(v.arquivo).toMatch(/^conteudo\/midia\/\d{4}\/[0-9a-f]{16}\.mp4$/);
    expect(v.poster).toMatch(/^conteudo\/midia\/\d{4}\/[0-9a-f]{16}\.(webp|jpg)$/);
    expect(fs.existsSync(noDisco(v.arquivo))).toBe(true);
    expect(fs.existsSync(noDisco(v.poster))).toBe(true);

    // poster = primeiro quadro do vídeo, e não um quadro do meio
    const poster = cinza32(noDisco(v.poster));
    const d0 = diferenca(poster, cinza32(noDisco(v.arquivo)));
    const dm = diferenca(poster, cinza32(noDisco(v.arquivo), caso.meio));
    expect(d0, 'diferença para o quadro 0').toBeLessThanOrEqual(5);
    expect(dm, 'a capa não é um quadro do meio').toBeGreaterThan(d0);

    // no site: preload="none", poster apontando para o arquivo novo, e o mp4 aceita Range
    const html = await (await page.request.get('/resultados.html')).text();
    const tag = (html.match(/<video\b[^>]*>/gi) || []).find((t) => t.includes(v.arquivo));
    expect(tag, 'a tag <video> do vídeo novo').toBeTruthy();
    expect(tag).toContain('preload="none"');
    expect(tag).toContain('playsinline');
    expect(tag).toContain('poster="' + v.poster + '"');
    expect((await page.request.get('/' + v.poster)).status()).toBe(200);
    const faixa = await page.request.get('/' + v.arquivo, { headers: { Range: 'bytes=0-99' } });
    expect(faixa.status()).toBe(206);

    // Desfazer
    await page.locator('.toast').getByRole('button', { name: 'Desfazer' }).click();
    await expect(page.locator('.toast')).toContainText('Desfeito');
    const html2 = await (await page.request.get('/resultados.html')).text();
    expect(html2).not.toContain(v.arquivo);
  });
});
