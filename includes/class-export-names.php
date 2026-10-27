<?php
/** Export rules, validation and deterministic collision protection.
 * @package GP_Project_Export_Name
 */
defined( 'ABSPATH' ) || exit;
/** Own the filename calculation without changing export contents or permissions. */
final class DDW_GPE_Names {
 /** @var array Request-local translation-set lists, keyed by website and project. */
 private static $sets = array();
 /** @var array Recognized naming plugins before explicit callback removal. */
 private static $recognized = array();
 /** @var array Request-local collision plans keyed by website, project and rules. */
 private static $plans = array();
 /** Get website rules; no setting is network-global.
  * @return array Current normalized settings.
  */
 public static function settings() {
  $value = get_option( 'ddw_gpe_settings', array() );
  $default=array('pattern'=>'{project}-{locale}','projects'=>array(),'variants'=>array(),'owner'=>'auto');
  if (!is_array($value)) return $default;
  foreach ($default as $key=>$fallback) {
   if (!isset($value[$key]) || gettype($value[$key])!==gettype($fallback)) $value[$key]=$fallback;
  }
  return array_intersect_key($value,$default);
 }
 /** Validate one safe component without changing case or locale separators.
  * @param mixed $value Candidate value.
  * @return string Valid component or empty string.
  */
 public static function component( $value ) {
  return is_string($value) && strlen($value) <= 160 && preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/D',$value) ? $value : '';
 }
 /** Resolve a locale, with the existing fixed override taking priority.
  * @param object|null $locale Export locale; null preserves the legacy helper.
  * @return string Validated language identifier.
  */
 public static function locale( $locale = null ) {
  if (defined('GP_PROJECT_EXPORT_LOCALE') && self::component(GP_PROJECT_EXPORT_LOCALE)) return GP_PROJECT_EXPORT_LOCALE;
  if (!is_object($locale)) return self::component(get_user_locale());
  return self::component($locale->wp_locale ?? '') ?: self::component($locale->slug ?? '');
 }
 /** Check whether GlotPress is usable in this website context.
  * @return bool Whether dependent settings and export features can run.
  */
 public static function available() {
  return class_exists('GP') && defined('GP_VERSION') && version_compare(GP_VERSION,'4.0','>=');
 }
 /** Locate known foreign naming callbacks without touching other hooks.
  * @return array Foreign callbacks keyed by their public class name.
  */
 public static function competitors() {
  global $wp_filter;
  $found=array();
  if (isset($wp_filter['gp_export_translations_filename'])) {
   foreach ($wp_filter['gp_export_translations_filename']->callbacks as $callbacks) foreach ($callbacks as $item) {
    $cb=$item['function'];
    if (is_array($cb) && is_object($cb[0]) && in_array(get_class($cb[0]),array('GP_Download_Name','GP_Use_Slug_for_Downloads'),true)) $found[get_class($cb[0])]=$cb;
   }
  }
  return $found;
 }
 /** Report recognized plugins even after an explicit ownership choice removes callbacks.
  * @return array Recognized plugin class names, never arbitrary callback data.
  */
 public static function recognized_competitors() {
  return array_values(array_unique(array_merge(self::$recognized,array_keys(self::competitors()))));
 }
 /** Apply explicit administrator ownership after GlotPress add-ons initialize.
  * Removes only verified naming callbacks when this plugin was explicitly chosen.
  * @return void
  */
 public static function ownership() {
  self::$recognized=array_keys(self::competitors());
  if ('ours' !== self::settings()['owner']) return;
  global $wp_filter;
  foreach (self::competitors() as $callback) {
   foreach (array_keys($wp_filter['gp_export_translations_filename']->callbacks) as $priority) remove_filter('gp_export_translations_filename',$callback,$priority);
  }
 }
 /** Validate the small template language; the language cannot be omitted.
  * @param mixed $pattern Candidate template without extension.
  * @return bool Whether the entire template is recognized and safe.
  */
 public static function valid_pattern($pattern) {
  if (!is_string($pattern) || strlen($pattern)>160 || strpos($pattern,'{locale}')===false) return false;
  $plain=str_replace(array('{project}','{path}','{locale}','{set}'),array('p','p','l','s'),$pattern);
  return (bool) preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/D',$plain);
 }
 /** Calculate the name before duplicate protection, for preview and downloads.
  * @param object $project Project containing slug/path.
  * @param object $locale Export locale.
  * @param object|null $set Translation set; null means default.
  * @param array $rules Validated rules, including unsaved preview rules.
  * @return string Safe basename without extension, or empty on invalid context.
  */
 public static function base($project,$locale,$set,$rules) {
  $path=$project->path ?? $project->slug ?? '';
  if (!is_string($path) || !self::component($project->slug ?? '') || !self::component($locale->slug ?? '')) return '';
  $project_name=$rules['projects'][$path] ?? ($project->slug ?? '');
  $project_name=self::component($project_name);
  $language=self::locale($locale);
  $slug=is_object($set) ? ($set->slug ?? 'default') : 'default';
  if (!self::component($slug) || !$project_name || !$language) return '';
  if ($slug !== 'default') {
   // Explicit mappings are complete locale identifiers; constants remain deliberate bases.
   $language=$rules['variants'][($locale->slug ?? '').'/'.$slug] ?? ($language . ($slug==='formal' ? '_formal' : '-x-'.$slug));
  }
  $flat=str_replace('/','-',$path);
  if (!self::component($flat) || !self::component($language) || !self::valid_pattern($rules['pattern'])) return '';
  return self::component(strtr($rules['pattern'],array('{project}'=>$project_name,'{path}'=>$flat,'{locale}'=>$language,'{set}'=>$slug)));
 }
 /** Retrieve sibling sets once per project and request; callers may bypass cache for preview.
  * @param int $project_id Project ID.
  * @param bool $refresh Whether freshly saved data must be read.
  * @return array GlotPress sets.
  */
 public static function sets($project_id,$refresh=false) {
  $key=get_current_blog_id().':'.(int)$project_id;
  if ($refresh) self::$plans=array();
  if ($refresh || !isset(self::$sets[$key])) self::$sets[$key]= self::available() ? (array) GP::$translation_set->by_project_id((int)$project_id) : array();
  return self::$sets[$key];
 }
 /** Return a safe unique filename within the project's translation sets.
  * Duplicate basenames receive stable set-ID suffixes; nothing depends on loop order.
  * @param object $project Export project.
  * @param object $locale Export language.
  * @param object|null $set Export set.
  * @param array $rules Normalized naming rules.
  * @param string $extension Format extension, including compound extensions.
  * @return string Filename or empty if no valid name is possible.
  */
 public static function filename($project,$locale,$set,$rules,$extension) {
  if (!is_string($extension) || strlen($extension)>32 || !preg_match('/\A[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)*\z/D',$extension)) return '';
  $base=self::base($project,$locale,$set,$rules);
  if (!$base) return '';
  if (is_object($set) && !empty($set->id) && !empty($project->id) && self::available()) {
   $key=get_current_blog_id().':'.(int)$project->id.':'.md5(wp_json_encode($rules));
   if (!isset(self::$plans[$key])) {
    $counts=array(); $reserved=false;
    foreach (self::sets($project->id) as $other) {
     $other_locale=GP_Locales::by_slug($other->locale);
     $other_base=$other_locale ? self::base($project,$other_locale,$other,$rules) : '';
     $counts[$other_base]=($counts[$other_base] ?? 0)+1;
     if (preg_match('/--set-[0-9]+$/D',$other_base)) $reserved=true;
    }
    self::$plans[$key]=array('counts'=>$counts,'reserved'=>$reserved);
   }
   $plan=self::$plans[$key];
   // A literal reserved suffix makes every name use its unique set ID.
   if (($plan['counts'][$base] ?? 0)>1 || $plan['reserved']) $base .= '--set-'.(int)$set->id;
  }
  return $base.'.'.$extension;
 }
 /** Filter native and bulk export filenames, or defer to the chosen foreign owner.
  * @param string $filename Incoming filename.
  * @param object $format GlotPress format.
  * @param object $locale Export locale.
  * @param object $project Export project.
  * @param object|null $set Translation set.
  * @return string Download filename.
  */
 public static function filter($filename,$format,$locale,$project,$set) {
  $rules=self::settings();
  if (!self::available()) return $filename;
  if ('other'===$rules['owner'] || ('ours'!==$rules['owner'] && self::competitors())) return $filename;
  if (!is_object($format) || !is_object($locale) || !is_object($project)) return $filename;
  return self::filename($project,$locale,$set,$rules,$format->extension ?? '') ?: $filename;
 }
}
