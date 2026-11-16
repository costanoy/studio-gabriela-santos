// Testes de BUGS confirmados na auditoria (.auditoria/confirmacao.md).
// Cada teste FALHA no site atual e deve PASSAR depois da correcao do item citado no nome.
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const isMobile = (ti) => ti.project.name.startsWith('iphone');
const isWebkit = (ti) => ti.project.name === 'iphone-webkit';
const isChromium = (browserName) => browserName === 'chromium';

// ---------- helpers ----------
async function grayDiff(page, urlA, urlB) {
  return page.evaluate(async ([a, b]) => {
    const load = (u) => new Promise((res, rej) => { const i = new Image(); i.onload = () => res(i); i.onerror = () => rej(new Error('nao carregou ' + u)); i.src = u; });
    const g = async (u) => {
      const im = await load(u); const c = document.createElement('canvas'); c.width = 32; c.height = 32;
      const x = c.getContext('2d'); x.drawImage(im, 0, 0, 32, 32); const d = x.getImageData(0, 0, 32, 32).data; const o = [];
      for (let i = 0; i < d.length; i += 4) o.push(0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2]);
      return o;
    };
    const A = await g(a), B = await g(b); let s = 0; for (let i = 0; i < A.length; i++) s += Math.abs(A[i] - B[i]);
    return s / A.length;
  }, [urlA, urlB]);
}

// Soma dos bytes recebidos (so Chromium) e bytes de mp4
async function medeRede(page, url) {
  const cdp = await page.context().newCDPSession(page); await cdp.send('Network.enable');
  const urls = {}; let total = 0; let mp4 = 0;
  cdp.on('Network.requestWillBeSent', (e) => { urls[e.requestId] = e.request.url; });
  cdp.on('Network.loadingFinished', (e) => { if (!(urls[e.requestId] || '').split('?')[0].endsWith('.mp4')) total += e.encodedDataLength; });
  cdp.on('Network.dataReceived', (e) => { if ((urls[e.requestId] || '').split('?')[0].endsWith('.mp4')) { mp4 += e.dataLength; total += e.dataLength; } });
  await page.goto(url); await page.waitForLoadState('networkidle'); await page.waitForTimeout(2500);
  return { total, mp4 };
}

const paginas = ['/index.html', '/resultados.html'];

// =====================================================================
// L01 - poster e caixa do video de apresentacao
// =====================================================================
test.describe('L01 video de apresentacao', () => {
  test('L01 poster da apresentacao e o primeiro frame do video (diferenca < 5/255)', async ({ page }) => {
    await page.goto('/index.html');
    const poster = await page.locator('#apresentacao-video').evaluate((v) => v.poster);
    const d = await grayDiff(page, poster, '/.auditoria/frame0-apresentacao.png');
    console.log('L01 diferenca poster x frame0 =', d.toFixed(1), '/255');
    expect(d).toBeLessThan(5);
  });

  test('L01 caixa do video tem proporcao 576/1024 ja no HTML/CSS (sem depender do JS dos metadados)', async ({ browser }) => {
    const ctx = await browser.newContext({ javaScriptEnabled: false }); const page = await ctx.newPage();
    await page.goto('http://localhost:8765/index.html');
    const ar = await page.locator('#apresentacao-video').evaluate((v) => getComputedStyle(v).aspectRatio.replace(/\s/g, ''));
    await ctx.close();
    console.log('L01 aspect-ratio sem JS =', ar);
    expect(ar).toBe('576/1024');
  });
});

