// Runs PHP against the browser test app, for what a trooper would only get by email
// (a signed link) or for checking what a flow saved.
import { spawnSync } from 'node:child_process';
import { e2eEnv, APP_ROOT } from './env.js';

const php = process.env.PHP_BINARY || 'php';

export function tinker(code) {
    const result = spawnSync(php, ['artisan', 'tinker', '--execute', code], {
        cwd: APP_ROOT,
        env: { ...process.env, ...e2eEnv },
        encoding: 'utf8',
    });

    if (result.status !== 0) {
        throw new Error(`tinker failed: ${result.stderr || result.stdout}`);
    }

    return result.stdout.trim();
}

// The link in the "verify your email" message.
export function verificationUrl(email) {
    return tinker(`
        $trooper = App\\Models\\Trooper::where('email', '${email}')->firstOrFail();
        echo Illuminate\\Support\\Facades\\URL::temporarySignedRoute(
            'verification.verify',
            now()->addHour(),
            ['id' => $trooper->id, 'hash' => sha1($trooper->email)],
        );
    `);
}
