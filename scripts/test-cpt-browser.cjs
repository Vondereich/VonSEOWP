const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const [baseUrl, outputDir] = process.argv.slice(2);
const base = new URL(baseUrl);
if (base.hostname !== '127.0.0.1' || !process.env.VONSEO_QC_PASSWORD || !outputDir) {
  throw new Error('Pass the isolated loopback QC URL/output directory and set VONSEO_QC_PASSWORD.');
}

(async () => {
  fs.mkdirSync(outputDir, { recursive: true });
  const browser = await chromium.launch({ executablePath: process.env.VONSEO_BROWSER_PATH, headless: true });
  try {
    const context = await browser.newContext();
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(new URL('/wp-login.php', base).href);
    await page.locator('#user_login').fill('vonseo_qc');
    await page.locator('#user_pass').fill(process.env.VONSEO_QC_PASSWORD);
    await Promise.all([page.waitForURL('**/wp-admin/**'), page.locator('#wp-submit').click()]);
    const settingsUrl = new URL('/wp-admin/admin.php?page=vonseo', base).href;
    await page.goto(settingsUrl);
    const book = page.locator('.von-checkbox-row input[value="vonseqc_book"]');
    assert.equal(await book.isChecked(), false, 'CPT must be opt-in on an existing settings record.');
    await book.check();
    await Promise.all([page.waitForNavigation(), page.locator('button[type="submit"]').click()]);
    assert.equal(await book.isChecked(), true, 'Native settings form must persist the CPT selection.');
    const sitemapIndex = await context.request.get(new URL('/sitemap.xml', base).href);
    assert.equal(sitemapIndex.status(), 200);
    assert.match(await sitemapIndex.text(), /<sitemapindex /);
    const locations = [];
    for (const [number, count] of [[1, 1000], [2, 1]]) {
      const response = await context.request.get(new URL(`/sitemap.xml?vonseowp_sitemap_page=${number}`, base).href);
      assert.equal(response.status(), 200);
      assert.match(response.headers()['content-type'], /application\/xml/);
      const urls = await page.evaluate(body => Array.from(new DOMParser().parseFromString(body, 'application/xml').getElementsByTagName('loc'), entry => entry.textContent), await response.text());
      assert.equal(urls.length, count);
      locations.push(...urls);
    }
    assert.equal(new Set(locations).size, 1001, 'Native HTTP sitemap pages must not duplicate URLs.');
    for (const suffix of ['3', '0', '-1', 'bad', '%5B%5D=1']) {
      const query = suffix === '%5B%5D=1' ? 'vonseowp_sitemap_page%5B%5D=1' : `vonseowp_sitemap_page=${suffix}`;
      assert.equal((await context.request.get(new URL(`/sitemap.xml?${query}`, base).href)).status(), 404, 'Invalid sitemap page must return 404.');
    }
    for (const width of [1440, 390, 320]) {
      await page.setViewportSize({ width, height: 900 });
      const fieldset = page.locator('fieldset.von-form-group');
      await fieldset.scrollIntoViewIfNeeded();
      assert.equal(await fieldset.evaluate(element => {
        const box = element.getBoundingClientRect();
        return box.left >= 0 && box.right <= innerWidth && element.scrollWidth <= element.clientWidth + 1;
      }), true, `Native content-type form overflow at ${width}px`);
      await fieldset.screenshot({ path: path.join(outputDir, `native-content-types-${width}.png`) });
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.goto(new URL('/wp-admin/post-new.php?post_type=vonseqc_book', base).href);
    await page.locator('#vonseowp_meta_box').waitFor({ state: 'attached' });
    assert.equal(await page.locator('#vonseowp-analyzer-js-js').count(), 1, 'Selected CPT editor must load the existing analyzer.');
    await page.goto(new URL('/wp-admin/edit.php?post_type=vonseqc_book', base).href);
    assert.equal(await page.locator('thead .column-vonseo_score').count(), 1, 'Selected CPT list must show the score column.');
    await page.goto(settingsUrl);
    await book.uncheck();
    await Promise.all([page.waitForNavigation(), page.locator('button[type="submit"]').click()]);
    assert.equal(await book.isChecked(), false, 'Native form must persist disabling the CPT.');
    await page.goto(new URL('/wp-admin/post-new.php?post_type=vonseqc_book', base).href);
    assert.equal(await page.locator('#vonseowp_meta_box').count(), 0, 'Disabled CPT editor must not show the SEO box.');
    assert.equal(await page.locator('#vonseowp-analyzer-js-js').count(), 0, 'Disabled CPT editor must not load the analyzer.');
    await page.goto(new URL('/wp-admin/edit.php?post_type=vonseqc_book', base).href);
    assert.equal(await page.locator('thead .column-vonseo_score').count(), 0, 'Disabled CPT list must not show the score column.');
    await page.goto(settingsUrl);
    await page.locator('a[href="#tab-redirects"]').click();
    await page.locator('textarea[name="vonseowp_settings[redirects_list]"]').fill('/vonseo-qc-self -> /vonseo-qc-self\n/vonseo-qc-old -> /vonseo-qc-new');
    await Promise.all([page.waitForNavigation(), page.locator('button[type="submit"]').click()]);
    const selfRedirect = await context.request.get(new URL('/vonseo-qc-self', base).href, { maxRedirects: 0 });
    assert.equal(selfRedirect.status(), 404, 'Self-redirect must fall through to normal WordPress handling.');
    const validRedirect = await context.request.get(new URL('/vonseo-qc-old', base).href, { maxRedirects: 0 });
    assert.equal(validRedirect.status(), 301);
    assert.equal(validRedirect.headers().location, '/vonseo-qc-new');
    assert.deepEqual(errors, [], 'Native CPT admin pages emitted JavaScript errors.');
    console.log('Authenticated native CPT browser checks passed: settings persistence, editor/list opt-in, desktop/mobile layout, HTTP sitemap index/pages/404 and redirect headers.');
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
