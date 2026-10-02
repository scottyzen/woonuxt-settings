# WordPress plugin update flow

![Settings for WooNuxt icon](../.wordpress-org/icon-256x256.png)

This repository produces the **Settings for WooNuxt** plugin (`settings-for-woonuxt`).
The active plugin bootstrap is `woonuxt.php`; the class-based files excluded by
`.distignore` are not part of the shipped plugin.

## How a release reaches WordPress sites

```text
Change merged to master
        |
        v
GitHub Actions CI: PHP lint, JavaScript syntax check, release ZIP build
        |
        v
Create and publish a GitHub Release for that commit
        |
        v
Deploy to WordPress.org workflow
        |
        v
WordPress.org serves the new version through normal WordPress updates
        |
        v
Site owner updates it from Dashboard > Updates / Plugins
```

The deployment workflow is [`.github/workflows/deploy-wordpress-org.yml`](../.github/workflows/deploy-wordpress-org.yml).
It runs **only** when a GitHub Release is published, then uses the WordPress.org
SVN credentials stored in the GitHub `wordpress-org` environment. A pushed commit
or Git tag by itself does not publish a plugin update.

## Releasing a new plugin version

Work from a clean branch/working tree and use a new semantic version, for example
`2.5.20`.

1. Make the code and documentation changes.
2. Update the version in all three required locations:

   | File | Value to update |
   | --- | --- |
   | `woonuxt.php` | Plugin-header `Version:` |
   | `includes/constants.php` | `WOONUXT_SETTINGS_VERSION` |
   | `readme.txt` | `Stable tag:` |

   `scripts/build-release.py` checks that these three values agree and fails if
   they do not.
3. Add release notes:

   - Add the detailed entry to `CHANGELOG.md` under a dated version heading.
   - Add a short WordPress.org-facing entry under `== Changelog ==` in
     `readme.txt`.
   - Add an `== Upgrade Notice ==` entry in `readme.txt` when the update needs
     customer attention (checkout testing, a migration, configuration changes,
     and so on).
4. Run the release checks locally:

   ```bash
   find . -name '*.php' -print0 | xargs -0 -n1 php -l
   node --check assets/admin.js
   python3 scripts/build-release.py
   ```

   The ZIP will be created at
   `output/settings-for-woonuxt-<version>.zip`. It uses the required top-level
   directory name, `settings-for-woonuxt/`, and contains only runtime files.
5. Commit and push the release commit to `master`. GitHub Actions repeats the
   checks above for pushes and pull requests.
6. In GitHub, create a release from the release commit and **publish** it. Use the
   same version number as the plugin metadata. Publishing triggers the
   WordPress.org deployment workflow.
7. Confirm the workflow completed successfully, then check the WordPress.org
   listing and a staging WordPress installation for the available update.

Do not publish a release from a commit whose plugin header, constant, and
`readme.txt` stable tag disagree: the submission ZIP build will fail, and the
directory metadata may be incorrect.

## Testing before publishing

The normal CI checks lint PHP, check the admin JavaScript syntax, and build the
submission ZIP. For a release that changes settings, GraphQL, payment, or admin
behaviour, also use a disposable WordPress installation with WooNuxt dependencies
active:

```bash
wp eval-file tests/review-regression.php
```

That regression script creates customer/order fixtures and changes settings, so
never run it against production. For the admin JavaScript regression test, install
`jsdom` and `jquery` outside this repository, then run:

```bash
NODE_PATH=/path/to/node_modules node tests/admin-regression.cjs
```

For payment-related releases, test storefront checkout on staging. Stripe-related
updates should be coordinated with the frontend, and any in-progress payments
should be reconciled before deployment.

## What site owners do

Once WordPress.org has the published version, the plugin uses WordPress's native
update system. A site administrator updates it from **Dashboard > Updates** or
**Plugins**, as with any other directory plugin. Existing `woonuxt_options`
settings remain in WordPress; the plugin does not have a separate updater or a
database migration step in this repository.

After updating, verify **Settings > WooNuxt** and the Connection Health panel,
then run a staging checkout if the release affects GraphQL, WooCommerce, or
Stripe.

## Updating the plugin dependencies

These are separate from updating Settings for WooNuxt:

| Dependency | Current configured target | Update route |
| --- | --- | --- |
| WooCommerce | `10.9.4` | Can be installed/activated from Settings > WooNuxt; regular updates come through WordPress.org. |
| WPGraphQL | `2.17.0` | Can be installed/activated from Settings > WooNuxt; regular updates come through WordPress.org. |
| WPGraphQL for WooCommerce | `1.0.3` | Install/update manually from its official GitHub releases. |
| WPGraphQL Headless Login | `0.4.4` | Install/update manually from its official GitHub releases. |

The target versions and plugin identifiers live in `includes/constants.php`.
When changing a target, test compatibility with the WooNuxt frontend and update
the constants, release notes, and the plugin release together. The Settings page
can install only WooCommerce and WPGraphQL; the two GitHub-hosted dependencies
intentionally require manual installation.

## If a release needs to be corrected

Treat a published WordPress.org version as immutable for users: make a follow-up
patch release with a higher version number, repeat the checks, and publish a new
GitHub Release. Do not rely on overwriting the released ZIP or changing the same
version in place.
