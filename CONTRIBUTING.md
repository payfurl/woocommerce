# Contributing

Thanks for helping with the PayFURL Extension for WooCommerce. Bug reports and pull requests are welcome on
[GitHub](https://github.com/payfurl/woocommerce). For a problem with a live store or your payFURL account, contact
payFURL support instead, with the plugin's log (turn **Debug** on, then **WooCommerce > Status > Logs**, source
`payfurl`).

## Running the checks

You need PHP 8.1 or later, [Composer](https://getcomposer.org/), Node.js 24, Docker and [just](https://just.systems/).

```bash
just build   # npm and Composer: build/ (the checkout's JS) and vendor/
just test    # PHPCS, PHPStan, ESLint, the JS unit tests, and the PHP unit tests
```

The PHP unit tests (`tests/php`) run on WordPress and WooCommerce in Docker through
[wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (`.wp-env.json`); the JS
tests (`src/js/test`) run with Vitest. `just plugin-check` runs WordPress.org's
[Plugin Check](https://wordpress.org/plugins/plugin-check/) on the release build, and `just zip` makes the release
zip.

GitHub Actions run the static checks, the JS tests, the PHP tests on PHP 8.1 to 8.4, and Plugin Check on every pull
request (`.github/workflows/ci.yml`).

## Pull requests

* Keep the checks passing, and add a unit test for a change in behaviour.
* Add a line under `## [Unreleased]` in `CHANGELOG.md` saying what changes for merchants.
* payFURL runs browser end-to-end tests (a WooCommerce store against payFURL's own stack) before each release, so
  they don't run on pull requests.

## payFURL staff

The end-to-end tests, a local WooCommerce store with this checkout mounted (`just woocommerce up`), and the release
and browser SDK update steps are in payFURL's platform repository: `docs/integrations/woocommerce.md` and
`e2e/api-tests/woocommerce/README.md`. With that checkout beside this one (or `PLATFORM_DIR` set), `just e2e` runs the
end-to-end tests against this checkout.
