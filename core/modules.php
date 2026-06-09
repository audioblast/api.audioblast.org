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
Build the info array for an analysis "index" module. These modules share an
identical structure and differ only in a few fields, so the common parts are
defined once here. Required $opts: mname, hname, table, code_name,
code_function. Optional: desc, vocab_url, histogram, references.
*/
function analysis_index_module($opts) {
  $info = array(
    "mname" => $opts["mname"],
    "version" => 1.0,
    "category" => "analysis",
    "hname" => $opts["hname"],
    "desc" => isset($opts["desc"]) ? $opts["desc"] : "Explain how this works...",
    "ab-plugin" => TRUE,
    "code" => array(
      "type" => "package",
      "language" => "R",
      "name" => $opts["code_name"],
      "source" => "CRAN",
      "function" => $opts["code_function"]
    ),
    "table" => $opts["table"],
    "params" => array(
      "source" => array(
        "desc" => "Filter by source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "="
      ),
      "id" => array(
        "desc" => "filter by id within source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "="
      ),
      "startTime" => array(
        "desc" => "start time(s) to return",
        "type" => "integer",
        "default" => 0,
        "column" => "startTime",
        "op" => "range"
      ),
      "value" => array(
        "desc" => "Output of analysis",
        "type" => "integer",
        "default" => 0,
        "column" => "value",
        "op" => "range"
      ),
      "channel" => array(
        "desc" => "Audio channel analysed",
        "type" => "integer",
        "default" => 1,
        "column" => "channel",
        "op" => "="
      ),
      "output" => array(
        "desc" => "At present just an array",
        "type" => "string",
        "allowed" => array(
          "JSON"
        ),
        "default" => "JSON"
      )
    )
  );
  if (isset($opts["vocab_url"]))  { $info["vocab_url"]  = $opts["vocab_url"]; }
  if (isset($opts["histogram"]))  { $info["histogram"]  = $opts["histogram"]; }
  if (isset($opts["references"])) { $info["references"] = $opts["references"]; }
  return($info);
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