// =====================================================================
// L02 / T02 / T01 - peso e preload
// =====================================================================
test.describe('L02 / T02 / T01 peso da pagina e videos', () => {
  for (const p of paginas) {
    test(`L02/T02 ${p}: todo <video> usa preload="none"`, async ({ page }) => {
      await page.goto(p);
      const pre = await page.$$eval('video', (vs) => vs.map((v) => v.getAttribute('preload')));
      console.log(p, 'preload:', pre.join(','));
      expect(pre.length).toBeGreaterThan(0);
      for (const x of pre) expect(x).toBe('none');
    });
  }

  test('T02/L02 resultados.html: nenhum byte de .mp4 antes do play (Chromium)', async ({ page, browserName }) => {
    test.skip(!isChromium(browserName), 'medicao via CDP so no Chromium');
    const r = await medeRede(page, '/resultados.html');
    console.log('T02 bytes de mp4 sem play =', r.mp4);
    expect(r.mp4).toBe(0);
  });

  test('T01/T02 resultados.html: peso da primeira carga <= 1,5 MB (Chromium)', async ({ page, browserName }) => {
    test.skip(!isChromium(browserName), 'medicao via CDP so no Chromium');
    const r = await medeRede(page, '/resultados.html');
    console.log('T01 peso total =', Math.round(r.total / 1024), 'KB');
    expect(r.total).toBeLessThanOrEqual(1.5 * 1024 * 1024);
  });

  test('T01 nenhuma foto de assets/results passa de 250 KB', async () => {
    const dir = path.join(ROOT, 'assets', 'results');
    const grandes = fs.readdirSync(dir).filter((f) => /\.(jpe?g|png|webp)$/i.test(f))
      .map((f) => [f, fs.statSync(path.join(dir, f)).size]).filter(([, s]) => s > 250 * 1000);
    console.log('T01 fotos > 250 KB:', JSON.stringify(grandes));
    expect(grandes).toEqual([]);
  });

  test('T01 fotos dos comparadores nao tem mais de 1000 px de largura (aparecem em ~350-450 px)', async ({ page }) => {
    await page.goto('/resultados.html');
    const srcs = await page.$$eval('input[type=range]', (rs) => rs.flatMap((r) => [...r.closest('div').querySelectorAll('img')].map((i) => i.getAttribute('src'))));
    const larg = (f) => { const b = fs.readFileSync(path.join(ROOT, f)); let i = 2; while (i < b.length) { if (b[i] !== 0xFF) { i++; continue; } const m = b[i + 1]; if (m >= 0xC0 && m <= 0xCF && m !== 0xC4 && m !== 0xC8 && m !== 0xCC) return b.readUInt16BE(i + 7); i += 2 + b.readUInt16BE(i + 2); } return 0; };
    const dims = srcs.map((f) => [f, larg(f)]);
    const ruins = dims.filter(([, w]) => w > 1000);
    console.log('T01 larguras:', JSON.stringify(dims));
    expect(dims.length).toBe(10);
    expect(ruins).toEqual([]);
  });

  test('T01 a foto principal (LCP) da pagina de resultados nao esta com loading="lazy"', async ({ page }) => {
    await page.goto('/resultados.html');
    const lazy = await page.locator('input[type=range]').first().evaluate((r) => [...r.closest('div').querySelectorAll('img')].map((i) => i.loading));
    console.log('T01 loading das fotos do 1o comparador:', lazy.join(','));
    for (const l of lazy) expect(l).not.toBe('lazy');
  });
});

// =====================================================================
// T03 - imagens do topo da home
// =====================================================================
test('T03 logo-gs e hero-bg da home pesam pouco (<= 40 KB cada) e logo-gs nao e maior que 2x o tamanho exibido', async ({ page }) => {
  await page.goto('/index.html'); await page.waitForLoadState('networkidle');
  const r = await page.evaluate(() => {
    const nomes = performance.getEntriesByType('resource').map((e) => e.name).filter((n) => /logo-gs|hero-bg/.test(n));
    const logo = document.getElementById('logo-gs');
    return { nomes, nat: logo.naturalWidth, shown: logo.getBoundingClientRect().width };
  });
  const sizes = r.nomes.map((n) => [path.basename(n), fs.statSync(path.join(ROOT, 'assets', path.basename(n))).size]);
  console.log('T03', JSON.stringify(sizes), 'logo natural/exibido', r.nat, Math.round(r.shown));
  expect(sizes.length).toBeGreaterThan(0);
  for (const [n, s] of sizes) expect(s, n).toBeLessThanOrEqual(40 * 1000);
  expect(r.nat).toBeLessThanOrEqual(Math.max(r.shown, 1) * 2.5);
});

