<?php

function modules_info() {
  $info = array(
    "mname" => "modules",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Loaded modules",
    "desc" => "Provide information about loaded modules",
    "endpoints" => array(
      "list_types" => array(
        "callback" => "modules_list_types",
        "desc" => "Returns a list of module types.",
        "params" => array(
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          )
        )
      ),
      "list_modules" => array(
        "callback" => "modules_list_modules",
        "desc" => "Returns a list of modules",
        "params" => array(
          "category" => array(
            "desc" => "Filter by module category",
            "type" => "string",
            "allowed" => listModuleTypes(),
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
      )
    )
  );
  return($info);
}

function modules_list_types($params) {
  return(listModuleTypes());
}

function modules_list_modules($params) {
  $modules = loadModules();
  $ret = array();
  foreach ($modules as $name => $info) {
    if (isset($params["category"]) && $info["category"] != $params["category"]) {continue;}
    $ret[] = array("name" => $name, "category" => $info["category"]);
  }
  return($ret);
}