// Testes do painel (Lote 2A). Rodam contra a prévia local: PORTA=8775 npx playwright test tests/painel.spec.js
// Os projetos (desktop, iphone) rodam ao mesmo tempo e dividem os mesmos dados do painel,
// por isso cada teste pega uma trava e começa com os dados zerados.
const { test: base, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const RAIZ = path.resolve(__dirname, '..');
const LOCAL = path.join(RAIZ, '.local');
const DADOS = path.join(LOCAL, 'painel-dados');
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
  for (const d of ['historico', 'lixeira', 'midia-privada']) fs.rmSync(path.join(DADOS, d), { recursive: true, force: true });
}

const test = base.extend({
  dados: [async ({}, use) => {
    await pegarTrava();
    zerarDados();
    try {
      await use(true);
    } finally {
      soltarTrava();
    }
  }, { auto: true }],
});
test.setTimeout(90000);

async function entrar(page) {
  await page.goto('/painel/');
  await page.fill('#senha', SENHA);
  await page.click('button[type=submit]');
  await expect(page.locator('#sair')).toBeVisible();
}

/** Chama a API do painel com a sessão e o código de proteção da página. */
async function chamar(page, corpo) {
  return page.evaluate(async (c) => {
    const csrf = document.querySelector('meta[name="csrf"]').content;
    const r = await fetch('api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': csrf },
      body: JSON.stringify(c),
    });
    const j = await r.json();
    j.status = r.status;
    return j;
  }, corpo);
}

// ===========================================================================
// T11: casca do painel, CSP e login
// ===========================================================================
test.describe('T11 casca, CSP e login', () => {
  test('senha errada mostra quantas tentativas faltam; senha certa entra', async ({ page }) => {
    await page.goto('/painel/');
    await page.fill('#senha', 'senha-errada-123');
    await page.click('button[type=submit]');
    await expect(page.locator('.alerta[role=alert]')).toContainText('Senha incorreta. Você ainda pode tentar 4 vezes.');
    await page.fill('#senha', SENHA);
    await page.click('button[type=submit]');
    await expect(page.locator('#sair')).toBeVisible();
    await expect(page.locator('#cartao-site')).toBeVisible();
  });

  test('"Mostrar senha" troca o tipo do campo', async ({ page }) => {
    await page.goto('/painel/');
    await expect(page.locator('#senha')).toHaveAttribute('type', 'password');
    await page.click('#ver-senha');
    await expect(page.locator('#senha')).toHaveAttribute('type', 'text');
    await expect(page.locator('#senha')).toHaveAttribute('autocomplete', 'current-password');
  });

  test('a CSP do painel não tem inline e bloqueia script escrito na página', async ({ page }) => {
    const resp = await page.goto('/painel/');
    const csp = resp.headers()['content-security-policy'];
    expect(csp).toContain("script-src 'self'");
    expect(csp).toContain("style-src 'self'");
    expect(csp).not.toContain('unsafe-inline');
    expect(csp).not.toContain('unsafe-eval');
    expect(resp.headers()['x-robots-tag']).toContain('noindex');
    await page.addScriptTag({ content: 'window.__inline = 1;' }).catch(() => {});
    expect(await page.evaluate(() => window.__inline)).toBeUndefined();
    await entrar(page);
    await page.addScriptTag({ content: 'window.__inline = 1;' }).catch(() => {});
    expect(await page.evaluate(() => window.__inline)).toBeUndefined();
  });

  test('sem login a API responde 401; sem X-CSRF responde 403; com tudo responde 200', async ({ page }) => {
    const sem = await page.request.post('/painel/api.php', { data: { acao: 'ler' } });
    expect(sem.status()).toBe(401);
    await entrar(page);
    const semCsrf = await page.request.post('/painel/api.php', { data: { acao: 'ler' } });
    expect(semCsrf.status()).toBe(403);
    const csrfErrado = await page.request.post('/painel/api.php', { data: { acao: 'ler' }, headers: { 'X-CSRF': 'errado' } });
    expect(csrfErrado.status()).toBe(403);
    const ok = await chamar(page, { acao: 'ler' });
    expect(ok.status).toBe(200);
    expect(ok.ok).toBe(true);
    expect(typeof ok.revisao).toBe('number');
    const get = await page.request.get('/painel/api.php');
    expect(get.status()).toBe(405);
  });

  test('painel.js monta o DOM só com textContent (sem innerHTML e afins)', async () => {
    const js = fs.readFileSync(path.join(RAIZ, 'painel', 'painel.js'), 'utf8');
    for (const proibido of ['innerHTML', 'outerHTML', 'insertAdjacentHTML', 'document.write', 'eval(', 'new Function', "setAttribute('style'", 'setAttribute("style"']) {
      expect(js.includes(proibido), proibido).toBe(false);
    }
  });

  test('texto com código salvo não vira código no site', async ({ page }) => {
    await entrar(page);
    const lido = await chamar(page, { acao: 'ler' });
    const c = lido.conteudo;
    c.home.hero.titulo = 'Teste <b>negrito</b>';
    const recusado = await chamar(page, { acao: 'salvar', conteudo: c, revisao: lido.revisao });
    expect(recusado.status).toBe(422);
    expect(recusado.erro).toBe('invalido');
    c.home.hero.titulo = 'Oi &lt;img src=x onerror=window.__xss=1&gt; "aspas" & etc';
    const salvo = await chamar(page, { acao: 'salvar', conteudo: c, revisao: lido.revisao });
    expect(salvo.status).toBe(200);
    await page.goto('/');
    expect(await page.evaluate(() => window.__xss)).toBeUndefined();
    await expect(page.locator('h1').first()).toContainText('&lt;img src=x onerror=window.__xss=1&gt;');
    expect(await page.locator('img[src="x"]').count()).toBe(0);
  });

  test('Sair encerra a sessão', async ({ page }) => {
    await entrar(page);
    await page.click('#sair');
    await expect(page.locator('#senha')).toBeVisible();
    const r = await page.request.post('/painel/api.php', { data: { acao: 'ler' } });
    expect(r.status()).toBe(401);
  });
});

