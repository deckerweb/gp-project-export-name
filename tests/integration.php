<?php
/** Run with WP-CLI eval-file in a disposable WordPress/GlotPress installation.
 * @package GP_Project_Export_Name
 */
if (!defined('WP_CLI') || !WP_CLI || !getenv('GPE_TEST_SITE')) throw new RuntimeException('Set GPE_TEST_SITE=1 for a disposable test installation.');
require_once WP_PLUGIN_DIR.'/gp-project-export-name/includes/class-admin.php';
require_once WP_PLUGIN_DIR.'/gp-project-export-name/includes/class-admin-tools.php';
require_once WP_PLUGIN_DIR.'/gp-project-export-name/includes/class-guide.php';
/** Verify one regression condition.
 * @param bool $condition Expected true condition.
 * @param string $label Failure explanation.
 * @return void Throws on failure.
 */
function ddw_gpe_test($condition,$label) {
 $GLOBALS['ddw_gpe_test_count']=($GLOBALS['ddw_gpe_test_count'] ?? 0)+1;
 if (!$condition) throw new RuntimeException($label);
}
$old=get_option('ddw_gpe_settings',null);$user=get_current_user_id();
try {
 wp_set_current_user(1);DDW_GPE_Admin::settings();delete_option('ddw_gpe_settings');
 $parent=GP::$project->by_path('ci-gpe') ?: GP::$project->create(array('name'=>'GPE test','slug'=>'ci-gpe'));
 $project=GP::$project->by_path('ci-gpe/example') ?: GP::$project->create(array('name'=>'GPE Example','slug'=>'example','parent_project_id'=>$parent->id));
 foreach (array(array('de','default'),array('de','formal'),array('de','internal'),array('fr','default')) as $spec) if (!GP::$translation_set->by_project_id_slug_and_locale($project->id,$spec[1],$spec[0])) GP::$translation_set->create(array('name'=>$spec[1],'slug'=>$spec[1],'locale'=>$spec[0],'project_id'=>$project->id));
 $rules=DDW_GPE_Admin::parse(array('pattern'=>'{project}-{locale}','owner'=>'ours','projects'=>array('ci-gpe/example'=>'custom'),'variants'=>array('de/formal'=>'de_DE_formal')));
 ddw_gpe_test(!is_wp_error($rules),'valid rules');update_option('ddw_gpe_settings',$rules);
 $json=DDW_GPE_Admin_Tools::export_json();
 ddw_gpe_test(DDW_GPE_Admin_Tools::decode($json)===$rules,'portable roundtrip');
 ddw_gpe_test(array_keys(json_decode($json,true))===array('plugin','schema','settings'),'no environment data');
 foreach (array('',str_repeat('x',65537),'null','[]','{"schema":1}',str_replace('"schema": 1','"schema": 2',$json),str_replace('gp-project-export-name','other-plugin',$json),str_replace('"custom"','"../escape"',$json),str_replace('"ours"','"unknown"',$json)) as $bad) ddw_gpe_test(is_wp_error(DDW_GPE_Admin_Tools::decode($bad)),'invalid transfer rejected');
 foreach (array('pattern','projects','variants','owner','all','project','variant') as $scope) {
  $reset=DDW_GPE_Admin_Tools::reset_rules($scope,$scope==='variant' ? 'de/formal' : 'ci-gpe/example');
  ddw_gpe_test(!is_wp_error($reset),'valid reset '.$scope);
  ddw_gpe_test(DDW_GPE_Names::settings()===$rules,'reset preview never writes');
 }
 ddw_gpe_test(is_wp_error(DDW_GPE_Admin_Tools::reset_rules('project','missing')),'unknown reset mapping');
 ddw_gpe_test(is_wp_error(DDW_GPE_Admin_Tools::reset_rules('variant','missing')),'unknown variant reset');
 ddw_gpe_test(is_wp_error(DDW_GPE_Admin_Tools::reset_rules('foreign')),'unknown reset scope');
 $candidate=$rules;$candidate['pattern']='{path}-{locale}';
 $transfer=wp_json_encode(array('plugin'=>'gp-project-export-name','schema'=>1,'settings'=>$candidate));
 $baseline=DDW_GPE_Admin_Tools::baseline();$signature=DDW_GPE_Admin_Tools::signature($transfer,$baseline);
 ddw_gpe_test(is_wp_error(DDW_GPE_Admin_Tools::commit($transfer,$baseline,'forged')),'invalid signature');
 ddw_gpe_test(is_wp_error(DDW_GPE_Admin_Tools::commit(str_replace('{path}','{project}',$transfer),$baseline,$signature)),'signed data cannot change');
 wp_set_current_user(0);ddw_gpe_test(is_wp_error(DDW_GPE_Admin_Tools::commit($transfer,$baseline,$signature)),'anonymous write denied');wp_set_current_user(1);
 $changed=$rules;$changed['owner']='auto';update_option('ddw_gpe_settings',$changed);
 ddw_gpe_test(is_wp_error(DDW_GPE_Admin_Tools::commit($transfer,$baseline,$signature)),'intervening edit protected');update_option('ddw_gpe_settings',$rules);
 ddw_gpe_test(DDW_GPE_Admin_Tools::commit($transfer,$baseline,$signature)===true,'reviewed candidate committed');
 ddw_gpe_test(DDW_GPE_Names::settings()===$candidate,'exact persisted rules');
 ddw_gpe_test(count(DDW_GPE_Admin_Tools::changes($rules,$candidate))===1,'exact diff');
 $project=GP::$project->get($project->id);
 $default=GP::$translation_set->by_project_id_slug_and_locale($project->id,'default','de');
 $sets=DDW_GPE_Names::sets($project->id,true);$names=array();
 foreach ($sets as $set) {
  $locale=GP_Locales::by_slug($set->locale);
  foreach (GP::$formats as $format) {
   $name=DDW_GPE_Names::filename($project,$locale,$set,$rules,$format->extension);
   ddw_gpe_test(strpos($name,'custom-')===0 && substr($name,-strlen('.'.$format->extension))==='.'. $format->extension,'all format extensions preserved');
  }
  $names[]=DDW_GPE_Names::filename($project,$locale,$set,$rules,'po');
  ddw_gpe_test(strpos(DDW_GPE_Admin_Tools::explain($project,$locale,$set,$rules),'custom')!==false,'mapping provenance');
 }
 ddw_gpe_test(count($names)===count(array_unique($names)),'variant names distinct');
 $colliding=$rules;$colliding['variants']=array('de/formal'=>'de_DE','de/internal'=>'de_DE');
 $info=DDW_GPE_Admin_Tools::inspect($project,$colliding);
 ddw_gpe_test(!empty($info['issues']),'collision inspection');
 $collision_names=array();
 foreach ($sets as $set) $collision_names[]=DDW_GPE_Names::filename($project,GP_Locales::by_slug($set->locale),$set,$colliding,'po');
 ddw_gpe_test(count($collision_names)===count(array_unique($collision_names)),'inspection still separates duplicate names');
 $path_rules=$rules;$path_rules['pattern']='{path}-{locale}';
 $explanation=DDW_GPE_Admin_Tools::explain($project,GP_Locales::by_slug('de'),$default,$path_rules);
 ddw_gpe_test(strpos($explanation,'custom')===false && strpos($explanation,'ci-gpe-example')!==false,'explain only used project source: '.$explanation);
 foreach (array('en','de') as $language) {
  $guide=DDW_GPE_Guide::content()[$language]['quickstart'];
  ddw_gpe_test(count($guide['steps'])===5 && $guide['before']==='my-plugin-stable-de.po' && $guide['after']==='my-plugin-de_DE.po','five shared guide steps and accurate mapped example');
  foreach ($guide['steps'] as $step) ddw_gpe_test(is_string($step['title']) && is_string($step['text']) && strlen($step['text'])>30,'guide step complete');
 }
 $page=DDW_GPE_Admin_Tools::project_page('ci-gpe/example',1,$rules);
 ddw_gpe_test(count($page['rows'])===1 && !$page['more'],'project search');
 ddw_gpe_test(!DDW_GPE_Admin_Tools::project_page("' OR 1=1 --",1,$rules)['rows'],'SQL search data stays literal');
 $missing=$rules;$missing['projects']['ci-gpe/missing']='absent';
 ddw_gpe_test(in_array('ci-gpe/missing',DDW_GPE_Admin_Tools::project_page('',1,$missing)['missing'],true),'stale project mapping');
 ddw_gpe_test(strpos(DDW_GPE_Admin_Tools::controller(array_merge($rules,array('owner'=>'other'))),'another')!==false,'other owner explanation');
 // Pagination remains bounded even with many projects.
 for ($i=1;$i<=21;$i++) if (!GP::$project->by_path('ci-page-'.$i)) GP::$project->create(array('name'=>'GPE page '.$i,'slug'=>'ci-page-'.$i));
 $first=DDW_GPE_Admin_Tools::project_page('ci-page-',1,$rules);$second=DDW_GPE_Admin_Tools::project_page('ci-page-',2,$rules);
 ddw_gpe_test(count($first['rows'])===20 && $first['more'] && count($second['rows'])===1 && !$second['more'],'bounded pagination');
 ddw_gpe_test(!array_intersect(array_column($first['rows'],'path'),array_column($second['rows'],'path')),'pages do not overlap');
 $before=get_num_queries();DDW_GPE_Names::filter('native.po',GP::$formats['po'],GP_Locales::by_slug('de'),$project,$sets[0]);$warm=get_num_queries();
 for($i=0;$i<1000;$i++)DDW_GPE_Names::filter('native.po',GP::$formats['po'],GP_Locales::by_slug('de'),$project,$sets[0]);
 ddw_gpe_test(get_num_queries()===$warm,'maintenance adds no download queries');
 // Exercise native route headers and compare unchanged translation content.
 update_option('ddw_gpe_settings',$rules);
 $original=GP::$original->create(array('project_id'=>$project->id,'singular'=>'GPE route fixture','status'=>'+active'));
 $default=GP::$translation_set->by_project_id_slug_and_locale($project->id,'default','de');
 GP::$translation->create(array('original_id'=>$original->id,'translation_set_id'=>$default->id,'translation_0'=>'GPE translation','status'=>'current','user_id'=>1));
 foreach (GP::$formats as $key=>$format) {
  $_GET=array('format'=>$key);unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
  $route=new GP_Route_Translation();$route->fake_request=true;
  ob_start();$route->export_translations_get($project->path,'de','default');$body=ob_get_clean();
  ddw_gpe_test(($route->headers['Content-Disposition'] ?? '')===' attachment; filename="custom-de_DE.'.$format->extension.'"','native download header');
  remove_filter('gp_export_translations_filename',array('DDW_GPE_Names','filter'),10);
  $native=new GP_Route_Translation();$native->fake_request=true;
  ob_start();$native->export_translations_get($project->path,'de','default');$native_body=ob_get_clean();
  add_filter('gp_export_translations_filename',array('DDW_GPE_Names','filter'),10,5);
  ddw_gpe_test($body===$native_body && strlen($body)>0,'native export content unchanged');
 }
 // Load unmodified public add-ons as test dependencies, without copying their source.
 $fixtures=getenv('GPE_COMPAT_FIXTURES') ?: WP_PLUGIN_DIR;
 require_once $fixtures.'/gp-use-slug-for-downloads/gp-use-slug-for-downloads.php';
 require_once $fixtures.'/gp-download-name/gp-download-name.php';
 $download_name=new GP_Download_Name();
 $foreign=new GP_Use_Slug_for_Downloads();$auto=$rules;$auto['owner']='auto';update_option('ddw_gpe_settings',$auto);
 ddw_gpe_test(DDW_GPE_Names::filter('foreign.po',GP::$formats['po'],GP_Locales::by_slug('de'),$project,$default)==='foreign.po','recognized foreign ownership');
 ddw_gpe_test(count(DDW_GPE_Names::competitors())===2,'both known naming plugins detected');
 update_option('ddw_gpe_settings',$rules);DDW_GPE_Names::ownership();
 ddw_gpe_test(!has_filter('gp_export_translations_filename',array($foreign,'gp_export_translations_filename')),'explicit ownership preserves other plugin activation');
 ddw_gpe_test(!has_filter('gp_export_translations_filename',array($download_name,'gp_export_translations_filename')) && has_action('admin_menu',array($download_name,'admin_menu'))!==false,'only filename callback removed');
 ddw_gpe_test(count(DDW_GPE_Names::recognized_competitors())===2 && strpos(DDW_GPE_Admin_Tools::controller($auto),'another')!==false,'recognized plugins retained for unsaved ownership preview');
 define('GP_BULK_DOWNLOAD_TRANSLATIONS_FORMAT_PO',true);define('GP_BULK_DOWNLOAD_TRANSLATIONS_FORMAT_MO',true);define('GP_BULK_DOWNLOAD_TRANSLATIONS_FORMAT_PHP',true);
 require_once $fixtures.'/gp-bulk-download-translations/gp-bulk-download-translations.php';
 $_GET=array();
 $bulk=new GP_Bulk_Download_Translations();$dir=get_temp_dir().'gpe-ci-'.wp_generate_uuid4();wp_mkdir_p($dir);
 try {
  $files=$bulk->generate_export_files($project,$dir);
  ddw_gpe_test(count($files)===count($sets)*3 && count($files)===count(array_unique($files)),'external bulk formats remain unique');
  foreach ($files as $file) ddw_gpe_test(is_file($dir.'/'.$file),'external bulk output exists');
 } finally {
  foreach (glob($dir.'/*') ?: array() as $file) unlink($file);rmdir($dir);
 }
 echo 'PASS '.$GLOBALS['ddw_gpe_test_count'].' checks; WP '.get_bloginfo('version').' / PHP '.PHP_VERSION.' / GP '.GP_VERSION."\n";
} finally {
 if ($old===null) delete_option('ddw_gpe_settings');else update_option('ddw_gpe_settings',$old);wp_set_current_user($user);
}
