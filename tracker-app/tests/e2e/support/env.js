// Environment for the app under browser test. These values win over anything in .env,
// so a developer's local settings never leak into (or get touched by) the E2E run.
import { fileURLToPath } from 'node:url';
import path from 'node:path';

export const APP_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
export const DATABASE = path.join(APP_ROOT, 'database', 'e2e.sqlite');

const host = '127.0.0.1';
const port = process.env.E2E_PORT || '8077';

export const BASE_URL = `http://${host}:${port}`;

// SQLite by default. Set E2E_DB_CONNECTION=mysql (CI does) to run on MySQL like production;
// some reports use MySQL-only SQL and only work there.
export const USES_MYSQL = process.env.E2E_DB_CONNECTION === 'mysql';

const database = USES_MYSQL
    ? {
        DB_CONNECTION: 'mysql',
        DB_HOST: process.env.E2E_DB_HOST || '127.0.0.1',
        DB_PORT: process.env.E2E_DB_PORT || '3306',
        DB_DATABASE: process.env.E2E_DB_DATABASE || 'trooper_e2e',
        DB_USERNAME: process.env.E2E_DB_USERNAME || 'e2e',
        DB_PASSWORD: process.env.E2E_DB_PASSWORD || 'e2e',
    }
    : { DB_CONNECTION: 'sqlite', DB_DATABASE: DATABASE };

export const e2eEnv = {
    E2E_HOST: host,
    E2E_PORT: port,
    APP_ENV: 'e2e',
    APP_DEBUG: 'true',
    // Test-only key; it protects nothing but the throwaway E2E database.
    APP_KEY: 'base64:ZTJlLW9ubHkta2V5LW5vdC1mb3ItcmVhbC11c2UhISE=',
    APP_URL: BASE_URL,
    BCRYPT_ROUNDS: '4',
    BROADCAST_CONNECTION: 'null',
    CACHE_STORE: 'array',
    ...database,
    DEBUGBAR_ENABLED: 'false',
    LOG_CHANNEL: 'stderr',
    LOG_LEVEL: 'warning',
    MAIL_MAILER: 'array',
    NIGHTWATCH_ENABLED: 'false',
    PULSE_ENABLED: 'false',
    QUEUE_CONNECTION: 'sync',
    SESSION_DRIVER: 'file',
    TELESCOPE_ENABLED: 'false',
    TRACKER_REQUIRE_XENFORO: 'false',
    TRACKER_IMAGE_DRIVER: 'Intervention\\Image\\Drivers\\Gd\\Driver',
};
