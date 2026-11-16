// Testes de mídia do painel (Lote 2B): envio de fotos e vídeos, de ponta a ponta, no navegador.
//   PORTA=8774 npx playwright test tests/midia.spec.js --project=desktop --project=iphone-chromium
//
// Como funciona sem o painel (que é do Lote 2A): o teste serve uma página mínima em /painel/_teste.html
// (interceptada pelo Playwright, nunca existe no disco) com o mesmo CSP do painel, que carrega
// /painel/midia.js de verdade e chama window.PainelMidia. A sessão logada vem de
// `php ferramentas/testar-midia.php --sessao`. A API (painel/api-midia.php) é a real.
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFileSync } = require('child_process');

const RAIZ = path.join(__dirname, '..');
const FIX = path.join(__dirname, 'fixtures', 'midia');
const MIDIA_DIR = path.join(RAIZ, 'conteudo', 'midia');
const ENVIOS_DIR = path.join(RAIZ, '.local', 'painel-dados', 'envios');

const CSP = "default-src 'self'; img-src 'self' blob: data:; media-src 'self' blob:; script-src 'self'; style-src 'self'; frame-ancestors 'none'";

const PAGINA = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Teste de mídia</title></head>'
  + '<body><button id="f" type="button">Foto</button> <button id="f8" type="button">Foto 800</button> <button id="v" type="button">Vídeo</button>'
  + '<pre id="saida"></pre><script src="/painel/midia.js"></script><script src="/painel/_teste.js"></script></body></html>';

const SCRIPT = [
  'window.__prog = [];',
  'function opcoes(extra) { var o = { csrf: window.__csrf, apiUrl: "/painel/api-midia.php", onProgresso: function (p) { window.__prog.push(p); } }; for (var k in extra) { o[k] = extra[k]; } return o; }',
  'function mostrar(p) { var s = document.getElementById("saida"); p.then(function (r) { s.textContent = "OK " + JSON.stringify(r); }, function (e) { s.textContent = "ERRO " + e.codigo + "|" + e.message; }); }',
  'document.getElementById("f").addEventListener("click", function () { mostrar(PainelMidia.escolherFoto(opcoes({}))); });',
  'document.getElementById("f8").addEventListener("click", function () { mostrar(PainelMidia.escolherFoto(opcoes({ maximo: 800 }))); });',
  'document.getElementById("v").addEventListener("click", function () { mostrar(PainelMidia.escolherVideo(opcoes({ semConfirmar: window.__semConfirmar !== false }))); });',
].join('\n');

function pastaDoPhp() {
  const cmd = process.platform === 'win32' ? 'where' : 'which';
  return path.dirname(execFileSync(cmd, ['php'], { encoding: 'utf8' }).split(/\r?\n/)[0].trim());
}

function sessaoNova() {
  const p = pastaDoPhp();
  const saida = execFileSync('php', ['-d', `extension_dir=${path.join(p, 'ext')}`, '-d', 'extension=gd', '-d', 'extension=exif',
    path.join(RAIZ, 'ferramentas', 'testar-midia.php'), '--sessao'], { encoding: 'utf8' });
  return JSON.parse(saida.slice(saida.indexOf('{')));
}

function listarMidia() {
  const r = [];
  const andar = (d) => {
    if (!fs.existsSync(d)) return;
    for (const n of fs.readdirSync(d)) {
      const c = path.join(d, n);
      if (fs.statSync(c).isDirectory()) andar(c); else r.push(c);
    }
  };
  andar(MIDIA_DIR);
  return r;
}

let antes = [];
let sessao;

test.beforeAll(() => {
  antes = listarMidia();
  sessao = sessaoNova();
});

// Tudo o que os testes enviaram sai de conteudo/midia/ no fim (a pasta é ignorada pelo Git, mas fica limpa)
test.afterAll(() => {
  for (const f of listarMidia()) {
    if (!antes.includes(f)) { try { fs.unlinkSync(f); } catch (e) { /* já saiu */ } }
  }
});

