<?php
/** Shared, localized introductory guide for the settings page and documentation.
 * @package GP_Project_Export_Name
 */
defined('ABSPATH') || exit;
/** Render a read-only guide from the same bilingual source as the public documents. */
final class DDW_GPE_Guide {
 /** Read bounded local documentation once per request.
  * @return array Parsed source, or an empty array when unavailable.
  */
 public static function content() {
  static $content=null;
  if ($content===null) {
   $path=dirname(DDW_GPE_FILE).'/docs/content.json';
   $parsed=is_readable($path) ? json_decode(file_get_contents($path,false,null,0,262144),true) : null;
   $content=is_array($parsed) ? $parsed : array();
  }
  return $content;
 }
 /** Select the guide using the current admin language, including formal German.
  * @return array Localized guide or an empty array.
  */
 public static function data() {
  $content=self::content();$language=strpos(determine_locale(),'de_')===0 ? 'de' : 'en';
  $guide=$content[$language]['quickstart'] ?? array();
  return is_array($guide) ? $guide : array();
 }
 /** Get one plain-text guide field without accepting arrays or objects.
  * @param array $guide Localized guide source.
  * @param string $key Fixed field name.
  * @return string Source text or an empty string.
  */
 private static function text($guide,$key) {
  return isset($guide[$key]) && is_string($guide[$key]) ? $guide[$key] : '';
 }
 /** Render escaped introductory text, examples and an ordered list of steps.
  * Settings links are fixed local targets; no source-provided URL is executed.
  * @return void Prints read-only guide content.
  */
 public static function render() {
  $guide=self::data();
  $targets=array('pattern'=>'ddw-pattern','projects'=>'ddw-project-search','preview'=>'ddw-preview-path','save'=>'submit');
  echo '<p class="ddw-gpe-guide-intro">'.esc_html(self::text($guide,'intro')).'</p>';
  echo '<section class="ddw-gpe-guide-example" aria-labelledby="ddw-guide-example-title"><h3 id="ddw-guide-example-title">'.esc_html(self::text($guide,'example_title')).'</h3><dl>';
  foreach (array('before','after') as $part) echo '<div><dt>'.esc_html(self::text($guide,$part.'_label')).'</dt><dd><code>'.esc_html(self::text($guide,$part)).'</code></dd></div>';
  echo '</dl><p>'.esc_html(self::text($guide,'example_note')).'</p></section><ol class="ddw-gpe-guide-steps" role="list">';
  $number=0;
  $steps=is_array($guide['steps'] ?? null) ? $guide['steps'] : array();
  foreach ($steps as $step) {
   if (!is_array($step) || !is_string($step['title'] ?? null) || !is_string($step['text'] ?? null)) continue;
   $number++;
   echo '<li><span class="ddw-gpe-guide-number" aria-hidden="true">'.esc_html((string)$number).'.</span><div><h3>'.esc_html($step['title']).'</h3><p>'.esc_html($step['text']).'</p>';
   if (isset($step['tip']) && is_string($step['tip'])) echo '<p class="ddw-gpe-guide-tip">'.esc_html($step['tip']).'</p>';
   $area=$step['area'] ?? '';
   if (DDW_GPE_Names::available() && is_string($area) && isset($targets[$area]) && is_string($step['action'] ?? null)) echo '<a href="#'.esc_attr($targets[$area]).'" data-ddw-guide-target="'.esc_attr($targets[$area]).'">'.esc_html($step['action']).'</a>';
   echo '</div></li>';
  }
  echo '</ol><h3>'.esc_html(self::text($guide,'optional_title')).'</h3><p>'.esc_html(self::text($guide,'optional')).'</p><p><a href="'.esc_url(DDW_GPE_Admin::document('guide')).'">'.esc_html(self::text($guide,'docs_label')).'</a></p>';
 }
}
