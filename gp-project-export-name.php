<?php # -*- coding: utf-8 -*-
/**
 * Main plugin file.
 * @package           GP Project Export Name
 * @author            David Decker
 * @copyright         Copyright (c) 2016, David Decker - DECKERWEB
 * @license           GPL-2.0+
 * @link              http://deckerweb.de/twitter
 *
 * @wordpress-plugin
 * Plugin Name:       GP Project Export Name
 * Plugin URI:        https://github.com/deckerweb/gp-project-export-name
 * Description:       In GlotPress plugin change the export file name to project name, plus locale string (as a language code).
 * Version:           2016.09.01
 * Author:            David Decker - DECKERWEB
 * Author URI:        http://deckerweb.de/
 * License:           GPL-2.0+
 * License URI:       http://www.opensource.org/licenses/gpl-license.php
 * Text Domain:       gp-project-export-name
 * Domain Path:       /languages/
 * GitHub Plugin URI: https://github.com/deckerweb/gp-project-export-name
 * GitHub Branch:     master
 *
 * Copyright (c) 2016 David Decker - DECKERWEB
 */

/**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {

	die;

}  // end if


add_filter( 'gp_export_locale', 'ddw_gp_filter_export_locale', 10, 2 );
/**
 * Let user define a custom locale string for the export; otherwise use
 *   currently set locale in WordPress general settings.
 *   (Reason: the GP filter 'gp_export_locale' currently does not work...)
 *
 * @since  2016.09.01
 *
 * @param  string $slug
 * @param  string $locale
 *
 * @return string Language code string for export locale.
 */
function ddw_gp_filter_export_locale( $slug, $locale ) {

	if ( defined( 'GP_PROJECT_EXPORT_LOCALE' ) && GP_PROJECT_EXPORT_LOCALE ) {

		$locale = esc_attr( GP_PROJECT_EXPORT_LOCALE );

	} else {

		$locale = get_locale();

	}  // end if

	return $locale;

}  // end function


add_filter( 'gp_export_translations_filename', 'ddw_gp_export_translations_filename', 10, 5 );
/**
 * Tweak the filename in GlotPress when exporting a translation set within a project.
 *
 *   Uses: Name of project as main file name (or sub project - always the deepest
 *   level gets used), plus a locale language code (like "en_US" or "de_DE"),
 *   plus the file format extension.
 *
 *   Example: Project name in GlotPress is "My Example Plugin" and locale is set
 *   to "it_IT"
 *
 *   Result: filename for example will be: "my-example-plugin-it_IT.po" (or .mo
 *   or whatever extension selected from the dropdown...)
 *
 * @since  2016.09.01
 *
 * @param  string $filename        Export file name.
 * @param  string $format          Desired export format.
 * @param  string $locale          Included locale code in file name.
 * @param  string $project         Project to export the file for.
 * @param  string $translation_set Current translation set.
 *
 * @return string                  Export file name for selected format.
 */
function ddw_gp_export_translations_filename( $filename, $format, $locale, $project, $translation_set ) {

	/** Check if project is not empty, then build file name */
	if ( $project !== '' ) {

		$filename = esc_html( $project->slug ) . '-' . ddw_gp_filter_export_locale() . '.' . $format->extension;

	}  // end if

	/** Output file name for saving */
	return $filename;

}  // end function
