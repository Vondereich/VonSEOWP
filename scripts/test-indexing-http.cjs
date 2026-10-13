const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const path = require('node:path');
const [wpRoot, baseUrl] = process.argv.slice(2);
const base = new URL(baseUrl);
if (base.hostname !== '127.0.0.1' || base.port !== '8766' || !process.env.VONSEO_PHP_PATH) throw new Error('Use only the isolated loopback server and configured PHP.');
const phpArgs = JSON.parse(process.env.VONSEO_PHP_ARGS || '[]');
if (!Array.isArray(phpArgs) || phpArgs.some(value => typeof value !== 'string')) throw new Error('VONSEO_PHP_ARGS must be a JSON array of PHP runtime arguments.');
const setState = mode => {
  const result = spawnSync(process.env.VONSEO_PHP_PATH, [...phpArgs, path.join(__dirname, 'test-wordpress-indexing.php'), wpRoot, mode], { encoding: 'utf8' });
  assert.equal(result.status, 0, result.stderr || result.stdout);
  assert.equal(result.stderr, '');
};
(async () => {
  for (const mode of ['llm-public', 'llm-private', 'llm-disabled', 'llm-default']) {
    setState(mode);
    for (const route of ['/llms.txt', '/?vonseowp_llm=1', '/?vonseowp_llm%5B%5D=1']) {
      const response = await fetch(new URL(route, base), { redirect: 'manual' });
      const body = await response.text();
      const denied = mode === 'llm-private' || mode === 'llm-disabled';
      assert.equal(response.status, denied ? 404 : 200, `${mode} ${route}`);
      assert.equal(response.headers.get('x-robots-tag'), denied ? 'noindex, nofollow' : 'index, follow');
      assert.match(response.headers.get('cache-control'), /no-cache|no-store/);
      if (denied) assert.doesNotMatch(body, /NativeCuratedVisibilitySentinel|Key Resources|Detailed index of all public pages/);
      else if (mode === 'llm-public') assert.match(body, /NativeCuratedVisibilitySentinel/);
      else assert.match(body, /Key Resources/);
    }
  }
  console.log('Native llms HTTP tests passed (12 rewritten/scalar/array alias responses; visibility, curated/default, headers and cache).');
})().catch(error => { console.error(error); process.exitCode = 1; });