// ===========================================================================
// P6: edição de textos com limites e contadores
// ===========================================================================
async function abrirGrupo(page, id) {
  if (!(await page.locator('#conteudo-tela').count())) await page.click('#cartao-textos');
  const det = page.locator('#grupo-' + id);
  if (!(await det.evaluate((d) => d.open))) await det.locator('summary').click();
  await expect(det).toHaveJSProperty('open', true);
  return det;
}

test.describe('P6 textos com limites', () => {
  test('o contador mostra "n de limite" e o campo não passa do limite', async ({ page }) => {
    await entrar(page);
    const det = await abrirGrupo(page, 'topo');
    const campo = det.locator('[data-caminho="home.hero.titulo"]');
    const entrada = campo.locator('input');
    await entrada.fill('Studio ' + 'x'.repeat(100));
    expect((await entrada.inputValue()).length).toBe(80);
    await expect(campo.locator('.contador')).toHaveText('80 de 80 (limite)');
    await entrada.fill('Oi');
    await expect(campo.locator('.contador')).toHaveText('2 de 80');
    // emoji conta como uma letra, como no servidor
    await entrada.fill('Oi 😀');
    await expect(campo.locator('.contador')).toHaveText('4 de 80');
    const texto = det.locator('[data-caminho="home.hero.texto"] textarea');
    await texto.fill('y'.repeat(700));
    expect((await texto.inputValue()).length).toBe(600);
  });

  test('editar, salvar e ver no site; o botão Salvar só liga depois de mudar algo', async ({ page }) => {
    await entrar(page);
    const det = await abrirGrupo(page, 'topo');
    await expect(page.locator('#salvar')).toBeDisabled();
    await det.locator('[data-caminho="home.hero.titulo"] input').fill('Meu novo título');
    await expect(page.locator('#salvar')).toBeEnabled();
    await expect(det.locator('.previa-titulo')).toHaveText('Meu novo título');
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
    await expect(page.locator('#salvar')).toBeDisabled();
    await page.goto('/');
    await expect(page.locator('h1').first()).toContainText('Meu novo título');
  });

  test('campo obrigatório vazio mostra aviso perto do campo e não salva', async ({ page }) => {
    await entrar(page);
    const det = await abrirGrupo(page, 'topo');
    await det.locator('[data-caminho="home.hero.titulo"] input').fill('');
    await page.click('#salvar');
    await expect(det.locator('[data-caminho="home.hero.titulo"] .msg-campo')).toContainText('não pode ficar vazio');
    const lido = await chamar(page, { acao: 'ler' });
    expect(lido.revisao).toBe(0);
    expect(lido.conteudo.home.hero.titulo.length).toBeGreaterThan(0);
  });

  test('WhatsApp aceita só números e mostra aviso se faltar número', async ({ page }) => {
    await entrar(page);
    const det = await abrirGrupo(page, 'contato');
    const zap = det.locator('[data-caminho="contato.whatsapp"] input');
    await zap.fill('(62) 9 8414-1116');
    expect(await zap.inputValue()).toBe('62984141116');
    await zap.fill('6298');
    await page.click('#salvar');
    await expect(det.locator('[data-caminho="contato.whatsapp"] .msg-campo')).toContainText('Digite só números');
  });

  test('endereço: cada linha vira um item, no máximo 6 linhas', async ({ page }) => {
    await entrar(page);
    const det = await abrirGrupo(page, 'contato');
    const area = det.locator('[data-caminho="contato.endereco_linhas"] textarea');
    await area.fill('Rua A\nRua B\nRua C\nRua D\nRua E\nRua F\nRua G');
    expect((await area.inputValue()).split('\n').length).toBe(6);
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
    const lido = await chamar(page, { acao: 'ler' });
    expect(lido.conteudo.contato.endereco_linhas).toEqual(['Rua A', 'Rua B', 'Rua C', 'Rua D', 'Rua E', 'Rua F']);
  });

  test('o servidor também recusa texto acima do limite', async ({ page }) => {
    await entrar(page);
    const lido = await chamar(page, { acao: 'ler' });
    lido.conteudo.home.hero.titulo = 'z'.repeat(81);
    const r = await chamar(page, { acao: 'salvar', conteudo: lido.conteudo, revisao: lido.revisao });
    expect(r.status).toBe(422);
    expect(r.detalhes[0].campo).toBe('home.hero.titulo');
  });

  test('adicionar e reordenar depoimentos', async ({ page }) => {
    await entrar(page);
    const det = await abrirGrupo(page, 'depoimentos');
    const antes = await det.locator('fieldset.item-lista').count();
    await det.getByRole('button', { name: '+ Adicionar depoimento' }).click();
    await expect(det.locator('fieldset.item-lista')).toHaveCount(antes + 1);
    const ultimo = det.locator('fieldset.item-lista').last();
    await ultimo.locator('textarea').fill('Adorei o atendimento!');
    await ultimo.getByRole('button', { name: /Subir Depoimento/ }).click();
    await expect(det.locator('fieldset.item-lista').nth(antes - 1).locator('textarea')).toHaveValue('Adorei o atendimento!');
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
    const lido = await chamar(page, { acao: 'ler' });
    expect(lido.conteudo.home.depoimentos.itens.length).toBe(antes + 1);
    expect(lido.conteudo.home.depoimentos.itens[antes - 1].texto).toBe('Adorei o atendimento!');
  });
});

// ===========================================================================
// P2 / L09: resultados, ordem e enquadramento
// ===========================================================================
const STUB_MIDIA = () => {
  window.PainelMidia = {
    escolherFoto: (o) => { if (o && o.onProgresso) o.onProgresso(0.5); return Promise.resolve({ arquivo: 'assets/results/drenagem-antes1.webp', largura: 1180, altura: 1600 }); },
    escolherVideo: () => Promise.resolve({ arquivo: 'assets/results/corrente-1.mp4', poster: 'assets/posteres/corrente-1.webp', largura: 1024, altura: 576 }),
    mensagemErro: (c) => 'Erro de teste ' + c
  };
};

// O midia.js de verdade (Lote 2B) substituiria o stub; nestes testes ele é bloqueado.
async function semMidiaReal(page) {
  await page.route('**/painel/midia.js*', (route) => route.fulfill({ status: 200, contentType: 'text/javascript', body: '' }));
}

