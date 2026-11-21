# syntax=docker/dockerfile:1
# Development and test images for Troop Tracker; compose.yaml uses them. See docs/DOCKER.md.
#
#   dev   PHP, Composer and Node. The code is bind-mounted, so edits show up right away.
#   test  The code copied into the image, with packages installed from the lockfiles.
#         Reading code through a bind mount is what makes tests slow on Docker Desktop.
#   e2e   test + Playwright's Chromium and a built front end.
#
# Same versions as CI (.github/workflows/tests.yml): PHP 8.5, Node 22. Debian trixie, because
# the app uses SQLite functions such as CONCAT() that need SQLite 3.44+ (bookworm has 3.40).

FROM node:22-trixie-slim AS node

FROM php:8.5-cli-trixie AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl pcntl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-tracker.ini
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

# ---- dev: compose.yaml mounts the code over /app ----
FROM base AS dev
COPY docker/dev-entrypoint.sh /usr/local/bin/tracker-dev
RUN chmod +x /usr/local/bin/tracker-dev
EXPOSE 8000 5173
CMD ["tracker-dev"]

# ---- packages, cached until a lockfile changes ----
FROM base AS deps
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.cache/composer \
    composer install --no-interaction --no-progress --no-scripts --no-autoloader
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund

# ---- test: PHPUnit, Pint, Vitest, svelte-check on the copied code ----
FROM deps AS test
# Test-only key (CI generates one); it protects nothing but throwaway test data.
ENV APP_KEY=base64:ZG9ja2VyLXRlc3QtaW1hZ2Utb25seS1ub3QtcmVhbCE=
COPY . .
RUN composer dump-autoload --optimize --no-scripts && php artisan package:discover
CMD ["php", "artisan", "test", "--parallel"]

# ---- e2e: Playwright against the built front end ----
FROM deps AS e2e
RUN npx playwright install --with-deps chromium && rm -rf /var/lib/apt/lists/*
COPY . .
RUN composer dump-autoload --optimize --no-scripts && php artisan package:discover && npm run build
CMD ["npm", "run", "test:e2e:parallel"]
