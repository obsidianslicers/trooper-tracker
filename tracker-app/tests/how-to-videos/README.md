# How-to videos

The FAQ's **How-To Videos** are recorded from the real app by these scripts, so they can be
recorded again whenever the screens change. Each `*.spec.js` is one video: Playwright drives
the app as a trooper would, with a visible cursor, and writes `<name>.mp4` (1280×720, silent)
and `<name>.srt` (the step captions) to `storage/how-to-videos/`.

```bash
npm run build                 # the videos use the built assets
npm run videos                # every video, about 10 minutes
npm run videos -- 04          # only the videos whose file name matches
```

It needs `ffmpeg` on the path. Like the browser tests, it starts its own app on a throwaway
SQLite database, seeded by `Database\Seeders\HowToVideoSeeder` (a made-up garrison; every
trooper's password is `password`, see `support/cast.js`). The HoloNet video links two trackers,
so a second copy runs on `http://localhost:8091` with a garrison of its own.

The captions are not burned into the picture. Upload the `.srt` with the video on YouTube, which
can then show them in the viewer's language, and paste the new links into the FAQ (Command
Staff > FAQ), or ship them in a migration like
`2026_11_26_000001_update_faq_videos.php`.

- `support/director.js`: the cursor, typing pace, captions and the MP4 conversion.
- `support/server.js`: seeds and serves the two trackers.
- `support/photos.js`: stand-in photos for the upload video.
- `VIDEO_CDN_DIR`: only for machines that can't reach cdnjs; serves Bootstrap and Font Awesome
  from npm copies instead (see `support/director.js`).
