<?php
/** Remove temporary shared-component state while retaining website export rules.
 * @package GP_Project_Export_Name
 */
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
if (version_compare(PHP_VERSION,'8.1','<')) return;
require_once __DIR__.'/includes/deckerweb-plugin-library/lifecycle.php';
deckerweb_library_uninstall_v3(__DIR__.'/gp-project-export-name.php');
$ddw_gpe_updater_class=require __DIR__.'/includes/deckerweb-github-release-updater-v2.php';
$ddw_gpe_updater_class::clear_repository_cache('https://github.com/deckerweb/gp-project-export-name');
// Export rules are durable website configuration and are intentionally retained.
