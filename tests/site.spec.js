const { test, expect } = require('@playwright/test');

const isMobile = (ti) => ti.project.name.startsWith('iphone');
const isWebkit = (ti) => ti.project.name === 'iphone-webkit';

// Luminancia media + desvio de um PNG (detecta tela preta)
async function stats(page, buf) {
  return page.evaluate(async (b64) => {
    const bin = atob(b64); const arr = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
    const bmp = await createImageBitmap(new Blob([arr], { type: 'image/png' }));
    const c = document.createElement('canvas'); c.width = 64; c.height = 64;
    const x = c.getContext('2d'); x.drawImage(bmp, 0, 0, 64, 64);
    const d = x.getImageData(0, 0, 64, 64).data; let s = 0, s2 = 0, n = 0;
    for (let i = 0; i < d.length; i += 4) { const l = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2]; s += l; s2 += l * l; n++; }
    const m = s / n; return { mean: m, sd: Math.sqrt(Math.max(0, s2 / n - m * m)) };
  }, buf.toString('base64'));
}

// Mede bytes de mp4 recebidos (so Chromium, via CDP)
async function mp4Bytes(page, browserName, fn) {
  if (browserName !== 'chromium') return null;
  const cdp = await page.context().newCDPSession(page); await cdp.send('Network.enable');
  const urls = {}, bytes = {};
  cdp.on('Network.requestWillBeSent', (e) => { if (e.request.url.endsWith('.mp4')) urls[e.requestId] = e.request.url.split('/').pop(); });
  cdp.on('Network.dataReceived', (e) => { if (urls[e.requestId]) bytes[urls[e.requestId]] = (bytes[urls[e.requestId]] || 0) + e.dataLength; });
  await fn(); return bytes;
}

const imgOk = (page, p) => page.evaluate((u) => new Promise((r) => { const i = new Image(); i.onload = () => r(i.naturalWidth); i.onerror = () => r(0); i.src = u; }), p);

