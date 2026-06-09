<?php

/*
Just lists the types of modules that can be handled by the API
*/
function listModuleTypes() {
  return(array(
    "source",
    "data",
    "analysis",
    "embed",
    "search",
    "standalone",
    "suggests"
  ));
}

function loadModules_analysisDefaultParams() {
  $ret = array(
    "source" => array(
      "desc" => "Source database",
      "type" => "string",
      "default" => ""
    ),
    "id" => array(
      "desc" => "Unique id within source",
      "type" => "string",
      "default" => ""
    )
  );
  return($ret);
}

/*
Load single module info
*/
function loadModule($mod) {
    $modfile = "modules/".$mod."/module.php";
    if (file_exists($modfile)) {
      include_once($modfile);
      $module = call_user_func($mod."_info");
      if ($module["category"] == "analysis") {
        $module["params"] = array_merge(
          loadModules_analysisDefaultParams(),
          $module["params"]
        );
      }
    if (isset($module["params"]["output"])) {
      $module["params"]["output"]["column"] = "";
      $module["params"]["output"]["op"] = "";
    }
    // Expose an internal/ac output-format selector on any module that maps at
    // least one field to an Audiovisual Core term (unless it defines its own).
    if (isset($module["params"]) && !isset($module["params"]["format"])) {
      foreach ($module["params"] as $pinfo) {
        if (isset($pinfo["ac"])) {
          $module["params"]["format"] = array(
            "desc" => "Data representation to return.",
            "type" => "string",
            "allowed" => array("internal", "ac"),
            "default" => "internal"
          );
          break;
        }
      }
    }
    } else {
      return(NULL);
    }
  return($module);
}

/*
Load all module info
*/
function loadModules($category=NULL) {
  $modules = array();
  foreach(glob("modules/"."*" , GLOB_ONLYDIR) as $mod_dir) {
      $mod_name = substr($mod_dir, 8);
      $module = loadModule($mod_name);
      if (is_null($category) || $module["category"] == $category) {
        $modules[$mod_name] = $module;
      }
  }
  return($modules);
}

/*
List module names
*/
function listModules($category=NULL) {
  $modules = array();
  foreach(glob("modules/"."*" , GLOB_ONLYDIR) as $mod_dir) {
      $modules[] = substr($mod_dir, 8);
  }
  return($modules);
}
