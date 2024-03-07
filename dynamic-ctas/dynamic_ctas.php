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

/*
* Add all actions that trigger based on the theme specific actions
*/
function nbrdcta_add_handlers()
{
  add_action('csco_header_after', 'nbrdcta_handle_sticky_widget', 100);
  add_filter('the_content', 'nbrdcta_handle_1_cta_body', 100);
  add_filter('the_content', 'nbrdcta_handle_2_cta_body', 100);
  add_filter('the_content', 'nbrdcta_handle_3_cta_body', 100);
  add_action('csco_footer_before', 'nbrdcta_handle_cta_pre_footer', 100);
}
// Priority for the child theme load is 99, set to 150 to execute after child theme is loaded
add_action('after_setup_theme', 'nbrdcta_add_handlers', 150);

/**
 * Set the anonymous id for the user
 */
$nbrdcta_anonymous_id = $_COOKIE['nbrdcta_anonymous_id'] ?? "";
add_action('init', 'nbrdcta_set_anonymous_id');
function nbrdcta_set_anonymous_id()
{
  global $nbrdcta_anonymous_id;
  if (empty($nbrdcta_anonymous_id)) {
    $nbrdcta_anonymous_id = bin2hex(random_bytes(16));
    // expires in 30 days
    setcookie('nbrdcta_anonymous_id', $nbrdcta_anonymous_id, time() + (86400 * 30), "/");
  }
}

/**
 * Handle adding 1st CTA to post body
 */
function nbrdcta_handle_1_cta_body($content)
{
  return nbrdcta_handle_cta_body($content, 'in_post', 0);
}

/**
 * Handle adding 2nd CTA to post body
 */
function nbrdcta_handle_2_cta_body($content)
{
  return nbrdcta_handle_cta_body($content, '2_in_post', 1);
}

/**
 * Handle adding 3rd CTA to post body
 */
function nbrdcta_handle_3_cta_body($content)
{
  return nbrdcta_handle_cta_body($content, '3_in_post', 2);
}

/*
* Handle adding CTA to post body
*/
function nbrdcta_handle_cta_body($content, $settings_key, $in_post_index)
{
  $min_num_headings_for_all_three = 6;
  $min_num_headings = 3;
  // where to insert the 3 CTAs in the post
  $insert_percentages = [25, 55, 85];

  $insert_html = nbrdcta_get_custom_html($settings_key);
  if (!$insert_html) {
    return $content;
  }
  $insert_html = <<<EOD
    <div id="$settings_key">
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
  $headings = $xpath->query('//h2');
  if ($headings->length < $min_num_headings_for_all_three) {
    $headings = $xpath->query('//h2 | //h3');
  }

  // If there are not enough headings at all, don't show any CTAs
  if ($headings->length < $min_num_headings) {
    return $content;
  }

  // If there are not enough headings for three, don't add the 1st CTA
  if ($headings->length < $min_num_headings_for_all_three && $settings_key === 'in_post') {
    return $content;
  }

  $heading_index = round($headings->length * $insert_percentages[$in_post_index] / 100) - 1;
  $heading_element = $headings->item($heading_index);
  $heading_element->parentNode->insertBefore(
    $dom->importNode($addition_doc->documentElement, true),
    $heading_element
  );

  return $dom->saveHTML();
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
  $underscored_category_name = str_replace(' ', '_', $post_category);
  $category_id = "nbrdcta_$underscored_category_name";
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
  return nbrdcta_get_ab_test_content($insert_html);
}

/**
 * Handle determining if there's an ab test and return content for arm
 */
function nbrdcta_get_ab_test_content($html)
{
  // check if the first div of provided $html has a class beginning with "AB_editorial"
  $dom = new DOMDocument;
  // The @ sign supresses warnings we are seeing. Not a permanent fix
  $succeeded = @$dom->loadHTML($html);
  if (!$succeeded) {
    return $html;
  }
  $divs = $dom->getElementsByTagName('div');
  if ($divs->length == 0) {
    return $html;
  }
  $first_div = $divs->item(0);
  $class = $first_div->getAttribute('class');
  if (strpos($class, 'AB_editorial_') === 0) {
    $test_name = substr($class, strlen('AB_editorial_'));
    $arm = nbrdcta_assign_ab_test_arm($test_name);
    if ($arm) {
      $html = nbrdcta_get_ab_test_arm_content($html, $arm);
    }
  }
  return $html;
}

/**
 * Assign an arm to the user
 * Returns "control" or "variant"
 */
