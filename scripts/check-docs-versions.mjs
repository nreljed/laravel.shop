#!/usr/bin/env node
/**
 * C3 — Docs age check.
 *
 * Fails if any version claimed in docs/integration/*.md tables is stale
 * against the installed versions in package-lock.json.
 *
 * Run: node scripts/check-docs-versions.mjs
 * CI:  added as a step in .github/workflows/ci.yml
 */
import { readFileSync, readdirSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");

// ── installed versions from package-lock.json ──────────────────────────
const lock = JSON.parse(readFileSync(join(root, "package-lock.json"), "utf8"));
const installed = {};
for (const [key, val] of Object.entries(lock.packages ?? {})) {
  if (key.startsWith("node_modules/") && !key.slice(13).includes("node_modules/")) {
    installed[key.slice(13)] = val.version;
  }
}

// ── extract version claims from docs tables ────────────────────────────
// Matches rows like:  | `react` | 19.2.7 | ...   or  | `react` | ^19.2.7 (installed 19.3.1) |
const DOC_DIR = join(root, "docs", "integration");
const files = readdirSync(DOC_DIR).filter((f) => f.endsWith(".md"));

const claims = []; // { file, pkg, claimed, line }
for (const file of files) {
  const lines = readFileSync(join(DOC_DIR, file), "utf8").split("\n");
  lines.forEach((line, i) => {
    // first cell must be a backtick-wrapped package name
    const m = line.match(/^\|\s*`([^`]+)`\s*\|/);
    if (!m) return;
    const pkg = m[1].trim();
    if (!installed[pkg]) return; // not an npm dependency we track

    // extract the first semver-looking token from the remaining cells
    const rest = line.slice(line.indexOf("|", line.indexOf("`" + pkg) + pkg.length + 2));
    const verMatch = rest.match(/(\d+\.\d+\.\d+)/);
    if (!verMatch) return;

    // prefer "(installed X.Y.Z)" if present
    const installedMatch = rest.match(/\(installed\s+(\d+\.\d+\.\d+)\)/);
    const claimed = installedMatch ? installedMatch[1] : verMatch[1];

    claims.push({ file, pkg, claimed, line: i + 1 });
  });
}

// ── compare ────────────────────────────────────────────────────────────
const stale = claims.filter((c) => installed[c.pkg] !== c.claimed);

if (stale.length === 0) {
  console.log(`✓ docs/integration version tables are current (${claims.length} claims checked)`);
  process.exit(0);
}

console.error(`✗ ${stale.length} stale version claim(s) in docs/integration/:\n`);
for (const s of stale) {
  console.error(`  ${s.file}:${s.line}  ${s.pkg}`);
  console.error(`    docs claim:  ${s.claimed}`);
  console.error(`    installed:   ${installed[s.pkg]}`);
  console.error("");
}
console.error("Update the docs table in the same PR that bumps the dependency (C3 habit).");
process.exit(1);
