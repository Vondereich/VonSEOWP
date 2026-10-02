const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const [publicUrl, protectedUrl, outputDir] = process.argv.slice(2);
if (!publicUrl || !protectedUrl || !outputDir) throw new Error('Pass public/protected QC fixture URLs and a screenshot directory.');
fs.mkdirSync(outputDir, { recursive: true });

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.VONSEO_BROWSER_PATH, headless: true, args: ['--host-resolver-rules=MAP localhost 127.0.0.1'] });
  const results = [];
  try {
    for (const width of [1440, 390, 320]) {
      const context = await browser.newContext({ viewport: { width, height: 900 } });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      page.on('response', response => { if (response.url().includes('/vonseo/public/') && response.status() >= 400) errors.push(response.url()); });
      const response = await page.goto(publicUrl, { waitUntil: 'networkidle' });
      assert.equal(response.status(), 200);
      const toc = page.locator('.vonseo-toc-container');
      assert.equal(await toc.count(), 1);
      await toc.scrollIntoViewIfNeeded();
      const problems = await toc.evaluate(element => {
        const failures = [];
        const box = element.getBoundingClientRect();
        if (element.scrollWidth > element.clientWidth + 1 || box.right > innerWidth + 1 || box.left < 0) failures.push('TOC overflow');
        for (const link of element.querySelectorAll('a')) {
          const id = decodeURIComponent(link.hash.slice(1));
          if (!document.getElementById(id)) failures.push(`Missing target ${id}`);
          if ([...document.querySelectorAll('[id]')].filter(node => node.id === id).length !== 1) failures.push(`Duplicate TOC target ${id}`);
        }
        return failures;
      });
      assert.deepEqual(problems, []);
      await toc.screenshot({ path: path.join(outputDir, `toc-${width}.png`) });
      const toggle = toc.locator('button');
      const list = toc.locator('ul');
      assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
      assert.equal(await toggle.getAttribute('aria-controls'), await list.getAttribute('id'));
      await toggle.click();
      assert.equal(await toggle.getAttribute('aria-expanded'), 'false');
      assert.equal(await list.isVisible(), false);
      await toggle.press('Enter');
      assert.equal(await list.isVisible(), true);
      assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
      for (const link of await toc.locator('a').all()) {
        const href = await link.getAttribute('href');
        await link.click();
        assert.equal(new URL(page.url()).hash, href);
        await page.waitForFunction(fragment => {
          const box = document.getElementById(decodeURIComponent(fragment.slice(1))).getBoundingClientRect();
          return box.top < innerHeight && box.bottom > 0;
        }, href);
        const targetVisible = await page.evaluate(fragment => {
          const target = document.getElementById(decodeURIComponent(fragment.slice(1)));
          const box = target.getBoundingClientRect();
          return box.top < innerHeight && box.bottom > 0;
        }, href);
        assert.equal(targetVisible, true);
      }
      assert.deepEqual(errors, []);
      const themeDuplicateIds = await page.evaluate(() => {
        const ids = [...document.querySelectorAll('[id]')].map(node => node.id);
        return [...new Set(ids.filter((id, index) => ids.indexOf(id) !== index))];
      });
      results.push({ width, targets: 4, toggle: 'mouse and keyboard passed', overflow: false, errors, themeDuplicateIds });
      await context.close();
    }
    if (protectedUrl !== '-') {
      const context = await browser.newContext();
      const page = await context.newPage();
      await page.goto(protectedUrl, { waitUntil: 'networkidle' });
      assert.equal((await page.locator('head').innerHTML()).includes('PrivateBrowser'), false);
      assert.equal(await page.locator('.vonseo-toc-container').count(), 0);
      await page.locator('input[name="post_password"]').fill('vonseo-qc-password');
      await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator('form.post-password-form input[type="submit"]').click()]);
      assert.equal((await page.locator('head').innerHTML()).includes('PrivateBrowserAnswerSentinel'), true);
      assert.equal(await page.locator('.vonseo-toc-container').count(), 1);
      results.push({ protected: 'anonymous head clean; correct native form/password cookie restores output' });
      await context.close();
    }
    console.log(JSON.stringify(results));
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
