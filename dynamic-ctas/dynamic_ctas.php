<?php
/*
* Plugin Name: Neighbor Dynamic CTA
* Description: Plugin to dynamically add CTAs to the Neighbor blog in various locations on the page
* Version: 1.0
* Author: Brayden Gibbons
*/
define('NBRDCTA_PLUGIN_PATH', plugin_dir_path(__FILE__));
include(NBRDCTA_PLUGIN_PATH . 'includes/landing_page_configs.php');
include(NBRDCTA_PLUGIN_PATH . 'includes/search_widget.php');

/*
* Add all actions that trigger based on the theme specific actions
*/
function nbrdcta_add_handlers()
{
  // add_action('csco_header_after', 'nbrdcta_handle_sticky_widget', 1);
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
  $num_headings = 2;
  $post_categories = get_the_category();
  if (count($post_categories) == 0) {
    return;
  }
  $post_category = $post_categories[0]->name;
  if (empty($post_category)) {
    return;
  }

  $html = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
  $dom = new DOMDocument;
  $dom->loadHTML($html);

  $addition_doc = new DOMDocument;
  $storage_type = nbrdcta_Landing_Configs::get_category_storage_type($post_category);
  $title = nbrdcta_Landing_Configs::get_type_title($storage_type);
  $dlp_title = nbrdcta_Landing_Configs::get_type_title_dlp($storage_type);
  $addition_doc->loadHTML(<<<EOD
    <div style="width:100%;color: white;display:flex;flex-direction:row;justify-content:space-between;border-radius:24px;overflow:hidden;box-shadow:0 6px 2px -2px darkgray;background:linear-gradient(90deg, rgba(112,210,255,1) 14%, rgba(34,113,233,1) 84%, rgba(0,212,255,1) 100%);">
      <div style="display:flex;flex-direction:column;width:100%;align-items:center;justify-content:center">
        <p style="text-align:center;font-size:x-large;font-weight:600;letter-spacing:.05em;">Search for $title on Neighbor</p>
        <a href="https://www.neighbor.com/$dlp_title">
          <button style="background-color:white;color:black;box-shadow:0 1px 2px 0 rgba(0, 0, 0, 0.16);border:1px solid #EBECF0;">Find Storage</button>
        </a>
      </div>
      <img src="https://d9lvjui2ux1xa.cloudfront.net/img/home-screen/webp/san-francisco-300-80.webp" class="pk-pin-it-ready lazyautosizes pk-lazyloaded" data-pk-sizes="auto" data-pk-src="https://d9lvjui2ux1xa.cloudfront.net/img/home-screen/webp/san-francisco-300-80.webp" sizes="null">
    </div>
  EOD);

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
  $post_categories = get_the_category();
  if (count($post_categories) == 0) {
    return;
  }
  $post_category = $post_categories[0]->name;
  if (empty($post_category)) {
    return;
  }
  $storage_type = nbrdcta_Landing_Configs::get_category_storage_type($post_category);
  $title = nbrdcta_Landing_Configs::get_type_title_upper($storage_type);
  $output = <<<EOD
    <h3 style="margin-top:64px">Want to Learn More About $title?</h3>
    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore 
    et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip 
    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. 
    Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
  EOD;
  echo $output;
}

/*
* Handle adding CTA before the footer but after the main content
*/
function nbrdcta_handle_cta_pre_footer()
{
  $post_categories = get_the_category();
  if (count($post_categories) == 0) {
    return;
  }
  $post_category = $post_categories[0]->name;
  if (empty($post_category)) {
    return;
  }
  $storage_type = nbrdcta_Landing_Configs::get_category_storage_type($post_category);
  $storage_text = nbrdcta_Landing_Configs::get_type_title($storage_type);
  $output = <<<EOD
    <div style="background-color:gainsboro;display:flex;flex-direction:row;justify-content:space-between;margin:10px">
      <div style="display:flex;flex-direction:column;width:100%;align-items:center;justify-content:center">
        <p style="text-align:center">Search for $storage_text on Neighbor</p>
        <a href="https://www.neighbor.com/$storage_type-near-me">
          <button>Find Storage</button>
        </a>
      </div>
      <img src="https://d9lvjui2ux1xa.cloudfront.net/img/home-screen/webp/san-francisco-300-80.webp"/>
    </div>
  EOD;
  echo $output;
}

/*
* Handle adding a sticky widget to the top of the page (maybe)
*/
// function nbrdcta_handle_sticky_widget()
// {
//   $post_categories = get_the_category();
//   if (count($post_categories) == 0) {
//     return;
//   }
//   $post_category = $post_categories[0]->name;
//   if (empty($post_category)) {
//     return;
//   }
//   $storage_type = nbrdcta_Landing_Configs::get_category_storage_type($post_category);
//   $storage_text = nbrdcta_Landing_Configs::get_type_title_upper($storage_type);
//   $output = <<<EOD
//   <div style="position:sticky;top:var(--cs-header-initial-height);width:100%;background-color:gainsboro">
//     <div style="display:flex;flex-direction:row;width:100%;align-items:space-around;justify-content:center">
//       <p style="text-align:center">Search for $storage_text on Neighbor</p>
//       <a href="https://www.neighbor.com/$storage_type-near-me">
//         <button>Find Storage</button>
//       </a>
//     </div>
//   </div>
//   EOD;
//   echo $output;
// }