async function abrirItem(page, id) {
  await page.click('#cartao-resultados');
  await page.locator('[data-id="' + id + '"]').getByRole('button', { name: /^Editar/ }).click();
  await expect(page.locator('#quadro-edicao')).toBeVisible();
}

/** Dedos sintéticos: posições em fração do quadro (0 a 1). */
async function gesto(page, passos) {
  await page.evaluate((ps) => {
    const q = document.getElementById('quadro-edicao');
    const r = q.getBoundingClientRect();
    for (const p of ps) {
      q.dispatchEvent(new PointerEvent(p.t, {
        pointerId: p.id, clientX: r.left + p.x * r.width, clientY: r.top + p.y * r.height,
        bubbles: true, cancelable: true, pointerType: 'touch', isPrimary: p.id === 1
      }));
    }
  }, passos);
}

/** Posição da foto dentro do quadro, em fração do quadro (o que o olho vê). */
function posicaoFoto(page, seletorImg, seletorQuadro) {
  return page.evaluate(([si, sq]) => {
    const i = document.querySelector(si).getBoundingClientRect();
    const q = document.querySelector(sq).getBoundingClientRect();
    return { l: (i.left - q.left) / q.width, t: (i.top - q.top) / q.height, w: i.width / q.width, h: i.height / q.height };
  }, [seletorImg, seletorQuadro]);
}
function estiloFoto(page, seletor) {
  return page.evaluate((s) => {
    const e = document.querySelector(s);
    return { left: parseFloat(e.style.left), top: parseFloat(e.style.top), width: parseFloat(e.style.width), height: parseFloat(e.style.height) };
  }, seletor);
}
function quase(a, b, tol) { expect(Math.abs(a - b)).toBeLessThanOrEqual(tol); }

test.describe('P2 resultados e enquadramento', () => {
  test('arrastar com 1 dedo e abrir com 2 dedos: o quadro fica igual ao do site', async ({ page }) => {
    await entrar(page);
    await abrirItem(page, 'cmp-liposhape-1');
    const ini = await estiloFoto(page, '#quadro-edicao img');
    quase(ini.left, 45.888, 0.01);

    await gesto(page, [{ t: 'pointerdown', id: 1, x: 0.5, y: 0.5 }, { t: 'pointermove', id: 1, x: 0.6, y: 0.5 }, { t: 'pointerup', id: 1, x: 0.6, y: 0.5 }]);
    const arrastou = await estiloFoto(page, '#quadro-edicao img');
    quase(arrastou.left, 55.888, 0.05);
    quase(arrastou.top, 50, 0.001);

    await gesto(page, [
      { t: 'pointerdown', id: 1, x: 0.3, y: 0.5 }, { t: 'pointerdown', id: 2, x: 0.7, y: 0.5 },
      { t: 'pointermove', id: 2, x: 0.9, y: 0.5 },
      { t: 'pointerup', id: 2, x: 0.9, y: 0.5 }, { t: 'pointerup', id: 1, x: 0.3, y: 0.5 }
    ]);
    const zoom = await estiloFoto(page, '#quadro-edicao img');
    quase(zoom.width, 188.25, 0.05);
    quase(zoom.height, 150, 0.05);
    await expect(page.locator('#zoom')).toHaveValue('150');
    await expect(page.locator('#zoom-valor')).toContainText('150%');

    // a prévia do painel já mostra o mesmo
    const noEditor = await posicaoFoto(page, '#quadro-edicao img', '#quadro-edicao');
    const naPrevia = await posicaoFoto(page, '#previa-site > img', '#previa-site');
    for (const k of ['l', 't', 'w', 'h']) quase(noEditor[k], naPrevia[k], 0.002);

    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');

    // o site mostra a foto exatamente no mesmo lugar
    await page.goto('/');
    const noSite = await page.evaluate(() => {
      const quadro = document.querySelector('.ba-clip').parentElement;
      const img = quadro.querySelector(':scope > img').getBoundingClientRect();
      const q = quadro.getBoundingClientRect();
      return { l: (img.left - q.left) / q.width, t: (img.top - q.top) / q.height, w: img.width / q.width, h: img.height / q.height };
    });
    for (const k of ['l', 't', 'w', 'h']) quase(noEditor[k], noSite[k], 0.002);
  });

  test('o zoom nunca passa do limite nem deixa a foto sair do quadro', async ({ page }) => {
    await entrar(page);
    await abrirItem(page, 'cmp-liposhape-1');
    await page.click('#aba-antes');
    for (let i = 0; i < 30; i++) await page.click('#zoom-mais');
    const e = await estiloFoto(page, '#quadro-edicao img');
    expect(e.width).toBeLessThanOrEqual(400.001);
    expect(e.height).toBeLessThanOrEqual(400.001);
    // empurra a foto para um canto: ela continua cobrindo o quadro
    await gesto(page, [{ t: 'pointerdown', id: 1, x: 0.5, y: 0.5 }, { t: 'pointermove', id: 1, x: 5, y: 5 }, { t: 'pointerup', id: 1, x: 5, y: 5 }]);
    const p = await posicaoFoto(page, '#quadro-edicao img', '#quadro-edicao');
    expect(p.l).toBeLessThanOrEqual(0.0011);
    expect(p.t).toBeLessThanOrEqual(0.0011);
    expect(p.l + p.w).toBeGreaterThanOrEqual(0.9989);
    expect(p.t + p.h).toBeGreaterThanOrEqual(0.9989);
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
  });

  test('controles de zoom (barra, − e +) e "Voltar ao automático"', async ({ page }) => {
    await entrar(page);
    await abrirItem(page, 'cmp-drenagem-1');
    await expect(page.locator('#voltar-auto')).toBeEnabled();
    const antes = Number(await page.locator('#zoom').inputValue());
    await page.click('#zoom-mais');
    expect(Number(await page.locator('#zoom').inputValue())).toBe(antes + 10);
    await page.click('#zoom-menos');
    await page.click('#zoom-menos');
    expect(Number(await page.locator('#zoom').inputValue())).toBe(antes - 10 < 100 ? 100 : antes - 10);
    await page.click('#voltar-auto');
    await expect(page.locator('#zoom')).toHaveValue('100');
    await expect(page.locator('#voltar-auto')).toBeDisabled();
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
    const lido = await chamar(page, { acao: 'ler' });
    const it = lido.conteudo.resultados.itens.find((x) => x.id === 'cmp-drenagem-1');
    expect(it.depois.enquadramento.automatico).toBe(true);
    quase(it.depois.enquadramento.width, 100, 0.001);
  });

  test('mudar a ordem na página inicial', async ({ page }) => {
    await entrar(page);
    await page.click('#cartao-resultados');
    const ids = () => page.locator('#lista-resultados .linha-res').evaluateAll((l) => l.map((x) => x.dataset.id));
    expect((await ids())[0]).toBe('cmp-liposhape-1');
    await page.getByRole('button', { name: 'Descer Liposhape' }).click();
    expect((await ids()).slice(0, 2)).toEqual(['cmp-drenagem-1', 'cmp-liposhape-1']);
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
    const lido = await chamar(page, { acao: 'ler' });
    const por = (id) => lido.conteudo.resultados.itens.find((x) => x.id === id);
    expect(por('cmp-drenagem-1').ordem_home).toBe(1);
    expect(por('cmp-liposhape-1').ordem_home).toBe(2);
  });

  test('aba da página de resultados filtra por categoria', async ({ page }) => {
    await entrar(page);
    await page.click('#cartao-resultados');
    await page.click('#aba-resultados');
    await page.selectOption('#filtro-categoria', 'corrente');
    await expect(page.locator('#lista-resultados .linha-res')).toHaveCount(2);
    await page.getByRole('button', { name: 'Descer Corrente Fortalecedora' }).first().click();
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
    const lido = await chamar(page, { acao: 'ler' });
    expect(lido.conteudo.resultados.itens.find((x) => x.id === 'vid-corrente-1').ordem).toBe(2);
    expect(lido.conteudo.resultados.itens.find((x) => x.id === 'vid-corrente-2').ordem).toBe(1);
  });

  test('novo antes e depois com o módulo de fotos (stub)', async ({ page }) => {
    await semMidiaReal(page);
    await page.addInitScript(STUB_MIDIA);
    await entrar(page);
    await page.click('#cartao-resultados');
    await page.click('#novo-comparacao');
    await expect(page.locator('#criar')).toBeDisabled();
    await page.locator('[data-caminho="titulo"] input').fill('Teste novo');
    await page.click('#escolher-antes');
    await page.click('#escolher-depois');
    await expect(page.locator('#criar')).toBeEnabled();
    await page.click('#criar');
    await expect(page.locator('#quadro-edicao')).toBeVisible();
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
    const lido = await chamar(page, { acao: 'ler' });
    const novo = lido.conteudo.resultados.itens.find((x) => x.titulo === 'Teste novo');
    expect(novo.tipo).toBe('comparacao');
    expect(novo.antes.enquadramento.automatico).toBe(true);
    expect(novo.depois.largura).toBe(1180);
  });

  test('novo vídeo com o módulo de fotos (stub)', async ({ page }) => {
    await semMidiaReal(page);
    await page.addInitScript(STUB_MIDIA);
    await entrar(page);
    await page.click('#cartao-resultados');
    await page.click('#novo-video');
    await page.locator('[data-caminho="titulo"] input').fill('Vídeo novo');
    await page.click('#escolher-video');
    await page.click('#criar');
    await expect(page.locator('#trocar-video')).toBeVisible();
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
    const lido = await chamar(page, { acao: 'ler' });
    const v = lido.conteudo.resultados.itens.find((x) => x.rotulo === 'Vídeo novo');
    expect(v.tipo).toBe('video');
    expect(v.poster).toBe('assets/posteres/corrente-1.webp');
  });

  test('sem o módulo de envio, mostra aviso simples', async ({ page }) => {
    await semMidiaReal(page);
    await entrar(page);
    await page.click('#cartao-resultados');
    await page.click('#novo-comparacao');
    await page.click('#escolher-antes');
    await expect(page.locator('#aviso-topo')).toContainText('Envio de fotos indisponível no momento');
  });
});

