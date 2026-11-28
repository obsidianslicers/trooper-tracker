// Records one how-to video per spec: a visible cursor that glides to what it clicks, typing at
// a readable pace, and step captions written to an .srt file beside the video (so YouTube can
// translate them; nothing is burned into the picture).
import { test as base, chromium } from '@playwright/test';
import { spawnSync } from 'node:child_process';
import { mkdirSync, writeFileSync, rmSync } from 'node:fs';
import path from 'node:path';
import { BASE_URL, OUTPUT_DIR } from './env.js';

export const PASSWORD = 'password';
const SIZE = { width: 1280, height: 720 };

const CURSOR = () => {
    const KEY = '__howto_cursor';
    const saved = JSON.parse(sessionStorage.getItem(KEY) || '{"x":640,"y":360}');
    const install = () => {
        const style = document.createElement('style');
        style.textContent = `
            #howto-cursor { position: fixed; z-index: 2147483647; pointer-events: none;
                width: 22px; height: 22px; margin: -3px 0 0 -3px;
                background: no-repeat url("data:image/svg+xml,${encodeURIComponent(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">'
                    + '<path d="M3 2l14 9-6 1.5L8 19z" fill="#fff" stroke="#000" stroke-width="1.5"'
                    + ' stroke-linejoin="round"/></svg>')}"); }
            .howto-ripple { position: fixed; z-index: 2147483646; pointer-events: none;
                width: 36px; height: 36px; margin: -18px 0 0 -18px; border-radius: 50%;
                border: 3px solid #ffc107; animation: howto-ripple .6s ease-out forwards; }
            @keyframes howto-ripple { from { transform: scale(.3); opacity: 1 }
                to { transform: scale(1.4); opacity: 0 } }`;
        document.head.appendChild(style);
        const cursor = document.createElement('div');
        cursor.id = 'howto-cursor';
        cursor.style.left = `${saved.x}px`;
        cursor.style.top = `${saved.y}px`;
        document.body.appendChild(cursor);
        document.addEventListener('mousemove', (e) => {
            cursor.style.left = `${e.clientX}px`;
            cursor.style.top = `${e.clientY}px`;
            sessionStorage.setItem(KEY, JSON.stringify({ x: e.clientX, y: e.clientY }));
        }, true);
        document.addEventListener('mousedown', (e) => {
            const ripple = document.createElement('div');
            ripple.className = 'howto-ripple';
            ripple.style.left = `${e.clientX}px`;
            ripple.style.top = `${e.clientY}px`;
            document.body.appendChild(ripple);
            setTimeout(() => ripple.remove(), 700);
        }, true);
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', install);
    } else {
        install();
    }
};

function srtTime(ms) {
    const pad = (n, w = 2) => String(Math.floor(n)).padStart(w, '0');
    return `${pad(ms / 3600000)}:${pad((ms / 60000) % 60)}:${pad((ms / 1000) % 60)},${pad(ms % 1000, 3)}`;
}

class Director {
    constructor(page, slug) {
        this.page = page;
        this.slug = slug;
        this.captions = [];
        this.mouse = { x: SIZE.width / 2, y: SIZE.height / 2 };
        this.created = Date.now();
        this.startedAt = null;
        this.finishedAt = null;
    }

    /** Marks where the finished video begins: everything before it is cut. */
    async action() {
        await this.page.waitForLoadState('networkidle').catch(() => {});
        await this.page.waitForTimeout(600);
        this.startedAt = Date.now();
    }

    elapsed() {
        return Date.now() - (this.startedAt ?? this.created);
    }

    /** Shows a step caption from now until the next one (or the end). */
    caption(text) {
        const now = this.elapsed();
        const last = this.captions.at(-1);
        if (last && last.end === null) {
            last.end = now;
        }
        this.captions.push({ start: now, end: null, text });
    }

    async pause(ms = 1200) {
        await this.page.waitForTimeout(ms);
    }

    async moveTo(locator) {
        await locator.scrollIntoViewIfNeeded();
        await this.page.waitForTimeout(250);
        const box = await locator.boundingBox();
        if (!box) {
            throw new Error(`Nothing to point at for ${locator}`);
        }
        const x = box.x + Math.min(box.width / 2, 60);
        const y = box.y + box.height / 2;
        const distance = Math.hypot(x - this.mouse.x, y - this.mouse.y);
        await this.page.mouse.move(x, y, { steps: Math.max(8, Math.round(distance / 18)) });
        this.mouse = { x, y };
        await this.page.waitForTimeout(250);
    }

    async click(locator, { pause = 900 } = {}) {
        await this.moveTo(locator);
        await locator.click();
        await this.page.waitForTimeout(pause);
    }

    async type(locator, text, { pause = 500 } = {}) {
        await this.moveTo(locator);
        await locator.click();
        await locator.fill('');
        await locator.pressSequentially(text, { delay: 55 });
        await this.page.waitForTimeout(pause);
    }

    async select(locator, option, { pause = 700 } = {}) {
        await this.moveTo(locator);
        await locator.selectOption(option);
        await this.page.waitForTimeout(pause);
    }

    async hover(locator, { pause = 900 } = {}) {
        await this.moveTo(locator);
        await this.page.waitForTimeout(pause);
    }

