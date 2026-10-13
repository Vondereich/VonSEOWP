const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const [root, output] = process.argv.slice(2);
const base = 'http://127.0.0.1:8766';
const auth = JSON.parse(fs.readFileSync(path.join(root, 'browser-auth.json'), 'utf8'));
const fixture = JSON.parse(fs.readFileSync(path.join(root, 'qc-final-result.json'), 'utf8'));
assert.equal(new URL(fixture.url).origin, base);
(async () => {
  const browser = await chromium.launch({ executablePath: process.env.VONSEO_BROWSER_PATH, headless: true });
  try {
    const context = await browser.newContext();
    const page = await context.newPage();
    const failures = [];
    page.on('pageerror', error => failures.push(error.message));
    const response = await context.request.get(fixture.url, { maxRedirects: 0 });
    assert.equal(response.status(), 200, 'The p+name rule must be suppressed before its first HTTP hop.');
    for (const query of [`?p=${fixture.id}&name=${fixture.slug}`, `?name=${fixture.slug}`]) {
      let url = fixture.url + query;
      const seen = new Set();
      for (let hop = 0; hop < 5; hop++) {
        assert.ok(!seen.has(url), 'Multiple-identity URL restarted a redirect cycle.');
        seen.add(url);
        const result = await context.request.get(url, { maxRedirects: 0 });
        if (result.status() === 200) break;
        assert.ok([301, 302].includes(result.status()));
        url = new URL(result.headers().location, url).href;
        assert.equal(new URL(url).origin, base);
        assert.ok(hop < 4, 'Redirect chain did not terminate.');
      }
    }
    await page.goto(base + '/wp-login.php');
    await page.locator('#user_login').fill(auth.username);
    await page.locator('#user_pass').fill(auth.password);
    await Promise.all([page.waitForURL(/wp-admin/), page.locator('#wp-submit').click()]);
    await page.goto(base + `/wp-admin/post.php?post=${fixture.id}&action=edit`);
    const metabox = page.locator('#vonseowp_meta_box');
    if (!(await metabox.locator('.inside').isVisible())) await metabox.locator('.hndle').click();
    await metabox.locator('[data-tab="meta-schema"]').click();
    await page.locator('#vonseowp_schema_type').selectOption('Review');
    assert.ok(await page.locator('#row-reviewed-product').isVisible());
    assert.ok(await page.locator('#row-review-rating').isVisible());
    assert.ok(!(await page.locator('input[name="vonseowp_rating_count"]').isVisible()));
    await page.locator('#vonseowp_reviewed_product').fill('Browser verified product');
    for (const width of [1440, 390, 320]) {
      await page.setViewportSize({ width, height: 1000 });
      await page.locator('#row-reviewed-product').scrollIntoViewIfNeeded();
      const measurements = await page.locator('#row-reviewed-product').evaluate(element => {
        const input = element.querySelector('input').getBoundingClientRect();
        const section = element.getBoundingClientRect();
        return { inputWidth: input.width, sectionWidth: section.width, inputRight: input.right, sectionRight: section.right };
      });
      assert.ok(measurements.inputWidth > 0 && measurements.inputRight <= measurements.sectionRight + 2, 'Schema input overflows its container.');
      await metabox.screenshot({ path: path.join(output, `schema-review-${width}.png`) });
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.locator('#vonseowp_schema_type').selectOption('Product');
    assert.ok(!(await page.locator('#row-reviewed-product').isVisible()));
    assert.ok(await page.locator('input[name="vonseowp_rating_count"]').isVisible());
    await page.locator('#vonseowp_schema_type').selectOption('Article');
    assert.ok(!(await page.locator('#row-review-rating').isVisible()));
    await page.locator('#vonseowp_schema_type').selectOption('Review');
    await page.locator('#publish').click();
    await page.waitForLoadState('networkidle');
    const html = await (await context.request.get(fixture.url)).text();
    const graph = JSON.parse(/<script[^>]*class="vonseowp-schema"[^>]*>\s*([\s\S]*?)<\/script>/.exec(html)[1])['@graph'];
    const review = graph.find(node => node['@type'] === 'Review');
    assert.equal(review.itemReviewed.name, 'Browser verified product');
    assert.equal(review.reviewRating.ratingValue, 4.5);
    assert.ok(!review.aggregateRating);
    assert.deepEqual(failures, [], 'Browser JS errors found.');
    console.log(JSON.stringify({ status: 'PASS', http_cycle: 'suppressed', authenticated_review_save: 'PASS', schema_fields: 'Review/Product/Article', viewports: [1440, 390, 320], js_errors: 0 }));
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
