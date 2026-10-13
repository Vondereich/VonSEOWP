const fs = require('node:fs');
const assert = require('node:assert/strict');

async function main() {
  const state = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
  const type = process.argv[3] || 'Review';
  const url = new URL(state.url);
  assert.equal(url.hostname, 'localhost', 'Only the authorized loopback site');
  assert(url.pathname.startsWith('/wordpress/'));
  url.searchParams.set('vonseo_author_qc', `${state.token}-${type}`);
  const response = await fetch(url, { signal: AbortSignal.timeout(20000) });
  assert.equal(response.status, 200);
  const html = await response.text();
  const match = html.match(/<script\b[^>]*class="vonseowp-schema"[^>]*>([\s\S]*?)<\/script>/i);
  assert(match, 'Actual theme head must contain VonSEO schema');
  const graph = JSON.parse(match[1])['@graph'];
  const node = graph.find((item) => item['@id']?.startsWith(`${state.url}#`));
  assert(node, 'Actual singular content node required');
  assert.equal(node['@type'], type);
  assert.deepEqual(node.author, state.author);
  if (type === 'Review') {
    assert.equal(node.itemReviewed.name, 'VonSEO author test product');
    assert.equal(node.reviewRating.ratingValue, 4.5);
    assert(!node.aggregateRating);
  }
  assert(/<meta name=['"]robots['"][^>]*noindex/i.test(html), 'Owned fixture remains noindex');
  console.log(`HTTP ${type} author passed on ${state.theme}; real theme head, anonymous request.`);
}
main().catch((error) => { console.error(error); process.exitCode = 1; });
