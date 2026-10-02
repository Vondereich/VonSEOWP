const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require('playwright');

const [wpRoot, outputDir] = process.argv.slice(2);
if (!wpRoot || !outputDir || !process.env.VONSEO_PHP_PATH) {
  throw new Error('Pass WP_ROOT OUTPUT_DIR and set VONSEO_PHP_PATH; this only renders the source admin partial.');
}
const repo = path.resolve(__dirname, '..');
const rendered = spawnSync(process.env.VONSEO_PHP_PATH, ['-r', `
  $_SERVER['REQUEST_METHOD'] = 'GET';
  $_SERVER['REQUEST_URI'] = '/wordpress/wp-admin/admin.php?page=vonseo';
  require getenv('VONSEO_QC_WP_ROOT') . '/wp-load.php';
  require_once ABSPATH . 'wp-admin/includes/template.php';
  $before = serialize(get_option('vonseowp_settings'));
  include getenv('VONSEO_QC_PARTIAL');
  if ($before !== serialize(get_option('vonseowp_settings'))) exit(1);
`], {
  env: { ...process.env, VONSEO_QC_WP_ROOT: wpRoot, VONSEO_QC_PARTIAL: path.join(repo, 'admin/partials/vonseowp-admin-display.php') },
  encoding: 'utf8', maxBuffer: 2 * 1024 * 1024,
});
assert.equal(rendered.status, 0, rendered.stderr);
assert.equal(rendered.stderr, '', 'Native PHP render emitted diagnostics.');
fs.mkdirSync(outputDir, { recursive: true });

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.VONSEO_BROWSER_PATH, headless: true });
  const results = [];
  try {
    for (const width of [1440, 1024, 390, 320]) {
      const page = await browser.newPage({ viewport: { width, height: 900 } });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.hostname !== 'vonseo-qc.test') return route.abort();
        if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/wp-includes/css/dashicons.css"><link rel="stylesheet" href="/wp-admin/css/common.css"><link rel="stylesheet" href="/wp-admin/css/forms.css"><link rel="stylesheet" href="/vonseo.css"></head><body class="wp-core-ui" style="margin:0;background:#f0f0f1">${rendered.stdout}</body></html>` });
        if (url.pathname === '/vonseo.css') return route.fulfill({ path: path.join(repo, 'admin/css/vonseowp-admin.css') });
        if (/^\/wp-(includes|admin)\/(css|fonts)\/[a-z0-9.-]+$/i.test(url.pathname)) {
          const asset = path.join(wpRoot, url.pathname.slice(1));
          if (fs.existsSync(asset)) return route.fulfill({ path: asset });
        }
        errors.push(`Unexpected asset: ${url.pathname}`);
        return route.abort();
      });
      await page.goto('http://vonseo-qc.test/', { waitUntil: 'networkidle' });
      await page.evaluate(() => document.fonts.ready);
      const buttons = page.locator('.von-btn-donate');
      assert.equal(await buttons.count(), 2);
      for (const [index, button] of (await buttons.all()).entries()) {
        if (index === 1) await page.evaluate(() => {
          document.querySelectorAll('.von-tab-content').forEach(tab => { tab.style.display = 'none'; });
          document.getElementById('tab-tutorial').style.display = 'block';
        });
        await button.scrollIntoViewIfNeeded();
        assert.equal(await button.getAttribute('href'), 'https://paypal.me/kurama87');
        assert.equal(await button.getAttribute('target'), '_blank');
        assert.match(await button.getAttribute('rel'), /noopener noreferrer/);
        assert.equal(await button.locator('.dashicons').getAttribute('aria-hidden'), 'true');
        const checkLayout = () => button.evaluate(element => {
          const box = element.getBoundingClientRect();
          const icon = element.querySelector('.dashicons').getBoundingClientRect();
          const label = element.querySelector('.von-donate-label').getBoundingClientRect();
          return {
            inside: box.left >= 0 && box.right <= innerWidth && element.scrollWidth <= element.clientWidth + 1,
            height: box.height,
            aligned: Math.abs((icon.top + icon.height / 2) - (label.top + label.height / 2)) < 1,
          };
        });
        const initial = await checkLayout();
        assert.equal(initial.inside, true, `Donate ${index} overflow at ${width}px: ${JSON.stringify(await button.evaluate(element => ({ box: element.getBoundingClientRect().toJSON(), scrollWidth: element.scrollWidth, clientWidth: element.clientWidth, viewport: innerWidth })))}`);
        assert.ok(initial.height >= 44);
        assert.equal(initial.aligned, true);
        await button.hover();
        assert.deepEqual(await checkLayout(), initial, 'Hover shifted the button.');
        await button.focus();
        assert.equal(await button.evaluate(element => getComputedStyle(element).outlineStyle), 'solid');
        await button.press('Tab');
        assert.equal(await button.evaluate(element => document.activeElement === element), false);
        const panel = page.locator(index === 0 ? '.von-support-card' : '.von-donate-section');
        await panel.screenshot({ path: path.join(outputDir, `donate-${index}-${width}.png`) });
        await button.locator('.von-donate-label').evaluate(element => { element.textContent = 'Support ongoing independent development with a coffee donation'; });
        const translated = await checkLayout();
        assert.equal(translated.inside, true, 'Long translated label overflowed.');
        assert.equal(translated.aligned, true);
        await button.locator('.von-donate-label').evaluate(element => { element.textContent = 'Buy me a coffee'; });
      }
      await page.emulateMedia({ reducedMotion: 'reduce' });
      assert.equal(await buttons.first().evaluate(element => getComputedStyle(element).transitionDuration), '0s');
      assert.deepEqual(errors, []);
      results.push({ width, buttons: 2, alignment: 'passed', focus: 'passed', longLabel: 'passed', destination: 'unchanged' });
      await page.close();
    }
    console.log(JSON.stringify(results, null, 2));
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