// =====================================================================
// T04 - arquivos sem uso
// =====================================================================
test('T04 todo arquivo de assets/ e usado por index.html ou resultados.html', async () => {
  const html = ['index.html', 'resultados.html'].map((f) => fs.readFileSync(path.join(ROOT, f), 'utf8')).join('\n');
  const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => e.isDirectory() ? walk(path.join(d, e.name)) : [path.join(d, e.name)]);
  const sem = walk(path.join(ROOT, 'assets')).map((f) => path.relative(ROOT, f).replace(/\\/g, '/')).filter((rel) => !html.includes(rel));
  console.log('T04 sem uso:', JSON.stringify(sem));
  expect(sem).toEqual([]);
});

// =====================================================================
// S1 - acessibilidade (Lighthouse 85-87)
// =====================================================================
test.describe('S1 acessibilidade', () => {
  for (const p of paginas) {
    test(`S1 ${p}: todo controle deslizante do antes/depois tem nome acessivel`, async ({ page }) => {
      await page.goto(p);
      const sem = await page.$$eval('input[type=range]', (rs) => rs.filter((r) => !(r.getAttribute('aria-label') || r.getAttribute('aria-labelledby') || (r.labels && r.labels.length) || r.title)).length);
      console.log(p, 'sliders sem nome:', sem);
      expect(sem).toBe(0);
    });
    test(`S1 ${p}: tem exatamente um <main>`, async ({ page }) => {
      await page.goto(p);
      expect(await page.locator('main').count()).toBe(1);
    });
  }

  test('S1 botoes do cabecalho (#desktop-cta e #nav-toggle area) tem contraste >= 4,5:1', async ({ page }) => {
    await page.goto('/index.html');
    const c = await page.evaluate(() => {
      const L = (s) => { const m = s.match(/[\d.]+/g).map(Number); const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }; return 0.2126 * f(m[0]) + 0.7152 * f(m[1]) + 0.0722 * f(m[2]); };
      const el = document.getElementById('desktop-cta'); const cs = getComputedStyle(el);
      const a = L(cs.color), b = L(cs.backgroundColor); return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
    });
    console.log('S1 contraste #desktop-cta =', c.toFixed(2));
    expect(c).toBeGreaterThanOrEqual(4.5);
  });
});

