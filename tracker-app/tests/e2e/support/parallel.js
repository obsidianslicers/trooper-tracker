// Runs the browser tests as several Playwright runs side by side. Specs share one database, so a
// single run goes one test at a time; here each shard gets its own app server, database and saved
// sign-ins (E2E_SHARD, see env.js), and the suite takes a fraction of the time.
//
//   npm run test:e2e:parallel                       # one shard per 2 CPU cores, at most 6
//   npm run test:e2e:parallel -- --shards=3         # pick the number
//   npm run test:e2e:parallel -- --shards=3 events  # anything else goes to `playwright test`
//
// Each test still runs once: Playwright's --shard splits the tests and --fully-parallel splits
// them one by one rather than by file (pages.spec.js alone is a third of the suite). Every
// shard signs the personas in for itself. Output: test-results/shard-<n>.log and
// test-results/shard-<n>/ (traces and screenshots of failures).
import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { createWriteStream, mkdirSync, readFileSync } from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { APP_ROOT } from './env.js';

const args = process.argv.slice(2);
const shardsArg = args.find((arg) => arg.startsWith('--shards='));
const passThrough = args.filter((arg) => arg !== shardsArg);
const cores = os.availableParallelism ? os.availableParallelism() : os.cpus().length;
const shards = shardsArg
    ? Number(shardsArg.split('=')[1])
    : Math.max(1, Math.min(6, Math.floor(cores / 2)));

if (!Number.isInteger(shards) || shards < 1) {
    console.error(`--shards must be a whole number of at least 1, not "${shardsArg}"`);
    process.exit(2);
}

const cli = createRequire(import.meta.url).resolve('@playwright/test/cli');
const results = path.join(APP_ROOT, 'test-results');
mkdirSync(results, { recursive: true });

console.log(`Running the browser tests in ${shards} shards; logs in test-results/shard-<n>.log`);
const started = Date.now();
const children = [];

const runs = Array.from({ length: shards }, (_, index) => {
    const shard = index + 1;
    const log = path.join(results, `shard-${shard}.log`);
    const child = spawn(
        process.execPath,
        [
            cli,
            'test',
            '--fully-parallel',
            `--shard=${shard}/${shards}`,
            `--output=${path.join(results, `shard-${shard}`)}`,
            '--reporter=line',
            ...passThrough,
        ],
        { cwd: APP_ROOT, env: { ...process.env, E2E_SHARD: String(shard) } },
    );
    const out = createWriteStream(log);
    child.stdout.pipe(out);
    child.stderr.pipe(out);
    children.push(child);

    return new Promise((resolve) => {
        child.on('close', (code) => out.end(() => resolve({ shard, code: code ?? 1, log })));
    });
});

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => children.forEach((child) => child.kill(signal)));
}

let failed = false;

for (const { shard, code, log } of await Promise.all(runs)) {
    const text = readFileSync(log, 'utf8');
    const totals = text.match(/^ +\d+ (passed|failed|flaky|skipped|did not run).*$/gm) ?? [];
    console.log(`shard ${shard}/${shards}: ${code === 0 ? 'ok' : `exit ${code}`}  ${totals.map((line) => line.trim()).join(', ')}`);

    if (code !== 0) {
        failed = true;
        const failures = text.indexOf('  1) ');
        console.log(failures >= 0 ? text.slice(failures) : text.split('\n').slice(-30).join('\n'));
    }
}

console.log(`${failed ? 'Failed' : 'Passed'} in ${Math.round((Date.now() - started) / 1000)}s`);
process.exit(failed ? 1 : 0);
