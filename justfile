# payFURL's platform checkout, where the e2e suite lives (e2e/api-tests/woocommerce).
platform := env("PLATFORM_DIR", justfile_directory() / "../platform")
slug := "payfurl-extension-for-woocommerce"

# Dependencies and the JS build: npm and Composer, dev tools included
build:
    npm ci --no-audit --no-fund
    npm run build
    composer install --no-interaction --no-progress

# Static checks and unit tests, as CI runs them: PHPCS, PHPStan, ESLint, the JS tests and the PHP tests (wp-env, Docker)
test: build
    vendor/bin/phpcs -q
    vendor/bin/phpstan analyse --no-progress --memory-limit=1G
    npm run lint:js
    npm run test:unit
    just test-php

# The PHP unit tests on WordPress and WooCommerce, in wp-env's tests site; args go to phpunit
test-php *args:
    @# wp-env's development site mounts the release build (just dist): the directory must exist.
    mkdir -p dist/{{slug}}
    npx wp-env start
    npx wp-env run tests-cli --env-cwd=wp-content/plugins/{{slug}} vendor/bin/phpunit {{args}}

# The plugin as released, in dist/{{slug}}: built, without dev dependencies or what .distignore lists
dist: build
    @# Synced into, not recreated: wp-env's development site has the directory mounted.
    mkdir -p dist/{{slug}}
    rsync -a --delete --exclude-from=.distignore --exclude=/vendor ./ dist/{{slug}}/
    COMPOSER_VENDOR_DIR=dist/{{slug}}/vendor composer install --no-dev --no-interaction --no-progress --optimize-autoloader
    rm -rf dist/{{slug}}/vendor/payfurl/phpsdk/tests dist/{{slug}}/vendor/payfurl/phpsdk/.github

# The release zip, {{slug}}.zip, as the release workflow attaches it
zip: dist
    rm -f {{slug}}.zip
    cd dist && zip -qr ../{{slug}}.zip {{slug}}

# WordPress.org's Plugin Check on the release build (wp-env's development site); fails on any error
plugin-check: dist
    npx wp-env start
    npx wp-env run cli wp plugin activate {{slug}}
    npx wp-env run cli wp plugin install plugin-check --activate
    npx wp-env run cli wp plugin check {{slug}} --format=strict-table --include-low-severity-errors > dist/plugin-check.txt || true
    @cat dist/plugin-check.txt
    @! grep -q "ERROR" dist/plugin-check.txt

# The browser e2e tests against this checkout, from the platform checkout in Docker; args go to pytest
e2e *args: build
    cd {{platform}} && WOOCOMMERCE_PLUGIN_DIR={{justfile_directory()}} just e2e woocommerce {{args}}
