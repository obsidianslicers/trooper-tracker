// Starts the app for the Playwright browser tests: rebuilds a throwaway database (SQLite, or
// MySQL when E2E_DB_CONNECTION=mysql) from the migrations and the E2E seeder, then serves
// the app with PHP's built-in server.
// Playwright runs this as its webServer (see playwright.config.js).
import { spawn, spawnSync } from 'node:child_process';
import { existsSync, rmSync, writeFileSync } from 'node:fs';
import { e2eEnv, APP_ROOT, DATABASE, USES_MYSQL } from './env.js';

const env = { ...process.env, ...e2eEnv };
const php = process.env.PHP_BINARY || 'php';

if (!existsSync(`${APP_ROOT}/public/build/manifest.json`) && !existsSync(`${APP_ROOT}/public/hot`)) {
    console.error('No front-end assets found. Run "npm run build" (or "npm run dev") first.');
    process.exit(1);
}

if (!USES_MYSQL) {
    rmSync(DATABASE, { force: true });
    writeFileSync(DATABASE, '');
}

const seed = spawnSync(
    php,
    ['artisan', 'migrate:fresh', '--seed', '--seeder=Database\\Seeders\\E2eSeeder', '--force'],
    { cwd: APP_ROOT, env, stdio: 'inherit' },
);

if (seed.status !== 0) {
    process.exit(seed.status ?? 1);
}

const server = spawn(
    php,
    [
        // -q drops the per-request access log so failures stay readable.
        '-q',
        '-S',
        `${e2eEnv.E2E_HOST}:${e2eEnv.E2E_PORT}`,
        `${APP_ROOT}/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`,
    ],
    // Laravel's router script serves files from the working directory, so start in public/.
    { cwd: `${APP_ROOT}/public`, env, stdio: 'inherit' },
);

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => server.kill(signal));
}

server.on('exit', (code) => process.exit(code ?? 0));