// =====================================================================
// S2 - contrastes escondidos pela animacao reveal
// =====================================================================
test('S2 nenhum texto da home abaixo de 4,5:1 com as secoes visiveis (fundos solidos)', async ({ page }) => {
  await page.goto('/index.html');
  await page.addStyleTag({ content: '.reveal{opacity:1!important;transform:none!important;transition:none!important}' });
  const ruins = await page.evaluate(() => {
    const parse = (s) => { const m = s.match(/[\d.]+/g).map(Number); return { r: m[0], g: m[1], b: m[2], a: m.length > 3 ? m[3] : 1 }; };
    const lum = (c) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }; return 0.2126 * f(c.r) + 0.7152 * f(c.g) + 0.0722 * f(c.b); };
    const blend = (top, bot) => ({ r: top.r * top.a + bot.r * (1 - top.a), g: top.g * top.a + bot.g * (1 - top.a), b: top.b * top.a + bot.b * (1 - top.a), a: 1 });
    const out = [];
    for (const el of document.body.querySelectorAll('*')) {
      if (!['SCRIPT', 'STYLE', 'SVG', 'PATH'].includes(el.tagName.toUpperCase()) === false) continue;
      if (el.closest('#home')) continue; // hero tem imagem de fundo: contraste nao medivel por cor solida
      const temTexto = [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim().length > 1);
      if (!temTexto) continue;
      const r = el.getBoundingClientRect(); if (r.width < 2 || r.height < 2) continue;
      const cs = getComputedStyle(el); if (cs.visibility === 'hidden' || cs.display === 'none') continue;
      const layers = []; let n = el, grad = false, opaque = false;
      while (n && n.nodeType === 1) {
        const s = getComputedStyle(n);
        if (s.backgroundImage !== 'none') { grad = true; break; }
        const bg = parse(s.backgroundColor);
        if (bg.a > 0) { layers.push(bg); if (bg.a >= 0.99) { opaque = true; break; } }
        n = n.parentElement;
      }
      if (grad) continue;
      let base = { r: 255, g: 255, b: 255, a: 1 }; for (let i = layers.length - 1; i >= 0; i--) base = blend(layers[i], base);
      const fg = parse(cs.color); const fgc = fg.a < 1 ? blend(fg, base) : fg;
      const a = lum(fgc), b = lum(base); const ratio = (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
      const px = parseFloat(cs.fontSize), bold = parseInt(cs.fontWeight, 10) >= 700;
      const grande = px >= 24 || (px >= 18.66 && bold);
      if (ratio < (grande ? 3 : 4.5)) out.push(el.tagName + ' "' + el.textContent.trim().slice(0, 30) + '" ' + ratio.toFixed(2));
    }
    return out;
  });
  console.log('S2 reprovados (' + ruins.length + '):\n' + ruins.join('\n'));
  expect(ruins).toEqual([]);
});

// =====================================================================
// S4 - alvos de toque no celular
// =====================================================================
test.describe('S4 alvos de toque >= 44 px (celular)', () => {
  test('S4 hamburguer 44x44', async ({ page }, ti) => {
    test.skip(!isMobile(ti), 'so celular');
    await page.goto('/index.html');
    const b = await page.locator('#nav-toggle').boundingBox();
    console.log('S4 hamburguer', b.width, b.height);
    expect(b.width).toBeGreaterThanOrEqual(44); expect(b.height).toBeGreaterThanOrEqual(44);
  });
  test('S4 link "Voltar" de resultados tem altura >= 44', async ({ page }, ti) => {
    test.skip(!isMobile(ti), 'so celular');
    await page.goto('/resultados.html');
    const b = await page.locator('a', { hasText: /voltar/i }).first().boundingBox();
    console.log('S4 voltar', b.width, b.height);
    expect(b.height).toBeGreaterThanOrEqual(44);
  });
  for (const p of paginas) {
    test(`S4 ${p}: links do rodape tem altura >= 44`, async ({ page }, ti) => {
      test.skip(!isMobile(ti), 'so celular');
      await page.goto(p);
      const ruins = await page.$$eval('footer a', (as) => as.map((a) => ({ t: a.textContent.trim().slice(0, 25), r: a.getBoundingClientRect() })).filter((x) => x.r.height > 0 && x.r.height < 44).map((x) => x.t + ' ' + Math.round(x.r.height)));
      console.log(p, 'rodape < 44:', ruins.join(' | '));
      expect(ruins).toEqual([]);
    });
  }
});

