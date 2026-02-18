const { readdirSync, readFileSync, statSync } = require('fs');
const { join } = require('path');

function walk(dir) {
  const files = [];
  for (const name of readdirSync(dir)) {
    const p = join(dir, name);
    if (statSync(p).isDirectory()) {
      files.push(...walk(p));
    } else {
      files.push(p);
    }
  }
  return files;
}

const repoRoot = join(__dirname, '..');
const apiDir = join(repoRoot, 'webapp', 'backend', 'api');
if (!statSync(apiDir).isDirectory()) {
  console.error('ERR: api folder not found:', apiDir);
  process.exit(2);
}

const phpFiles = walk(apiDir).filter(f => f.endsWith('.php'));
const missing = [];
for (const f of phpFiles) {
  const txt = readFileSync(f, 'utf8');
  if (!txt.includes('cors.php')) missing.push(f.replace(process.cwd() + '/', ''));
}
if (missing.length) {
  console.error('Missing cors.php in:');
  missing.forEach(m => console.error('  ' + m));
  process.exit(1);
}
console.log('OK: all API PHP files include cors.php');
process.exit(0);