async function abrir(page, context, baseURL, opcoes = {}) {
  await context.addCookies([{ name: sessao.nome, value: sessao.id, url: baseURL }]);
  await page.route('**/painel/_teste.html', (r) => r.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', headers: { 'Content-Security-Policy': CSP }, body: PAGINA }));
  await page.route('**/painel/_teste.js', (r) => r.fulfill({ status: 200, contentType: 'text/javascript; charset=utf-8', body: SCRIPT }));
  const csrf = opcoes.csrf === undefined ? sessao.csrf : opcoes.csrf;
  await page.addInitScript((c) => { window.__csrf = c; }, csrf);
  const violacoes = [];
  page.on('console', (m) => { if (/content security policy|refused to/i.test(m.text())) violacoes.push(m.text()); });
  page.on('pageerror', (e) => violacoes.push(String(e)));
  await page.goto('/painel/_teste.html');
  return violacoes;
}

async function enviar(page, botao, arquivo, espera = 90000) {
  const [fc] = await Promise.all([page.waitForEvent('filechooser'), page.click(botao)]);
  await fc.setFiles(arquivo);
  return resultado(page, espera);
}

async function resultado(page, espera = 90000) {
  await page.waitForFunction(() => /^(OK|ERRO) /.test(document.getElementById('saida').textContent), null, { timeout: espera });
  const t = await page.locator('#saida').textContent();
  if (t.startsWith('OK ')) return { ok: true, ...JSON.parse(t.slice(3)) };
  const [codigo, mensagem] = t.slice(5).split('|');
  return { ok: false, codigo, mensagem };
}

const urlDe = (arquivo) => '/' + arquivo;
const noDisco = (arquivo) => path.join(RAIZ, arquivo);

async function bytesDe(page, arquivo) {
  const r = await page.request.get(urlDe(arquivo));
  expect(r.status(), arquivo).toBe(200);
  return { corpo: await r.body(), tipo: r.headers()['content-type'] };
}

function dimensoes(buf) {
  // WebP (VP8 / VP8L / VP8X), JPEG e PNG: o bastante para os testes
  if (buf.slice(0, 4).toString() === 'RIFF') {
    const f = buf.slice(12, 16).toString();
    if (f === 'VP8X') return [1 + buf.readUIntLE(24, 3), 1 + buf.readUIntLE(27, 3)];
    if (f === 'VP8 ') return [buf.readUInt16LE(26) & 0x3fff, buf.readUInt16LE(28) & 0x3fff];
    if (f === 'VP8L') { const b = buf.readUInt32LE(21); return [(b & 0x3fff) + 1, ((b >> 14) & 0x3fff) + 1]; }
  }
  if (buf[0] === 0xff && buf[1] === 0xd8) {
    let i = 2;
    while (i < buf.length) {
      if (buf[i] !== 0xff) { i++; continue; }
      const m = buf[i + 1];
      if (m >= 0xc0 && m <= 0xcf && m !== 0xc4 && m !== 0xc8 && m !== 0xcc) return [buf.readUInt16BE(i + 7), buf.readUInt16BE(i + 5)];
      i += 2 + buf.readUInt16BE(i + 2);
    }
  }
  throw new Error('formato de imagem desconhecido');
}

function ffprobe(arquivo) {
  return execFileSync('ffprobe', ['-v', 'error', '-show_format', '-show_streams', arquivo], { encoding: 'utf8' });
}

function cinza32(arquivo, ate) {
  // 32x32 em tons de cinza do primeiro quadro (ou do quadro em `ate` segundos)
  const args = ['-v', 'error', ...(ate ? ['-ss', String(ate)] : []), '-i', arquivo, '-frames:v', '1', '-vf', 'scale=32:32:flags=area,format=gray', '-f', 'rawvideo', '-'];
  return execFileSync('ffmpeg', args, { maxBuffer: 1 << 20 });
}
function diferenca(a, b) {
  let s = 0;
  for (let i = 0; i < a.length; i++) s += Math.abs(a[i] - b[i]);
  return s / a.length;
}

// ---------------------------------------------------------------------------

test.describe('interface e frases (P4)', () => {
  test('window.PainelMidia tem as três funções e a página não viola o CSP', async ({ page, context, baseURL }) => {
    const violacoes = await abrir(page, context, baseURL);
    const nomes = await page.evaluate(() => Object.keys(window.PainelMidia).sort());
    expect(nomes).toEqual(['escolherFoto', 'escolherVideo', 'mensagemErro']);
    expect(violacoes).toEqual([]);
  });

  test('toda frase de erro é em português simples, sem termo técnico', async ({ page, context, baseURL }) => {
    await abrir(page, context, baseURL);
    const codigos = ['tipo', 'heic', 'foto_ilegivel', 'foto_pesada', 'foto_enorme', 'hevc', 'grande_demais', 'video_pesado', 'video_formato', 'video_codec',
      'video_ilegivel', 'rede', 'cancelado', 'poster_falhou', 'envio_nao_encontrado', 'parte_invalida', 'partes_faltando', 'sem_espaco', 'gravacao',
      'limite_servidor', 'sessao_expirada', 'csrf', 'nao_configurado'];
    const frases = await page.evaluate((cs) => cs.map((c) => [c, window.PainelMidia.mensagemErro(c, { tamanho_mb: 300, limite_mb: 250 })]), codigos);
    const generica = await page.evaluate(() => window.PainelMidia.mensagemErro('codigo_que_nao_existe'));
    const proibidos = /\b(heic|heif|hevc|h\.?26[45]|exif|mime|codec|megapixel|mp4|json|api|csrf|http|php|erro \d+|null|undefined|exception|stack|bitrate|upload)\b/i;
    for (const [codigo, frase] of frases) {
      expect(frase.length, codigo).toBeGreaterThan(20);
      expect(frase, codigo).not.toMatch(proibidos);
      if (codigo !== 'cancelado') expect(frase, codigo).not.toBe(generica);
    }
    expect(generica).not.toMatch(proibidos);
    const porCodigo = Object.fromEntries(frases);
    expect(porCodigo.heic).toContain('Mais compatível');
    expect(porCodigo.hevc).toContain('Mais compatível');
    expect(porCodigo.grande_demais).toContain('1080p');
    expect(porCodigo.video_pesado).toContain('300 MB');
    expect(porCodigo.video_pesado).toContain('250 MB');
    expect(porCodigo.video_pesado).toContain('Editar');
    expect(porCodigo.rede).toContain('Tentar de novo');
  });
});

test.describe('L12 fotos', () => {
  test('foto deitada com orientação 6 e GPS sai em pé, sem EXIF', async ({ page, context, baseURL }) => {
    await abrir(page, context, baseURL);
    const r = await enviar(page, '#f', path.join(FIX, 'jpeg-orientacao6-gps.jpg'));
    expect(r.ok, JSON.stringify(r)).toBe(true);
    expect(r.largura).toBe(300);
    expect(r.altura).toBe(400);
    expect(r.arquivo).toMatch(/^conteudo\/midia\/\d{4}\/[0-9a-f]{16}\.(webp|jpg)$/);
    const { corpo, tipo } = await bytesDe(page, r.arquivo);
    expect(tipo).toMatch(/image\/(webp|jpeg)/);
    expect(dimensoes(corpo)).toEqual([300, 400]);
    const texto = corpo.toString('latin1');
    for (const marca of ['Exif', 'GPS', 'Apple', 'iPhone', 'http://ns.adobe.com']) expect(texto, marca).not.toContain(marca);
    expect(corpo.length).toBeLessThanOrEqual(250 * 1024);
  });

  test('foto grande é reduzida no navegador antes de subir e sai com 1600 px e até 250 KB', async ({ page, context, baseURL }) => {
    const grande = path.join(os.tmpdir(), 'sgs-teste-grande-' + process.pid + '.jpg');
    execFileSync('ffmpeg', ['-v', 'error', '-y', '-f', 'lavfi', '-i', 'testsrc2=size=4000x3000', '-frames:v', '1', '-q:v', '2', grande]);
    const tamanhoOriginal = fs.statSync(grande).size;
    await abrir(page, context, baseURL);
    let enviado = 0;
    page.on('request', (rq) => { if (rq.url().includes('api-midia') && rq.method() === 'POST') { const b = rq.postDataBuffer(); if (b) enviado = Math.max(enviado, b.length); } });
    const r = await enviar(page, '#f', grande);
    fs.unlinkSync(grande);
    expect(r.ok, JSON.stringify(r)).toBe(true);
    expect(Math.max(r.largura, r.altura)).toBe(1600);
    expect(enviado, 'o navegador reduziu antes de subir').toBeLessThan(tamanhoOriginal * 0.8);
    const { corpo } = await bytesDe(page, r.arquivo);
    expect(corpo.length).toBeLessThanOrEqual(250 * 1024);
  });

  test('modo comparador: lado maior de 800 px', async ({ page, context, baseURL }) => {
    await abrir(page, context, baseURL);
    const grande = path.join(os.tmpdir(), 'sgs-teste-800-' + process.pid + '.jpg');
    execFileSync('ffmpeg', ['-v', 'error', '-y', '-f', 'lavfi', '-i', 'testsrc2=size=2000x1500', '-frames:v', '1', grande]);
    const r = await enviar(page, '#f8', grande);
    fs.unlinkSync(grande);
    expect(r.ok, JSON.stringify(r)).toBe(true);
    expect(Math.max(r.largura, r.altura)).toBe(800);
  });

  test('HEIC cru é recusado com a frase do iPhone, sem termo técnico', async ({ page, context, baseURL }) => {
    await abrir(page, context, baseURL);
    const r = await enviar(page, '#f', path.join(FIX, 'foto-iphone.heic'));
    expect(r.ok).toBe(false);
    expect(r.codigo).toBe('heic');
    expect(r.mensagem).toContain('Mais compatível');
    expect(r.mensagem).not.toMatch(/heic|heif/i);
  });
});

test.describe('T12 servidor seguro', () => {
  test('arquivo .php disfarçado de .jpg é recusado e nada é gravado', async ({ page, context, baseURL }) => {
    await abrir(page, context, baseURL);
    const r = await enviar(page, '#f', path.join(FIX, 'php-disfarcado.jpg'));
    expect(r.ok).toBe(false);
    expect(r.codigo).toBe('tipo');
    expect(listarMidia().filter((f) => !antes.includes(f) && /php/i.test(f))).toEqual([]);
  });

  test('a API recusa quem não entrou, quem não manda o X-CSRF e quem não usa POST', async ({ page, context, baseURL, playwright }) => {
    await abrir(page, context, baseURL);
    const foto = { name: 'a.jpg', mimeType: 'image/jpeg', buffer: fs.readFileSync(path.join(FIX, 'jpeg-em-pe-sem-exif.jpg')) };
    const sem = await playwright.request.newContext({ baseURL });
    const semLogin = await sem.post('/painel/api-midia.php', { multipart: { acao: 'foto', arquivo: foto }, headers: { 'X-CSRF': sessao.csrf } });
    expect(semLogin.status()).toBe(401);
    const semToken = await context.request.post('/painel/api-midia.php', { multipart: { acao: 'foto', arquivo: foto } });
    expect(semToken.status()).toBe(403);
    const tokenErrado = await context.request.post('/painel/api-midia.php', { multipart: { acao: 'foto', arquivo: foto }, headers: { 'X-CSRF': 'errado' } });
    expect(tokenErrado.status()).toBe(403);
    const get = await context.request.get('/painel/api-midia.php');
    expect(get.status()).toBe(405);
    const acao = await context.request.post('/painel/api-midia.php', { multipart: { acao: 'apagar_tudo' }, headers: { 'X-CSRF': sessao.csrf } });
    expect(acao.status()).toBe(400);
    const csp = (await get.headers())['content-security-policy'];
    expect(csp).toContain("script-src 'self'");
    await sem.dispose();
  });

  test('foto acima de 40 megapixels é recusada pelo cabeçalho, antes de abrir no GD', async ({ page, context, baseURL }) => {
    await abrir(page, context, baseURL);
    // PNG de mentira: só o cabeçalho diz 7000x6000 (42 MP)
    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(7000, 0); ihdr.writeUInt32BE(6000, 4); ihdr[8] = 8; ihdr[9] = 2;
    const parte = (tipo, dados) => { const len = Buffer.alloc(4); len.writeUInt32BE(dados.length); return Buffer.concat([len, Buffer.from(tipo), dados, Buffer.alloc(4)]); };
    const png = Buffer.concat([Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]), parte('IHDR', ihdr), parte('IEND', Buffer.alloc(0))]);
    const r = await context.request.post('/painel/api-midia.php', { multipart: { acao: 'foto', arquivo: { name: 'g.png', mimeType: 'image/png', buffer: png } }, headers: { 'X-CSRF': sessao.csrf } });
    expect(r.status()).toBe(422);
    expect((await r.json()).erro).toBe('foto_enorme');
  });
});