// ===========================================================================
// L13: versões e Desfazer
// ===========================================================================
async function salvarTitulo(page, titulo, rev) {
  const lido = await chamar(page, { acao: 'ler' });
  lido.conteudo.home.hero.titulo = titulo;
  return chamar(page, { acao: 'salvar', conteudo: lido.conteudo, revisao: rev === undefined ? lido.revisao : rev, resumo: 'Título ' + titulo });
}

test.describe('L13 versões e Desfazer', () => {
  test('Desfazer logo depois de salvar volta ao que estava (primeiro save volta ao original)', async ({ page }) => {
    await entrar(page);
    const original = (await chamar(page, { acao: 'ler' })).conteudo.home.hero.titulo;
    await page.click('#cartao-textos');
    const det = await abrirGrupo(page, 'topo');
    await det.locator('[data-caminho="home.hero.titulo"] input').fill('Título provisório');
    await page.click('#salvar');
    const toastEl = page.locator('.toast');
    await expect(toastEl).toContainText('Salvo no site');
    await toastEl.getByRole('button', { name: 'Desfazer' }).click();
    await expect(page.locator('.toast')).toContainText('Desfeito');
    expect((await chamar(page, { acao: 'ler' })).conteudo.home.hero.titulo).toBe(original);
    // desfazer o desfazer traz o título de volta
    await page.locator('.toast').getByRole('button', { name: 'Desfazer' }).click();
    await expect(page.locator('.toast')).toContainText('Desfeito');
    expect((await chamar(page, { acao: 'ler' })).conteudo.home.hero.titulo).toBe('Título provisório');
  });

  test('o aviso Desfazer dura 10 segundos', async ({ page }) => {
    await entrar(page);
    await page.click('#cartao-textos');
    const det = await abrirGrupo(page, 'topo');
    await det.locator('[data-caminho="home.hero.titulo"] input').fill('Aviso longo');
    await page.click('#salvar');
    await expect(page.locator('.toast')).toBeVisible();
    await page.waitForTimeout(7000);
    await expect(page.locator('.toast')).toBeVisible();
    await page.waitForTimeout(4500);
    await expect(page.locator('.toast')).toHaveCount(0);
  });

  test('21 saves deixam 20 versões; voltar para uma versão pede confirmação e dá para desfazer', async ({ page }) => {
    await entrar(page);
    let rev = 0;
    for (let i = 1; i <= 21; i++) {
      const r = await salvarTitulo(page, 'T' + i, rev);
      expect(r.ok).toBe(true);
      rev = r.revisao;
    }
    const lista = await chamar(page, { acao: 'versoes' });
    expect(lista.versoes.length).toBe(21); // 20 guardadas + o conteúdo original
    expect(lista.versoes.map((v) => v.n).slice(0, 2)).toEqual([21, 20]);
    expect(lista.versoes.some((v) => v.n === 1)).toBe(false);

    await page.reload();
    await page.click('#cartao-versoes');
    await expect(page.locator('#lista-versoes .versao')).toHaveCount(21);
    await expect(page.locator('.versao[data-n="21"]')).toContainText('versão que está no site agora');
    await page.locator('.versao[data-n="15"]').getByRole('button', { name: /Voltar para/ }).click();
    await expect(page.locator('#modal-ok')).toBeVisible();
    await page.click('#modal-cancelar');
    expect((await chamar(page, { acao: 'ler' })).conteudo.home.hero.titulo).toBe('T21');
    await page.locator('.versao[data-n="15"]').getByRole('button', { name: /Voltar para/ }).click();
    await page.click('#modal-ok');
    await expect(page.locator('.toast')).toContainText('Pronto');
    expect((await chamar(page, { acao: 'ler' })).conteudo.home.hero.titulo).toBe('T15');
    await page.locator('.toast').getByRole('button', { name: 'Desfazer' }).click();
    await expect(page.locator('.toast')).toContainText('Desfeito');
    expect((await chamar(page, { acao: 'ler' })).conteudo.home.hero.titulo).toBe('T21');
    // a versão original sempre está na lista
    await expect(page.locator('.versao[data-n="0"]')).toContainText('Conteúdo original');
  });

  test('a mídia só vai para a lixeira quando nenhuma versão guardada usa mais', async ({ page }) => {
    const pasta = path.join(__dirname, '..', 'conteudo', 'midia', '2099');
    const arquivo = path.join(pasta, 'a1b2c3d4e5f60718.jpg');
    const privado = path.join(DADOS, 'midia-privada', '2099', 'a1b2c3d4e5f60718.jpg');
    fs.mkdirSync(pasta, { recursive: true });
    fs.copyFileSync(path.join(__dirname, '..', 'assets', 'results', 'drenagem-antes1.webp'), arquivo);
    try {
      await entrar(page);
      const trocar = async (arq, rev) => {
        const lido = await chamar(page, { acao: 'ler' });
        lido.conteudo.resultados.itens.find((x) => x.id === 'foto-ultrassom-1').arquivo = arq;
        return chamar(page, { acao: 'salvar', conteudo: lido.conteudo, revisao: rev, resumo: 'Trocou a foto' });
      };
      let r = await trocar('conteudo/midia/2099/a1b2c3d4e5f60718.jpg', 0);
      expect(r.ok).toBe(true);
      r = await trocar('assets/results/ultrassom-antes1.jpeg', r.revisao);
      expect(r.ok).toBe(true);
      expect((await chamar(page, { acao: 'lixeira' })).itens.length).toBe(0);
      for (let i = 0; i < 18; i++) {
        r = await salvarTitulo(page, 'L' + i, r.revisao);
        expect(r.ok).toBe(true);
      }
      // 20 saves: a versão 1 (que usava a foto) ainda está guardada. A foto já saiu do ar, mas fica na área privada.
      expect(fs.existsSync(arquivo)).toBe(false);
      expect(fs.existsSync(privado)).toBe(true);
      expect((await chamar(page, { acao: 'lixeira' })).itens.length).toBe(0);
      r = await salvarTitulo(page, 'L-final', r.revisao); // 21º: a versão 1 sai do histórico
      expect(r.ok).toBe(true);
      const lix = await chamar(page, { acao: 'lixeira' });
      expect(lix.itens.length).toBe(1);
      expect(lix.itens[0].nome).toBe('a1b2c3d4e5f60718.jpg');
      expect(fs.existsSync(arquivo)).toBe(false);
      expect(fs.existsSync(privado)).toBe(false);
    } finally {
      fs.rmSync(arquivo, { force: true });
      fs.rmSync(pasta, { recursive: true, force: true });
      fs.rmSync(path.dirname(privado), { recursive: true, force: true });
    }
  });
});

