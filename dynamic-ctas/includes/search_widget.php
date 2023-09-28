<?php
// Register and load the widget
function nbrdcta_load_widget()
{
  register_widget('nbrdcta_search_widget');
}
add_action('widgets_init', 'nbrdcta_load_widget');

class nbrdcta_search_widget extends WP_Widget
{
  function __construct()
  {
    $widget_options = array(
      'classname' => 'nbrdcta_search_widget',
      'description' => 'Search Neighbor by type',
    );
    parent::__construct('nbrdcta_search_widget', 'Neighbor Search Bar', $widget_options);
  }

  // Creating widget front-end
  public function widget($args, $instance)
  {
    echo $args['before_widget'];
    echo $args['before_title'], $args['after_title'];

    // This is where you run the code and display the output
    $post_categories = get_the_category();
    if (count($post_categories) == 0) {
      return;
    }
    $post_category = $post_categories[0]->name;
    if (empty($post_category)) {
      return;
    }

    $settings_key = "search_widget";
    $insert_html = nbrdcta_search_widget::nbrdcta_get_custom_html($settings_key);
    if (!$insert_html) {
      return;
    }
    $content = <<<EOD
      <div id="search-widget">
        $insert_html
      </div>
    EOD;
    echo $content;

    echo $args['after_widget'];
  }

  /*
  * Handle getting cta options from settings
  */
  public function nbrdcta_get_custom_html($key)
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


  // public function widget($args, $instance)
  // {
  //   echo $args['before_widget'];
  //   echo $args['before_title'], $args['after_title'];

  //   // This is where you run the code and display the output
  //   $post_categories = get_the_category();
  //   if (count($post_categories) == 0) {
  //     return;
  //   }
  //   $post_category = $post_categories[0]->name;
  //   if (empty($post_category)) {
  //     return;
  //   }
  //   $storage_type = nbrdcta_Landing_Configs::get_category_storage_type($post_category);
  //   $title = nbrdcta_Landing_Configs::get_type_title_upper($storage_type);
  //   $search_params = nbrdcta_Landing_Configs::$nbrdcta_search_params[$storage_type] ?? null;
  //   $search_inputs = '';
  //   if (!empty($search_params)) {
  //     foreach ($search_params['filters'] as $key => $value) {
  //       if (is_array($value)) {
  //         foreach ($value as $subvalue) {
  //           $search_inputs .= <<<EOD
  //             <input name="$key\[\]" value="$subvalue" hidden=""/>
  //           EOD;
  //         }
  //       } else {
  //         $search_inputs .= <<<EOD
  //           <input name="$key" value="$value" hidden=""/>
  //         EOD;
  //       }
  //     }
  //   }

  //   $output = <<<EOD
  //     <h3>Find $title</h3>
  //     <div class="neighbor-search-form-simple">
  //     <form action="https://www.neighbor.com/search">
  //       <input type="text" placeholder="ZIP Code or City" name="search" required=""><br>
  //       <input name="utm_source" value="blog" hidden="">
  //       <input name="utm_campaign" value="sidebar" hidden="">
  //       <input name="utm_medium" value="sidebarsearchform" hidden="">
  //       <input name="fromBlog" value="true" hidden="">
  //       $search_inputs
  //       <input class="submit-button" type="submit" value="🔍︎" onclick="() => {
  //         gtag('event', 'submit', {
  //         'event_category': 'storage_search',
  //         'event_label': 'find_storage_widget_search'
  //         });
  //       }"><br>
  //     </form>
  //     </div>
  //   EOD;
  //   echo $output;

  //   echo $args['after_widget'];
  // }

  // Widget Backend
  public function form($instance)
  {
    $title = !empty($instance['title']) ? $instance['title'] : '';
    // Add widget config here
?>
    
    <?php
  }

  // Updating widget replacing old instances with new
  public function update($new_instance, $old_instance)
  {
    $instance = array();
    $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
    return $instance;
  }
}
