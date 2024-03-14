<?php
/**
 * This file contains the code for dealing with ab testing
 */

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
 * Get test arm assignments
 * The priority is set to 8 to run before the custom header code (in Admin Settings) is added
 */
add_action('wp_head', 'nbrdcta_get_test_assignments', 8);
function nbrdcta_get_test_assignments()
{
  global $nbrdcta_anonymous_id;

  $test_assignments = array();
  $settings_keys = ['sticky_header', 'in_post', '2_in_post', '3_in_post', 'above_footer', 'search_widget'];
  foreach ($settings_keys as $settings_key) {
    $html = nbrdcta_get_custom_html($settings_key);
    if ($html) {
      $test = nbrdcta_get_ab_test_name_and_arm($html);
      if ($test) {
        $test_name = $test['test_name'];
        $arm = $test['arm'];
        $full_test_name = $test['full_test_name'];

        $test_assignments[$full_test_name] = $arm;
      }
    }
  }

  nbrdcta_set_local_storage_item('test_assignments', $test_assignments);

  return $test_assignments;
}

/**
 * Sets test assignments in local storage to be used by rudderstack script in custom header code (in Admin Settings)
 */
function nbrdcta_set_local_storage_item($key, $value)
{
  add_action('wp_head', function() use ($key, $value) {
    $json_data = json_encode($value);
    ?>
    <script>
      const testAssignments = JSON.stringify(<?php echo $json_data; ?>);
      localStorage.setItem("<?php echo $key; ?>", testAssignments);
    </script>
    <?php
  }, 9);
}

/**
 * Handle determining if there's an ab test and return the test and arm or false
 */
function nbrdcta_get_ab_test_name_and_arm($html)
{
  global $nbrdcta_anonymous_id;
  if (empty($nbrdcta_anonymous_id)) {
    return false;
  }
  if (empty($html)) {
    return false;
  }
  // check if the first div of provided $html has a class beginning with "AB_editorial"
  $dom = new DOMDocument;
  // The @ sign supresses warnings we are seeing. Not a permanent fix
  $succeeded = @$dom->loadHTML($html);
  if (!$succeeded) {
    return false;
  }
  $divs = $dom->getElementsByTagName('div');
  if ($divs->length == 0) {
    return false;
  }
  $first_div = $divs->item(0);
  $class = $first_div->getAttribute('class');
  if (strpos($class, 'AB_editorial_') === 0) {
    $test_name = substr($class, strlen('AB_editorial_'));
    $arm = nbrdcta_get_test_arm($test_name);
    return array("test_name" => $test_name, "arm" => $arm, "full_test_name" => $class);
  }
  return false;
}

/**
 * Handle determining if there's an ab test and return content for arm
 */
function nbrdcta_get_ab_test_content($html)
{
  if (empty($html)) {
    return $html;
  }
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
    $arm = nbrdcta_get_test_arm($test_name);
    $html = nbrdcta_get_test_arm_content($html, $arm);
  }
  return $html;
}

/**
 * Consitently returns the same test arm based on a hash composed from test_name and anon_id
 */
define("MAX_UNSIGNED_32BIT", 4294967295);
function nbrdcta_get_test_arm($test_name)
{
  global $nbrdcta_anonymous_id;
  if (empty($nbrdcta_anonymous_id)) {
    return "control";
  }

  $hashed_index = hexdec(hash('murmur3a', $test_name . "_" . $nbrdcta_anonymous_id));
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
function nbrdcta_get_test_arm_content($html, $arm)
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

/**
 * Helpful function to log to the console
 */
function nbrdcta_console_log($label, $data)
{
  add_action('wp_footer', function() use ($label, $data) {
    echo '<script>';
    echo 'console.log("' . $label . ': ",' . json_encode($data) . ')';
    echo '</script>';
  }, 100);
}