// ===========================================================================
// P3: apagar com confirmação e lixeira
// ===========================================================================
test.describe('P3 apagar e lixeira', () => {
  test('apagar pede confirmação com o nome, tira do site e dá para desfazer', async ({ page }) => {
    await entrar(page);
    const antes = (await chamar(page, { acao: 'ler' })).conteudo.resultados.itens;
    await page.click('#cartao-resultados');
    const linha = page.locator('#lista-resultados .linha-res').first();
    const id = await linha.getAttribute('data-id');
    const nome = await linha.locator('strong').innerText();
    await linha.getByRole('button', { name: /^Apagar/ }).click();
    await expect(page.locator('.modal')).toContainText(nome);
    await page.click('#modal-cancelar');
    expect((await chamar(page, { acao: 'ler' })).conteudo.resultados.itens.length).toBe(antes.length);

    await linha.getByRole('button', { name: /^Apagar/ }).click();
    await page.click('#modal-ok');
    await expect(page.locator('.toast')).toContainText('foi apagado');
    await expect(page.locator('.linha-res[data-id="' + id + '"]')).toHaveCount(0);
    let depois = (await chamar(page, { acao: 'ler' })).conteudo.resultados.itens;
    expect(depois.length).toBe(antes.length - 1);
    expect(depois.some((i) => i.id === id)).toBe(false);

    await page.locator('.toast').getByRole('button', { name: 'Desfazer' }).click();
    await expect(page.locator('.toast')).toContainText('Desfeito');
    depois = (await chamar(page, { acao: 'ler' })).conteudo.resultados.itens;
    expect(depois.length).toBe(antes.length);
    expect(depois.some((i) => i.id === id)).toBe(true);
  });

  test('apagar pela tela de edição volta para a lista', async ({ page }) => {
    await entrar(page);
    await page.click('#cartao-resultados');
    const id = await page.locator('#lista-resultados .linha-res').first().getAttribute('data-id');
    await page.click('#topo-voltar, header.topo .botao');
    await abrirItem(page, id);
    await page.click('#apagar-item');
    await page.click('#modal-ok');
    await expect(page.locator('#lista-resultados')).toBeVisible();
    await expect(page.locator('.linha-res[data-id="' + id + '"]')).toHaveCount(0);
  });

  test('a lixeira lista o arquivo e Restaurar traz de volta', async ({ page }) => {
    const pasta = path.join(__dirname, '..', 'conteudo', 'midia', '2099');
    const arquivo = path.join(pasta, 'a1b2c3d4e5f60718.jpg');
    const privado = path.join(DADOS, 'midia-privada', '2099', 'a1b2c3d4e5f60718.jpg');
    fs.mkdirSync(pasta, { recursive: true });
    fs.copyFileSync(path.join(__dirname, '..', 'assets', 'results', 'drenagem-antes1.webp'), arquivo);
    try {
      await entrar(page);
      const trocar = async (arq, rev) => {
        const lido = await chamar(page, { acao: 'ler' });
        lido.conteudo.resultados.itens.find((x) => x.id === 'foto-ultrassom-1').arquivo = arq;
        return chamar(page, { acao: 'salvar', conteudo: lido.conteudo, revisao: rev, resumo: 'Trocou a foto' });
      };
      let r = await trocar('conteudo/midia/2099/a1b2c3d4e5f60718.jpg', 0);
      r = await trocar('assets/results/ultrassom-antes1.jpeg', r.revisao);
      for (let i = 0; i < 19; i++) {
        r = await salvarTitulo(page, 'X' + i, r.revisao);
        expect(r.ok).toBe(true);
      }
      expect(fs.existsSync(arquivo)).toBe(false);
      await page.reload();
      await page.click('#cartao-lixeira');
      const linha = page.locator('#lista-lixeira .linha-res');
      await expect(linha).toHaveCount(1);
      await expect(linha).toContainText('a1b2c3d4e5f60718.jpg');
      await linha.getByRole('button', { name: /Restaurar/ }).click();
      await expect(page.locator('.toast')).toContainText('voltou');
      await expect(page.locator('#lista-lixeira')).toContainText('vazia');
      expect(fs.existsSync(arquivo)).toBe(true);
    } finally {
      fs.rmSync(arquivo, { force: true });
      fs.rmSync(pasta, { recursive: true, force: true });
      fs.rmSync(path.dirname(privado), { recursive: true, force: true });
    }
  });
});