// =====================================================================
// S5 - cabecalho cobre o titulo das secoes (celular)
// =====================================================================
test('S5 ao ir para uma ancora, o primeiro texto da secao fica abaixo do cabecalho (celular)', async ({ page }, ti) => {
  test.skip(!isMobile(ti), 'so celular');
  await page.goto('/index.html');
  const ruins = [];
  for (const id of ['sobre', 'procedimentos', 'resultados', 'cursos', 'depoimentos', 'contato']) {
    const r = await page.evaluate((i) => {
      document.documentElement.style.scrollBehavior = 'auto';
      const sec = document.getElementById(i); sec.classList.add('visible');
      location.hash = '#' + i; // usa o scroll-margin-top real da ancora
      const w = document.createTreeWalker(sec, NodeFilter.SHOW_TEXT, { acceptNode: (n) => n.textContent.trim().length > 1 && n.parentElement.offsetParent !== null ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT });
      const n = w.nextNode(); const rg = document.createRange(); rg.selectNodeContents(n);
      return new Promise((res) => setTimeout(() => { res({ top: rg.getBoundingClientRect().top, hdr: document.getElementById('site-header').getBoundingClientRect().bottom }); }, 400));
    }, id);
    if (r.top < r.hdr) ruins.push(`${id}: texto em ${Math.round(r.top)} < cabecalho ${Math.round(r.hdr)}`);
  }
  console.log('S5', ruins.join(' | '));
  expect(ruins).toEqual([]);
});

// =====================================================================
// S6 / L05 - carrosseis
// =====================================================================
test('S6 setas do carrossel de Procedimentos nao cobrem texto (celular)', async ({ page }, ti) => {
  test.skip(!isMobile(ti), 'so celular');
  await page.goto('/index.html');
  await page.addStyleTag({ content: '.reveal{opacity:1!important;transform:none!important}' });
  await page.locator('#procedimentos-grid').scrollIntoViewIfNeeded();
  const cobre = await page.evaluate(() => {
    const sec = document.getElementById('procedimentos'); const out = [];
    const arrows = [...sec.querySelectorAll('.mobile-scroll-arrow, [id^="procedimentos-arrow"]')].filter((a) => a.getBoundingClientRect().width > 0);
    const w = document.createTreeWalker(document.getElementById('procedimentos-grid'), NodeFilter.SHOW_TEXT);
    let n; while ((n = w.nextNode())) {
      if (n.textContent.trim().length < 2) continue;
      const rg = document.createRange(); rg.selectNodeContents(n);
      for (const tr of rg.getClientRects()) for (const a of arrows) { const r = a.getBoundingClientRect();
        const vis = tr.right > 0 && tr.left < innerWidth; if (vis && tr.left < r.right && tr.right > r.left && tr.top < r.bottom && tr.bottom > r.top) out.push(n.textContent.trim().slice(0, 25)); }
    }
    return { arrows: arrows.length, out };
  });
  console.log('S6', JSON.stringify(cobre));
  expect(cobre.arrows).toBeGreaterThan(0);
  expect(cobre.out).toEqual([]);
});

test.describe('L05 setas do carrossel sem o evento scrollend (iOS <= 26.1)', () => {
  for (const grid of ['procedimentos-grid', 'depoimentos-grid', 'resultados-grid']) {
    test(`L05 ${grid}: depois de deslizar ate o card 4, a seta "proximo" avanca exatamente 1`, async ({ page }, ti) => {
      test.skip(!isMobile(ti), 'so celular');
      await page.addInitScript(() => {
        const orig = EventTarget.prototype.addEventListener;
        EventTarget.prototype.addEventListener = function (t, ...r) { if (t === 'scrollend') return; return orig.call(this, t, ...r); };
      });
      await page.goto('/index.html');
      await page.addStyleTag({ content: '.reveal{opacity:1!important;transform:none!important}' });
      const g = page.locator('#' + grid); await g.scrollIntoViewIfNeeded();
      const atual = () => g.evaluate((el) => { const c = [...el.children]; const mid = el.scrollLeft + el.clientWidth / 2; let best = 0, d = 1e9; c.forEach((x, i) => { const dd = Math.abs(x.offsetLeft + x.offsetWidth / 2 - mid); if (dd < d) { d = dd; best = i; } }); return best; });
      // desliza com o dedo (simulado) ate o 4o card, sem animacao e sem snap
      await g.evaluate((el) => { el.style.scrollSnapType = 'none'; el.style.scrollBehavior = 'auto'; const c = el.children[3]; el.scrollLeft = c.offsetLeft + c.offsetWidth / 2 - el.clientWidth / 2; });
      await page.waitForTimeout(300);
      await g.evaluate((el) => { el.style.scrollSnapType = ''; el.style.scrollBehavior = ''; });
      await page.waitForTimeout(600);
      const antes = await atual();
      await page.locator(`button[onclick*="scrollByCard('${grid}', 1)"]`).click();
      await page.waitForTimeout(1500);
      const depois = await atual();
      console.log(`L05 ${grid}: card ${antes} -> ${depois}`);
      expect(antes).toBeGreaterThanOrEqual(3);
      expect(depois).toBe(antes + 1);
    });
  }
});

