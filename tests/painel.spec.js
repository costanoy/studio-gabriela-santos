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
  for (const d of ['historico', 'lixeira']) fs.rmSync(path.join(DADOS, d), { recursive: true, force: true });
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

// @@FIM