test.describe('index.html', () => {
  test.beforeEach(async ({ page }) => { await page.goto('/index.html'); });

  test('carrega sem erros de JS e com titulo', async ({ page }) => {
    const errs = []; page.on('pageerror', (e) => errs.push(e.message));
    await page.reload();
    await expect(page).toHaveTitle(/Gabriela/i);
    await expect(page.locator('h1')).toContainText('Studio Gabriela Santos');
    expect(errs).toEqual([]);
  });

  test('navegacao por ancoras', async ({ page }, ti) => {
    for (const id of ['sobre', 'procedimentos', 'resultados', 'cursos', 'depoimentos', 'contato']) {
      if (isMobile(ti)) { await page.locator('#nav-toggle').click(); await page.locator(`#mobile-nav-wrap a[href="#${id}"]`).click(); }
      else await page.locator(`#desktop-nav a[href="#${id}"]`).click();
      await expect.poll(() => page.evaluate((i) => Math.abs(document.getElementById(i).getBoundingClientRect().top) < 400, id), { timeout: 5000 }).toBe(true);
      expect(page.url()).toContain('#' + id);
    }
  });

  test('Inicio volta ao topo e troca hash', async ({ page }, ti) => {
    await page.evaluate(() => document.getElementById('contato').scrollIntoView());
    if (isMobile(ti)) { await page.locator('#nav-toggle').click(); await page.locator('#mobile-nav-wrap a[href="#home"]').click(); }
    else await page.locator('#desktop-nav a[href="#home"]').click();
    await expect.poll(() => page.evaluate(() => window.scrollY), { timeout: 6000 }).toBeLessThan(5);
    expect(page.url()).toContain('#home');
  });

  test('menu mobile abre e fecha', async ({ page }, ti) => {
    test.skip(!isMobile(ti), 'so celular');
    const wrap = page.locator('#mobile-nav-wrap');
    await expect(wrap).toBeHidden();
    await page.locator('#nav-toggle').click(); await expect(wrap).toBeVisible();
    await page.locator('#nav-toggle').click(); await expect(wrap).toBeHidden();
    await page.locator('#nav-toggle').click(); await expect(wrap).toBeVisible();
    await wrap.locator('a[href="#cursos"]').click(); await expect(wrap).toBeHidden();
  });

  test('menu desktop visivel e hamburguer escondido', async ({ page }, ti) => {
    test.skip(isMobile(ti), 'so desktop');
    await expect(page.locator('#desktop-nav')).toBeVisible();
    await expect(page.locator('#nav-toggle')).toBeHidden();
  });

  test('logo troca no scroll', async ({ page }) => {
    const op = (id) => page.evaluate((i) => +getComputedStyle(document.getElementById(i)).opacity, id);
    await expect(page.locator('#site-header')).not.toHaveClass(/scrolled/);
    const full0 = await op('logo-full');
    await page.evaluate(() => window.scrollTo(0, 600));
    await expect(page.locator('#site-header')).toHaveClass(/scrolled/);
    await page.waitForTimeout(900);
    const full1 = await op('logo-full'), gs1 = await op('logo-gs');
    console.log('logo opacity full/gs depois do scroll', full1, gs1);
    expect(full0).toBeGreaterThan(0.9);
    expect(gs1).toBeGreaterThan(0.9); expect(full1).toBeLessThan(0.1);
    await page.evaluate(() => window.scrollTo(0, 0));
    await expect(page.locator('#site-header')).not.toHaveClass(/scrolled/);
  });

  test('comparadores antes/depois com mouse (arrastar)', async ({ page }, ti) => {
    test.skip(isWebkit(ti), 'WebKit mobile coberto por toque');
    const sliders = page.locator('#resultados-grid input[type=range]');
    expect(await sliders.count()).toBe(5);
    for (let i = 0; i < 5; i++) {
      const s = sliders.nth(i); await s.scrollIntoViewIfNeeded();
      const b = await s.boundingBox();
      await page.mouse.move(b.x + b.width / 2, b.y + b.height / 2); await page.mouse.down();
      await page.mouse.move(b.x + b.width * 0.8, b.y + b.height / 2, { steps: 8 }); await page.mouse.up();
      const v = +(await s.inputValue()); expect(v).toBeGreaterThan(65);
      const clip = await s.evaluate((el) => el.closest('div').querySelector('.ba-clip').style.clipPath);
      const line = await s.evaluate((el) => el.closest('div').querySelector('.ba-line').style.left);
      expect(clip).toMatch(/inset/);
      expect(parseFloat(line)).toBeCloseTo(v, 0);
    }
  });

  test('comparadores antes/depois com toque (tap e arrastar)', async ({ page, browserName }, ti) => {
    test.skip(!isMobile(ti), 'so celular');
    const s = page.locator('#resultados-grid input[type=range]').first();
    await s.scrollIntoViewIfNeeded();
    const b = await s.boundingBox();
    await page.touchscreen.tap(b.x + b.width * 0.2, b.y + b.height / 2);
    const v1 = +(await s.inputValue()); console.log('valor apos tap 20%:', v1);
    expect(v1).toBeLessThan(35);
    if (browserName === 'chromium') {
      const cdp = await page.context().newCDPSession(page);
      const y = b.y + b.height / 2; const x0 = b.x + b.width * 0.2;
      await cdp.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [{ x: x0, y }] });
      for (let i = 1; i <= 10; i++) await cdp.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [{ x: x0 + (b.width * 0.6 * i) / 10, y }] });
      await cdp.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
      const v2 = +(await s.inputValue()); console.log('valor apos arrasto por toque:', v2);
      expect(v2).toBeGreaterThan(65);
    }
    const clip = await s.evaluate((el) => el.closest('div').querySelector('.ba-clip').style.clipPath);
    expect(clip).toMatch(/inset/);
  });

  test('Ver mais leva a resultados.html', async ({ page }, ti) => {
    const link = isMobile(ti) ? page.locator('#ver-mais-mobile') : page.locator('#ver-mais-cell a');
    await link.scrollIntoViewIfNeeded(); await expect(link).toBeVisible();
    await Promise.all([page.waitForURL(/resultados\.html/), link.click()]);
    await expect(page.locator('h1')).toContainText(/resultados/i);
  });

  test('links externos corretos, target=_blank e rel=noopener', async ({ page }) => {
    const hrefs = await page.$$eval('a[href^="http"]', (as) => as.map((a) => ({ h: a.href, t: a.target, r: a.rel })));
    expect(hrefs.length).toBeGreaterThan(8);
    for (const l of hrefs) { expect(l.t, l.h).toBe('_blank'); expect(l.r, l.h).toContain('noopener'); }
    const all = hrefs.map((l) => l.h);
    expect(all.filter((h) => h.startsWith('https://wa.me/5562984141116')).length).toBeGreaterThanOrEqual(6);
    expect(all).toContain('https://instagram.com/gabisantos_estetica');
    expect(all).toContain('https://forms.gle/nkKaVqY9BxyV5XVPA');
    expect(all).toContain('https://maps.app.goo.gl/SFJzFnDdAkket245A');
    for (const h of all) expect(h).toMatch(/^https:\/\/(wa\.me\/5562984141116|instagram\.com\/gabisantos_estetica|forms\.gle\/|maps\.app\.goo\.gl\/|www\.google\.com\/maps)/);
    await expect(page.locator('#avaliacao-online-link')).toHaveAttribute('href', /forms\.gle/);
  });

  test('links externos respondem (rede)', async ({ request }, ti) => {
    test.skip(ti.project.name !== 'desktop', 'basta uma vez');
    const urls = ['https://forms.gle/nkKaVqY9BxyV5XVPA', 'https://maps.app.goo.gl/SFJzFnDdAkket245A', 'https://wa.me/5562984141116', 'https://instagram.com/gabisantos_estetica'];
    for (const u of urls) {
      try { const r = await request.get(u, { timeout: 15000 }); console.log('LINK', u, r.status()); expect(r.status()).toBeLessThan(500); }
      catch (e) { console.log('LINK sem resposta', u, e.message.slice(0, 80)); }
    }
  });

  test('carrosseis: setas movem o scroll', async ({ page }) => {
    for (const id of ['procedimentos', 'depoimentos']) {
      const grid = page.locator(`#${id}-grid`); await page.evaluate((i) => document.getElementById(i + '-grid').scrollIntoView({ block: 'center' }), id);
      await page.locator(`#${id}-arrow-right`).click({ force: true });
      await expect.poll(() => grid.evaluate((g) => g.scrollLeft), { timeout: 4000 }).toBeGreaterThan(10);
      await page.locator(`#${id}-arrow-left`).click({ force: true });
      await expect.poll(() => grid.evaluate((g) => Math.abs(g.scrollLeft - g.children[0].offsetLeft)), { timeout: 4000 }).toBeLessThan(5);
    }
    expect(await page.locator('#procedimentos-grid').evaluate((g) => g.children.length)).toBe(7);
    expect(await page.locator('#depoimentos-grid').evaluate((g) => g.children.length)).toBe(4);
  });

  test('carrossel de resultados (setas no celular)', async ({ page }, ti) => {
    test.skip(!isMobile(ti), 'setas so no celular');
    const grid = page.locator('#resultados-grid'); await grid.scrollIntoViewIfNeeded();
    await page.locator('button[aria-label="Ver mais resultados"]').click({ force: true });
    await expect.poll(() => grid.evaluate((g) => g.scrollLeft), { timeout: 4000 }).toBeGreaterThan(10);
  });

  test('arrastar carrossel de procedimentos com mouse', async ({ page }, ti) => {
    test.skip(isMobile(ti), 'so desktop');
    await page.evaluate(() => document.getElementById('procedimentos-grid').scrollIntoView({ block: 'center' }));
    await page.waitForTimeout(1200);
    const grid = page.locator('#procedimentos-grid');
    const b = await grid.boundingBox();
    await page.mouse.move(b.x + b.width * 0.8, b.y + 80); await page.mouse.down();
    await page.mouse.move(b.x + b.width * 0.2, b.y + 80, { steps: 10 }); await page.mouse.up();
    await expect.poll(() => grid.evaluate((g) => g.scrollLeft), { timeout: 3000 }).toBeGreaterThan(50);
  });

  test('cursos: ver descricao completa abre e fecha', async ({ page }) => {
    for (const id of ['curso-metodo', 'curso-limpeza']) {
      const el = page.locator('#' + id); const btn = page.locator(`button[onclick*="${id}"]`);
      await btn.scrollIntoViewIfNeeded(); await expect(el).toBeHidden();
      await btn.click(); await expect(el).toBeVisible(); await expect(btn).toHaveText('Fechar descrição');
      await btn.click(); await expect(el).toBeHidden(); await expect(btn).toHaveText('Ver descrição completa');
    }
  });

  test('animacoes reveal deixam secoes visiveis', async ({ page }) => {
    const ids = await page.$$eval('.reveal', (e) => e.map((x) => x.id));
    expect(ids.length).toBeGreaterThanOrEqual(6);
    for (const id of ids) {
      await page.evaluate((i) => document.getElementById(i).scrollIntoView(), id);
      const el = page.locator('#' + id);
      await expect(el).toHaveClass(/visible/, { timeout: 4000 });
      await expect.poll(() => el.evaluate((e) => +getComputedStyle(e).opacity), { timeout: 3000 }).toBeGreaterThan(0.95);
    }
  });

  test('mapa embed e botao Google Maps', async ({ page }) => {
    await page.evaluate(() => document.getElementById('contato').scrollIntoView());
    const f = page.locator('#contato iframe'); await f.scrollIntoViewIfNeeded();
    await expect(f).toBeVisible();
    await expect(f).toHaveAttribute('src', /google\.com\/maps\?q=-15\.33.*output=embed/);
    await expect(f).toHaveAttribute('loading', 'lazy');
    expect((await f.boundingBox()).height).toBeGreaterThan(200);
    await expect(page.locator('#contato a[href*="maps.app.goo.gl"]')).toBeVisible();
    await page.waitForTimeout(4000);
    const fr = page.frames().find((x) => x.url().includes('google.com/maps'));
    console.log('iframe do mapa carregou frame:', !!fr);
    expect(fr, 'frame do mapa nao carregou (rede?)').toBeTruthy();
  });

  test('contato e rodape', async ({ page }) => {
    await expect(page.locator('#contato')).toContainText(/Goian/i);
    const foot = page.locator('footer'); await foot.scrollIntoViewIfNeeded(); await expect(foot).toBeVisible();
    await expect(foot.locator('a[href^="https://wa.me/5562984141116"]').first()).toBeVisible();
    await expect(foot.locator('a[href*="instagram.com/gabisantos_estetica"]').first()).toBeVisible();
    await expect(foot.locator('a[href*="maps.app.goo.gl"]').first()).toBeVisible();
    for (const id of ['sobre', 'procedimentos', 'resultados', 'cursos', 'depoimentos', 'contato']) expect(await foot.locator(`a[href="#${id}"]`).count()).toBeGreaterThan(0);
  });

  test('imagens carregam (sem quebradas)', async ({ page }) => {
    await page.evaluate(() => { for (const i of document.images) i.loading = 'eager'; window.scrollTo(0, document.body.scrollHeight); });
    await page.waitForTimeout(3000);
    const broken = await page.$$eval('img', (is) => is.filter((i) => i.complete && i.naturalWidth === 0).map((i) => i.src));
    expect(broken).toEqual([]);
  });

  test('sem overflow horizontal', async ({ page }) => {
    const o = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(o).toBeLessThanOrEqual(1);
  });
});

