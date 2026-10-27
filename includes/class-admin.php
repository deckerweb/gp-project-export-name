<?php
/** Website settings, validated previews and administrative status.
 * @package GP_Project_Export_Name
 */
defined('ABSPATH') || exit;
/** Render the compact settings UI; all saves use the native Settings API. */
final class DDW_GPE_Admin {
 /** Register administrator-only hooks.
  * @return void
  */
 public static function register() {
  require_once __DIR__.'/class-admin-tools.php';
  require_once __DIR__.'/class-guide.php';
  DDW_GPE_Admin_Tools::register();
  add_action('admin_menu',array(__CLASS__,'menu'));
  add_action('admin_init',array(__CLASS__,'settings'));
  add_action('admin_enqueue_scripts',array(__CLASS__,'assets'));
  add_action('wp_ajax_ddw_gpe_preview',array(__CLASS__,'preview'));
  add_filter('plugin_action_links_'.plugin_basename(DDW_GPE_FILE),array(__CLASS__,'links'));
 }
 /** Register the site settings screen.
  * @return void
  */
 public static function menu() {
  add_options_page('GP Project Export Name','GP Project Export Name','manage_options','gp-project-export-name',array(__CLASS__,'page'));
 }
 /** Register the option with validation at the persistence boundary.
  * @return void
  */
 public static function settings() {
  register_setting('ddw_gpe','ddw_gpe_settings',array('type'=>'array','sanitize_callback'=>array(__CLASS__,'sanitize'),'default'=>array()));
 }
 /** Add a settings action only for administrators of the current website.
  * @param array $links Existing action links.
  * @return array Action links.
  */
 public static function links($links) {
  if (current_user_can('manage_options') && !is_network_admin()) array_unshift($links,'<a href="'.esc_url(admin_url('options-general.php?page=gp-project-export-name')).'">'.esc_html__('Settings','gp-project-export-name').'</a>');
  return $links;
 }
 /** Validate mapping lines without silently changing identifiers.
  * @param mixed $text Lines using key = value.
  * @param bool $variants Whether keys use locale/set rather than project paths.
  * @return array|WP_Error Valid mappings or a localized error.
  */
 public static function map($text,$variants) {
  if (!is_string($text) || strlen($text)>16000) return new WP_Error('rules',__('The entries could not be read. Use one project/path = filename-start or language/set = language-code per line.','gp-project-export-name'));
  $result=array();
  foreach (preg_split('/\r\n|\r|\n/',$text) as $line) {
   if (trim($line)==='') continue;
   $pair=array_map('trim',explode('=',$line,2));
   if (count($pair)!==2 || !preg_match('/\A[A-Za-z0-9][A-Za-z0-9_\/-]*\z/D',$pair[0]) || strpos($pair[0],'//')!==false || substr($pair[0],-1)==='/' || !DDW_GPE_Names::component($pair[1]) || isset($result[$pair[0]]) || ($variants && count(explode('/',$pair[0]))!==2)) return new WP_Error('rules',__('Check the entries: each project path or language/set may occur only once. Use letters, numbers, underscores and hyphens for the value after =.','gp-project-export-name'));
   $result[$pair[0]]=$pair[1];
   if (count($result)>100) return new WP_Error('rules',__('Use at most 100 entries per field.','gp-project-export-name'));
  }
  return $result;
 }
 /** Normalize a submitted form for preview or persistence, with shared validation.
  * @param mixed $input Unslashed form fields.
  * @return array|WP_Error Normalized settings or a localized error.
  */
 public static function parse($input) {
  if (!is_array($input) || !DDW_GPE_Names::valid_pattern($input['pattern'] ?? null) || !in_array($input['owner'] ?? '',array('auto','ours','other'),true)) return new WP_Error('rules',__('Check the filename pattern: keep {locale}, use only the listed placeholders, letters, numbers, underscores and hyphens, and leave out .po or other file extensions.','gp-project-export-name'));
  $projects=self::map($input['projects_text'] ?? self::lines(is_array($input['projects'] ?? null) ? $input['projects'] : array()),false);
  $variants=self::map($input['variants_text'] ?? self::lines(is_array($input['variants'] ?? null) ? $input['variants'] : array()),true);
  if (is_wp_error($projects)) return $projects;
  if (is_wp_error($variants)) return $variants;
  return array('pattern'=>$input['pattern'],'owner'=>$input['owner'],'projects'=>$projects,'variants'=>$variants);
 }
 /** Reject writes without a usable dependency, including forged Settings API POSTs.
  * @param mixed $input Submitted option value.
  * @return array Valid new settings or unchanged previous settings.
  */
 public static function sanitize($input) {
  $result=DDW_GPE_Names::available() ? self::parse($input) : new WP_Error('dependency',__('To change download names, activate GlotPress 4.0 or newer and choose a permalink structure other than Plain under Settings → Permalinks. Existing rules stay saved.','gp-project-export-name'));
  if (is_wp_error($result)) {
   add_settings_error('ddw_gpe_settings','invalid',$result->get_error_message());
   return DDW_GPE_Names::settings();
  }
  return $result;
 }
 /** Load local assets only on this screen.
  * @param string $hook Current screen suffix.
  * @return void
  */
 public static function assets($hook) {
  if ($hook!=='settings_page_gp-project-export-name') return;
  wp_enqueue_style('ddw-gpe',plugins_url('assets/admin.css',DDW_GPE_FILE),array(),DDW_GPE_VERSION);
  wp_enqueue_script('ddw-gpe',plugins_url('assets/admin.js',DDW_GPE_FILE),array(),DDW_GPE_VERSION,true);
  wp_localize_script('ddw-gpe','ddwGpe',array('url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('ddw_gpe_preview'),'labels'=>array('map'=>__('Apply this name to the draft','gp-project-export-name'),'preview'=>__('Preview','gp-project-export-name'),'missing'=>__('These saved project paths were not found on this website:','gp-project-export-name'),'empty'=>__('No projects found.','gp-project-export-name'),'replace'=>__('Replace this project’s existing name in the unsaved draft? Save Changes is still required.','gp-project-export-name'),'base'=>__('Project name in filenames','gp-project-export-name'),'invalid'=>__('Use a name containing only letters, numbers, underscores and hyphens (maximum 160 characters).','gp-project-export-name'),'file'=>__('The file could not be read or exceeds 64 KB.','gp-project-export-name'),'changed'=>__('The draft changed. Choose Find projects and check names again to refresh the examples.','gp-project-export-name')),'error'=>__('The preview could not be loaded. Please try again.','gp-project-export-name')));
 }
 /** Check preview authorization and validate all unsaved settings before querying.
  * @return void Sends a JSON response and terminates.
  */
 public static function preview() {
  if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>__('Permission denied.','gp-project-export-name')),403);
  check_ajax_referer('ddw_gpe_preview');
  if (!DDW_GPE_Names::available()) wp_send_json_error(array('message'=>__('GlotPress is not ready. Activate GlotPress and check its permalink requirements.','gp-project-export-name')),409);
  $input=isset($_POST['ddw_gpe_settings']) ? wp_unslash($_POST['ddw_gpe_settings']) : array();
  $path=isset($_POST['project_path']) && is_string($_POST['project_path']) ? sanitize_text_field(wp_unslash($_POST['project_path'])) : '';
  $rules=self::parse($input);
  if (is_wp_error($rules)) wp_send_json_error(array('message'=>$rules->get_error_message()),400);
  $project=GP::$project->by_path($path);
  if (!$project) wp_send_json_error(array('message'=>__('Project not found. Use the project search, or enter its full GlotPress path without the website address.','gp-project-export-name')),404);
  $rows=array(); $raw=array(); $duplicates=false;
  foreach (DDW_GPE_Names::sets($project->id,true) as $set) {
   $locale=GP_Locales::by_slug($set->locale);
   if (!$locale) continue;
   $base=DDW_GPE_Names::base($project,$locale,$set,$rules);
   if (isset($raw[$base])) $duplicates=true;
   $raw[$base]=true;
   $name=DDW_GPE_Names::filename($project,$locale,$set,$rules,'po');
   $native=is_string($locale->native_name ?? null) ? $locale->native_name : $set->locale;
   $set_name=$set->slug==='default' ? __('Default translation set','gp-project-export-name') : (is_string($set->name ?? null) ? $set->name : $set->slug);
   $rows[]=array('set'=>$set->locale.'/'.$set->slug,'set_label'=>$native.' — '.$set_name.' ('.$set->locale.'/'.$set->slug.')','name'=>$name ?: __('Original GlotPress filename','gp-project-export-name'),'explanation'=>DDW_GPE_Admin_Tools::explain($project,$locale,$set,$rules));
  }
  $message=$duplicates ? __('Some names would be identical. A --set- number is added so different translation sets keep distinct filenames within this project.','gp-project-export-name') : __('Examples use .po. An MO export, for example, keeps .mo instead. The preview does not save or download anything.','gp-project-export-name');
  wp_send_json_success(array('rows'=>$rows,'message'=>DDW_GPE_Admin_Tools::controller($rules).' '.$message));
 }
 /** Convert validated mappings back to an editable text representation.
  * @param array $map Stored mappings.
  * @return string Mapping lines.
  */
 private static function lines($map) {
  $lines=array(); foreach ($map as $key=>$value) { if (!is_string($key) || !is_string($value)) return '!invalid!'; $lines[]=$key.' = '.$value; }
  return implode("\n",$lines);
 }
 /** Resolve local documentation in the active administrative language.
  * @param string $type Fixed guide or changelog basename.
  * @return string Local document URL.
  */
 public static function document($type) {
  $suffix=strpos(determine_locale(),'de_')===0 ? '-de' : '';
  return plugins_url('docs/'.$type.$suffix.'.html',DDW_GPE_FILE);
 }
 /** Render local release history using the categorized deckerweb layout.
  * JSON is local package data; every value is escaped before HTML output.
  * @return void Prints version/date headings and one category badge per entry.
  */
 public static function history() {
  $content=DDW_GPE_Guide::content();
  $lang=strpos(determine_locale(),'de_')===0 ? 'de' : 'en';
  $categories=array('New'=>'new','Neu'=>'new','Improved'=>'improved','Verbessert'=>'improved','Fixed'=>'fixed','Behoben'=>'fixed','Misc'=>'misc','Sonstiges'=>'misc');
  foreach (($content[$lang]['history'] ?? array()) as $release) {
   if (!is_array($release) || !isset($release[0],$release[1],$release[2]) || !is_string($release[0]) || !is_string($release[1]) || !is_array($release[2])) continue;
   $valid_date=(bool)preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D',$release[1]);
   $date=$valid_date ? wp_date(get_option('date_format'),strtotime($release[1].' 12:00:00 UTC'),new DateTimeZone('UTC')) : $release[1];
   echo '<section class="ddw-gpe-history-release"><div class="ddw-gpe-history-version"><h3>'.esc_html($release[0]).'</h3>';
   if ($valid_date) echo '<time datetime="'.esc_attr($release[1]).'">'.esc_html($date).'</time>';
   else echo '<span>'.esc_html($date).'</span>';
   echo '</div><ul class="ddw-gpe-history-entries">';
   foreach ($release[2] as $category=>$items) {
    if (!is_string($category) || !isset($categories[$category]) || !is_array($items)) continue;
    foreach ($items as $item) {
     if (!is_string($item)) continue;
     echo '<li><span class="ddw-gpe-history-badge ddw-gpe-history-'.esc_attr($categories[$category]).'">'.esc_html($category).'</span><span>'.esc_html($item).'</span></li>';
    }
   }
   echo '</ul></section>';
  }
 }
 /** Render settings with labels, preview and local documentation fallback.
  * @return void
  */
 public static function page() {
  if (!current_user_can('manage_options')) return;
  DDW_GPE_Admin_Tools::process();
  $s=DDW_GPE_Names::settings(); $ready=DDW_GPE_Names::available();
  ?>
  <div class="wrap ddw-gpe">
   <header class="ddw-gpe-header" id="ddw-gpe-header">
    <picture class="ddw-gpe-icon"><source srcset="<?php echo esc_url(plugins_url('assets/icon.svg',DDW_GPE_FILE)); ?>" type="image/svg+xml"><img src="<?php echo esc_url(plugins_url('assets/icon-128.png',DDW_GPE_FILE)); ?>" width="64" height="64" alt=""></picture>
    <div class="ddw-gpe-header-copy"><div class="ddw-gpe-heading"><h1>GP Project Export Name</h1><span class="ddw-gpe-version"><?php echo esc_html(DDW_GPE_VERSION); ?></span></div>
    <p class="ddw-gpe-slogan"><?php esc_html_e('Practical filenames for every translation.','gp-project-export-name'); ?></p></div>
   </header>
   <hr class="wp-header-end" id="ddw-gpe-header-end">
   <aside class="ddw-gpe-helpbar" aria-label="<?php esc_attr_e('Help with download names','gp-project-export-name'); ?>"><span><?php esc_html_e('A suitable filename in a few steps.','gp-project-export-name'); ?></span><a class="button" id="ddw-guide-link" href="<?php echo esc_url(self::document('quickstart')); ?>" data-ddw-dialog="ddw-guide" aria-haspopup="dialog" aria-controls="ddw-guide"><?php esc_html_e('Quick guide','gp-project-export-name'); ?></a></aside>

   <?php if (!$ready): ?><div class="notice notice-warning inline"><p><?php esc_html_e('To change download names, activate GlotPress 4.0 or newer and choose a permalink structure other than Plain under Settings → Permalinks. Existing rules stay saved.','gp-project-export-name'); ?></p></div><?php endif; ?>
   <?php if (DDW_GPE_Names::recognized_competitors()): ?><div class="notice notice-warning inline"><p><?php esc_html_e('Another plugin also changes download names. Under Which plugin names the files?, choose whose rules should apply.','gp-project-export-name'); ?></p></div><?php endif; ?>
   <?php if (defined('GP_PROJECT_EXPORT_LOCALE')): ?><div class="notice notice-info inline"><p><?php esc_html_e('A fixed language code is set with GP_PROJECT_EXPORT_LOCALE. If valid, it replaces the exported language code. Variant rules still apply.','gp-project-export-name'); ?></p></div><?php endif; ?>
   <p><?php esc_html_e('These settings apply only to this website. Deactivating or deleting the plugin keeps them for later use.','gp-project-export-name'); ?></p>
   <section class="ddw-gpe-purpose" aria-labelledby="ddw-purpose-title"><h2 id="ddw-purpose-title"><?php esc_html_e('Recognizable downloads, less manual renaming','gp-project-export-name'); ?></h2><p><?php esc_html_e('Choose the project name and let the exported language complete the filename, for example my-plugin-de_DE.po. These settings change names for future GlotPress exports. They do not translate texts or install language files.','gp-project-export-name'); ?></p></section>
   <form action="options.php" method="post" id="ddw-gpe-form">
    <?php settings_fields('ddw_gpe'); ?>
    <fieldset <?php disabled(!$ready); ?>>
     <h2><?php esc_html_e('Choose how filenames are built','gp-project-export-name'); ?></h2>
     <label for="ddw-pattern"><strong><?php esc_html_e('Filename pattern','gp-project-export-name'); ?></strong></label>
     <input class="regular-text" id="ddw-pattern" name="ddw_gpe_settings[pattern]" value="<?php echo esc_attr($s['pattern']); ?>" aria-describedby="ddw-pattern-help" required maxlength="160">
     <p id="ddw-pattern-help"><?php esc_html_e('The recommended pattern builds names such as my-plugin-de_DE.po. Choose a preset below or edit the placeholders. Always keep {locale}; do not type a file extension.','gp-project-export-name'); ?></p>
     <details class="ddw-gpe-placeholders"><summary><?php esc_html_e('What do the placeholders mean?','gp-project-export-name'); ?></summary><dl><dt><code>{project}</code></dt><dd><?php esc_html_e('The project name chosen below, otherwise the last part of its GlotPress path.','gp-project-export-name'); ?></dd><dt><code>{locale}</code></dt><dd><?php esc_html_e('The exported language code, including any variant, for example de_DE or de_DE_formal.','gp-project-export-name'); ?></dd><dt><code>{path}</code></dt><dd><?php esc_html_e('The full project path with hyphens instead of slashes. A chosen {project} name does not replace {path}.','gp-project-export-name'); ?></dd><dt><code>{set}</code></dt><dd><?php esc_html_e('The translation set’s short name, for example default or formal.','gp-project-export-name'); ?></dd></dl></details>
     <p class="ddw-presets"><button type="button" class="button" data-pattern="{project}-{locale}"><?php esc_html_e('Recommended: project and language','gp-project-export-name'); ?></button> <button type="button" class="button" data-pattern="{path}-{locale}"><?php esc_html_e('Project path','gp-project-export-name'); ?></button> <button type="button" class="button" data-pattern="{project}-{locale}-{set}"><?php esc_html_e('Also include the translation set','gp-project-export-name'); ?></button></p>
     <h2><?php esc_html_e('Choose a name for each project (optional)','gp-project-export-name'); ?></h2>
     <label for="ddw-projects"><?php esc_html_e('Usually, choose a project in the search below. For direct entry, use one full project path = desired name per line, for example my-plugin/stable = my-plugin. This name replaces {project}. Without an entry, the last part of the project path is used, for example stable.','gp-project-export-name'); ?></label>
     <textarea id="ddw-projects" name="ddw_gpe_settings[projects_text]" rows="5" class="large-text code" placeholder="my-plugin/stable = my-plugin"><?php echo esc_textarea(self::lines($s['projects'])); ?></textarea>
     <h2><?php esc_html_e('Language variants (optional)','gp-project-export-name'); ?></h2>
     <p><?php esc_html_e('GlotPress can hold several translation sets for one language, for example German informal and formal. A set named formal gets _formal automatically; other extra sets get -x- and their short name. Usually, no entry is needed here.','gp-project-export-name'); ?></p>
     <label for="ddw-variants"><?php esc_html_e('Only for a required custom code: one language/set = full language code per line, for example de/formal = de_DE_formal. These entries change filenames, not the languages installed in WordPress.','gp-project-export-name'); ?></label>
     <textarea id="ddw-variants" name="ddw_gpe_settings[variants_text]" rows="4" class="large-text code" placeholder="de/formal = de_DE_formal"><?php echo esc_textarea(self::lines($s['variants'])); ?></textarea>
     <h2><?php esc_html_e('Which plugin names the files?','gp-project-export-name'); ?></h2>
     <label for="ddw-owner"><?php esc_html_e('Choose whose filename rules apply','gp-project-export-name'); ?></label>
     <select id="ddw-owner" name="ddw_gpe_settings[owner]">
     <?php foreach (array('auto'=>__('Automatic: this plugin unless another is detected','gp-project-export-name'),'ours'=>__('Use GP Project Export Name','gp-project-export-name'),'other'=>__('Use GlotPress or another naming plugin','gp-project-export-name')) as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected($s['owner'],$value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
     </select>
     <?php submit_button(); ?>
    </fieldset>
   </form>
   <section class="ddw-gpe-projects" aria-labelledby="ddw-project-title"><h2 id="ddw-project-title"><?php esc_html_e('Find projects and choose their filenames','gp-project-export-name'); ?></h2>
    <p><?php esc_html_e('Find an existing GlotPress project. The name field controls {project}; Apply this name to the draft adds it to the form above. Preview checks all translation sets without saving. Finish with Save Changes.','gp-project-export-name'); ?></p>
    <label for="ddw-project-search"><?php esc_html_e('Project name or path','gp-project-export-name'); ?></label> <input id="ddw-project-search" class="regular-text" type="search" maxlength="160">
    <button type="button" class="button" id="ddw-project-search-button" <?php disabled(!$ready); ?>><?php esc_html_e('Find projects and check names','gp-project-export-name'); ?></button>
    <p role="status" id="ddw-project-status" aria-live="polite"></p><div id="ddw-project-missing"></div>
    <div class="ddw-gpe-table-wrap"><table class="widefat striped" id="ddw-project-table" hidden><thead><tr><th><?php esc_html_e('Project path','gp-project-export-name'); ?></th><th><?php esc_html_e('Project name in filenames','gp-project-export-name'); ?></th><th><?php esc_html_e('Filename example and notes','gp-project-export-name'); ?></th><th><?php esc_html_e('Actions','gp-project-export-name'); ?></th></tr></thead><tbody></tbody></table></div>
    <p><button type="button" class="button" id="ddw-project-prev" hidden><?php esc_html_e('Previous page','gp-project-export-name'); ?></button> <button type="button" class="button" id="ddw-project-next" hidden><?php esc_html_e('Next page','gp-project-export-name'); ?></button></p>
    <noscript><p><?php esc_html_e('Project search needs JavaScript. Project mappings can also be edited in the text field above.','gp-project-export-name'); ?></p></noscript>
   </section>
   <section class="ddw-preview" aria-labelledby="ddw-preview-title"><h2 id="ddw-preview-title"><?php esc_html_e('Check filenames before saving','gp-project-export-name'); ?></h2>
    <label for="ddw-preview-path"><?php esc_html_e('Full project path, without the website address','gp-project-export-name'); ?></label>
    <input type="text" id="ddw-preview-path" class="regular-text" placeholder="my-plugin/stable">
    <button type="button" class="button" id="ddw-preview-button" <?php disabled(!$ready); ?>><?php esc_html_e('Preview filenames','gp-project-export-name'); ?></button>
    <p id="ddw-preview-status" role="status" aria-live="polite"></p><ul id="ddw-preview-results"></ul>
    <noscript><p><?php esc_html_e('The preview needs JavaScript. Saving settings works without JavaScript.','gp-project-export-name'); ?></p></noscript>
   </section>
   <?php DDW_GPE_Admin_Tools::render(); ?>
   <footer class="ddw-gpe-footer" aria-label="<?php esc_attr_e('About GP Project Export Name','gp-project-export-name'); ?>">
    <div class="ddw-gpe-footer-identity"><div class="ddw-gpe-footer-links"><strong>GP Project Export Name</strong><span><?php echo esc_html(DDW_GPE_VERSION); ?></span>
     <a href="<?php echo esc_url(self::document('guide')); ?>"><?php esc_html_e('Documentation','gp-project-export-name'); ?></a>
     <a href="<?php echo esc_url(self::document('changelog')); ?>" id="ddw-history-link" data-ddw-dialog="ddw-history" aria-haspopup="dialog" aria-controls="ddw-history"><?php esc_html_e('Changelog','gp-project-export-name'); ?></a>
    </div><p><?php esc_html_e('Practical filenames for every translation.','gp-project-export-name'); ?></p></div>
    <div class="ddw-gpe-footer-meta"><span>© 2016–2026 <a href="https://github.com/deckerweb" target="_blank" rel="noopener noreferrer">David Decker – DECKERWEB</a></span><a href="<?php echo esc_url(DDW_GPE_REPO); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Plugin repository','gp-project-export-name'); ?></a></div>
   </footer>
   <dialog id="ddw-guide" class="ddw-gpe-history ddw-gpe-guide" hidden aria-labelledby="ddw-guide-title">
    <div class="ddw-gpe-history-heading"><h2 id="ddw-guide-title" tabindex="-1" autofocus><?php $guide=DDW_GPE_Guide::data(); echo esc_html(is_string($guide['title'] ?? null) ? $guide['title'] : __('Quick guide','gp-project-export-name')); ?></h2><form method="dialog" class="ddw-gpe-history-close"><button type="submit" class="button"><?php esc_html_e('Close','gp-project-export-name'); ?></button></form></div>
    <div class="ddw-gpe-history-content ddw-gpe-guide-content" tabindex="0"><?php DDW_GPE_Guide::render(); ?></div>
   </dialog>
   <dialog id="ddw-history" class="ddw-gpe-history" hidden aria-labelledby="ddw-history-title">
    <div class="ddw-gpe-history-heading"><h2 id="ddw-history-title"><?php esc_html_e('Changelog','gp-project-export-name'); ?></h2>
     <form method="dialog" class="ddw-gpe-history-close"><button type="submit" class="button"><?php esc_html_e('Close','gp-project-export-name'); ?></button></form>
    </div>
    <div class="ddw-gpe-history-content" tabindex="0"><?php self::history(); ?></div>
   </dialog>
  </div><?php
 }
}
