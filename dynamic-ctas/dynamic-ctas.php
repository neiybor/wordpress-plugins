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
  add_action('csco_site_content_start', 'nbrdcta_check_has_action', 100);
  add_filter('the_content', 'nbrdcta_handle_cta_body', 100);
  add_action('csco_entry_content_after', 'nbrdcta_handle_cta_content_end', 1);
  add_action('csco_footer_before', 'nbrdcta_handle_cta_pre_footer', 1);
  add_action(' ', 'nbrdcta_handle_cta_search_widget', 1);
}

// Priority for the child theme load is 99, set to 150 to execute after child theme is loaded
add_action('after_setup_theme', 'nbrdcta_add_handlers', 150);

function nbrdcta_check_has_action()
{
  // 	$posttags = get_the_tags();
  $posttags = get_the_category();
  if ($posttags) {
    foreach ($posttags as $tag) {
      // echo $tag->name . ' '; 
    }
  }
}

/*
* Handle adding CTA to post body
*/
function nbrdcta_handle_cta_body($content)
{
  $num_headings = 2;
  $post_category = get_the_category();

  $html = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
  $dom = new DOMDocument;
  $dom->loadHTML($html);

  $addition_doc = new DOMDocument;
  $addition_doc->loadHTML('<a href="https://www.neighbor.com">Click Me!</a>');

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
  $post_category = get_the_category();
  echo '<a href="https://www.neighbor.com">Also Click Me Please!</a>';
}

/*
* Handle adding CTA before the footer but after the main content
*/
function nbrdcta_handle_cta_pre_footer()
{
  $post_category = get_the_category();
  echo '<a href="https://www.neighbor.com">I am also here, please!</a>';
}

/*
* Handle changing search widget dest and text
*/
function nbrdcta_handle_cta_search_widget()
{
  $post_category = get_the_category();
}
