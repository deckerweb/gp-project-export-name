<?php
/** Validate this host's exact identity and requirements after updater normalization.
 * @package GP_Project_Export_Name
 */
defined('ABSPATH') || exit;
/**
 * Inspect an unpacked candidate only for the exact host update, including bulk.
 * @param string|WP_Error $source Normalized source directory.
 * @param string $remote_source Original extraction directory.
 * @param object $upgrader Native upgrader.
 * @param array $context Native per-plugin operation context.
 * @return string|WP_Error Source directory or rejection.
 */
function ddw_gpe_validate_update($source,$remote_source,$upgrader,$context) {
 $file='gp-project-export-name/gp-project-export-name.php';
 if (is_wp_error($source) || !is_array($context) || ($context['plugin'] ?? '')!==$file || (isset($context['type']) && $context['type']!=='plugin') || (isset($context['action']) && $context['action']!=='update') || (!isset($context['type'],$context['action']) && !($upgrader instanceof Plugin_Upgrader && $upgrader->bulk===true))) return $source;
 global $wp_filesystem;
 if (!$wp_filesystem) return new WP_Error('ddw_gpe_package',__('The update package could not be verified.','gp-project-export-name'));
 $main=trailingslashit($source).'gp-project-export-name.php';
 $size=$wp_filesystem->size($main);
 if (!$size || $size>262144) return new WP_Error('ddw_gpe_package',__('The update package could not be verified.','gp-project-export-name'));
 $text=$wp_filesystem->get_contents($main);
 if (!is_string($text)) return new WP_Error('ddw_gpe_package',__('The update package could not be verified.','gp-project-export-name'));
 $headers=array();
 foreach (array('Plugin Name','Version','Text Domain','Update URI','Requires at least','Requires PHP') as $field) {
  $headers[$field]=preg_match('/^[ \t\/*#@]*'.preg_quote($field,'/').':\s*(.+)$/mi',substr($text,0,8192),$m) ? trim($m[1]) : '';
 }
 $updates=get_site_transient('update_plugins');
 $offer=is_object($updates) ? ($updates->response[$file] ?? null) : null;
 $offered=is_object($offer) ? ($offer->new_version ?? '') : '';
 $installed=get_file_data(WP_PLUGIN_DIR.'/'.$file,array('Version'=>'Version'));
 $old=$installed['Version'];
 $new=$headers['Version'];
 $newer=version_compare($new,$old,'>') || ($old==='2018.09.15' && preg_match('/^2\.[0-9]+\.[0-9]+$/D',$new));
 if (!$offered || $offered!==$new || !$newer || $headers['Plugin Name']!=='GP Project Export Name' || $headers['Text Domain']!=='gp-project-export-name' || $headers['Update URI']!=='https://github.com/deckerweb/gp-project-export-name' || !$headers['Requires at least'] || !$headers['Requires PHP'] || version_compare($headers['Requires at least'],'6.4','<') || version_compare($headers['Requires PHP'],'8.1','<') || version_compare(get_bloginfo('version'),$headers['Requires at least'],'<') || version_compare(PHP_VERSION,$headers['Requires PHP'],'<')) return new WP_Error('ddw_gpe_package',__('The update package could not be verified.','gp-project-export-name'));
 return $source;
}
