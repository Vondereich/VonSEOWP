const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const analyzerPath = path.join(__dirname, '..', 'admin', 'js', 'vonseowp-analyzer.js');
const code = fs.readFileSync(analyzerPath, 'utf8');
const context = {
  window: {},
  module: { exports: {} },
  exports: {},
};

vm.createContext(context);
vm.runInContext(code, context, { filename: analyzerPath });

const analyzer = context.module.exports || context.window.VonSEOWPAnalyzer;

assert.ok(analyzer, 'analyzer should export a public API');
assert.strictEqual(typeof analyzer.analyze, 'function', 'analyzer should expose analyze()');

assert.strictEqual(
  analyzer.stripHtml(
    '<p>Visible copy</p><script>hidden script copy</script ><style>hidden style copy</style data-test="1"><p>Final copy</p>',
  ),
  'Visible copy Final copy',
  'stripHtml should discard script/style content when malformed end tags contain whitespace or attributes',
);
assert.strictEqual(
  analyzer.stripHtml('<p>Before</p><script type="text/javascript">hidden</script foo="bar"><p>After</p>'),
  'Before After',
  'stripHtml should handle browser-tolerated script end-tag attributes',
);
assert.strictEqual(
  analyzer.stripHtml('<p>Before</p><script>if (a < b) { window.hidden = true; }</script ><p>After</p>'),
  'Before After',
  'stripHtml fallback should ignore less-than operators inside scripts',
);
assert.strictEqual(
  analyzer.stripHtml('<p>Before</p><script/>hidden script copy</script><p>After</p>'),
  'Before After',
  'stripHtml fallback should follow HTML semantics for self-closing syntax on script elements',
);

function getCheck(result, codeName) {
  return result.checks.find((check) => check.code === codeName);
}

const strongContent = `
  <h1>Lightweight SEO guide</h1>
  <h2>Practical publishing workflow</h2>
  <p>Lightweight SEO helps editors keep metadata focused without adding slow external services.</p>
  <p>This local content analyzer checks headings, image descriptions, useful links, and body structure while the editor writes a post. It should guide practical improvements without promising search rankings or sending private drafts away from the site.</p>
  <p>Editors need short, readable prompts that explain the next useful action. A good analyzer should notice missing structure, weak snippets, and media issues while staying fast enough for shared hosting and normal WordPress screens.</p>
  <p>The goal is steady editorial quality, not a heavy audit suite. Checks should remain deterministic, cached only when needed, and easy to understand during daily publishing.</p>
  <p>Clear advice, native WordPress loading, and local-only calculations keep the workflow quick for small publishers and larger content teams.</p>
  <img src="cover.jpg" alt="Lightweight SEO dashboard">
  <a href="/internal-guide">Internal guide</a>
  <a href="https://example.com/reference">External reference</a>
`;

const strongResult = analyzer.analyze({
  keyword: 'lightweight seo',
  title: 'Lightweight SEO checklist for WordPress publishers',
  description: 'A lightweight SEO checklist for WordPress publishers who want fast local content guidance.',
  content: strongContent,
  siteUrl: 'https://mysite.test',
});

assert.ok(strongResult.score >= 80, `expected strong content to score >= 80, got ${strongResult.score}`);
assert.strictEqual(getCheck(strongResult, 'keyword_density').status, 'good');
assert.strictEqual(getCheck(strongResult, 'heading_structure').status, 'good');
assert.strictEqual(getCheck(strongResult, 'image_alt').status, 'good');
assert.strictEqual(getCheck(strongResult, 'link_presence').status, 'good');
assert.strictEqual(getCheck(strongResult, 'first_paragraph_keyword').status, 'good');

const weakContent = `
  <p>This draft is short and misses useful structure.</p>
  <img src="draft.jpg" alt="">
`;

const weakResult = analyzer.analyze({
  keyword: 'lightweight seo',
  title: 'Draft',
  description: 'Short.',
  content: weakContent,
  siteUrl: 'https://mysite.test',
});

assert.strictEqual(getCheck(weakResult, 'heading_structure').status, 'bad');
assert.strictEqual(getCheck(weakResult, 'image_alt').status, 'bad');
assert.strictEqual(getCheck(weakResult, 'link_presence').status, 'warn');
assert.strictEqual(getCheck(weakResult, 'keyword_density').status, 'bad');
assert.ok(weakResult.score < 50, `expected weak content to score below 50, got ${weakResult.score}`);

const waitingResult = analyzer.analyze({
  keyword: '',
  title: 'Any title',
  description: 'Any description',
  content: strongContent,
});

assert.strictEqual(waitingResult.score, 0);
assert.strictEqual(waitingResult.waitingForKeyword, true);

const emptyMetaResult = analyzer.analyze({
  keyword: 'lightweight seo',
  title: '',
  description: '',
  content: strongContent,
  siteUrl: 'https://mysite.test',
});

assert.strictEqual(getCheck(emptyMetaResult, 'title_length').status, 'bad');
assert.strictEqual(getCheck(emptyMetaResult, 'title_length').meta.length, 0);
assert.strictEqual(getCheck(emptyMetaResult, 'description_length').status, 'bad');
assert.strictEqual(getCheck(emptyMetaResult, 'description_length').meta.length, 0);

console.log('Analyzer tests passed');
