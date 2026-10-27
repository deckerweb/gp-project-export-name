<?php
/**
 * Plugin Name: GP Project Export Name
 * Plugin URI: https://github.com/deckerweb/gp-project-export-name
 * Description: Give GlotPress downloads recognizable project and language names, with previews and step-by-step help.
 * Version: 2.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: David Decker – DECKERWEB
 * Author URI: https://github.com/deckerweb
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gp-project-export-name
 * Domain Path: /languages/
 * Update URI: https://github.com/deckerweb/gp-project-export-name
 * GitHub Plugin URI: https://github.com/deckerweb/gp-project-export-name
 *
 * Copyright © 2016–2026 David Decker – DECKERWEB.
 * SPDX-License-Identifier: GPL-2.0-or-later
 *
 * @package GP_Project_Export_Name
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '8.1', '<' ) || version_compare( get_bloginfo( 'version' ), '6.4', '<' ) ) { return; }

define( 'DDW_GPE_VERSION', '2.0.0' );
define( 'DDW_GPE_FILE', __FILE__ );
define( 'DDW_GPE_REPO', 'https://github.com/deckerweb/gp-project-export-name' );
require_once __DIR__ . '/includes/class-export-names.php';
require_once __DIR__ . '/includes/components.php';
add_filter( 'gp_export_translations_filename', array( 'DDW_GPE_Names', 'filter' ), 10, 5 );
add_action( 'gp_init', array( 'DDW_GPE_Names', 'ownership' ), 100 );
if ( is_admin() ) {
 require_once __DIR__ . '/includes/class-admin.php';
 DDW_GPE_Admin::register();
}
/**
 * Preserve the public legacy locale helper.
 * @param object|null $locale GlotPress locale, if supplied.
 * @return string Safe locale or empty string.
 */
function ddw_gp_filter_export_locale( $locale = null ) {
 return DDW_GPE_Names::locale( $locale );
}
/**
 * Preserve the legacy callback for existing integrations.
 * @param string $filename Incoming filename.
 * @param object $format Export format.
 * @param object $locale Export language.
 * @param object $project GlotPress project.
 * @param object $translation_set Translation set.
 * @return string Download filename.
 */
function ddw_gp_export_translations_filename( $filename, $format, $locale, $project, $translation_set ) {
 return DDW_GPE_Names::filter( $filename, $format, $locale, $project, $translation_set );
}