test.describe('video da home', () => {
  test('poster existe, carrega e nao ha tela preta antes do play', async ({ page }) => {
    await page.goto('/index.html');
    const v = page.locator('#apresentacao-video'); await v.scrollIntoViewIfNeeded();
    const poster = await v.getAttribute('poster'); expect(poster).toBeTruthy();
    expect(await imgOk(page, poster)).toBeGreaterThan(0);
    await page.waitForTimeout(1500);
    const st = await stats(page, await v.screenshot());
    console.log('HOME video antes do play: media/desvio', st.mean.toFixed(1), st.sd.toFixed(1));
    expect(st.mean, 'tela preta?').toBeGreaterThan(25); expect(st.sd).toBeGreaterThan(10);
  });

  test('nao baixa o video inteiro antes do play', async ({ page, browserName }) => {
    const bytes = await mp4Bytes(page, browserName, async () => {
      await page.goto('/index.html'); await page.waitForTimeout(2500);
      await page.evaluate(() => document.getElementById('apresentacao-video').scrollIntoView()); await page.waitForTimeout(3500);
    });
    console.log('HOME mp4 baixado antes do play:', JSON.stringify(bytes));
    test.skip(bytes === null, 'medicao de bytes so em Chromium');
    const total = Object.values(bytes).reduce((a, x) => a + x, 0);
    expect(total).toBeLessThan(3 * 1024 * 1024);
  });

  test('toca ao clicar no overlay e pausa ao clicar de novo', async ({ page }) => {
    await page.goto('/index.html');
    const v = page.locator('#apresentacao-video'); await v.scrollIntoViewIfNeeded();
    const ov = page.locator('div[onclick="toggleApresentacaoVideo()"]');
    await ov.click();
    const played = await page.waitForFunction(() => { const e = document.getElementById('apresentacao-video'); return e.currentTime > 0.3 && !e.paused; }, null, { timeout: 15000 }).then(() => true).catch(() => false);
    const info = await v.evaluate((e) => ({ err: e.error && e.error.code, rs: e.readyState, ns: e.networkState, t: e.currentTime, w: e.videoWidth }));
    console.log('HOME play:', played, JSON.stringify(info));
    expect(played, JSON.stringify(info)).toBe(true);
    await ov.click(); expect(await v.evaluate((e) => e.paused)).toBe(true);
  });
});

