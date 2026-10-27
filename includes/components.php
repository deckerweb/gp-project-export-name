<?php
/** Pinned shared components; both keep their own implementation unchanged.
 * @package GP_Project_Export_Name
 */
defined('ABSPATH') || exit;
require_once __DIR__.'/deckerweb-plugin-library/bootstrap.php';
deckerweb_library_register_v2(DDW_GPE_FILE,array(),__DIR__.'/deckerweb-plugin-library');
require_once __DIR__.'/update-validation.php';
add_filter('upgrader_source_selection','ddw_gpe_validate_update',30,4);
add_action('init','ddw_gpe_components');
/** Initialize host translations and the pinned updater after WordPress init.
 * @return void Registers the public repository updater without fetching metadata.
 */
function ddw_gpe_components() {
 load_plugin_textdomain('gp-project-export-name',false,dirname(plugin_basename(DDW_GPE_FILE)).'/languages');
 $class=require __DIR__.'/deckerweb-github-release-updater-v2.php';
 $language=str_starts_with(determine_locale(),'de') ? 'de' : 'en';
 $artwork=array('icons'=>array('svg'=>plugins_url('assets/icon.svg',DDW_GPE_FILE),'1x'=>plugins_url('assets/icon-128.png',DDW_GPE_FILE),'2x'=>plugins_url('assets/icon-256.png',DDW_GPE_FILE)),'banners'=>array('low'=>plugins_url('assets/banner-wordpress-'.$language.'-772x250.png',DDW_GPE_FILE),'high'=>plugins_url('assets/banner-wordpress-'.$language.'-1544x500.png',DDW_GPE_FILE)));
 $updater=new $class(DDW_GPE_FILE,DDW_GPE_REPO,'GP Project Export Name',__('Practical filenames for every translation.','gp-project-export-name'),$artwork,array('translate'=>require __DIR__.'/updater-translations.php'));
 $updater->register();
}
