const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const list = { hidden: false };
const attributes = { 'aria-expanded': 'true', 'data-show-label': '[papar]', 'data-hide-label': '[sembunyi]' };
let click;
const toggle = {
  closest: () => ({ querySelector: () => list }),
  getAttribute: name => attributes[name],
  setAttribute: (name, value) => { attributes[name] = value; },
  addEventListener: (name, callback) => { click = callback; },
};
const document = {
  addEventListener: (name, callback) => callback(),
  querySelectorAll: () => [toggle],
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../public/js/vonseowp-public.js'), 'utf8'), { document });
click.call(toggle);
assert.equal(list.hidden, true);
assert.equal(attributes['aria-expanded'], 'false');
assert.equal(toggle.textContent, '[papar]');
click.call(toggle);
assert.equal(list.hidden, false);
assert.equal(attributes['aria-expanded'], 'true');
assert.equal(toggle.textContent, '[sembunyi]');
toggle.closest = () => null;
assert.doesNotThrow(() => click.call(toggle));
console.log('TOC toggle tests passed (translated labels, expanded state, missing target)');
