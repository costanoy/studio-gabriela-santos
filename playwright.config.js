const { defineConfig, devices } = require('@playwright/test');
module.exports = defineConfig({
  testDir: './tests',
  timeout: 60000,
  workers: 2,
  retries: 0,
  reporter: [['list'], ['json', { outputFile: '.auditoria/playwright-resultados.json' }]],
  use: { baseURL: 'http://localhost:8765', trace: 'off' },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'], channel: 'chrome', viewport: { width: 1440, height: 900 } } },
    { name: 'iphone-webkit', use: { ...devices['iPhone 13'] } },
    { name: 'iphone-chromium', use: { ...devices['iPhone 13'], browserName: 'chromium', channel: 'chrome', defaultBrowserType: 'chromium' } },
  ],
});
