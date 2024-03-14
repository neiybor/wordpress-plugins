<?php
/**
 * This file contains the code for adding CTAs to posts and pages
 */

/**
 * Add all actions that trigger based on the theme specific actions
 */
function nbrdcta_add_handlers()
{
  add_action('csco_header_after', 'nbrdcta_handle_sticky_widget', 100);
  add_filter('the_content', 'nbrdcta_handle_cta_body', 100);
  add_action('csco_footer_before', 'nbrdcta_handle_cta_pre_footer', 100);
}
// Priority for the child theme load is 99, set to 150 to execute after child theme is loaded
add_action('after_setup_theme', 'nbrdcta_add_handlers', 150);

/**
 * Handle adding the 3 in-post CTAs to post body
 */
function nbrdcta_handle_cta_body($content)
{
  $min_num_headings_for_all_three = 6;
  $min_num_headings = 3;
  // percentage of where to insert the 3 CTAs in the post
  $in_post_ctas = [['in_post', 25], ['2_in_post', 55], ['3_in_post', 85]];

  $html = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
  $dom = new DOMDocument;
  // The @ sign supresses warnings we are seeing. Not a permanent fix
  $succeeded = @$dom->loadHTML($html);
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

  // loop over $in_post_ctas in reverse order so inserted content doesn't affect the index
  for ($i = count($in_post_ctas) - 1; $i >= 0; $i--) {
    $settings_key = $in_post_ctas[$i][0];
    $percentage = $in_post_ctas[$i][1];
    // If there are not enough headings for three, don't add the 1st CTA
    if ($headings->length < $min_num_headings_for_all_three && $settings_key === 'in_post') {
      continue;
    }
    $custom_html = nbrdcta_get_custom_html($settings_key);
    $insert_html = nbrdcta_get_ab_test_content($custom_html);
    if (!$insert_html) {
      continue;
    }
    $insert_html = <<<EOD
      <div id="$settings_key">
        $insert_html
      </div>
    EOD;

    $addition_doc = new DOMDocument;
    // The @ sign supresses warnings we are seeing. Not a permanent fix
    $succeeded = @$addition_doc->loadHTML($insert_html);
    if (!$succeeded) {
      continue;
    }

    $heading_index = round($headings->length * $percentage / 100) - 1;
    $heading_element = $headings->item($heading_index);
    $heading_element->parentNode->insertBefore(
      $dom->importNode($addition_doc->documentElement, true),
      $heading_element
    );
  }

  return $dom->saveHTML();
}

/*
* Handle adding CTA before the footer but after the main content
*/
function nbrdcta_handle_cta_pre_footer()
{
  $settings_key = "above_footer";
  $custom_html = nbrdcta_get_custom_html($settings_key);
  $insert_html = nbrdcta_get_ab_test_content($custom_html);
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
  $custom_html = nbrdcta_get_custom_html($settings_key);
  $insert_html = nbrdcta_get_ab_test_content($custom_html);
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
  return $insert_html;
}
