<?php
class nbrdcta_Landing_Configs
{
  public static $nbrdcta_search_params = array(
    "self" => array(
      "filters" => array(
        "can_store_vehicle" => "false"
      )
    ),
    "boat" => array(
      "filters" => array(
        "can_store_vehicle" => "true",
        "min_size" => 160,
        "types" => array(
          "Carport",
          "Driveway",
          "Garage",
          "Parking Lot",
          "Unpaved Lot",
          "Street Parking",
          "Warehouse",
          "Other"
        ),
        "exposures" => array(
          "covered",
          "uncovered",
          "outdoor"
        ),
        "hasStandardSubPages" => "true"
      ),
    ),
    "car" => array(
      "filters" =>  array(
        "can_store_vehicle" => "true"
      ),
      "hasStandardSubPages" => "true"
    ),
    "climate-controlled" => array(
      "filters" => array(
        "features" => array(
          "climate_controlled"
        )
      )
    ),
    "garages" => array(
      "filters" => array(
        "types" => array(
          "Garage", "Parking Garage"
        ),
        "exposures" => array("indoor")
      ),
      "alternateNoun" => "",
      "titleOverride" => "garages for rent"
    ),
    "long-term" => array(
      "filters" =>  array(
        "can_store_vehicle" => "true"
      ),
      "alternateNoun" => "parking"
    ),
    "24-hour" => array(
      "filters" => array(
        "twenty_four_access" => "true"
      )
    ),
    "monthly" => array(
      "filters" => array(
        "can_store_vehicle" => "true"
      ),
      "alternateNoun" => "parking"
    ),
    "parking" => array(
      "filters" => array(
        "can_store_vehicle" => "true"
      ),
      "alternateNoun" => "spaces"
    ),
    "rv" => array(
      "filters" => array(
        "can_store_vehicle" => "true"
      ),
      "hasStandardSubPages" => "true",
      "titleOverride" => "RV & Camper storage"
    ),
    "truck" =>  array(
      "filters" => array(
        "can_store_vehicle" => "true"
      ),
      "hasStandardSubPages" => "true",
      "titleOverride" => "semi truck parking"
    )
  );

  /*
  * Convert category to storage type
  */
  public static function get_category_storage_type($category)
  {
    $types = array(
      "Lifestyle" => "boat"
    );
    return $types[$category] ?? null;
  }

  /*
  * Get title by storage type
  */
  public static function get_type_title($storage_type)
  {
    $config = nbrdcta_Landing_Configs::$nbrdcta_search_params[$storage_type] ?? null;
    if (!$config) {
      return "";
    }
    $alternate_noun = $config["alternateNoun"] ?? null;
    if (!$alternate_noun) {
      return "$storage_type storage";
    }
    return "$storage_type $alternate_noun";
  }

  /*
  * Get dlp title by storage type
  */
  public static function get_type_title_dlp($storage_type)
  {
    $config = nbrdcta_Landing_Configs::$nbrdcta_search_params[$storage_type] ?? null;
    if (!$config) {
      return "";
    }
    $alternate_noun = $config["alternateNoun"] ?? null;
    if (!$alternate_noun) {
      return "$storage_type-storage-near-me";
    }
    return "$storage_type-$alternate_noun-near-me";
  }

  /*
  * Get first word cased title by storage type
  */
  public static function get_type_title_upper_first($storage_type)
  {
    $title = nbrdcta_Landing_Configs::get_type_title($storage_type);
    $title = str_replace("rv", "RV", $title);
    return ucfirst($title);
  }

  /*
  * Get cased title by storage type
  */
  public static function get_type_title_upper($storage_type)
  {
    $title = nbrdcta_Landing_Configs::get_type_title($storage_type);
    $title = str_replace("rv", "RV", $title);
    return ucwords($title);
  }
}
