# GlotPress Plugin: GP Project Export Name

* Contributors: [David Decker](https://github.com/deckerweb), [contributors](https://github.com/deckerweb/gp-project-export-name/graphs/contributors)
* Tags: glotpress, export, project, name, file name, filename, projects, translations, translation, download, saving
* Requires at least: 4.7.0
* Tested up to: 4.9.8
* Requires PHP: 5.6
* Stable tag: master
* Donate link: [https://www.paypal.me/deckerweb](https://www.paypal.me/deckerweb)
* License: GPL-2.0+
* License URI: [http://www.opensource.org/licenses/gpl-license.php](http://www.opensource.org/licenses/gpl-license.php)

In GlotPress plugin change the export file name to project name, plus locale string (as a language code).


## Description:

Using native filters in the GlotPress plugin for WordPress to tweak the export file names to a more practical format/ string.

*Backstory:* In my private GlotPress install I only translate from English to German. Therefore I only always need to export/ save translation files with the German locale "de_DE". The project name as file name makes a lot of sense: using plugin name also as plugin slug and textdomain is common sense nowadays in WordPress universe. As a result I get file names like "my-plugin-name-de_DE.mo" that I only need to upload, package as ZIP or something like that. Pretty cool, hehe! :-)


## Features:

* Install, activate and use. That's all!
* It works seamlessly in the background only using native GlotPress filters.
* Makes work of translators and plugin/ theme developers a bit more easier, hopefully.
* A plugin for "GlotPress as a WordPress plugin".


## Plugin Installation:

**Requirements/ Prerequisites**
* [WordPress v4.4.0 or higher](https://wordpress.org/download/)
* [Plugin: GlotPress v2.1.0 or higher](https://wordpress.org/plugins/glotpress/)
* *PLEASE: For security and best support, always use the latest stable versions of WordPress, GlotPress and plugins!*

**Manual Upload**
* Download current .zip archive from master branch here, URL: [https://github.com/deckerweb/gp-project-export-name/archive/master.zip](https://github.com/deckerweb/gp-project-export-name/archive/master.zip)
* Unzip the package, then **rename the folder to `gp-project-export-name`**, then upload renamed folder via SFTP/ FTP to your WordPress plugin directory
* Activate the plugin

**Via "GitHub Updater" Plugin** *(recommended!)*

* Install & activate the "GitHub Updater" plugin, get from here: [https://github.com/afragen/github-updater](https://github.com/afragen/github-updater)
* Recommended: set your API Token in the plugin's settings
* Go to "Settings > GitHub Updater > Install Plugin"
* Paste the GitHub URL `https://github.com/deckerweb/gp-project-export-name` in the "Plugin URI" field (branch "master" is pre-set), then hit the "Install Plugin" button there
* Install & activate the plugin

**Updates**
* Are done via the plugin "GitHub Updater" (see above) - leveraging the default WordPress update system!
* Setting your GitHub API Token is recommended! :)
* It's so easy and seamless you won't find any better solution for this ;-)


## Usage:

* Uses name of project as main file name (or sub project - always the deepest level gets used), plus a locale language code (like "en_US" or "de_DE"), plus the file format extension.
* Example: Project name in GlotPress is "My Example Plugin" and locale is set to "it_IT"
* Result: filename for example will be: "my-example-plugin-it_IT.po" (or .mo or whatever extension selected from the dropdown...)

## Translations:
= Localization & Internationalizaton:

* Used textdomain: `gp-project-export-name`
* Default `.pot` file included
* German translations included (`de_DE`)
* Plugin's own path for translations: `wp-content/plugins/gp-project-export-name/languages/gp-project-export-name-de_DE.mo`
* *Recommended:* Global WordPress lang dir path for translations: `wp-content/languages/plugins/gp-project-export-name-de_DE.mo` ---> *NOTE: if this file/path exists it will be loaded at higher priority than the plugin path! This is the recommended path & way to store your translations as it is update-safe and allows for custom translations!*
* Recommended translation tools: *Poedit Pro v1.8+* or *WordPress Plugin "Loco Translate"* or *your IDE/ Code Editor* or *old WordPress plugin "Codestyling Localization"* (for the brave who know what they are doing :) )


## Changelog - Version History:

--> See plugin file [CHANGES.md here](https://github.com/deckerweb/gp-project-export-name/blob/master/CHANGES.md)


Copyright (c) 2016-2018 David Decker - DECKERWEB.de
