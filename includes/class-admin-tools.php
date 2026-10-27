<?php
/** Reviewed settings transfers, resets and paged project inspection.
 * @package GP_Project_Export_Name
 */
defined('ABSPATH') || exit;
/** Keep administrative maintenance outside the download request path. */
final class DDW_GPE_Admin_Tools {
 /** @var array Current request review result, computed before rendering settings. */
 private static $review=array(null,'',false);
 /** Register the authorized download endpoint.
  * @return void
  */
 public static function register() {
  add_action('admin_post_ddw_gpe_export',array(__CLASS__,'download'));
  add_action('wp_ajax_ddw_gpe_projects',array(__CLASS__,'projects'));
 }
 /** Encode only this website's export rules, without environment or account data.
  * @return string Versioned portable JSON.
  */
 public static function export_json() {
  return wp_json_encode(array('plugin'=>'gp-project-export-name','schema'=>1,'settings'=>DDW_GPE_Names::settings()),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
 }
 /** Validate a bounded, exact transfer schema before using any setting.
  * @param mixed $json Pasted or locally selected file contents.
  * @return array|WP_Error Normalized rules or error.
  */
 public static function decode($json) {
  $error=new WP_Error('transfer',__('This is not a supported settings backup. Choose an unchanged JSON export from GP Project Export Name (file format version 1, up to 64 KB).','gp-project-export-name'));
  if (!is_string($json) || strlen($json)>65536) return $error;
  $data=json_decode($json,true,16);
  if (!is_array($data) || count($data)!==3 || ($data['plugin'] ?? '')!=='gp-project-export-name' || ($data['schema'] ?? null)!==1 || !is_array($data['settings'] ?? null)) return $error;
  $rules=$data['settings'];
  if (count($rules)!==4 || !isset($rules['pattern'],$rules['owner'],$rules['projects'],$rules['variants']) || !is_array($rules['projects']) || !is_array($rules['variants'])) return $error;
  return DDW_GPE_Admin::parse($rules);
 }
 /** Download portable rules after capability and nonce checks.
  * @return void Sends a JSON attachment and terminates.
  */
 public static function download() {
  if (!current_user_can('manage_options')) wp_die(esc_html__('Permission denied.','gp-project-export-name'),'',array('response'=>403));
  check_admin_referer('ddw_gpe_export');
  nocache_headers();
  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="gp-project-export-name-settings.json"');
  wp_send_json(json_decode(self::export_json(),true),200,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
 }
 /** Produce a reset candidate; no database writes happen here.
  * @param string $scope Named group or individual project mapping.
  * @param string $key Exact project path for an individual reset.
  * @return array|WP_Error Candidate rules.
  */
 public static function reset_rules($scope,$key='') {
  $rules=DDW_GPE_Names::settings();
  switch ($scope) {
   case 'pattern': $rules['pattern']='{project}-{locale}'; break;
   case 'projects': $rules['projects']=array(); break;
   case 'variants': $rules['variants']=array(); break;
   case 'owner': $rules['owner']='auto'; break;
   case 'all': $rules=array('pattern'=>'{project}-{locale}','owner'=>'auto','projects'=>array(),'variants'=>array()); break;
   case 'variant':
    if (!isset($rules['variants'][$key])) return new WP_Error('reset',__('Variant mapping not found.','gp-project-export-name'));
    unset($rules['variants'][$key]); break;
   case 'project':
    if (!isset($rules['projects'][$key])) return new WP_Error('reset',__('Project mapping not found.','gp-project-export-name'));
    unset($rules['projects'][$key]); break;
   default: return new WP_Error('reset',__('Choose which settings should return to their defaults.','gp-project-export-name'));
  }
  return $rules;
 }
 /** Bind a confirmation to its exact data, website, administrator and saved baseline.
  * @param string $json Canonical transfer document.
  * @param string $baseline Saved settings digest.
  * @return string HMAC signature, checked with hash_equals on confirmation.
  */
 public static function signature($json,$baseline) {
  return hash_hmac('sha256',get_current_blog_id().'|'.get_current_user_id().'|'.$baseline.'|'.$json,wp_salt('nonce'));
 }
 /** Hash the saved normalized rules to prevent overwriting intervening edits.
  * @return string Baseline digest.
  */
 public static function baseline() { return hash('sha256',wp_json_encode(DDW_GPE_Names::settings())); }
 /** Commit a previously reviewed candidate only when the saved baseline still matches.
  * Caller must also enforce capability, dependency and nonce checks.
  * @param string $json Reviewed canonical transfer document.
  * @param string $baseline Saved baseline at review time.
  * @param string $signature Server signature from the review.
  * @return true|WP_Error Result; a no-op is successful.
  */
 public static function commit($json,$baseline,$signature) {
  if (!current_user_can('manage_options') || !DDW_GPE_Names::available()) return new WP_Error('permission',__('Saving requires administrator permission and a working GlotPress installation.','gp-project-export-name'));
  if (!hash_equals(self::signature($json,$baseline),$signature) || !hash_equals(self::baseline(),$baseline)) return new WP_Error('stale',__('Settings changed or the confirmation is invalid. Review the changes again.','gp-project-export-name'));
  $rules=self::decode($json);
  if (is_wp_error($rules)) return $rules;
  update_option('ddw_gpe_settings',$rules,false);
  return DDW_GPE_Names::settings()===$rules ? true : new WP_Error('write',__('The settings could not be saved.','gp-project-export-name'));
 }
 /** Describe changed values, including individual additions, replacements and removals.
  * @param array $before Existing rules.
  * @param array $after Reviewed rules.
  * @return array Display rows with field, old and new values.
  */
 public static function changes($before,$after) {
  $rows=array();
  foreach (array('pattern','owner','projects','variants') as $group) {
   if (in_array($group,array('pattern','owner'),true)) {
    if ($before[$group]!==$after[$group]) {
     $owners=array('auto'=>__('Automatic: this plugin unless another is detected','gp-project-export-name'),'ours'=>__('Use GP Project Export Name','gp-project-export-name'),'other'=>__('Use GlotPress or another naming plugin','gp-project-export-name'));
     $rows[]=array($group==='pattern' ? __('Filename pattern','gp-project-export-name') : __('Which plugin names the files?','gp-project-export-name'),$group==='owner' ? ($owners[$before[$group]] ?? $before[$group]) : $before[$group],$group==='owner' ? ($owners[$after[$group]] ?? $after[$group]) : $after[$group]);
    }
   } else {
    foreach (array_unique(array_merge(array_keys($before[$group]),array_keys($after[$group]))) as $key) {
     $old=$before[$group][$key] ?? ''; $new=$after[$group][$key] ?? '';
     if ($old!==$new) $rows[]=array(($group==='projects' ? __('Name chosen for this project','gp-project-export-name') : __('Custom language code','gp-project-export-name')).': '.$key,$old,$new);
    }
   }
  }
  return $rows;
 }
 /** Resolve the controller that would apply for the candidate rules.
  * @param array $rules Candidate rules.
  * @return string Localized ownership explanation.
  */
 public static function controller($rules) {
  if ($rules['owner']==='other' || ($rules['owner']==='auto' && DDW_GPE_Names::recognized_competitors())) return __('GlotPress or another naming plugin will name the files. The examples show what these rules would produce if GP Project Export Name were selected.','gp-project-export-name');
  return __('These rules will be used when GP Project Export Name names the downloads. Save Changes makes the draft apply to future exports.','gp-project-export-name');
 }
 /** Explain a filename with the same inputs used by the actual naming engine.
  * @param object $project GlotPress project.
  * @param object $locale Export locale.
  * @param object $set Translation set.
  * @param array $rules Candidate rules.
  * @return string Human-readable provenance.
  */
 public static function explain($project,$locale,$set,$rules) {
  $path=$project->path;$parts=array();
  if (strpos($rules['pattern'],'{project}')!==false) $parts[]=(isset($rules['projects'][$path]) ? __('Name chosen for this project','gp-project-export-name') : __('Last part of the project path','gp-project-export-name')).': '.($rules['projects'][$path] ?? $project->slug);
  if (strpos($rules['pattern'],'{path}')!==false) $parts[]=__('Project path','gp-project-export-name').': '.str_replace('/','-',$path);
  $code=DDW_GPE_Names::locale($locale);$key=$set->locale.'/'.$set->slug;
  if ($set->slug!=='default' && isset($rules['variants'][$key])) $parts[]=__('Custom language code','gp-project-export-name').': '.$rules['variants'][$key];
  else {
   if ($set->slug!=='default') $code.=$set->slug==='formal' ? '_formal' : '-x-'.$set->slug;
   $parts[]=(defined('GP_PROJECT_EXPORT_LOCALE') && DDW_GPE_Names::component(GP_PROJECT_EXPORT_LOCALE) ? __('Language code fixed in the configuration','gp-project-export-name') : __('Code of the exported language','gp-project-export-name')).': '.$code;
  }
  if (strpos($rules['pattern'],'{set}')!==false) $parts[]=__('Translation set','gp-project-export-name').': '.$set->slug;
  $parts[]=__('Filename pattern','gp-project-export-name').': '.$rules['pattern'];
  return implode(' · ',$parts);
 }
 /** Inspect one project without modifying it.
  * @param object $project GlotPress project.
  * @param array $rules Candidate rules.
  * @return array Project information, example and diagnostic messages.
  */
 public static function inspect($project,$rules) {
  $issues=array(); $seen=array(); $example=''; $count=0;
  foreach (DDW_GPE_Names::sets($project->id,true) as $set) {
   $locale=GP_Locales::by_slug($set->locale);
   if (!$locale) { $issues[]=__('Unknown translation language.','gp-project-export-name'); continue; }
   $base=DDW_GPE_Names::base($project,$locale,$set,$rules);
   $name=DDW_GPE_Names::filename($project,$locale,$set,$rules,'po');
   if (!$base || !$name) $issues[]=__('This rule cannot produce a valid filename here. GlotPress’s original name will be used; check the pattern and project name.','gp-project-export-name');
   if (isset($seen[$base])) $issues[]=__('Some names would be identical. A --set- number keeps the translation sets distinct.','gp-project-export-name');
   $seen[$base]=true; $count++;
   if (!$example) $example=$name;
  }
  if (!$count) $issues[]=__('This project has no translation sets to preview yet.','gp-project-export-name');
  return array('path'=>$project->path,'base'=>$rules['projects'][$project->path] ?? $project->slug,'mapped'=>isset($rules['projects'][$project->path]),'example'=>$example,'sets'=>$count,'issues'=>array_values(array_unique($issues)));
 }
 /** Search and inspect a bounded page, using GlotPress's prepared model API.
  * @param string $search Search fragment for project name or complete path.
  * @param int $page One-based result page.
  * @param array $rules Validated candidate rules.
  * @return array Rows, pagination and stale mapping warnings.
  */
 public static function project_page($search,$page,$rules) {
  global $wpdb;
  $page=max(1,min(10000,$page)); $like='%'.$wpdb->esc_like($search).'%';
  $projects=GP::$project->many('SELECT * FROM '.GP::$project->table.' WHERE path LIKE %s OR name LIKE %s ORDER BY path ASC LIMIT %d OFFSET %d',$like,$like,21,($page-1)*20);
  $more=count($projects)>20; $rows=array();
  foreach (array_slice($projects,0,20) as $project) $rows[]=self::inspect($project,$rules);
  $missing=array();
  // Only the first page checks stored mappings, bounded by the 100-rule limit.
  if ($page===1) foreach ($rules['projects'] as $path=>$base) if (!GP::$project->by_path($path)) $missing[]=$path;
  return array('rows'=>$rows,'more'=>$more,'page'=>$page,'missing'=>$missing,'controller'=>self::controller($rules));
 }
 /** Serve administrator-only unsaved project search and validation.
  * @return void Sends JSON and terminates.
  */
 public static function projects() {
  if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>__('Permission denied.','gp-project-export-name')),403);
  check_ajax_referer('ddw_gpe_preview');
  if (!DDW_GPE_Names::available()) wp_send_json_error(array('message'=>__('GlotPress is not ready. Activate GlotPress and check its permalink requirements.','gp-project-export-name')),409);
  $rules=DDW_GPE_Admin::parse(isset($_POST['ddw_gpe_settings']) ? wp_unslash($_POST['ddw_gpe_settings']) : array());
  if (is_wp_error($rules)) wp_send_json_error(array('message'=>$rules->get_error_message()),400);
  $search=isset($_POST['search']) && is_string($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
  $page=isset($_POST['project_page']) && is_scalar($_POST['project_page']) ? absint($_POST['project_page']) : 1;
  if (strlen($search)>160) wp_send_json_error(array('message'=>__('Use at most 160 characters for project search.','gp-project-export-name')),400);
  wp_send_json_success(self::project_page($search,$page,$rules));
 }
 /** Render transfer/reset forms and the explicit review step; works without JavaScript.
  * @return void Prints escaped native admin HTML.
  */
 public static function process() {
  if (!current_user_can('manage_options')) return;
  $ready=DDW_GPE_Names::available(); $candidate=null; $notice=''; $error=false;
  if (isset($_POST['ddw_gpe_tool'])) {
   check_admin_referer('ddw_gpe_tools');
   $operation=is_string($_POST['ddw_gpe_tool']) ? sanitize_key(wp_unslash($_POST['ddw_gpe_tool'])) : '';
   $json=isset($_POST['rules_json']) && is_string($_POST['rules_json']) ? wp_unslash($_POST['rules_json']) : '';
   if (!$ready) $candidate=new WP_Error('dependency',__('GlotPress is not ready. Activate GlotPress and check its permalink requirements.','gp-project-export-name'));
   elseif ($operation==='import') $candidate=self::decode($json);
   elseif ($operation==='reset') $candidate=self::reset_rules(isset($_POST['scope']) && is_string($_POST['scope']) ? sanitize_key(wp_unslash($_POST['scope'])) : '',isset($_POST['mapping_key']) && is_string($_POST['mapping_key']) ? sanitize_text_field(wp_unslash($_POST['mapping_key'])) : '');
   elseif ($operation==='apply') {
    $baseline=isset($_POST['baseline']) && is_string($_POST['baseline']) ? sanitize_text_field(wp_unslash($_POST['baseline'])) : '';
    $signature=isset($_POST['signature']) && is_string($_POST['signature']) ? sanitize_text_field(wp_unslash($_POST['signature'])) : '';
    $result=self::commit($json,$baseline,$signature);
    if (is_wp_error($result)) $candidate=$result;
    else $notice=__('Settings saved.','gp-project-export-name');
   }
   if (is_wp_error($candidate)) { $notice=$candidate->get_error_message(); $candidate=null; $error=true; }
  }
  self::$review=array($candidate,$notice,$error);
 }
 /** Render transfer forms and the reviewed candidate using escaped values.
  * @return void Prints native settings forms.
  */
 public static function render() {
  $ready=DDW_GPE_Names::available();
  list($candidate,$notice,$error)=self::$review;
  ?>
  <section class="ddw-gpe-tools" aria-labelledby="ddw-tools-title"><h2 id="ddw-tools-title"><?php esc_html_e('Back up or restore your settings','gp-project-export-name'); ?></h2>
  <?php if ($notice): ?><div class="notice inline <?php echo $error ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html($notice); ?></p></div><?php endif; ?>
  <?php if (is_array($candidate)):
   $json=wp_json_encode(array('plugin'=>'gp-project-export-name','schema'=>1,'settings'=>$candidate)); $baseline=self::baseline();
   $changes=self::changes(DDW_GPE_Names::settings(),$candidate);
   ?>
   <div class="ddw-gpe-review" id="ddw-gpe-review"><h3><?php esc_html_e('Review changes before saving','gp-project-export-name'); ?></h3>
   <p><?php esc_html_e('Confirming replaces the saved filename rules on this website with the values shown below. Unsaved edits in the main form are not included. A dash means that an entry is removed. Back up the current settings first if needed.','gp-project-export-name'); ?></p>
   <?php if (!$changes): ?><p><?php esc_html_e('No settings would change.','gp-project-export-name'); ?></p><?php else: ?>
   <div class="ddw-gpe-table-wrap"><table class="widefat striped"><thead><tr><th><?php esc_html_e('Setting','gp-project-export-name'); ?></th><th><?php esc_html_e('Current value','gp-project-export-name'); ?></th><th><?php esc_html_e('New value','gp-project-export-name'); ?></th></tr></thead><tbody>
   <?php foreach ($changes as $row): ?><tr><?php foreach ($row as $value): ?><td><code><?php echo esc_html($value==='' ? '—' : $value); ?></code></td><?php endforeach; ?></tr><?php endforeach; ?>
   </tbody></table></div>
   <?php foreach ($candidate['projects'] as $path=>$base): if (GP::$project->by_path($path)) continue; ?><p><?php esc_html_e('Project not found on this website; its mapping will be retained:','gp-project-export-name'); ?> <code><?php echo esc_html($path); ?></code></p><?php endforeach; ?>
   <p><?php echo esc_html(self::controller($candidate)); ?></p>
   <p><?php esc_html_e('Rule check for the first 20 projects; use the project overview to check further pages.','gp-project-export-name'); ?></p>
   <?php $inspection=self::project_page('',1,$candidate); foreach ($inspection['rows'] as $row): foreach ($row['issues'] as $issue): ?><p><code><?php echo esc_html($row['path']); ?></code>: <?php echo esc_html($issue); ?></p><?php endforeach; endforeach; ?>
   <form method="post"><?php wp_nonce_field('ddw_gpe_tools'); ?>
    <input type="hidden" name="ddw_gpe_tool" value="apply"><input type="hidden" name="baseline" value="<?php echo esc_attr($baseline); ?>"><input type="hidden" name="signature" value="<?php echo esc_attr(self::signature($json,$baseline)); ?>">
    <input type="hidden" name="rules_json" value="<?php echo esc_attr($json); ?>">
    <?php submit_button(__('Confirm these changes','gp-project-export-name'),'primary','ddw-confirm',false); ?>
   </form><?php endif; ?>
   <p><a href="<?php echo esc_url(admin_url('options-general.php?page=gp-project-export-name')); ?>"><?php esc_html_e('Cancel and return to settings','gp-project-export-name'); ?></a></p></div>
  <?php endif; ?>
  <p><?php esc_html_e('Save a backup of the filename settings, or transfer them to another website. This file contains saved rules only, not translations or user accounts.','gp-project-export-name'); ?></p>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('ddw_gpe_export'); ?><input type="hidden" name="action" value="ddw_gpe_export"><?php submit_button(__('Download settings backup (.json)','gp-project-export-name'),'secondary','ddw-export',false); ?></form>
  <details><summary><?php esc_html_e('Import settings','gp-project-export-name'); ?></summary><form method="post"><?php wp_nonce_field('ddw_gpe_tools'); ?><fieldset <?php disabled(!$ready); ?>><input type="hidden" name="ddw_gpe_tool" value="import">
   <p><label for="ddw-import-file"><?php esc_html_e('Choose a settings backup downloaded from this plugin (up to 64 KB). Its contents appear below for review. Without JavaScript, paste the file’s text into the field instead.','gp-project-export-name'); ?></label></p><input type="file" id="ddw-import-file" accept="application/json,.json">
   <p><label for="ddw-import-json"><?php esc_html_e('Backup file contents (JSON text)','gp-project-export-name'); ?></label></p><textarea id="ddw-import-json" name="rules_json" rows="6" class="large-text code" maxlength="65536" required></textarea><p id="ddw-import-status" role="status"></p>
   <?php submit_button(__('Review import','gp-project-export-name'),'secondary','ddw-import',false); ?></fieldset></form></details>
  <details><summary><?php esc_html_e('Reset filename settings','gp-project-export-name'); ?></summary><form method="post"><?php wp_nonce_field('ddw_gpe_tools'); ?><fieldset <?php disabled(!$ready); ?>><input type="hidden" name="ddw_gpe_tool" value="reset">
   <p><label for="ddw-reset-scope"><?php esc_html_e('Settings to reset','gp-project-export-name'); ?></label> <select name="scope" id="ddw-reset-scope">
   <?php foreach (array('pattern'=>__('Filename pattern','gp-project-export-name'),'projects'=>__('All chosen project names','gp-project-export-name'),'project'=>__('One chosen project name','gp-project-export-name'),'variant'=>__('One custom language code','gp-project-export-name'),'variants'=>__('All custom language codes','gp-project-export-name'),'owner'=>__('Choice of naming plugin','gp-project-export-name'),'all'=>__('All filename settings','gp-project-export-name')) as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></p>
   <p><label for="ddw-reset-key"><?php esc_html_e('For a single entry only: full project path or language/set, exactly as saved above','gp-project-export-name'); ?></label> <input id="ddw-reset-key" name="mapping_key" class="regular-text" maxlength="160"></p>
   <?php submit_button(__('Review reset','gp-project-export-name'),'secondary','ddw-reset',false); ?></fieldset></form></details>
  </section>
  <?php
 }
}
