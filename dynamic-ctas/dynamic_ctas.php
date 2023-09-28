<?php
/*
* Plugin Name: Neighbor Dynamic CTA
* Description: Plugin to dynamically add CTAs to the Neighbor blog in various locations on the page
* Version: 1.0
* Author: Brayden Gibbons
*/
define('NBRDCTA_PLUGIN_PATH', plugin_dir_path(__FILE__));
include(NBRDCTA_PLUGIN_PATH . 'includes/search_widget.php');

function nbrdcta_add_settings_page()
{
  if (!current_user_can('manage_options')) {
    return;
  }
  add_menu_page('Neighbor Dynamic CTAs Settings', 'Neighbor CTAs', 'manage_options', 'nbrdcta-settings', 'nbrdcta_render_plugin_settings_page');
}
add_action('admin_menu', 'nbrdcta_add_settings_page');


/*
* Render the plugin settings interface
*/
function nbrdcta_render_plugin_settings_page()
{
?>
  <h1>Neighbor CTA Plugin Settings</h1>
  <form action="options.php" method="post">
    <?php
    settings_fields('nbrdcta_plugin');
    do_settings_sections('nbrdcta_plugin');
    ?>
    <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
  </form>
<?php
}

/*
* Set up the settings section and fields
*/
function nbrdcta_register_settings()
{
  $settings_name = 'nbrdcta_plugin';
  $section_id = "nbrdcta_section_categories";
  register_setting($settings_name, $settings_name);
  add_settings_section($section_id, "Category CTAs", 'nbrdcta_settings_section_callback', $settings_name);

  $categories = get_categories();
  if (count($categories)) {
    foreach ($categories as $category) {
      $category_name = $category->name;
      $underscored_category_name = str_replace(' ', '_', $category->name);
      add_settings_field("nbrdcta_settings_field_$category_name", $category_name, nbrdcta_settings_field_callback($settings_name, $underscored_category_name), $settings_name, $section_id);
    }
  }
}
add_action('admin_init', 'nbrdcta_register_settings');

function nbrdcta_settings_section_callback()
{
}

/*
* Render the inputs for the settings fields
*/
function nbrdcta_settings_field_callback($settings_name, $category_name)
{
  $category_id = "nbrdcta_$category_name";
  $all_options = get_option($settings_name);
  $category_options = array();
  if (is_array($all_options)) {
    $category_options = $all_options[$category_id];
  }
  return function () use ($settings_name, $category_options, $category_id) {
    echo "<div style='width:100%;display:flex;flex-direction:row;justify-content:space-between'>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_sticky_header''>Sticky Header</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_sticky_header' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[sticky_header]' type='text' >" . esc_attr($category_options['sticky_header'] ?? "") . "</textarea>
      </div>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_in_post''>In Post</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_in_post' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[in_post]' type='text' >" . esc_attr($category_options['in_post'] ?? "") . "</textarea>
      </div>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_article_end''>Article End</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_article_end' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[article_end]' type='text' >" . esc_attr($category_options['article_end'] ?? "") . "</textarea>
      </div>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_above_footer''>Above Footer</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_above_footer' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[above_footer]' type='text' >" . esc_attr($category_options['above_footer'] ?? "") . "</textarea>
      </div>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_search_widget''>Search Widget</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_search_widget' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[search_widget]' type='text' >" . esc_attr($category_options['search_widget'] ?? "") . "</textarea>
      </div>
    </div>";
  };
}

/*
* Add all actions that trigger based on the theme specific actions
*/
function nbrdcta_add_handlers()
{
  add_action('csco_header_after', 'nbrdcta_handle_sticky_widget', 1);
  add_filter('the_content', 'nbrdcta_handle_cta_body', 100);
  add_action('csco_entry_content_after', 'nbrdcta_handle_cta_content_end', 1);
  add_action('csco_footer_before', 'nbrdcta_handle_cta_pre_footer', 1);
}
// Priority for the child theme load is 99, set to 150 to execute after child theme is loaded
add_action('after_setup_theme', 'nbrdcta_add_handlers', 150);

/*
* Handle adding CTA to post body
*/
function nbrdcta_handle_cta_body($content)
{
  $settings_key = 'in_post';
  $num_headings = 2;
  $insert_html = nbrdcta_get_custom_html($settings_key);
  if (!$insert_html) {
    return $content;
  }
  $insert_html = <<<EOD
    <div id="in-post">
      $insert_html
    </div>
  EOD;

  $html = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
  $dom = new DOMDocument;
  // The @ sign supresses warnings we are seeing. Not a permanent fix
  $succeeded = @$dom->loadHTML($html);
  if (!$succeeded) {
    return $content;
  }

  $addition_doc = new DOMDocument;
  // The @ sign supresses warnings we are seeing. Not a permanent fix
  $succeeded = @$addition_doc->loadHTML($insert_html);
  if (!$succeeded) {
    return $content;
  }

  $xpath = new DOMXpath($dom);
  $headings = $xpath->query('//h1 | //h2 | //h3 | //h4');
  if ($headings->length >= $num_headings) {
    $element = $headings->item($num_headings);
    $element->parentNode->insertBefore(
      $dom->importNode($addition_doc->documentElement, true),
      $element
    );
  }

  return $dom->saveHTML();
}

/*
* Handle adding CTA to content end
*/
function nbrdcta_handle_cta_content_end()
{
  $settings_key = "article_end";
  $insert_html = nbrdcta_get_custom_html($settings_key);
  if (!$insert_html) {
    return;
  }
  $container = <<<EOD
    <div id="article-end">
      $insert_html
    </div>
  EOD;
  echo $container;
}

/*
* Handle adding CTA before the footer but after the main content
*/
function nbrdcta_handle_cta_pre_footer()
{
  $settings_key = "above_footer";
  $insert_html = nbrdcta_get_custom_html($settings_key);
  if (!$insert_html) {
    return;
  }
  $container = <<<EOD
    <div id="above-footer">
      $insert_html
    </div>
  EOD;
  echo $container;
}

/*
* Handle adding a sticky widget to the top of the page (maybe)
*/
function nbrdcta_handle_sticky_widget()
{
  $settings_key = "sticky_header";
  $insert_html = nbrdcta_get_custom_html($settings_key);
  if (!$insert_html) {
    return;
  }

  $css = "";
  if (is_admin_bar_showing()) {
    $css .= "top:32px;";
  }

  $output = <<<EOD
    <div id="sticky-header" style="position:sticky;top:0;width:100%;z-index:5;height:var(--cs-header-height);background-color:white;$css">
      $insert_html
    </div>
  EOD;
  echo $output;
}

/*
* Handle getting cta options from settings
*/
function nbrdcta_get_custom_html($key)
{
  $settings_name = 'nbrdcta_plugin';
  $post_categories = get_the_category();
  if (count($post_categories) == 0) {
    return "";
  }
  $post_category = $post_categories[0]->name;
  if (empty($post_category)) {
    return "";
  }
  $category_id = "nbrdcta_$post_category";
  $all_options = get_option($settings_name);
  $category_options = array();
  if (is_array($all_options) && array_key_exists($category_id, $all_options)) {
    $category_options = $all_options[$category_id];
  }
  if (!array_key_exists($key, $category_options)) {
    return "";
  }
  $insert_html = $category_options[$key];
  if (!$insert_html) {
    return "";
  }
  return $insert_html;
}
