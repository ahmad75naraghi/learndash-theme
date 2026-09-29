#!/usr/bin/env node
/**
 * ممیزی واقعی چیدمان قالب در Chromium.
 * استفاده: node tests/visual-audit.mjs http://127.0.0.1:9400 [/tmp/visual-audit]
 */
import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:9400').replace(/\/$/, '');
const outputDir = process.argv[3] || '/tmp/visual-audit';
const username = process.env.SMOKE_USERNAME || 'admin';
const password = process.env.SMOKE_PASSWORD || 'password';
const viewports = {
  mobile320: { width: 320, height: 700 },
  mobile390: { width: 390, height: 844 },
  tablet768: { width: 768, height: 1024 },
  desktop1440: { width: 1440, height: 900 },
};
const routes = [
  ['home', '/'],
  ['article', '/post-1/'],
  ['archive', '/?post_type=post'],
  ['search', '/?s=آزمایشی'],
  ['courses', '/courses/'],
  ['videos', '/videos/'],
  ['not-found', '/visual-audit-not-found/'],
  ['panel', '/panel/'],
  ['panel-profile', '/panel/profile/'],
  ['panel-courses', '/panel/my-courses/'],
  ['panel-payments', '/panel/payments/'],
  ['panel-wishlist', '/panel/wishlist/'],
  ['panel-certificates', '/panel/certificates/'],
  ['panel-settings', '/panel/settings/'],
];

const browser = await chromium.launch({ headless: true });
const failures = [];
const warnings = [];
let screenshots = 0;

function report(kind, viewport, route, message) {
  const entry = `${viewport} ${route}: ${message}`;
  (kind === 'failure' ? failures : warnings).push(entry);
  console[kind === 'failure' ? 'error' : 'warn'](`${kind === 'failure' ? 'FAIL' : 'WARN'} ${entry}`);
}

try {
  for (const [viewportName, viewport] of Object.entries(viewports)) {
    const context = await browser.newContext({ viewport, locale: 'fa-IR', colorScheme: 'light' });
    const login = await context.newPage();
    await login.goto(`${base}/wp-login.php`, { waitUntil: 'networkidle' });
    const userInput = login.locator('#user_login');
    if (await userInput.count()) {
      await userInput.fill(username);
      await login.locator('#user_pass').fill(password);
      await Promise.all([
        login.waitForLoadState('networkidle'),
        login.locator('#wp-submit').click(),
      ]);
    }
    await login.close();

    for (const [slug, route] of routes) {
      const page = await context.newPage();
      const responseErrors = [];
      const consoleErrors = [];
      page.on('response', response => {
        const url = response.url();
        if (url.startsWith(base) && response.status() >= 400 && !url.includes('favicon')) {
          responseErrors.push(`${response.status()} ${new URL(url).pathname}`);
        }
      });
      page.on('console', message => {
        if (message.type() === 'error' && !/favicon|Failed to load resource.*404/i.test(message.text())) {
          consoleErrors.push(message.text());
        }
      });
      page.on('pageerror', error => consoleErrors.push(error.message));

      try {
        const response = await page.goto(`${base}${route}`, { waitUntil: 'networkidle', timeout: 45_000 });
        if (!response || response.status() >= 400 && slug !== 'not-found') {
          report('failure', viewportName, route, `HTTP ${response?.status() ?? 'no response'}`);
        }
        await page.evaluate(() => document.fonts?.ready);

        const metrics = await page.evaluate(() => {
          const root = document.documentElement;
          const brokenImages = [...document.images]
            .filter(img => img.offsetParent !== null && (!img.complete || img.naturalWidth === 0))
            .map(img => img.currentSrc || img.src || img.alt || 'image');
          const unnamed = [...document.querySelectorAll('button, a[href], input, select, textarea')]
            .filter(el => el.offsetParent !== null)
            .filter(el => {
              if (el.matches('input[type="hidden"], input[type="submit"], input[type="button"]')) return false;
              const name = (el.getAttribute('aria-label') || el.getAttribute('title') || el.textContent || '').trim();
              const imageAlt = el.querySelector?.('img[alt]')?.getAttribute('alt')?.trim();
              const labelled = el.getAttribute('aria-labelledby');
              return !name && !imageAlt && !labelled;
            }).length;
          const escaped = [...document.querySelectorAll('input, select, textarea, table')]
            .filter(el => el.offsetParent !== null)
            .filter(el => {
              const rect = el.getBoundingClientRect();
              return rect.right > root.clientWidth + 2 || rect.left < -2;
            }).length;
          return {
            title: document.title,
            clientWidth: root.clientWidth,
            scrollWidth: Math.max(root.scrollWidth, document.body?.scrollWidth || 0),
            brokenImages,
            unnamed,
            escaped,
            main: Boolean(document.querySelector('main, #main, #ee-main, [role="main"]')),
          };
        });

        if (metrics.scrollWidth > metrics.clientWidth + 2) {
          report('failure', viewportName, route, `horizontal overflow ${metrics.scrollWidth}px > ${metrics.clientWidth}px`);
        }
        if (metrics.brokenImages.length) {
          report('failure', viewportName, route, `broken images: ${metrics.brokenImages.slice(0, 3).join(', ')}`);
        }
        if (metrics.escaped) report('failure', viewportName, route, `${metrics.escaped} form/table element(s) escape viewport`);
        if (!metrics.title) report('failure', viewportName, route, 'document title is empty');
        if (!metrics.main) report('warning', viewportName, route, 'semantic main landmark is missing');
        if (metrics.unnamed) report('warning', viewportName, route, `${metrics.unnamed} visible interactive element(s) have no accessible name`);
        if (responseErrors.length) report('failure', viewportName, route, `local resource errors: ${[...new Set(responseErrors)].join(', ')}`);
        if (consoleErrors.length) report('failure', viewportName, route, `browser errors: ${[...new Set(consoleErrors)].slice(0, 3).join(' | ')}`);

        const dir = path.join(outputDir, viewportName);
        await fs.mkdir(dir, { recursive: true });
        await page.screenshot({ path: path.join(dir, `${slug}.png`), fullPage: true, animations: 'disabled' });
        screenshots++;
      } catch (error) {
        report('failure', viewportName, route, error.message);
      } finally {
        await page.close();
      }
    }
    await context.close();
  }
} finally {
  await browser.close();
}

const summary = { success: failures.length === 0, screenshots, failures, warnings };
await fs.mkdir(outputDir, { recursive: true });
await fs.writeFile(path.join(outputDir, 'summary.json'), JSON.stringify(summary, null, 2));
console.log(`Visual audit: ${screenshots} screenshots, ${failures.length} failure(s), ${warnings.length} warning(s).`);
if (process.env.GITHUB_ACTIONS === 'true') {
  for (const message of failures.slice(0, 9)) console.log(`::error title=Visual layout audit::${message}`);
}
if (failures.length) process.exit(1);
