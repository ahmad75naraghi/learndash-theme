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
  ['course-single', '/sfwd-courses/report-course/'],
  ['lesson-single', '/sfwd-lessons/report-lesson/'],
  ['quiz-single', '/sfwd-quiz/report-quiz/'],
  ['library-page', '/library/'],
  ['videos-page', '/videos/'],
  ['podcast-page', '/podcast/'],
  ['podcast-single', '/sr_playlist/legacy-podcast-fixture/'],
  ['gallery-page', '/gallery-page/'],
  ['library-archive', '/lib/'],
  ['library-single', '/lib/lib-item-1/'],
  ['video-archive', '/clip/'],
  ['video-single', '/clip/clip-item-1/'],
  ['downloads-page', '/download/'],
  ['download-single', '/download/download-item-1/'],
  ['gallery-archive', '/gallery/'],
  ['gallery-category', '/galery_cat/%DA%AF%D8%B2%D8%A7%D8%B1%D8%B4-%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%DB%8C/'],
  ['gallery-single', '/gallery/gallery-item-1/'],
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
    // WP Playground auto-authenticates the first request unless this marker exists.
    // Set it explicitly so the guest login template can be measured as a real visitor.
    await context.addCookies([{ name: 'playground_auto_login_already_happened', value: '1', url: base }]);

    // The custom login template redirects authenticated users, so audit it before
    // establishing the session used by panel routes.
    const guestLogin = await context.newPage();
    try {
      const response = await guestLogin.goto(`${base}/login/`, { waitUntil: 'load', timeout: 30_000 });
      if (!response || response.status() >= 400) {
        report('failure', viewportName, '/login/', `HTTP ${response?.status() ?? 'no response'}`);
      } else {
        const loginMetrics = await guestLogin.evaluate(() => {
          const root = document.documentElement;
          const container = document.querySelector('.auth-container');
          const containerRect = container?.getBoundingClientRect();
          const escapedControls = [...document.querySelectorAll('input, button, select, textarea')]
            .filter(el => el.offsetParent !== null)
            .filter(el => {
              const rect = el.getBoundingClientRect();
              return rect.left < -2 || rect.right > root.clientWidth + 2;
            }).length;
          return {
            clientWidth: root.clientWidth,
            scrollWidth: Math.max(root.scrollWidth, document.body?.scrollWidth || 0),
            hasContainer: Boolean(container),
            containerEscapes: Boolean(containerRect && (containerRect.left < -2 || containerRect.right > root.clientWidth + 2)),
            escapedControls,
          };
        });
        if (!loginMetrics.hasContainer) report('failure', viewportName, '/login/', 'custom login container is missing');
        if (loginMetrics.scrollWidth > loginMetrics.clientWidth + 2) report('failure', viewportName, '/login/', `horizontal overflow ${loginMetrics.scrollWidth}px > ${loginMetrics.clientWidth}px`);
        if (loginMetrics.containerEscapes) report('failure', viewportName, '/login/', 'login container escapes viewport');
        if (loginMetrics.escapedControls) report('failure', viewportName, '/login/', `${loginMetrics.escapedControls} login control(s) escape viewport`);
        const dir = path.join(outputDir, viewportName);
        await fs.mkdir(dir, { recursive: true });
        await guestLogin.screenshot({ path: path.join(dir, 'login-guest.png'), fullPage: true, animations: 'disabled' });
        screenshots++;
      }
    } catch (error) {
      report('failure', viewportName, '/login/', error.message);
    } finally {
      await guestLogin.close();
    }

    const login = await context.newPage();
    await login.goto(`${base}/wp-login.php`, { waitUntil: 'domcontentloaded', timeout: 30_000 });
    const userInput = login.locator('#user_login');
    if (await userInput.count()) {
      await userInput.fill(username);
      await login.locator('#user_pass').fill(password);
      await Promise.all([
        login.waitForLoadState('domcontentloaded'),
        login.locator('#wp-submit').click(),
      ]);
    }
    await login.close();

    for (const [slug, route] of routes) {
      const page = await context.newPage();
      // Keep only the latest status per resource. A successful retry should clear a
      // transient Playground/PHP-worker failure instead of failing the whole audit.
      const responseErrors = new Map();
      const consoleErrors = [];
      page.on('response', response => {
        const url = response.url();
        const parsed = new URL(url);
        parsed.searchParams.delete('__ee_retry');
        const key = parsed.href;
        const expected404 = slug === 'not-found' && parsed.pathname === route;
        if (url.startsWith(base) && response.status() >= 400 && !url.includes('favicon') && !expected404) {
          responseErrors.set(key, `${response.status()} ${parsed.pathname}`);
        } else if (response.ok()) {
          responseErrors.delete(key);
        }
      });
      page.on('console', message => {
        if (message.type() === 'error' && !/favicon|Failed to load resource.*404/i.test(message.text())) {
          consoleErrors.push(message.text());
        }
      });
      page.on('pageerror', error => consoleErrors.push(error.message));

      try {
        let response;
        // Playground occasionally restarts its lone PHP worker during the full multi-page audit.
        // Retry transient gateway failures, while keeping real route/application failures visible.
        for (let attempt = 0; attempt < 5; attempt++) {
          response = await page.goto(`${base}${route}`, { waitUntil: 'load', timeout: 30_000 });
          if (!response || ![500, 502, 503, 504].includes(response.status()) || attempt === 4) break;
          responseErrors.clear();
          consoleErrors.length = 0;
          await page.waitForTimeout(1_000 * (attempt + 1));
        }
        if (!response || response.status() >= 400 && slug !== 'not-found') {
          report('failure', viewportName, route, `HTTP ${response?.status() ?? 'no response'}`);
        }
        await page.evaluate(() => document.fonts?.ready);
        await page.waitForTimeout(250);

        // Under a heavy multi-page audit Playground can briefly return 502/503 for a
        // media request even after the document succeeded. Retry visible same-origin
        // images before declaring the layout broken; permanent 4xx/5xx still fail.
        await page.evaluate(async origin => {
          const broken = [...document.images].filter(img => img.offsetParent !== null && (!img.complete || img.naturalWidth === 0));
          for (const img of broken) {
            let source;
            try { source = new URL(img.currentSrc || img.src, document.baseURI); } catch { continue; }
            if (source.origin !== origin) continue;
            for (let attempt = 1; attempt <= 3 && (!img.complete || img.naturalWidth === 0); attempt++) {
              source.searchParams.set('__ee_retry', `${Date.now()}-${attempt}`);
              img.removeAttribute('srcset');
              await new Promise(resolve => {
                const done = () => resolve();
                img.addEventListener('load', done, { once: true });
                img.addEventListener('error', done, { once: true });
                img.src = source.href;
                setTimeout(done, 750 * attempt);
              });
            }
          }
        }, new URL(base).origin);

        if (viewportName === 'desktop1440' && slug === 'home') {
          const nestedItem = page.locator('.ee-nav-item.has-sub:has(.ee-sub-item.has-children)').first();
          if (await nestedItem.count()) {
            await nestedItem.locator(':scope > a').hover();
            await page.waitForTimeout(200);
            const parentCategory = nestedItem.locator('.ee-sub-item.has-children').first();
            await parentCategory.hover();
            await page.waitForTimeout(200);
            const parentBox = await parentCategory.boundingBox();
            const flyout = parentCategory.locator(':scope > .ee-sub-flyout');
            const flyoutBox = await flyout.boundingBox();
            const childLink = flyout.locator('.ee-sub-link').first();
            if (!parentBox || !flyoutBox || !await flyout.isVisible() || !await childLink.isVisible()) {
              report('failure', viewportName, route, 'nested category flyout did not open on hover');
            } else if (flyoutBox.x + flyoutBox.width > parentBox.x + 2) {
              report('failure', viewportName, route, 'nested category flyout did not open to the left of its parent');
            }
          } else {
            report('failure', viewportName, route, 'nested parent category fixture is missing');
          }
        }

        if (route.startsWith('/panel')) {
          const helpButton = page.locator('[data-ee-panel-help]');
          if (await helpButton.count()) {
            await helpButton.evaluate(button => button.scrollIntoView({ behavior: 'instant', block: 'center', inline: 'center' }));
            await page.waitForTimeout(50);
            const clickability = await helpButton.evaluate(button => {
              const rect = button.getBoundingClientRect();
              const top = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);
              const blockerRect = top?.getBoundingClientRect();
              return {
                clear: top === button || button.contains(top),
                blocker: top ? `${top.tagName.toLowerCase()}${top.id ? `#${top.id}` : ''}${typeof top.className === 'string' && top.className ? `.${top.className.trim().split(/\\s+/).join('.')}` : ''}` : 'unknown',
                geometry: `button(${Math.round(rect.left)},${Math.round(rect.top)},${Math.round(rect.width)}x${Math.round(rect.height)}) blocker(${Math.round(blockerRect?.left || 0)},${Math.round(blockerRect?.top || 0)},${Math.round(blockerRect?.width || 0)}x${Math.round(blockerRect?.height || 0)})`,
              };
            });
            if (!clickability.clear) report('failure', viewportName, route, `section help is covered by ${clickability.blocker}; ${clickability.geometry}`);
            await helpButton.evaluate(button => button.click());
            const helpId = await helpButton.getAttribute('aria-controls');
            const helpState = await page.locator(`#${helpId}`).evaluate(el => ({ hidden: el.hidden, text: el.textContent.trim() }));
            if (helpState.hidden || !helpState.text || await helpButton.getAttribute('aria-expanded') !== 'true') {
              report('failure', viewportName, route, 'section help did not open correctly');
            }
          } else {
            report('failure', viewportName, route, 'section help button is missing');
          }
        }

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
              const htmlLabel = el.labels && [...el.labels].some(label => label.textContent.trim());
              return !name && !imageAlt && !labelled && !htmlLabel;
            })
            .map(el => `${el.tagName.toLowerCase()}${el.id ? `#${el.id}` : ''}${el.className && typeof el.className === 'string' ? `.${el.className.trim().split(/\\s+/).join('.')}` : ''}${el.getAttribute('href') ? `[href=${el.getAttribute('href')}]` : ''}`);
          const escaped = [...document.querySelectorAll('input, select, textarea, table')]
            .filter(el => el.offsetParent !== null)
            .filter(el => {
              const rect = el.getBoundingClientRect();
              return rect.right > root.clientWidth + 2 || rect.left < -2;
            }).length;
          const escapedContent = [...document.querySelectorAll('main img, main video, main audio, main iframe, main pre, main .ee-post, main .ee-ccard, main .ee-arch-card, main .ee-resource-card, main .horizontal-card, main .panel-card')]
            .filter(el => el.offsetParent !== null)
            .filter(el => {
              const rect = el.getBoundingClientRect();
              return rect.right > root.clientWidth + 2 || rect.left < -2;
            })
            .map(el => `${el.tagName.toLowerCase()}.${typeof el.className === 'string' ? el.className.trim().split(/\\s+/).join('.') : ''}`);
          const escapedHeadings = [...document.querySelectorAll('h1, h2, h3, h4, h5, h6')]
            .filter(el => el.offsetParent !== null && el.textContent.trim())
            .filter(el => {
              const rect = el.getBoundingClientRect();
              const parent = el.parentElement?.getBoundingClientRect();
              return rect.right > root.clientWidth + 2 || rect.left < -2 || (parent && (rect.right > parent.right + 2 || rect.left < parent.left - 2));
            })
            .map(el => `${el.tagName.toLowerCase()}.${typeof el.className === 'string' ? el.className.trim().split(/\\s+/).join('.') : ''}`);
          const wrappedSectionHeadings = innerWidth <= 640
            ? [...document.querySelectorAll('.ee-sec-title, .ee-ct-title, .ee-steps-top h3, .ee-latest-card .lc-title, .ee-radio-title, .ee-feature .feat-title')]
              .filter(el => el.offsetParent !== null && el.textContent.trim())
              .filter(el => {
                const style = getComputedStyle(el);
                const lineHeight = Number.parseFloat(style.lineHeight) || Number.parseFloat(style.fontSize) * 1.7;
                return el.getBoundingClientRect().height > lineHeight * 1.55;
              })
              .map(el => `${el.tagName.toLowerCase()}.${typeof el.className === 'string' ? el.className.trim().split(/\\s+/).join('.') : ''}`)
            : [];
          return {
            title: document.title,
            clientWidth: root.clientWidth,
            scrollWidth: Math.max(root.scrollWidth, document.body?.scrollWidth || 0),
            brokenImages,
            unnamed,
            escaped,
            escapedContent,
            escapedHeadings,
            wrappedSectionHeadings,
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
        if (metrics.escapedContent.length) report('failure', viewportName, route, `media/card elements escape viewport: ${metrics.escapedContent.slice(0, 6).join(', ')}`);
        if (metrics.escapedHeadings.length) report('failure', viewportName, route, `headings escape their box/viewport: ${metrics.escapedHeadings.slice(0, 6).join(', ')}`);
        if (metrics.wrappedSectionHeadings.length) report('failure', viewportName, route, `mobile section headings wrap to multiple lines: ${metrics.wrappedSectionHeadings.slice(0, 6).join(', ')}`);
        if (!metrics.title) report('failure', viewportName, route, 'document title is empty');
        if (!metrics.main) report('warning', viewportName, route, 'semantic main landmark is missing');
        if (metrics.unnamed.length) report('warning', viewportName, route, `interactive elements without accessible names: ${metrics.unnamed.slice(0, 8).join(', ')}`);
        if (responseErrors.size) report('failure', viewportName, route, `local resource errors: ${[...new Set(responseErrors.values())].join(', ')}`);
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
  if (!failures.length) {
    for (const message of warnings.slice(0, 9)) console.log(`::warning title=Visual accessibility audit::${message}`);
  }
}
if (failures.length) process.exit(1);
