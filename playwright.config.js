const { defineConfig, devices } = require('@playwright/test');
const PORTA = process.env.PORTA || '8765';
const APARELHOS = {
  desktop: { ...devices['Desktop Chrome'], channel: 'chrome', viewport: { width: 1440, height: 900 } },
  'iphone-webkit': { ...devices['iPhone 13'] },
  'iphone-chromium': { ...devices['iPhone 13'], browserName: 'chromium', channel: 'chrome', defaultBrowserType: 'chromium' },
};
module.exports = defineConfig({
  testDir: './tests',
  timeout: 60000,
  workers: 2,
  retries: 0,
  reporter: [['list'], ['json', { outputFile: '.auditoria/playwright-resultados.json' }]],
  use: { baseURL: `http://localhost:${PORTA}`, trace: 'off' },
  webServer: { command: 'npm run previa', url: `http://localhost:${PORTA}/`, reuseExistingServer: true, timeout: 30000 },
  // Os testes do painel gravam no conteúdo da prévia; rodam depois dos do site para não se misturarem.
  projects: [
    { name: 'desktop', testIgnore: /painel\.spec/, use: { ...APARELHOS.desktop } },
    { name: 'iphone-webkit', testIgnore: /painel\.spec/, use: { ...APARELHOS['iphone-webkit'] } },
    { name: 'iphone-chromium', testIgnore: /painel\.spec/, use: { ...APARELHOS['iphone-chromium'] } },
    ...Object.keys(APARELHOS).map((n) => ({
      name: 'painel-' + n, testMatch: /painel\.spec/, use: { ...APARELHOS[n] },
      dependencies: ['desktop', 'iphone-webkit', 'iphone-chromium'],
    })),
  ],
});
