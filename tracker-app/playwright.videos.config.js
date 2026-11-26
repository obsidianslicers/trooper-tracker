import { defineConfig } from '@playwright/test';
import { BASE_URL, OTHER_URL } from './tests/how-to-videos/support/env.js';

// Records the FAQ's how-to videos (npm run videos). Each spec writes one MP4 and its captions
// to storage/how-to-videos; see tests/how-to-videos/README.md.
export default defineConfig({
    testDir: './tests/how-to-videos',
    fullyParallel: false,
    workers: 1,
    reporter: 'list',
    use: { baseURL: BASE_URL },
    // A second tracker beside the first, for the HoloNet video.
    webServer: [1, 2].map((site) => ({
        command: `VIDEO_SITE=${site} node tests/how-to-videos/support/server.js`,
        url: `${site === 1 ? BASE_URL : OTHER_URL}/login`,
        reuseExistingServer: !!process.env.VIDEO_REUSE_SERVER,
        timeout: 180_000,
        stdout: 'ignore',
        stderr: 'ignore',
    })),
});