// ===========================================================================
// L14: aviso de outra tela (409) e rascunho local
// ===========================================================================
test.describe('L14 conflito e rascunho', () => {
  test('salvar depois de o site mudar em outra tela mostra o aviso e carrega a versão nova', async ({ page, context }) => {
    await entrar(page);
    const outra = await context.newPage();
    await outra.goto('/painel/');
    await expect(outra.locator('#sair')).toBeVisible();
    await outra.click('#cartao-textos');
    const det2 = await abrirGrupo(outra, 'topo');
    await det2.locator('[data-caminho="home.hero.titulo"] input').fill('Título da outra tela');
    await outra.click('#salvar');
    await expect(outra.locator('.toast')).toContainText('Salvo no site');

    await page.click('#cartao-textos');
    const det = await abrirGrupo(page, 'topo');
    await det.locator('[data-caminho="home.hero.titulo"] input').fill('Título da primeira tela');
    await page.click('#salvar');
    await expect(page.locator('#aviso-topo')).toContainText('O site foi alterado em outra tela');
    expect((await chamar(page, { acao: 'ler' })).conteudo.home.hero.titulo).toBe('Título da outra tela');
    await page.click('#carregar-nova');
    await expect(page.locator('.toast')).toContainText('versão mais nova');
    await page.click('#cartao-textos');
    const det3 = await abrirGrupo(page, 'topo');
    await expect(det3.locator('[data-caminho="home.hero.titulo"] input')).toHaveValue('Título da outra tela');
    await outra.close();
  });

  test('sessão expirada guarda rascunho e oferece de volta depois de entrar', async ({ page, context }) => {
    await entrar(page);
    await page.click('#cartao-textos');
    const det = await abrirGrupo(page, 'topo');
    await det.locator('[data-caminho="home.hero.titulo"] input').fill('Meu texto guardado');
    await context.clearCookies();
    await page.click('#salvar');
    await expect(page.locator('#aviso-topo')).toContainText('entre de novo');
    await expect(page.locator('#entrar-de-novo')).toBeVisible();
    expect(await page.evaluate(() => window.localStorage.getItem('painel-rascunho'))).toContain('Meu texto guardado');

    await page.click('#entrar-de-novo');
    await expect(page.locator('#aviso-rascunho')).toBeVisible();
    await page.fill('#senha', SENHA);
    await page.click('button[type=submit]');
    await expect(page.locator('#oferta-rascunho')).toBeVisible();
    await page.click('#rascunho-recuperar');
    const det2 = await abrirGrupo(page, 'topo');
    await expect(det2.locator('[data-caminho="home.hero.titulo"] input')).toHaveValue('Meu texto guardado');
    await page.click('#salvar');
    await expect(page.locator('.toast')).toContainText('Salvo no site');
    expect((await chamar(page, { acao: 'ler' })).conteudo.home.hero.titulo).toBe('Meu texto guardado');
    expect(await page.evaluate(() => window.localStorage.getItem('painel-rascunho'))).toBeNull();
  });

  test('rascunho de uma revisão antiga só é recuperado depois de um sim claro na janela de confirmação', async ({ page }) => {
    await entrar(page);
    await page.evaluate(() => {
      const c = { home: {}, resultados: { itens: [] } };
      window.localStorage.setItem('painel-rascunho', JSON.stringify({ c, rev: -5, tela: 'textos', quando: new Date().toISOString() }));
    });
    await page.reload();
    await expect(page.locator('#oferta-rascunho')).toContainText('O site foi alterado desde então');
    await page.click('#rascunho-recuperar');
    await expect(page.locator('#modal-ok')).toBeVisible();
    await expect(page.locator('.modal')).toContainText('tomar o lugar do que está no site agora');
    // Não: nada muda e o rascunho continua guardado
    await page.click('#modal-cancelar');
    await expect(page.locator('#oferta-rascunho')).toBeVisible();
    expect(await page.evaluate(() => window.localStorage.getItem('painel-rascunho'))).not.toBeNull();
    // Sim: recupera
    await page.click('#rascunho-recuperar');
    await page.click('#modal-ok');
    await expect(page.locator('#oferta-rascunho')).toHaveCount(0);
    expect(await page.evaluate(() => window.localStorage.getItem('painel-rascunho'))).toBeNull();
  });

  test('descartar o rascunho apaga o que estava guardado', async ({ page }) => {
    await entrar(page);
    await page.evaluate(() => {
      const c = { home: {}, resultados: { itens: [] } };
      window.localStorage.setItem('painel-rascunho', JSON.stringify({ c, rev: 0, tela: 'textos', quando: new Date().toISOString() }));
    });
    await page.reload();
    await expect(page.locator('#oferta-rascunho')).toBeVisible();
    await page.click('#rascunho-descartar');
    await expect(page.locator('#oferta-rascunho')).toHaveCount(0);
    expect(await page.evaluate(() => window.localStorage.getItem('painel-rascunho'))).toBeNull();
  });
});

