// Starts the app for recording the how-to videos: rebuilds a throwaway SQLite database from
// the migrations and HowToVideoSeeder, works out achievements, then serves the app.
import { spawn, spawnSync } from 'node:child_process';
import { createServer } from 'node:http';
import { existsSync, rmSync, writeFileSync } from 'node:fs';
import { videoEnv, APP_ROOT, DATABASE, DIRECTORY_PORT, HOLONET_TRACKERS } from './env.js';

const env = { ...process.env, ...videoEnv };
const php = process.env.PHP_BINARY || 'php';

function artisan(...args) {
    const result = spawnSync(php, ['artisan', ...args], { cwd: APP_ROOT, env, stdio: 'inherit' });

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}

rmSync(DATABASE, { force: true });
writeFileSync(DATABASE, '');

artisan('migrate:fresh', '--seed', '--seeder=Database\\Seeders\\HowToVideoSeeder', '--force');
artisan('tracker:calculate-trooper-achievements', '--without-notifications');

artisan('plugins:enable', 'holonet');

// Uploaded photos are served through public/storage.
if (!existsSync(`${APP_ROOT}/public/storage`)) {
    artisan('storage:link');
}

if (videoEnv.HOW_TO_VIDEO_SITE === '1') {
    createServer((request, response) => {
        response.writeHead(200, { 'Content-Type': 'application/json' });
        response.end(JSON.stringify(HOLONET_TRACKERS));
    }).listen(DIRECTORY_PORT, '127.0.0.1');
}

const server = spawn(
    php,
    ['-q', '-S', `${videoEnv.E2E_HOST}:${videoEnv.E2E_PORT}`,
        `${APP_ROOT}/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`],
    { cwd: `${APP_ROOT}/public`, env, stdio: 'inherit' },
);

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => server.kill(signal));
}

server.on('exit', (code) => process.exit(code ?? 0));