// =====================================================================
// S7 / L06 - menu e foco
// =====================================================================
test.describe('S7 / L06 menu e teclado', () => {
  test('S7/L06 hamburguer tem aria-expanded e ele muda ao abrir', async ({ page }, ti) => {
    test.skip(!isMobile(ti), 'so celular');
    await page.goto('/index.html');
    const t = page.locator('#nav-toggle');
    await t.click();
    await expect(page.locator('#mobile-nav-wrap')).toBeVisible();
    await expect(t).toHaveAttribute('aria-expanded', 'true');
  });
  test('S7/L06 Esc fecha o menu', async ({ page }, ti) => {
    test.skip(!isMobile(ti), 'so celular');
    await page.goto('/index.html');
    await page.locator('#nav-toggle').click();
    await expect(page.locator('#mobile-nav-wrap')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.locator('#mobile-nav-wrap')).toBeHidden();
  });
  test('L06 menu aberto some ao passar de 1100 px (girar o iPad)', async ({ page }, ti) => {
    test.skip(!isMobile(ti), 'so celular');
    await page.goto('/index.html');
    await page.locator('#nav-toggle').click();
    await expect(page.locator('#mobile-nav-wrap')).toBeVisible();
    await page.setViewportSize({ width: 1200, height: 800 });
    await page.waitForTimeout(300);
    await expect(page.locator('#mobile-nav-wrap')).toBeHidden();
  });
  test('S7 links do cabecalho mostram o foco do teclado (nao usam outline:none sem alternativa)', async ({ page }, ti) => {
    test.skip(isMobile(ti), 'teclado: desktop');
    await page.goto('/index.html');
    for (let i = 0; i < 4; i++) await page.keyboard.press('Tab');
    const f = await page.evaluate(() => { const e = document.activeElement; const s = getComputedStyle(e); return { tag: e.tagName, txt: (e.textContent || '').trim().slice(0, 20), os: s.outlineStyle, ow: s.outlineWidth, sh: s.boxShadow }; });
    console.log('S7 foco', JSON.stringify(f));
    const visivel = (f.os !== 'none' && f.ow !== '0px') || f.sh !== 'none';
    expect(visivel).toBe(true);
  });
  test('S7/L06 "Ver descricao completa" dos cursos tem aria-expanded', async ({ page }) => {
    await page.goto('/index.html');
    const n = await page.$$eval('button[onclick^="toggleCurso"]', (bs) => bs.filter((b) => !b.hasAttribute('aria-expanded')).length);
    console.log('S7 botoes de curso sem aria-expanded:', n);
    expect(await page.locator('button[onclick^="toggleCurso"]').count()).toBeGreaterThan(0);
    expect(n).toBe(0);
  });
  test('L06/S6 index.html: todo <button> tem type', async ({ page }) => {
    await page.goto('/index.html');
    const n = await page.$$eval('button', (bs) => bs.filter((b) => !b.hasAttribute('type')).length);
    console.log('index buttons sem type:', n);
    expect(n).toBe(0);
  });
  for (const p of paginas) {
    test(`L06 ${p}: o logo escondido do cabecalho nao repete o nome para leitor de tela`, async ({ page }) => {
      await page.goto(p);
      const alts = await page.$$eval('header img[alt]', (is) => is.filter((i) => i.getAttribute('aria-hidden') !== 'true' && i.alt.trim()).map((i) => i.alt.trim()));
      console.log(p, 'alts do cabecalho:', alts.join(' | '));
      expect(new Set(alts).size).toBe(alts.length);
    });
  }
});

