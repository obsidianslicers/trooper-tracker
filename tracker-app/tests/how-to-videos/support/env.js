// Environment for the app the how-to videos are recorded against. Like the browser tests'
// (tests/e2e/support/env.js), these values win over .env, on a throwaway SQLite database.
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { e2eEnv } from '../../e2e/support/env.js';

export const APP_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

// VIDEO_SITE=2 runs a second tracker beside the first, for the HoloNet video.
const site = Number(process.env.VIDEO_SITE || 1);
// The second tracker is on localhost so the two keep separate cookies in one browser.
const host = site === 1 ? '127.0.0.1' : 'localhost';
const port = String(8089 + site);

export const DATABASE = path.join(APP_ROOT, 'database', `how-to-videos-${site}.sqlite`);
export const BASE_URL = `http://${host}:${port}`;
export const OTHER_URL = site === 1 ? 'http://localhost:8091' : 'http://127.0.0.1:8090';
export const DIRECTORY_PORT = 8099;
export const OUTPUT_DIR = path.join(APP_ROOT, 'storage', 'how-to-videos');

export const videoEnv = {
    ...e2eEnv,
    E2E_HOST: host,
    E2E_PORT: port,
    // HoloNet accepts plain http:// trackers only when local.
    APP_ENV: 'local',
    APP_DEBUG: 'false',
    APP_NAME: site === 1 ? 'Sunrise Garrison Tracker' : 'Mountain Garrison Tracker',
    APP_URL: BASE_URL,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: DATABASE,
    SESSION_DRIVER: 'file',
    FILESYSTEM_DISK: 'public',
    // Geocoding new events needs Google; a failed lookup is logged and only leaves no map pin.
    LOG_LEVEL: 'critical',
    HOW_TO_VIDEO_SITE: String(site),
    // The HoloNet video links the two trackers; both read the list of trackers from a small
    // server of its own (PHP's built-in server answers one request at a time, and a link has
    // the trackers calling each other mid-request).
    HOLONET_DIRECTORY_URL: `http://127.0.0.1:${DIRECTORY_PORT}/trackers.json`,
};

export const HOLONET_TRACKERS = {
    trackers: [
        { name: 'Sunrise Garrison Tracker', url: 'http://127.0.0.1:8090' },
        { name: 'Mountain Garrison Tracker', url: 'http://localhost:8091' },
    ],
};
