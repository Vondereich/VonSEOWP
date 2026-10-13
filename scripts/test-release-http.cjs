const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const [baseUrl] = process.argv.slice(2);
const base = new URL(baseUrl);
if (base.hostname !== '127.0.0.1' || base.port !== '8766') throw new Error('Use only the disposable loopback QC server.');

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.VONSEO_BROWSER_PATH, headless: true });
  try {
    const context = await browser.newContext();
    const page = await context.newPage();
    const chain = async pathname => {
      const hops = [];
      let url = new URL(pathname, base);
      for (let count = 0; count < 5; count += 1) {
        const response = await context.request.get(url.href, { maxRedirects: 0 });
        hops.push({ path: url.pathname + url.search, status: response.status(), location: response.headers().location });
        if (![301, 302, 303, 307, 308].includes(response.status())) return { hops, response };
        url = new URL(response.headers().location, url);
        assert.equal(url.origin, base.origin, 'QC redirects must stay on the disposable site.');
      }
      throw new Error(`Redirect cycle or excessive chain: ${JSON.stringify(hops)}`);
    };
    const loop = await chain('/vonseo-qc-articles/loop/');
    assert.equal(loop.response.status(), 200);
    assert.equal(loop.hops.length, 1, 'The identity-query loop must be suppressed before redirect.');
    const multi = await chain('/vonseo-qc-articles/multi/');
    assert.equal(multi.response.status(), 200);
    assert.equal(multi.hops.length, 3, 'Multipage target must retain plugin and native canonical redirects.');
    assert.equal(multi.hops[2].path, '/vonseo-qc-articles/multi/2/');
    const cross = await chain('/vonseo-qc-books/same-slug/');
    assert.equal(cross.response.status(), 200);
    assert.equal(cross.hops.length, 3);
    assert.equal(cross.hops[2].path, '/vonseo-qc-articles/same-slug/');
    const protectedResponse = await context.request.get(new URL('/vonseo-qc-articles/protected/', base).href);
    assert.equal(protectedResponse.status(), 200);
    const protectedHtml = await protectedResponse.text();
    const head = await page.evaluate(html => new DOMParser().parseFromString(html, 'text/html').head.innerHTML, protectedHtml);
    assert.doesNotMatch(head, /NativeProtected(?:Body|Description)Sentinel/);
    assert.doesNotMatch(protectedHtml, /NativeProtectedBodySentinel/);
    assert.match(protectedHtml, /post_password/);
    const sitemap = await context.request.get(new URL('/sitemap.xml', base).href);
    assert.equal(sitemap.status(), 200);
    const sitemapPages = await page.evaluate(xml => Array.from(new DOMParser().parseFromString(xml, 'application/xml').querySelectorAll('sitemap > loc'), node => node.textContent), await sitemap.text());
    assert.equal(sitemapPages.length, 2);
    const urls = [];
    for (const url of sitemapPages) {
      assert.equal(new URL(url).origin, base.origin);
      const response = await context.request.get(url);
      assert.equal(response.status(), 200);
      const entries = await page.evaluate(xml => Array.from(new DOMParser().parseFromString(xml, 'application/xml').querySelectorAll('url > loc'), node => node.textContent), await response.text());
      assert.ok(entries.length > 0 && entries.length <= 1000);
      urls.push(...entries);
    }
    assert.equal(new Set(urls).size, urls.length);
    assert.equal(urls.filter(url => url === new URL('/', base).href).length, 1);
    assert.ok(!urls.some(url => url.includes('/protected/')));
    console.log(JSON.stringify({ status: 'PASS', loop: loop.hops, multipage: multi.hops, cross_type: cross.hops, sitemap_urls: urls.length, protected_head: 'clean' }));
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
