=== GP Project Export Name ===
Contributors: daveshine
Tags: glotpress, translations, export
Requires at least: 6.4
Tested up to: 7.1.3
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Choose recognizable GlotPress download names, check language variants and follow a five-step guide.

== Description ==

Download GlotPress translations with names that are easier to recognize and prepare for their destination. Choose a project name, keep the exported language and distinguish informal, formal or other translation sets. The translation itself stays unchanged.

Version 2.0.0 · WordPress 6.4+ · PHP 8.1+ · GlotPress 4.0+ with a permalink structure other than Plain for export features.

* Choose recognizable project names instead of renaming each downloaded file.
* Use the exported language, including formal and other variants.
* Check every translation set’s filename before saving changes.
* Find projects without typing their full path.
* Keep otherwise identical names distinct within a project.
* Choose which recognized naming plugin is responsible.
* Back up, transfer or reset filename settings after reviewing the changes.
* Read the five-step quick guide directly on the settings page.

The updater contacts api.github.com for public release metadata and GitHub for update packages; standard HTTP metadata such as the server IP is transmitted. No telemetry or credentials are required. Metadata is cached by the updater. The optional Library online catalog defaults off; its UI explains activation and installation. Only validated release packages with matching identity, offered version and compatible requirements are accepted. Prereleases are not advertised as stable updates.

== Installation ==

Activate the plugin and open Settings → GP Project Export Name. GlotPress must be active with a permalink structure other than Plain (Settings → Permalinks). Quick guide is available on the settings page even before GlotPress is ready. Start with its five steps; the advanced fields can remain unchanged.

== Quick guide ==

1. Start with the recommended pattern
With GlotPress active, the plugin already uses the project’s last path component and the exported language. Keep Recommended: project and language unless a different structure is needed. For a project ending in stable, the name starts with stable until a project name is chosen in step 2.

2. Choose the project name, if needed
Under Find projects and choose their filenames, search by project name or path. In Project name in filenames, enter the desired name without a language code or extension, for example my-plugin. Choose Apply this name to the draft. For a WordPress plugin, use its actual text domain: the identifier that plugin uses for translations. If unsure, check that plugin’s documentation.

3. Check the filename before saving
In the project’s result row, choose Preview. All its translation sets are listed, including language variants. German normally uses de_DE; a German set named formal automatically uses de_DE_formal. The preview explains how each name is built and does not save or download anything.

4. Save the chosen rules
Choose Save Changes to apply the draft to future downloads on this website. The preview alone does not activate edits. If another naming plugin is detected, check Which plugin names the files? and deliberately select whose rules should apply.

5. Export as usual in GlotPress
Open the translation set in GlotPress and use its normal export link. Choose the required format there. PO is usually for editing translations; MO is a compiled format used by WordPress. The chosen format supplies .po, .mo or another extension. This settings page does not download translations.

== Frequently Asked Questions ==

= Do I need to fill in every field? =
No. Keep the recommended filename pattern. Choose a project name only if the last part of its GlotPress path is unsuitable, then preview and save. Custom language codes and backup/reset tools are optional.

= Which name should I use for a WordPress plugin? =
Use that plugin’s actual text domain: the identifier it uses for translations, for example my-plugin. The project’s display title is not necessarily its text domain. A correct filename still needs the right format, language code and file location in WordPress.

= Where do I download the translation? =
Use the normal export link inside the translation set in GlotPress. This settings page only configures names and shows examples; it does not download or install translations.

= Does it change translation contents? =
No. Only download names change. GlotPress retains access control and export contents.

= What is a translation set or language variant? =
A translation set is a group of translations in GlotPress. A language can have more than one, for example German informal and formal. A set named formal gets _formal automatically; other additional sets get -x- plus their short name. These suffixes distinguish files, but do not install WordPress languages.

= Does the preview save settings? =
No. Save Changes commits the rules separately. Project search, file selection and filename previews need JavaScript; saving and pasted-JSON import/reset work without it.

= Why are the filename settings unavailable? =
GlotPress 4.0 or newer must be active with a permalink structure other than Plain. Administrator permission is needed to save rules. Existing settings and the quick guide remain accessible while export features are paused.

Full documentation and FAQ: docs/guide.html and docs/guide-de.html in this package.

== Changelog ==

= 2.0.0 =
* New: Configure project names, filename patterns and language variants on a dedicated settings page, with project search and a live preview.
* New: Use backup, reviewed import and selective reset tools; open the five-step quick guide at any time.
* New: Includes the deckerweb Library and validated GitHub updates.
* Improved: Use the exported translation’s language, distinguish formal and custom sets, and prevent filename collisions within a project.
* Improved: Choose responsibility when other recognized filename plugins are active.
* Improved: Adds accessible bilingual documentation, illustrated instructions and the plugin icon.
* Fixed: Keep the original GlotPress filename when a generated name is invalid; preserve export contents and compound file extensions.
* Misc: Requires WordPress 6.4, PHP 8.1 and GlotPress 4.0 or newer. Settings belong to each website and remain after uninstall.
* Misc: From version 2018.09.15, upload the ZIP and confirm replacement because WordPress compares the old date-based version as newer.

= 2018.09.15 =
* Improved: Simplified filename handling.
* Fixed: Restored export filenames.

= 2016.09.02 =
* New: Initial release.