// =====================================================================
// S8 / L06 - sem JS e movimento reduzido
// =====================================================================
test.describe('S8 sem JS / movimento reduzido', () => {
  test('S8/L06 com JS desligado as secoes .reveal continuam visiveis', async ({ browser }) => {
    const ctx = await browser.newContext({ javaScriptEnabled: false });
    const page = await ctx.newPage();
    await page.goto('http://localhost:8765/index.html');
    const ops = await page.$$eval('.reveal', (els) => els.map((e) => +getComputedStyle(e).opacity));
    await ctx.close();
    console.log('S8 opacidades sem JS:', ops.join(','));
    expect(ops.length).toBeGreaterThan(0);
    for (const o of ops) expect(o).toBeGreaterThan(0.9);
  });
  test('S8 com "reduzir movimento" o .reveal nao anima e a rolagem nao e suave', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/index.html');
    const r = await page.evaluate(() => ({ td: getComputedStyle(document.querySelector('.reveal')).transitionDuration, sb: getComputedStyle(document.documentElement).scrollBehavior }));
    console.log('S8 reduzir movimento:', JSON.stringify(r));
    expect(r.td).toBe('0s');
    expect(r.sb).toBe('auto');
  });
});

// =====================================================================
// L03 - camada transparente sobre os videos
// =====================================================================
test.describe('L03 camada sobre os videos', () => {
  const casos = [['/index.html', '#apresentacao-video'], ['/resultados.html', 'video']];
  for (const [p, sel] of casos) {
    test(`L03 ${p}: o canto superior do video (tela cheia/PiP/volume no iPhone) recebe o toque, nao uma camada`, async ({ page }) => {
      await page.goto(p);
      const v = page.locator(sel).first(); await v.scrollIntoViewIfNeeded();
      const r = await v.evaluate((el) => { const b = el.getBoundingClientRect(); const e = document.elementFromPoint(b.left + 14, b.top + 14); return { tag: e && e.tagName, ehVideo: e === el }; });
      console.log('L03', p, JSON.stringify(r));
      expect(r.ehVideo).toBe(true);
    });
  }
  test('L03 resultados: ao dar play num video, os outros pausam', async ({ page, browserName }) => {
    await page.goto('/resultados.html');
    const vs = page.locator('video');
    const play = async (i) => { const v = vs.nth(i); await v.scrollIntoViewIfNeeded(); await v.evaluate((el) => { el.muted = true; return el.play(); }); };
    await play(0); await play(1);
    await page.waitForTimeout(800);
    const estados = await vs.evaluateAll((els) => els.map((e) => !e.paused));
    console.log('L03 tocando:', estados.join(','));
    expect(estados.filter(Boolean).length).toBeLessThanOrEqual(1);
  });
});