function nbrdcta_assign_ab_test_arm($test_name)
{
  global $nbrdcta_anonymous_id;
  if (empty($nbrdcta_anonymous_id)) {
    return "control";
  }


  // $anonymous_id = nbrdcta_get_anonymous_id();
  $arm = nbrdcta_get_test_arm($test_name, $nbrdcta_anonymous_id);
  nbrdcta_send_analytics_event($test_name, $arm);
  
  return $arm;
}
add_action('wp_footer', function() use ($nbrdcta_anonymous_id) {
  global $nbrdcta_anonymous_id;
  nbrdcta_console_log($nbrdcta_anonymous_id);
}, 100);
function nbrdcta_console_log($message)
{
  ?>
  <script>
    console.log("<?php echo "message: $message"; ?>");
  </script>
  <?php
}

/**
 * Get/Set anonymous id from cookies
 */
// function nbrdcta_get_anonymous_id()
// {
//   $anonymous_id = $_COOKIE['nbrdcta_anonymous_id'] ?? "";
//   if (empty($anonymous_id)) {
//     $anonymous_id = bin2hex(random_bytes(16));
//     // expires in 30 days
//     setcookie('nbrdcta_anonymous_id', $anonymous_id, time() + (86400 * 30), "/");
//   }
//   return $anonymous_id;
// }

/**
 * Send an event to analytics
 */
function nbrdcta_send_analytics_event($test_name, $arm)
{
  $full_test_name = "AB_editorial_" . $test_name;
  // add script tag to send event to analytics
  add_action('wp_footer', function() use ($full_test_name, $arm) {
    nbrdcta_prepare_analytics_script($full_test_name, $arm);
  }, 100);

}

/**
 * Add script tag to send event to analytics
 */
function nbrdcta_prepare_analytics_script($full_test_name, $arm)
{
  ?>
    <script>
      // Your JavaScript code here
      console.log('Hello, world!');
      var fullTestName = "<?php echo "$full_test_name"; ?>";
      var arm = "<?php echo "$arm"; ?>";
      console.log('fullTestName: ', fullTestName);
      console.log('arm: ', arm);
      rudderanalytics.identify({ [fullTestName]: arm });
    </script>
  <?php
}

/**
 * Consitently returns the same test arm based on a hash composed from test_name and anon_id
 */
define("MAX_UNSIGNED_32BIT", 4294967295);
function nbrdcta_get_test_arm($test_name, $anon_id)
{
  $hashed_index = hexdec(hash('murmur3a', $test_name . "_" . $anon_id));
  $arms = [['control', 0.5], ['variant', 0.5]];
  $weight_sum = array_reduce($arms, function($sum, $arm) {
    return $sum + $arm[1];
  }, 0);
  $normalized_weights = [];
  array_reduce($arms, function($prev_range_end, $arm) use (&$normalized_weights, $weight_sum) {
    $amount_of_range = round(($arm[1] / $weight_sum) * MAX_UNSIGNED_32BIT);
    $end_of_range = $prev_range_end + $amount_of_range;
    if ($amount_of_range > 0) {
      $normalized_weights[] = [$arm[0], $end_of_range];
    }
    return $end_of_range;
  }, 0);

  $matching_arms = array_filter($normalized_weights, function($weight) use ($hashed_index) {
    $end_of_range = $weight[1];
    return $hashed_index <= $end_of_range;
  });

  $assigned_arm = reset($matching_arms) ?: end($normalized_weights);
  return $assigned_arm[0];
}

/**
 * Retrieve just the div with the class matching the arm ("control" or "variant")
 * Ex: $html = "<div class="AB_editorial_test_name"><div class="control">Control</div><div class="variant">Variant</div></div>" => <div class="variant">Variant</div>
 */
function nbrdcta_get_ab_test_arm_content($html, $arm)
{
  $dom = new DOMDocument;
  // The @ sign supresses warnings we are seeing. Not a permanent fix
  $succeeded = @$dom->loadHTML($html);
  if (!$succeeded) {
    return $html;
  }
  $divs = $dom->getElementsByTagName('div');
  if ($divs->length == 0) {
    return $html;
  }
  $first_div = $divs->item(0);
  $children = $first_div->childNodes;
  foreach ($children as $child) {
    if ($child->nodeType == XML_ELEMENT_NODE) {
      if ($child->getAttribute('class') == $arm) {
        return $dom->saveHTML($child);
      }
    }
  }
  return $html;
}
