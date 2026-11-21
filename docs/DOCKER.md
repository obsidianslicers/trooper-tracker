# Docker

An optional way to run Troop Tracker without installing PHP, Composer, Node or MySQL. The files
are `tracker-app/Dockerfile`, `tracker-app/compose.yaml` and `tracker-app/docker/`. Run the commands
below from `tracker-app/`. They need Docker with Compose v2 (Docker Desktop, or Docker Engine on
Linux). The versions match CI: PHP 8.5, Node 22, MySQL 8.0.

## Develop

```bash
docker compose up -d                       # app, queue worker, Vite and MySQL
docker compose exec app php artisan migrate                            # first time:
docker compose exec app php artisan db:seed --class=OrganizationSeeder # the database and
docker compose exec app php artisan db:seed --class=ActorSeeder        # an admin account
docker compose logs -f app                 # server, queue and Vite output
```

The app is on http://localhost:8000 and Vite on :5173. Edits on your machine show up right away,
because the `app` container mounts `tracker-app/`. `vendor` and `node_modules` live in Docker
volumes instead, so they don't clash with anything installed on your machine. On start the
container runs `composer install`, and `npm ci` when `package-lock.json` has changed. It also
creates `.env` from `.env.example` if there is none. The first start takes about a minute and a
half (building the image, then the packages); after that, about 15 seconds.

- **Database.** MySQL takes its database name and root password from `DB_DATABASE` and
  `DB_PASSWORD` in `.env`, so the defaults in `.env.example` work as they are. The container sets
  `DB_HOST=mysql`, so `.env` can keep the value you use without Docker. From your machine, MySQL
  is on port 3306.
- **Ports.** If 8000, 5173 or 3306 are taken, set `APP_PORT`, `VITE_PORT` or `FORWARD_DB_PORT` in
  `.env`.
- **Commands.** Prefix them with `docker compose exec app`: `php artisan tinker`, `composer require
  ...`, `npm run build`. `php artisan test` works here too, and still uses in-memory SQLite: the
  container sets only `DB_HOST` and `DB_PORT`, so `phpunit.xml` keeps its database settings.
- **Windows.** Keep the checkout in the WSL file system (`\\wsl$\...`) rather than on `C:`.
  Every request reads the code through the mount, and from `C:` that is many times slower. Vite
  also can't see edits made on `C:`.

## Test

```bash
docker compose run --rm --build test                                  # whole PHPUnit suite, in parallel
docker compose run --rm --build test php artisan test --filter=EventTest
docker compose run --rm --build test composer pint:test
docker compose run --rm --build test npm test                         # Vitest
docker compose run --rm --build e2e                                   # whole Playwright suite, in parallel
docker compose run --rm --build e2e npx playwright test approvals     # one spec
```

The `test` and `e2e` images hold a copy of `tracker-app/` as it was when the run started,
uncommitted changes included (`.dockerignore` lists what's left out). Packages are installed in an
earlier layer that is rebuilt only when `composer.lock` or `package-lock.json` changes, so a
rebuild usually takes seconds. These runs don't need the dev stack, and don't touch its database
or `public/hot`. You can keep working, or run them from several worktrees at once: give each
worktree its own name with `docker compose -p <name> run ...`.

Testing a copy is what makes this fast on Docker Desktop. On macOS and Windows, a container reads
mounted files through a slow file-sharing layer, and every test boots the app from those files.
On a 16-core Windows machine with Docker Desktop:

| Run | Time |
|---|---|
| First build of the `test` image | ~1.5 min |
| `docker compose run --rm --build test` after editing a file (rebuild + whole suite) | ~13 s |
| Whole PHPUnit suite, reading the code through a bind mount instead | ~15 min |
| First build of the `e2e` image (adds Chromium) | ~1.5 min |
| `docker compose run --rm e2e`, whole browser suite in 6 shards | ~2 min |

To keep the traces and screenshots of failed browser tests, mount the results folder:
`docker compose run --rm -v ./test-results:/app/test-results e2e`.

Old images pile up as you rebuild; `docker image prune` clears them.

The images use Debian trixie because the app's SQLite queries use functions such as `CONCAT()`,
which need SQLite 3.44 or newer. On bookworm (3.40), hundreds of tests fail.