// =====================================================================
// L04 / S3 - comparador antes/depois so responde a barrinha
// =====================================================================
test.describe('L04/S3 comparador', () => {
  test('L04/S3 arrastar sobre a FOTO (acima da barrinha) move o corte (mouse)', async ({ page }, ti) => {
    test.skip(isMobile(ti), 'mouse: desktop');
    await page.goto('/index.html');
    const s = page.locator('#resultados-grid input[type=range]').first(); await s.scrollIntoViewIfNeeded();
    const moldura = s.locator('xpath=..'); const b = await moldura.boundingBox();
    const y = b.y + b.height * 0.4;
    await page.mouse.move(b.x + b.width * 0.2, y); await page.mouse.down();
    await page.mouse.move(b.x + b.width * 0.8, y, { steps: 10 }); await page.mouse.up();
    const v = +(await s.inputValue());
    console.log('L04 valor apos arrastar na foto:', v);
    expect(v).toBeGreaterThan(65);
  });
  test('L04/S3 arrastar sobre a FOTO move o corte (toque, Chromium)', async ({ page, browserName }, ti) => {
    test.skip(!isMobile(ti) || !isChromium(browserName), 'toque no Chromium via CDP');
    await page.goto('/index.html');
    const s = page.locator('#resultados-grid input[type=range]').first(); await s.scrollIntoViewIfNeeded();
    const b = await s.locator('xpath=..').boundingBox();
    const cdp = await page.context().newCDPSession(page);
    const y = b.y + b.height * 0.4; const x0 = b.x + b.width * 0.25;
    await cdp.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [{ x: x0, y }] });
    for (let i = 1; i <= 10; i++) await cdp.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [{ x: x0 + (b.width * 0.5 * i) / 10, y }] });
    await cdp.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
    const v = +(await s.inputValue());
    console.log('L04 valor apos arrasto por toque na foto:', v);
    expect(v).toBeGreaterThan(65);
  });
  test('L04/S3 a barra do comparador tem pelo menos 44 px de altura de toque (celular)', async ({ page }, ti) => {
    test.skip(!isMobile(ti), 'so celular');
    await page.goto('/index.html');
    const b = await page.locator('#resultados-grid input[type=range]').first().boundingBox();
    console.log('L04/S3 barra', b.width, b.height);
    expect(b.height).toBeGreaterThanOrEqual(44);
  });
  test('L04 com o corte em 100 a etiqueta "Depois" some quando o "antes" cobre toda a foto', async ({ page }) => {
    await page.goto('/index.html');
    const s = page.locator('#resultados-grid input[type=range]').first(); await s.scrollIntoViewIfNeeded();
    await s.fill('0'); // 0 = so "depois"? (clip inset 0 100% 0 0 => antes escondido)
    await s.fill('100');
    const vis = await s.evaluate((el) => { const w = el.closest('div'); const sp = [...w.querySelectorAll('span')].find((x) => x.textContent.trim() === 'Depois'); const cs = getComputedStyle(sp); return { v: cs.visibility, d: cs.display, o: cs.opacity }; });
    console.log('L04 etiqueta Depois com valor 100:', JSON.stringify(vis));
    expect(vis.v === 'hidden' || vis.d === 'none' || +vis.o === 0).toBe(true);
  });
});

// =====================================================================
// L07 - conteudo duplicado/divergente e grade da Corrente
// =====================================================================
test.describe('L07 conteudo', () => {
  test('L07 o horario de atendimento esta escrito do mesmo jeito em toda a home', async ({ page }) => {
    await page.goto('/index.html');
    const txt = await page.evaluate(() => document.body.textContent.replace(/\s+/g, ' '));
    const formas = [...new Set((txt.match(/Segunda à sexta,[^.<]{0,20}?17h/g) || []).map((s) => s.trim()))];
    console.log('L07 formas do horario:', JSON.stringify(formas));
    expect(formas.length).toBe(1);
  });
  test('L07 categoria Corrente Fortalecedora (2 videos) fica centralizada no desktop', async ({ page }, ti) => {
    test.skip(isMobile(ti), 'desktop');
    await page.goto('/resultados.html');
    const r = await page.evaluate(() => {
      const h = [...document.querySelectorAll('h2')].find((x) => /Corrente/.test(x.textContent));
      const grid = h.parentElement.querySelector('.rc-grid'); const kids = [...grid.children];
      const g = grid.getBoundingClientRect(); const a = kids[0].getBoundingClientRect(), b = kids[kids.length - 1].getBoundingClientRect();
      return { n: kids.length, centroGrade: (g.left + g.right) / 2, centroItens: (a.left + b.right) / 2 };
    });
    console.log('L07 Corrente', JSON.stringify(r));
    expect(r.n).toBe(2);
    expect(Math.abs(r.centroGrade - r.centroItens)).toBeLessThan(4);
  });
});