test.describe('resultados.html', () => {
  test.beforeEach(async ({ page }) => { await page.goto('/resultados.html'); });

  test('carrega, logo troca e voltar', async ({ page }) => {
    await expect(page.locator('h1')).toContainText(/resultados/i);
    await page.evaluate(() => window.scrollTo(0, 600));
    await expect(page.locator('#site-header')).toHaveClass(/scrolled/);
    await Promise.all([page.waitForURL(/index\.html|localhost:8765\/(#.*)?$/), page.locator('#rc-back-link').click()]);
  });

  test('comparadores (5) mouse/toque', async ({ page }, ti) => {
    const sliders = page.locator('input[type=range]'); expect(await sliders.count()).toBe(5);
    for (let i = 0; i < 5; i++) {
      const s = sliders.nth(i); await s.scrollIntoViewIfNeeded(); const b = await s.boundingBox();
      if (isMobile(ti)) await page.touchscreen.tap(b.x + b.width * 0.8, b.y + b.height / 2);
      else { await page.mouse.move(b.x + b.width / 2, b.y + b.height / 2); await page.mouse.down(); await page.mouse.move(b.x + b.width * 0.8, b.y + b.height / 2, { steps: 6 }); await page.mouse.up(); }
      expect(+(await s.inputValue())).toBeGreaterThan(65);
      expect(await s.evaluate((el) => el.closest('div').querySelector('.ba-clip').style.clipPath)).toMatch(/inset/);
    }
  });

  test('links externos seguros e rodape aponta para index', async ({ page }) => {
    for (const l of await page.$$eval('a[href^="http"]', (as) => as.map((a) => ({ h: a.href, t: a.target, r: a.rel })))) { expect(l.t, l.h).toBe('_blank'); expect(l.r, l.h).toContain('noopener'); }
    expect(await page.$$eval('footer a[href*="index.html#"]', (as) => as.length)).toBeGreaterThan(3);
  });

  const VIDEOS = ['liposhape-depois3', 'liposhape-pos', 'ultrassom-depois1', 'corrente-1', 'corrente-2'];
  for (const [idx, name] of VIDEOS.entries()) {
    test(`video ${name}: poster, sem tela preta, tocar`, async ({ page }) => {
      const v = page.locator('video').nth(idx);
      expect(await v.getAttribute('src')).toContain(name);
      await v.scrollIntoViewIfNeeded();
      const poster = await v.getAttribute('poster'); expect(poster).toBeTruthy();
      expect(await v.getAttribute('playsinline')).not.toBeNull();
      expect(await imgOk(page, poster), 'poster nao carrega').toBeGreaterThan(0);
      await page.waitForTimeout(1200);
      const st = await stats(page, await v.screenshot());
      console.log(`RES ${name} antes do play: media ${st.mean.toFixed(1)} desvio ${st.sd.toFixed(1)}`);
      expect(st.mean, 'tela preta antes do play').toBeGreaterThan(25); expect(st.sd).toBeGreaterThan(10);
      await v.locator('xpath=following-sibling::div[@onclick][1]').click();
      const played = await page.waitForFunction((i) => { const e = document.querySelectorAll('video')[i]; return e.currentTime > 0.3 && !e.paused; }, idx, { timeout: 15000 }).then(() => true).catch(() => false);
      const info = await v.evaluate((e) => ({ err: e.error && e.error.code, rs: e.readyState, w: e.videoWidth }));
      console.log(`RES ${name} play:`, played, JSON.stringify(info));
      expect(played, JSON.stringify(info)).toBe(true);
    });
  }

  test('mp4 nao baixados por inteiro antes do play', async ({ page, browserName }) => {
    const bytes = await mp4Bytes(page, browserName, async () => {
      await page.goto('/resultados.html');
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2)); await page.waitForTimeout(2500);
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight)); await page.waitForTimeout(3000);
    });
    console.log('RES mp4 antes do play:', JSON.stringify(bytes));
    test.skip(bytes === null, 'medicao de bytes so em Chromium');
    expect(Object.values(bytes).reduce((a, x) => a + x, 0)).toBeLessThan(8 * 1024 * 1024);
  });
});
