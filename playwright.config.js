const { defineConfig, devices } = require('@playwright/test');
const PORTA = process.env.PORTA || '8765';
module.exports = defineConfig({
  testDir: './tests',
  timeout: 60000,
  workers: 2,
  retries: 0,
  reporter: [['list'], ['json', { outputFile: '.auditoria/playwright-resultados.json' }]],
  use: { baseURL: `http://localhost:${PORTA}`, trace: 'off' },
  webServer: { command: 'npm run previa', url: `http://localhost:${PORTA}/`, reuseExistingServer: true, timeout: 30000 },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'], channel: 'chrome', viewport: { width: 1440, height: 900 } } },
    { name: 'iphone-webkit', use: { ...devices['iPhone 13'] } },
    { name: 'iphone-chromium', use: { ...devices['iPhone 13'], browserName: 'chromium', channel: 'chrome', defaultBrowserType: 'chromium' } },
  ],
});