test.describe('L10 / T13 vídeo', () => {
  test.setTimeout(120000);

  test('vídeo H.264 passa, sem localização, e o poster é o primeiro quadro (L11)', async ({ page, context, baseURL, browserName }) => {
    test.skip(browserName !== 'chromium', 'a capa é extraída no Chromium neste teste');
    await abrir(page, context, baseURL);
    const origem = path.join(FIX, 'video-h264-com-local.mp4');
    expect(ffprobe(origem)).toMatch(/TAG:location=/);
    const r = await enviar(page, '#v', origem);
    expect(r.ok, JSON.stringify(r)).toBe(true);
    expect(r.arquivo).toMatch(/^conteudo\/midia\/\d{4}\/[0-9a-f]{16}\.mp4$/);
    expect(r.poster).toMatch(/^conteudo\/midia\/\d{4}\/[0-9a-f]{16}\.(webp|jpg)$/);
    expect([r.largura, r.altura]).toEqual([320, 240]);

    // T13: nada de localização no arquivo gravado
    const saida = ffprobe(noDisco(r.arquivo));
    expect(saida).not.toMatch(/TAG:[^=\n]*(location|xyz)/i);
    expect(saida).toContain('codec_name=h264');
    const bruto = fs.readFileSync(noDisco(r.arquivo)).toString('latin1');
    expect(bruto).not.toContain('ISO6709');
    expect(bruto).not.toContain('+15.9365');

    // L11: a capa é o quadro 0 do vídeo (diferença média baixa) e não um quadro do meio
    const poster = cinza32(noDisco(r.poster));
    const quadro0 = cinza32(noDisco(r.arquivo));
    const meio = cinza32(noDisco(r.arquivo), 0.6);
    const d0 = diferenca(poster, quadro0);
    const dm = diferenca(poster, meio);
    expect(d0, 'diferença para o quadro 0').toBeLessThanOrEqual(5);
    expect(dm, 'a capa não é um quadro do meio').toBeGreaterThan(d0);
    const { corpo } = await bytesDe(page, r.poster);
    expect(corpo.length).toBeLessThanOrEqual(250 * 1024);
  });

  test('HEVC é recusado com a frase do iPhone', async ({ page, context, baseURL }) => {
    await abrir(page, context, baseURL);
    const r = await enviar(page, '#v', path.join(FIX, 'video-hevc.mp4'));
    expect(r.ok).toBe(false);
    expect(r.codigo).toBe('hevc');
    expect(r.mensagem).toContain('Mais compatível');
    expect(r.mensagem).not.toMatch(/hevc|h\.?265/i);
  });

  test('vídeo 4K é recusado e a frase manda gravar em 1080p', async ({ page, context, baseURL }) => {
    await abrir(page, context, baseURL);
    const r = await enviar(page, '#v', path.join(FIX, 'video-4k.mp4'));
    expect(r.ok).toBe(false);
    expect(r.codigo).toBe('grande_demais');
    expect(r.mensagem).toContain('1080p');
  });

  test('vídeo do iPhone gravado deitado com giro de 90 graus passa como 1080x1920', async ({ page, context, baseURL, browserName }) => {
    test.skip(browserName !== 'chromium', 'o WebKit do Playwright no Windows não decodifica H.264 para a capa; Safari de verdade não foi testado aqui');
    await abrir(page, context, baseURL);
    const r = await enviar(page, '#v', path.join(FIX, 'video-1080-girado.mp4'));
    expect(r.ok, JSON.stringify(r)).toBe(true);
    expect([r.largura, r.altura]).toEqual([1080, 1920]);
  });

  test('vídeo de mais de 250 MB é recusado antes de subir, com os números na frase', async ({ page, context, baseURL }) => {
    await abrir(page, context, baseURL);
    const grande = path.join(os.tmpdir(), 'sgs-teste-300mb-' + process.pid + '.mp4');
    const fd = fs.openSync(grande, 'w');
    fs.ftruncateSync(fd, 300 * 1024 * 1024);
    fs.closeSync(fd);
    let pedidos = 0;
    page.on('request', (rq) => { if (rq.url().includes('api-midia')) pedidos++; });
    const r = await enviar(page, '#v', grande);
    fs.unlinkSync(grande);
    expect(r.ok).toBe(false);
    expect(r.codigo).toBe('video_pesado');
    expect(r.mensagem).toContain('300 MB');
    expect(r.mensagem).toContain('250 MB');
    expect(pedidos).toBe(0);
  });
});

