import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const allowedPrefixes = [
  'wp-content/themes/papetarie-storefront/',
  'wp-content/themes/papetarie-store/',
  'tools/',
];

const allowedExtensions = new Set(['.php', '.js', '.json', '.po', '.pot', '.html', '.htm']);
const mojibakePattern = /[ÃÄÈÅ]|â†’|â€”|â€“|â€¦|�/;

const files = execFileSync(
  'git',
  ['ls-files', '--', 'wp-content/themes/papetarie-storefront', 'wp-content/themes/papetarie-store', 'tools'],
  { encoding: 'utf8' }
)
  .split('\n')
  .filter(Boolean)
  .filter((file) => allowedPrefixes.some((prefix) => file.startsWith(prefix)))
  .filter((file) => !file.includes('/data/'))
  .filter((file) => allowedExtensions.has(file.slice(file.lastIndexOf('.'))));

const failures = [];

for (const file of files) {
  const content = readFileSync(file, 'utf8');

  const lines = content.split(/\r?\n/);
  lines.forEach((line, index) => {
    if (mojibakePattern.test(line)) {
      failures.push(`${file}:${index + 1}: ${line}`);
    }
  });
}

if (failures.length > 0) {
  console.error('Romanian encoding check failed:');
  for (const failure of failures) {
    console.error(failure);
  }
  process.exit(1);
}

console.log(`Romanian encoding check passed for ${files.length} files.`);