// ===========================================================================
// P4: mensagens de erro em português claro
// ===========================================================================
const TECNICO = /\b(csrf|json|http|https|api|php|undefined|null|exception|status|timeout|fetch|token|[1-5]\d\d)\b|erro \d|error/i;

test.describe('P4 mensagens', () => {
  const casos = [
    { nome: 'rede', resposta: null, texto: 'A internet caiu. Nada foi mudado no site.' },
    { nome: 'servidor', resposta: { status: 500, corpo: { ok: false, erro: 'servidor' } }, texto: 'Não consegui salvar. O site continua como estava.' },
    { nome: 'pedido', resposta: { status: 400, corpo: { ok: false, erro: 'pedido' } }, texto: 'Não consegui entender o pedido.' },
    { nome: 'metodo', resposta: { status: 405, corpo: { ok: false, erro: 'metodo' } }, texto: 'Não consegui entender o pedido.' },
    { nome: 'nao_encontrada', resposta: { status: 404, corpo: { ok: false, erro: 'nao_encontrada' } }, texto: 'Essa versão não existe mais.' },
    { nome: 'invalido', resposta: { status: 422, corpo: { ok: false, erro: 'invalido', detalhes: [] } }, texto: 'Alguns campos precisam de ajuste.' },
    { nome: 'conflito', resposta: { status: 409, corpo: { ok: false, erro: 'conflito' } }, texto: 'O site foi alterado em outra tela.' },
    { nome: 'sessao', resposta: { status: 401, corpo: { ok: false, erro: 'sessao' } }, texto: 'Por segurança, entre de novo. Suas mudanças foram guardadas.' },
    { nome: 'csrf', resposta: { status: 403, corpo: { ok: false, erro: 'csrf' } }, texto: 'Por segurança, entre de novo.' },
    { nome: 'desconhecido', resposta: { status: 418, corpo: { ok: false, erro: 'xyz_estranho' } }, texto: 'Não consegui salvar.' }
  ];
  for (const caso of casos) {
    test('erro ' + caso.nome + ' mostra a frase certa e nenhum termo técnico', async ({ page }) => {
      await entrar(page);
      await page.click('#cartao-textos');
      const det = await abrirGrupo(page, 'topo');
      await det.locator('[data-caminho="home.hero.titulo"] input').fill('Teste de erro ' + caso.nome);
      await page.route('**/api.php', (route) => {
        let acao = '';
        try { acao = JSON.parse(route.request().postData() || '{}').acao; } catch (e) { acao = ''; }
        if (acao !== 'salvar') return route.continue();
        if (!caso.resposta) return route.abort();
        return route.fulfill({ status: caso.resposta.status, contentType: 'application/json', body: JSON.stringify(caso.resposta.corpo) });
      });
      await page.click('#salvar');
      const aviso = page.locator('#aviso-topo');
      await expect(aviso).toContainText(caso.texto);
      const t = await aviso.innerText();
      expect(t).not.toMatch(TECNICO);
      if (caso.nome === 'rede' || caso.nome === 'servidor') await expect(page.locator('#tentar-de-novo')).toBeVisible();
    });
  }

  test('lixeira que não consegue restaurar mostra a frase certa', async ({ page }) => {
    await entrar(page);
    await page.route('**/api.php', (route) => {
      let acao = '';
      try { acao = JSON.parse(route.request().postData() || '{}').acao; } catch (e) { acao = ''; }
      if (acao === 'lixeira') return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ ok: true, itens: [{ id: 'abc', nome: 'foto.jpg', data: '2026-01-01T10:00:00+00:00', tamanho: 2048 }] }) });
      if (acao === 'lixeira_restaurar') return route.fulfill({ status: 409, contentType: 'application/json', body: JSON.stringify({ ok: false, erro: 'nao_restaurou' }) });
      return route.continue();
    });
    await page.click('#cartao-lixeira');
    await page.getByRole('button', { name: /Restaurar/ }).click();
    const t = await page.locator('.toast').innerText();
    expect(t).toContain('Não foi possível trazer de volta');
    expect(t).not.toMatch(TECNICO);
  });
});