    /** Scrolls the page smoothly by `by` pixels. */
    async scroll(by, { pause = 900 } = {}) {
        const steps = Math.max(10, Math.round(Math.abs(by) / 25));
        for (let i = 0; i < steps; i++) {
            await this.page.mouse.wheel(0, by / steps);
            await this.page.waitForTimeout(20);
        }
        await this.page.waitForTimeout(pause);
    }

    async goto(url) {
        await this.page.goto(url);
        await this.page.waitForLoadState('networkidle').catch(() => {});
        await this.page.waitForTimeout(500);
    }

    srt() {
        const end = this.finishedAt;
        return this.captions
            .map((c, i) => `${i + 1}\n${srtTime(c.start)} --> ${srtTime(c.end ?? end)}\n${c.text}\n`)
            .join('\n');
    }
}

// Where the CDN can't be reached (a locked-down container), VIDEO_CDN_DIR serves cdnjs files
// from npm copies: <dir>/bootswatch-5.2.3/package and <dir>/fortawesome-fontawesome-free-6.2.0/package.
async function serveCdnLocally(context) {
    const dir = process.env.VIDEO_CDN_DIR;
    if (!dir) {
        return;
    }
    await context.route('https://cdnjs.cloudflare.com/**', (route) => {
        const url = new URL(route.request().url()).pathname;
        const file = url
            .replace('/ajax/libs/bootswatch/5.2.3/', `${dir}/bootswatch-5.2.3/package/dist/`)
            .replace('/ajax/libs/font-awesome/6.2.0/', `${dir}/fortawesome-fontawesome-free-6.2.0/package/`);
        return file.startsWith(dir) ? route.fulfill({ path: file }) : route.abort();
    });
}

export async function launch() {
    const proxy = process.env.HTTPS_PROXY
        ? { server: process.env.HTTPS_PROXY, bypass: '127.0.0.1,localhost' }
        : undefined;
    return chromium.launch({ proxy });
}

async function signIn(browser, email) {
    const context = await browser.newContext({ baseURL: BASE_URL, ignoreHTTPSErrors: true });
    const page = await context.newPage();
    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(PASSWORD);
    await page.getByRole('button', { name: 'Login' }).click();
    await page.waitForURL((url) => !url.pathname.startsWith('/login'));
    const state = await context.storageState();
    await context.close();
    return state;
}

/**
 * Club roster lookups ask the club's real member service, which knows nothing of the demo
 * troopers. This answers them as if the trooper was found in good standing.
 */
export async function fakeRosterLookup(page, names) {
    await page.route('**/lookup-membership', async (route) => {
        const response = await route.fetch();
        const lookup = await response.json();
        if (lookup.identifier) {
            lookup.existing_trooper_membership = null;
            lookup.member = {
                full_name: names[lookup.identifier] ?? '',
                formatted_identifier: `TK-${lookup.identifier}`,
                is_approved: true,
                status: 'Active',
                standing: 'Good',
                unit_name: 'Sunrise Garrison',
                profile_url: null,
                thumbnail_url: null,
            };
        }
        await route.fulfill({ response, json: lookup });
    });
}

/**
 * `howTo(slug, title, { as }, body)` records `body` into storage/how-to-videos/<slug>.mp4 and
 * <slug>.srt, signed in as the trooper whose email is `as` (or signed out).
 */
export function howTo(slug, title, options, body) {
    base(title, async ({}, testInfo) => {
        testInfo.setTimeout(240_000);
        const browser = await launch();
        const storageState = options.as ? await signIn(browser, options.as) : undefined;
        const raw = path.join(OUTPUT_DIR, 'raw', slug);
        rmSync(raw, { recursive: true, force: true });
        const context = await browser.newContext({
            baseURL: BASE_URL,
            storageState,
            viewport: SIZE,
            deviceScaleFactor: 1,
            recordVideo: { dir: raw, size: SIZE },
            colorScheme: 'dark',
            ignoreHTTPSErrors: true,
        });
        await serveCdnLocally(context);
        await context.addInitScript(CURSOR);
        const page = await context.newPage();
        page.setDefaultTimeout(15_000);
        const director = new Director(page, slug);

        try {
            await body({ page, director, options });
            await director.pause(1500);
            director.finishedAt = director.elapsed();
        } catch (error) {
            mkdirSync(OUTPUT_DIR, { recursive: true });
            await page.screenshot({ path: path.join(OUTPUT_DIR, `failed-${slug}.png`) }).catch(() => {});
            throw error;
        } finally {
            await context.close();
            await browser.close();
        }

        const offset = (director.startedAt ?? director.created) - director.created;
        mkdirSync(OUTPUT_DIR, { recursive: true });
        const output = path.join(OUTPUT_DIR, `${slug}.mp4`);
        const ffmpeg = spawnSync('ffmpeg', [
            '-y', '-loglevel', 'error', '-ss', (offset / 1000).toFixed(2), '-i', await page.video().path(),
            '-c:v', 'libx264', '-preset', 'slow', '-crf', '23', '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart', '-an', output,
        ], { stdio: 'inherit' });
        if (ffmpeg.status !== 0) {
            throw new Error(`ffmpeg failed for ${slug}`);
        }
        writeFileSync(path.join(OUTPUT_DIR, `${slug}.srt`), director.srt());
    });
}
