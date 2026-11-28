import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';
import { makePhotos } from './support/photos.js';

howTo('12-uploading-and-tagging-photos', 'Uploading & Tagging Photos', { as: troopers.sam }, async ({ page, director: d }) => {
    const photos = makePhotos();
    await d.goto('/events/closed');
    await d.action();

    d.caption('Open the troop under Events, Completed.');
    await d.click(page.getByRole('link', { name: 'Harbor Mall Toy Drive' }).first(), { pause: 1500 });

    d.caption('Scroll to the Mission Review.');
    const upload = page.locator('label[for="upload-type-troop"]');
    await d.moveTo(upload);
    await d.pause(800);

    d.caption('Upload as Troop photo for pictures from the troop, or Instructional for maps and parking.');
    await d.hover(page.locator('label[for="upload-type-instructional"]'), { pause: 1200 });
    await d.click(upload);

    d.caption('Drag your photos onto the box, or click it to choose them.');
    await d.hover(page.locator('.image-input').locator('..'), { pause: 1000 });
    await page.locator('input.image-input').setInputFiles(photos);
    await page.locator('#event-uploads img').first().waitFor();
    await d.pause(2500);

    d.caption('Press Tag me on a photo you are in. Tagged photos show on your service record.');
    await d.click(page.getByRole('button', { name: 'Tag me' }).first(), { pause: 2200 });

    d.caption('Every troop photo also appears in the Photo Gallery.');
    await d.click(page.locator('.navbar').getByRole('button', { name: /service records/i }).first());
    await d.click(page.locator('.navbar').getByRole('link', { name: 'Photo Gallery' }).first(), { pause: 3000 });
});