test.describe('T15 envio em partes', () => {
  test.setTimeout(240000);

  function videoGrande(mbs) {
    const base = fs.readFileSync(path.join(FIX, 'video-1080-em-pe.mp4'));
    const total = mbs * 1024 * 1024;
    const resto = total - base.length;
    const cab = Buffer.alloc(8);
    cab.writeUInt32BE(resto, 0);
    cab.write('free', 4);
    const arq = path.join(os.tmpdir(), `sgs-teste-${mbs}mb-${process.pid}-${Math.random().toString(16).slice(2, 6)}.mp4`);
    const fd = fs.openSync(arq, 'w');
    fs.writeSync(fd, base);
    fs.writeSync(fd, cab);
    fs.ftruncateSync(fd, total);
    fs.closeSync(fd);
    return arq;
  }

  function indiceDe(rq) {
    const b = rq.postDataBuffer();
    if (!b) return null;
    const m = /name="indice"\r\n\r\n(\d+)/.exec(b.subarray(0, 2000).toString('latin1'));
    return m ? Number(m[1]) : null;
  }

  test('vídeo de 60 MB sobe em partes de até 5 MB, com progresso que só cresce', async ({ page, context, baseURL, browserName }) => {
    test.skip(browserName !== 'chromium', 'rodamos o vídeo grande só no Chromium');
    await abrir(page, context, baseURL);
    const arq = videoGrande(60);
    let envioId = '';
    page.on('response', async (resp) => { if (resp.url().includes('api-midia')) { try { const j = await resp.json(); if (j.envio && !envioId) envioId = j.envio; } catch (e) { /* não é JSON */ } } });
    const partes = [];
    page.on('request', (rq) => { if (rq.url().includes('api-midia') && rq.method() === 'POST') { const i = indiceDe(rq); if (i !== null) partes.push([i, rq.postDataBuffer().length]); } });
    const r = await enviar(page, '#v', arq, 200000);
    fs.unlinkSync(arq);
    expect(r.ok, JSON.stringify(r)).toBe(true);
    expect(partes.length).toBe(12);
    for (const [, tam] of partes) expect(tam).toBeLessThanOrEqual(5 * 1024 * 1024 + 4096);
    expect(fs.statSync(noDisco(r.arquivo)).size).toBe(60 * 1024 * 1024);
    const prog = await page.evaluate(() => window.__prog.filter((p) => p.etapa === 'video').map((p) => p.percentual));
    expect(prog.length).toBeGreaterThan(5);
    for (let i = 1; i < prog.length; i++) expect(prog[i]).toBeGreaterThanOrEqual(prog[i - 1]);
    expect(prog[prog.length - 1]).toBeGreaterThanOrEqual(95);
    expect(envioId, 'o envio foi criado').toMatch(/^[0-9a-f]{24}$/);
    expect(fs.existsSync(path.join(ENVIOS_DIR, envioId))).toBe(false);
  });

  test('a internet cai no meio: aparece "Tentar de novo" e só as partes que faltam são enviadas', async ({ page, context, baseURL, browserName }) => {
    test.skip(browserName !== 'chromium', 'rodamos o vídeo grande só no Chromium');
    await abrir(page, context, baseURL);
    const arq = videoGrande(30);
    const vistas = [];
    let derrubou = false;
    await page.route('**/api-midia.php', async (route) => {
      const i = indiceDe(route.request());
      if (i !== null) vistas.push(i);
      if (i === 3 && !derrubou) { derrubou = true; await route.abort('connectionfailed'); return; }
      await route.continue();
    });
    const [fc] = await Promise.all([page.waitForEvent('filechooser'), page.click('#v')]);
    await fc.setFiles(arq);
    const tentar = page.getByRole('button', { name: 'Tentar de novo' });
    await expect(tentar).toBeVisible({ timeout: 60000 });
    await expect(page.locator('.pm-erro')).toContainText('A internet caiu');
    await expect(page.locator('.pm-erro')).not.toContainText(/xhr|network|fetch/i);
    await tentar.click();
    const r = await resultado(page, 120000);
    fs.unlinkSync(arq);
    expect(r.ok, JSON.stringify(r)).toBe(true);
    expect(fs.statSync(noDisco(r.arquivo)).size).toBe(30 * 1024 * 1024);
    const contagem = {};
    for (const i of vistas) contagem[i] = (contagem[i] || 0) + 1;
    expect(contagem[3]).toBe(2);
    for (const i of [0, 1, 2, 4, 5]) expect(contagem[i], 'parte ' + i).toBe(1);
  });

  test('Cancelar para o envio, apaga as partes e não muda nada no site', async ({ page, context, baseURL, browserName }) => {
    test.skip(browserName !== 'chromium', 'rodamos o vídeo grande só no Chromium');
    await abrir(page, context, baseURL);
    const arq = videoGrande(30);
    let envioId = '';
    page.on('response', async (resp) => { if (resp.url().includes('api-midia')) { try { const j = await resp.json(); if (j.envio && !envioId) envioId = j.envio; } catch (e) { /* não é JSON */ } } });
    let segurou = false;
    const chamadas = [];
    await page.route('**/api-midia.php', async (route) => {
      const b = route.request().postDataBuffer();
      const m = b && /name="acao"\r\n\r\n(\w+)/.exec(b.subarray(0, 400).toString('latin1'));
      if (m) chamadas.push(m[1]);
      if (indiceDe(route.request()) === 1 && !segurou) { segurou = true; await new Promise((ok) => setTimeout(ok, 4000)); }
      await route.continue().catch(() => {});
    });
    const [fc] = await Promise.all([page.waitForEvent('filechooser'), page.click('#v')]);
    await fc.setFiles(arq);
    await page.waitForFunction(() => window.__prog.some((p) => p.etapa === 'video' && p.percentual >= 10), null, { timeout: 60000 });
    await page.getByRole('button', { name: 'Cancelar' }).click();
    const r = await resultado(page, 30000);
    fs.unlinkSync(arq);
    expect(r.ok).toBe(false);
    expect(r.codigo).toBe('cancelado');
    expect(r.mensagem).toContain('Nada foi mudado no site');
    await expect(page.locator('.pm-fundo')).toHaveCount(0);
    expect(envioId, 'o envio foi criado').toMatch(/^[0-9a-f]{24}$/);
    await expect.poll(() => fs.existsSync(path.join(ENVIOS_DIR, envioId)), { timeout: 15000 }).toBe(false);
    expect(chamadas).toContain('video_cancelar');
  });
});
