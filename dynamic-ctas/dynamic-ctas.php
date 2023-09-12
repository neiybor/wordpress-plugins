<?php
/*
* Plugin Name: Neighbor Dynamic CTA
* Description: Plugin to dynamically add CTAs to the Neighbor blog in various locations on the page
* Version: 1.0
* Author: Brayden Gibbons
*/

/*
* Add all actions that trigger based on the theme specific actions
*/
function nbrdcta_add_handlers()
{
  // add_action('csco_header_after', 'nbrdcta_handle_sticky_widget', 1);
  add_filter('the_content', 'nbrdcta_handle_cta_body', 100);
  add_action('csco_entry_content_after', 'nbrdcta_handle_cta_content_end', 1);
  add_action('csco_footer_before', 'nbrdcta_handle_cta_pre_footer', 1);
  add_action('', 'nbrdcta_handle_cta_search_widget', 1);
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
  $post_category = $post_categories[0]->name;

  $html = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
  $dom = new DOMDocument;
  $dom->loadHTML($html);

  $addition_doc = new DOMDocument;
  $storage_type = nbrdcta_get_storage_type_from_category($post_category);
  $storage_text = nbrdcta_get_text_from_storage_type($storage_type);
  $addition_doc->loadHTML(<<<EOD
    <div style="background-color:gainsboro;width:100%;display:flex;flex-direction:row;justify-content:space-between">
      <div style="display:flex;flex-direction:column;width:100%;align-items:center;justify-content:center">
        <p style="text-align:center">Search for $storage_text on Neighbor</p>
        <a href="https://www.neighbor.com/$storage_type-near-me">
          <button>Find Storage</button>
        </a>
      </div>
      <img src="https://d9lvjui2ux1xa.cloudfront.net/img/home-screen/webp/san-francisco-300-80.webp"/>
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
  $post_category = $post_categories[0]->name;
  $storage_type = nbrdcta_get_storage_type_from_category($post_category);
  $storage_text = nbrdcta_get_text_from_storage_type($storage_type);
  $output = <<<EOD
    <h3 style="margin-top:64px">Want to Learn More About $storage_text?</h3>
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
  $post_category = $post_categories[0]->name;
  $storage_type = nbrdcta_get_storage_type_from_category($post_category);
  $storage_text = nbrdcta_get_text_from_storage_type($storage_type);
  $output = <<<EOD
    <div style="background-color:gainsboro;width:100%;display:flex;flex-direction:row;justify-content:space-between;margin:10px">
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
* Handle changing search widget dest and text
*/
function nbrdcta_handle_cta_search_widget()
{
  $post_category = get_the_category();
}

/*
* Handle adding a sticky widget to the top of the page (maybe)
*/
function nbrdcta_handle_sticky_widget()
{
  $post_categories = get_the_category();
  $post_category = $post_categories[0]->name;
  $storage_type = nbrdcta_get_storage_type_from_category($post_category);
  $storage_text = nbrdcta_get_text_from_storage_type($storage_type);
  $output = <<<EOD
  <div style="position:sticky;top:var(--cs-header-initial-height);width:100%;background-color:gainsboro">
    <div style="display:flex;flex-direction:row;width:100%;align-items:space-around;justify-content:center">
      <p style="text-align:center">Search for $storage_text on Neighbor</p>
      <a href="https://www.neighbor.com/$storage_type-near-me">
        <button>Find Storage</button>
      </a>
    </div>
  </div>
  EOD;
  echo $output;
}

/*
*
*/
function nbrdcta_get_body_text($category)
{
  $category_text = array(
    ""
  );
  return $category_text[$category];
}

/*
* Convert category to storage type
*/
function nbrdcta_get_storage_type_from_category($category)
{
  $types = array(
    "Lifestyle" => "rv-storage"
  );
  return $types[$category];
}

/*
* Convert storage type to readable text
*/
function nbrdcta_get_text_from_storage_type($storage_type)
{
  $text = array(
    "rv-storage" => "RV storage"
  );
  return $text[$storage_type];
}
