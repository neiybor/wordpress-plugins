<?php
/*
* Plugin Name: Neighbor Dynamic CTA
* Description: Plugin to dynamically add CTAs to the Neighbor blog in various locations on the page
* Version: 1.1
* Author: Brayden Gibbons
*/

/**
 * Page content related imports
 */
define('NBRDCTA_PLUGIN_PATH', plugin_dir_path(__FILE__));
include(NBRDCTA_PLUGIN_PATH . 'includes/ab_testing.php');
include(NBRDCTA_PLUGIN_PATH . 'includes/search_widget.php');
include(NBRDCTA_PLUGIN_PATH . 'includes/post_ctas.php');

/**
 * Admin page related code in this file
 */

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
    $category_options = $all_options[$category_id] ?? array();
  }
  return function () use ($settings_name, $category_options, $category_id) {
    echo "<div style='width:100%;display:flex;flex-direction:row;justify-content:space-between'>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_sticky_header'>Sticky Header</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_sticky_header' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[sticky_header]' type='text' >" . esc_attr($category_options['sticky_header'] ?? "") . "</textarea>
      </div>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_in_post'>1st In Post</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_in_post' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[in_post]' type='text' >" . esc_attr($category_options['in_post'] ?? "") . "</textarea>
      </div>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_2_in_post'>2nd In Post</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_2_in_post' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[2_in_post]' type='text' >" . esc_attr($category_options['2_in_post'] ?? "") . "</textarea>
      </div>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_3_in_post'>3rd In Post</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_3_in_post' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[3_in_post]' type='text' >" . esc_attr($category_options['3_in_post'] ?? "") . "</textarea>
      </div>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_above_footer'>Above Footer</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_above_footer' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[above_footer]' type='text' >" . esc_attr($category_options['above_footer'] ?? "") . "</textarea>
      </div>
      <div style='display:flex;flex-direction:column'>
        <label for='" . "$category_id" . "_field_search_widget'>Search Widget</label>
        <textarea style='resize:both' rows='5' id='" . "$category_id" . "_field_search_widget' name='" . "$settings_name" . "[" . "$category_id" . "]" . "[search_widget]' type='text' >" . esc_attr($category_options['search_widget'] ?? "") . "</textarea>
      </div>
    </div>";
  };
}
