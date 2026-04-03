# Release flow guidelines

These guidelines are meant to keep releases consistent and prevent regressions from slipping through.

## Goals

- Ship repeatable, verifiable releases.
- Keep version metadata in sync.
- Validate GraphQL schema changes before tagging.

## Preflight

- Ensure your branch is up to date with the target branch.
- Confirm the working tree is clean.
- Identify the release version (use SemVer).
- Review any recent GraphQL changes and note any new fields.

## Version and metadata updates

Update all version references for the new release.

- includes/constants.php (WOONUXT_SETTINGS_VERSION)
- woonuxt.php (plugin header Version)
- plugin.json (version, download_url, upgrade_notice, sections.changelog)
- CHANGELOG.md (add release entry)

## Release sanity checks

Run these before tagging a release.

- Activate the plugin in a test site.
- Open the WooNuxt settings page.
- Verify GraphQL schema includes expected fields. Example:

```graphql
query {
  viewer {
    stripeCustomerId
  }
}
```

- If you added new GraphQL fields, also verify they appear in schema introspection.

## Build the release artifact

Create the zip from the repo root, excluding developer-only files.

Example:

```bash
zip -r woonuxt-settings.zip . -x "*.git*" -x "*node_modules*" -x "*.DS_Store"
```

## Tag and publish

- Merge to the release branch (usually master).
- Tag the commit (for example, v2.5.6).
- Create a GitHub release and upload the zip.
- Ensure plugin.json download_url points to the new release asset.

## Post-release verification

- Check the update is visible via the plugin update checker.
- Update the plugin on a test site and re-run the GraphQL sanity query.
- Spot check WooCommerce and WPGraphQL dependency versions.

## Hotfix flow

- Create a hotfix branch from the release commit.
- Apply the fix and bump the patch version.
- Repeat the release steps and publish a new patch release.
