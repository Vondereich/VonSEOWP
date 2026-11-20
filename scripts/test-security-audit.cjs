const fs = require("fs");
const path = require("path");

const root = path.resolve(__dirname, "..");
const read = (file) => fs.readFileSync(path.join(root, file), "utf8");
const countLineEndings = (file) => {
  const source = fs.readFileSync(path.join(root, file));
  let lf = 0;
  let crlf = 0;
  let crOnly = 0;

  for (let i = 0; i < source.length; i += 1) {
    if (source[i] === 10) {
      lf += 1;
      if (i > 0 && source[i - 1] === 13) {
        crlf += 1;
      }
    } else if (source[i] === 13 && (i + 1 >= source.length || source[i + 1] !== 10)) {
      crOnly += 1;
    }
  }

  return { lf, crlf, crOnly };
};
const assert = (condition, message) => {
  if (!condition) {
    throw new Error(message);
  }
};

const devActivationPath = path.join(root, "test_activation.php");
assert(
  !fs.existsSync(devActivationPath),
  "test_activation.php must not live in the plugin root; keep activation harnesses outside release/source surface.",
);

const competitors = read("includes/class-vonseowp-competitors.php");
assert(
  /wp_safe_remote_get\s*\(/.test(competitors),
  "Competitor scanner must use wp_safe_remote_get().",
);
assert(
  /limit_response_size/.test(competitors),
  "Competitor scanner must cap remote response size.",
);
assert(
  /wp_remote_retrieve_header\s*\(\s*\$response\s*,\s*['"]content-type['"]\s*\)/.test(competitors),
  "Competitor scanner must inspect remote content-type before parsing HTML.",
);
assert(
  /text\\\/html|text\/html|application\\\/xhtml\\\+xml|application\/xhtml\+xml/.test(competitors),
  "Competitor scanner must only parse HTML-like response content.",
);

const columns = read("includes/class-vonseowp-columns.php");
assert(
  !/\bstrip_tags\s*\(/.test(columns),
  "All Posts score normalization must use wp_strip_all_tags(), not native strip_tags().",
);
assert(
  /wp_strip_all_tags\s*\(/.test(columns),
  "All Posts score normalization must use the WordPress text stripping helper.",
);

const columnsLineEndings = countLineEndings("includes/class-vonseowp-columns.php");
assert(
  columnsLineEndings.crlf === 0 && columnsLineEndings.crOnly === 0,
  "All Posts columns file must use LF-only line endings.",
);

const siteAudit = read("includes/class-vonseowp-site-audit.php");
assert(
  /current_user_can\s*\(\s*['"]manage_options['"]\s*\)/.test(siteAudit),
  "Site audit actions must require manage_options.",
);
assert(
  /check_admin_referer\s*\(\s*['"]vonseowp_run_site_audit['"]\s*\)/.test(siteAudit),
  "Site audit actions must verify their nonce.",
);
assert(
  /private const MAX_POSTS\s*=\s*25/.test(siteAudit),
  "Site audit must retain its bounded 25-post scan limit.",
);
assert(
  /absint\s*\(\s*wp_unslash\s*\(\s*\$_POST\[['"]vonseowp_audit_page['"]\]/.test(siteAudit),
  "Site audit batch input must be unslashed and converted to a non-negative integer.",
);
assert(
  /'paged'\s*=>\s*\$page/.test(siteAudit) && /min\s*\(\s*\$page\s*,\s*\$pages\s*\)/.test(siteAudit),
  "Site audit must use a clamped WordPress query page for bounded batch navigation.",
);
assert(
  !/wp_(safe_)?remote_(get|post|request)\s*\(/.test(siteAudit),
  "Site audit must remain local-only and avoid remote requests.",
);

const siteAuditDisplay = read("admin/partials/vonseowp-site-audit-display.php");
assert(
  /echo\s+esc_html\s*\(\s*sprintf\s*\([\s\S]*?Batch %1\$d of %2\$d/.test(siteAuditDisplay),
  "Site audit must escape the final formatted batch pagination label.",
);

const uninstall = read("uninstall.php");
assert(
  /delete_transient\s*\(\s*['"]vonseowp_site_audit_results['"]\s*\)/.test(uninstall),
  "Uninstall must remove cached site audit results.",
);

// Every plugin-owned post meta key read or written in runtime code must be removed on uninstall.
const runtimeFiles = [];
const walk = (dir) => {
  for (const entry of fs.readdirSync(path.join(root, dir), { withFileTypes: true })) {
    const relative = path.posix.join(dir, entry.name);
    if (entry.isDirectory()) walk(relative);
    else if (/\.php$/.test(entry.name)) runtimeFiles.push(relative);
  }
};
["includes", "admin"].forEach(walk);
const termOnlyKeys = new Set(["_vonseowp_canonical"]);
const postMetaKeys = new Set();
for (const file of runtimeFiles) {
  for (const match of read(file).matchAll(/'(_vonseowp_[a-z_]+)'/g)) postMetaKeys.add(match[1]);
}
const uninstallPostKeys = new Set(
  [...(uninstall.match(/\$vonseowp_meta_keys\s*=\s*array\(([\s\S]*?)\);/) || ["", ""])[1].matchAll(/'(_vonseowp_[a-z_]+)'/g)].map((m) => m[1]),
);
for (const key of postMetaKeys) {
  if (termOnlyKeys.has(key)) continue;
  assert(uninstallPostKeys.has(key), `Uninstall must delete post meta key ${key}.`);
}
for (const key of ["_vonseowp_title", "_vonseowp_description", "_vonseowp_canonical", "_vonseowp_social_desc", "_vonseowp_noindex"]) {
  assert(uninstall.includes(`'${key}'`), `Uninstall must delete term meta key ${key}.`);
}
assert(
  /_transient_vonseowp_comp_/.test(uninstall) && /_transient_timeout_vonseowp_comp_/.test(uninstall),
  "Uninstall must remove cached Competitor scan transients.",
);

const metaBox = read("includes/class-vonseowp-meta-box.php");
assert(
  /esc_url_raw\([^;]*\$_POST\['vonseowp_image'\][^;]*array\('http', 'https'\)\)/.test(metaBox),
  "The social image must be stored with esc_url_raw() restricted to http/https.",
);

const llm = read("includes/class-vonseowp-llm.php");
assert(
  !/wp_kses_post\s*\(/.test(llm) && /text\/plain/.test(llm) && /nosniff/.test(llm),
  "llms.txt must stay text/plain with nosniff and must not HTML-encode Markdown via wp_kses_post().",
);

console.log("Security audit regression tests passed");
