# Compatibility tests

[Deutsch](README-de.md)

Run only in a disposable WordPress installation with active GlotPress and GP Project Export Name. The suite creates test projects and translation sets. It restores the previous naming settings but keeps its test content. Administrator ID 1 must exist.

Install unmodified GP Download Name 0.6, GP Use Slug for Downloads 1.0 and GP Bulk Download Translations 1.2 as inactive test dependencies. These are separate GPL plugins by Greg Ross; their code is not bundled here. Sources: https://wordpress.org/plugins/gp-download-name/ , https://wordpress.org/plugins/gp-use-slug-for-downloads/ and https://wordpress.org/plugins/gp-bulk-download-translations/ .

```sh
GPE_TEST_SITE=1 wp eval-file wp-content/plugins/gp-project-export-name/tests/integration.php
```

The suite checks JSON transfer validation, signed review and concurrent edits, reset scopes, project inspection, filename variants, native export headers and unchanged contents, competing naming callbacks, external bulk exports and database query behavior. GPE_COMPAT_FIXTURES may point to a directory containing the three external add-on folders.

The repository's Compatibility workflow runs PHP syntax and integration tests on push, pull request, manual dispatch and weekly schedule. Its matrix combines WordPress 6.4 and the current stable WordPress release with PHP 8.1/8.4 and minimum/current GlotPress versions. The workflow uses a disposable MySQL 8 service, requires no repository secrets and cannot publish releases. A prepared workflow is not evidence of a successful GitHub run; consult its actual job log.

Browser accessibility and visual behavior require separate browser tests. The installable release ZIP excludes this directory and the workflow.