// ===========================================================================
// P5: ajustes para iPhone e página de ajuda
// ===========================================================================
/** Confere a tela aberta: alvos de toque, tamanho da letra dos campos e rolagem lateral. */
async function auditarTela(page) {
  return page.evaluate(() => {
    const visivel = (el) => {
      const r = el.getBoundingClientRect();
      const cs = getComputedStyle(el);
      return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none';
    };
    const pequenos = [];
    document.querySelectorAll('button, a[href], summary, select, input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=range]), textarea').forEach((el) => {
      if (!visivel(el) || el.closest('.previa, .site-previa, [data-previa]')) return;
      const r = el.getBoundingClientRect();
      if (r.height < 44 || r.width < 44) pequenos.push((el.id || el.className || el.tagName) + ' ' + Math.round(r.width) + 'x' + Math.round(r.height));
    });
    const letra = [];
    document.querySelectorAll('input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=range]), select, textarea').forEach((el) => {
      if (!visivel(el)) return;
      const f = parseFloat(getComputedStyle(el).fontSize);
      if (f < 16) letra.push((el.id || el.tagName) + ' ' + f);
    });
    const doc = document.documentElement;
    return { pequenos, letra, larguraExtra: doc.scrollWidth - window.innerWidth };
  });
}

test.describe('P5 iPhone e ajuda', () => {
  test('telas principais têm alvos ≥44 px, letra ≥16 px nos campos e sem rolagem lateral', async ({ page }, info) => {
    await entrar(page);
    const conferir = async (nome) => {
      const a = await auditarTela(page);
      expect(a.pequenos, nome + ': alvos pequenos').toEqual([]);
      expect(a.letra, nome + ': letra pequena').toEqual([]);
      expect(a.larguraExtra, nome + ': rolagem lateral').toBeLessThanOrEqual(0);
    };
    await conferir('início');
    await page.click('#cartao-textos');
    await abrirGrupo(page, 'topo');
    await conferir('textos');
    await page.click('header.topo .botao');
    await page.click('#cartao-resultados');
    await expect(page.locator('#lista-resultados')).toBeVisible();
    await conferir('resultados');
    const id = await page.locator('#lista-resultados .linha-res').first().getAttribute('data-id');
    await page.locator('[data-id="' + id + '"]').getByRole('button', { name: /^Editar/ }).click();
    await expect(page.locator('#quadro-edicao')).toBeVisible();
    await conferir('editar item');
    await page.screenshot({ path: info.outputPath('painel-editar-' + info.project.name + '.png'), fullPage: false });
    await page.click('header.topo .botao');
    await page.click('header.topo .botao');
    await page.click('#cartao-versoes');
    await expect(page.locator('#lista-versoes .versao').first()).toBeVisible();
    await conferir('versões');
    await page.click('header.topo .botao');
    await page.click('#cartao-lixeira');
    await expect(page.locator('#lista-lixeira')).toBeVisible();
    await conferir('lixeira');
  });

  test('a barra de salvar respeita a área segura do iPhone', async ({ page }) => {
    await entrar(page);
    await page.click('#cartao-textos');
    const css = await page.evaluate(() => {
      const b = document.querySelector('.barra-acao');
      const p = getComputedStyle(b);
      return { fixa: p.position, rodape: b.getBoundingClientRect().bottom <= window.innerHeight + 1 };
    });
    expect(css.fixa).toBe('fixed');
    expect(css.rodape).toBe(true);
    const fonte = await (await page.request.get('/painel/painel.css')).text();
    expect(fonte).toContain('env(safe-area-inset-bottom)');
    expect(await page.locator('meta[name=viewport]').getAttribute('content')).toContain('viewport-fit=cover');
  });

  test('a página de ajuda abre pelo cartão, tem os passos e volta ao painel', async ({ page }, info) => {
    await entrar(page);
    await page.click('#cartao-ajuda');
    await expect(page).toHaveURL(/\/painel\/ajuda\.php/);
    await expect(page.locator('h1')).toHaveText('Ajuda');
    await expect(page.locator('.ajuda-secao')).toHaveCount(7);
    await expect(page.locator('body')).toContainText('Desfazer');
    const a = await auditarTela(page);
    expect(a.pequenos).toEqual([]);
    expect(a.larguraExtra).toBeLessThanOrEqual(0);
    await page.screenshot({ path: info.outputPath('painel-ajuda-' + info.project.name + '.png'), fullPage: true });
    await page.click('#ajuda-voltar');
    await expect(page.locator('#sair')).toBeVisible();
  });

  test('a ajuda sem entrar manda para o login', async ({ page }) => {
    await page.goto('/painel/ajuda.php');
    await expect(page.locator('#senha')).toBeVisible();
  });
});

// ===========================================================================
// T10: diagnóstico de IP
// ===========================================================================
test.describe('T10 diagnóstico de IP', () => {
  test('a tela mostra o endereço usado, o da conexão e o cabeçalho de proxy', async ({ page }) => {
    await entrar(page);
    const api = await chamar(page, { acao: 'diagnostico_ip' });
    expect(api.ok).toBe(true);
    await page.click('#cartao-diagnostico');
    const caixa = page.locator('#diagnostico-ip');
    await expect(caixa).toContainText('Endereço que o painel usa');
    await expect(caixa).toContainText('Endereço da conexão direta');
    await expect(caixa).toContainText('Cabeçalho de proxy configurado');
    await expect(caixa.locator('dd').first()).toHaveText(api.ip_cliente || '(vazio)');
    const t = await caixa.innerText();
    expect(t).not.toMatch(/undefined|null|\[object/);
  });

  test('a ação diagnostico_ip exige entrar e o código de proteção', async ({ request }) => {
    const r = await request.post('/painel/api.php', { data: { acao: 'diagnostico_ip' } });
    expect([401, 403]).toContain(r.status());
  });

  test('sem proxy configurado a tela explica em português', async ({ page }) => {
    await entrar(page);
    await page.route('**/api.php', (route) => {
      let acao = '';
      try { acao = JSON.parse(route.request().postData() || '{}').acao; } catch (e) { acao = ''; }
      if (acao !== 'diagnostico_ip') return route.continue();
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ ok: true, ip_cliente: '203.0.113.9', remote_addr: '10.0.0.1', cabecalho: 'HTTP_X_FORWARDED_FOR', valor_cabecalho: '', proxies_confiaveis: ['10.0.0.1'], remoto_e_proxy_confiavel: true, ambiente: 'teste' }) });
    });
    await page.click('#cartao-diagnostico');
    await expect(page.locator('#diagnostico-ip')).toContainText('não está conseguindo ler o endereço real');
  });
});

// @@FIM
