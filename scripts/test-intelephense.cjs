const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawn } = require('node:child_process');
const { pathToFileURL } = require('node:url');

const serverPath = process.argv[2];
if (!serverPath || !fs.existsSync(serverPath)) throw new Error('Pass an already-installed Intelephense server path.');
const root = path.resolve(__dirname, '..');
const localSettings = JSON.parse(fs.readFileSync(path.join(root, '.vscode/settings.json'), 'utf8'));
const files = ['includes/class-vonseowp-frontend.php', 'includes/class-vonseowp-toc.php', 'includes/class-vonseowp-site-audit.php', 'scripts/test-toc.php', 'scripts/qc-wordpress-package.php', 'scripts/test-wordpress-privacy.php', 'scripts/test-wordpress-toc-robots.php'];
const settings = {
  environment: { includePaths: localSettings['intelephense.environment.includePaths'] },
  diagnostics: { undefinedParameters: true },
};
const server = spawn(process.execPath, [serverPath, '--stdio'], { stdio: ['pipe', 'pipe', 'pipe'] });
const diagnostics = new Map();
const requests = new Map();
let buffer = Buffer.alloc(0);
let nextId = 0;
function send(message) {
  const body = Buffer.from(JSON.stringify({ jsonrpc: '2.0', ...message }));
  server.stdin.write(`Content-Length: ${body.length}\r\n\r\n`);
  server.stdin.write(body);
}
function request(method, params) {
  const id = ++nextId;
  return new Promise((resolve, reject) => { requests.set(id, { resolve, reject }); send({ id, method, params }); });
}
server.stdout.on('data', data => {
  buffer = Buffer.concat([buffer, data]);
  while (true) {
    const end = buffer.indexOf('\r\n\r\n');
    if (end < 0) return;
    const length = Number(/Content-Length: (\d+)/i.exec(buffer.subarray(0, end).toString())[1]);
    if (buffer.length < end + 4 + length) return;
    const message = JSON.parse(buffer.subarray(end + 4, end + 4 + length).toString());
    buffer = buffer.subarray(end + 4 + length);
    if (message.method === 'textDocument/publishDiagnostics') diagnostics.set(decodeURIComponent(message.params.uri).toLowerCase(), message.params.diagnostics);
    if (message.id !== undefined && message.method) {
      const result = message.method === 'workspace/configuration' ? message.params.items.map(item => (item.section || 'intelephense').split('.').slice(1).reduce((value, key) => value && value[key], settings) ?? null) : null;
      send({ id: message.id, result });
    } else if (requests.has(message.id)) {
      const pending = requests.get(message.id);
      requests.delete(message.id);
      if (message.error) pending.reject(new Error(message.error.message)); else pending.resolve(message.result);
    }
  }
});
server.stderr.on('data', data => process.stderr.write(data));
(async () => {
  const storagePath = fs.mkdtempSync(path.join(os.tmpdir(), 'vonseo-intelephense-'));
  try {
    await request('initialize', { processId: process.pid, rootUri: pathToFileURL(root).href, workspaceFolders: [{ uri: pathToFileURL(root).href, name: 'vonseowp' }], capabilities: { workspace: { configuration: true } }, initializationOptions: { storagePath, globalStoragePath: storagePath } });
    send({ method: 'initialized', params: {} });
    send({ method: 'workspace/didChangeConfiguration', params: { settings: { intelephense: settings } } });
    for (const file of files) send({ method: 'textDocument/didOpen', params: { textDocument: { uri: pathToFileURL(path.join(root, file)).href, languageId: 'php', version: 1, text: fs.readFileSync(path.join(root, file), 'utf8') } } });
    await new Promise(resolve => setTimeout(resolve, 30000));
    const results = files.map(file => ({ file, diagnostics: diagnostics.get(decodeURIComponent(pathToFileURL(path.join(root, file)).href).toLowerCase()) ?? null }));
    console.log(JSON.stringify(results));
    if (results.some(result => result.diagnostics === null || result.diagnostics.some(diagnostic => diagnostic.severity <= 2))) process.exitCode = 1;
  } finally {
    server.kill();
  }
})().catch(error => { server.kill(); console.error(error); process.exitCode = 1; });
