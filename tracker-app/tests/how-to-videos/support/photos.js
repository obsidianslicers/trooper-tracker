// Stand-in troop photos for the upload video: soft colour gradients made with ffmpeg, so no
// real person's picture is needed.
import { spawnSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import path from 'node:path';
import { OUTPUT_DIR } from './env.js';

const PALETTES = [
    ['0x1d2b53', '0x7e2553', '0xff8c42'],
    ['0x0b3d2e', '0x2a9d8f', '0xe9c46a'],
    ['0x14213d', '0x3a86ff', '0xe5e5e5'],
];

export function makePhotos() {
    const dir = path.join(OUTPUT_DIR, 'photos');
    mkdirSync(dir, { recursive: true });

    return PALETTES.map(([c0, c1, c2], i) => {
        const file = path.join(dir, `troop-photo-${i + 1}.jpg`);
        const result = spawnSync('ffmpeg', [
            '-y', '-loglevel', 'error', '-f', 'lavfi',
            '-i', `gradients=s=1200x800:c0=${c0}:c1=${c1}:c2=${c2}:nb_colors=3:seed=${i + 7}:type=radial`,
            '-frames:v', '1', file,
        ]);
        if (result.status !== 0) {
            throw new Error(`ffmpeg could not make ${file}`);
        }
        return file;
    });
}
