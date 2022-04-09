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
        "returns" => "data",
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
        "returns" => "data",
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
      ),
      "module_info" => array(
        "callback" => "modules_module_info",
        "desc" => "Returns information about a module",
        "returns" => "data",
        "params" => array(
          "module" => array(
            "desc" => "Filter by module shortname",
            "type" => "string",
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
      ),
      "list_sources" => array(
        "callback" => "modules_list_sources",
        "desc" => "Returns a list of sources for data ingest",
        "returns" => "data",
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
      "list_embeds" => array(
        "callback" => "modules_list_embeds",
        "desc" => "Returns a list of embed endpoints",
        "returns" => "data",
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
      )
    )
  );
  return($info);
}

function modules_list_types($params) {
  $ret["data"]=listModuleTypes();
  return($ret);
}

function modules_module_info($params) {
  $module["data"] = loadModule($params["module"]);
  return($module);
}

function modules_list_modules($params) {
  $modules = loadModules();
  foreach ($modules as $name => $info) {
    if (isset($params["category"]) && $info["category"] != $params["category"]) {continue;}
    $mods["data"][] = array("name" => $name, "hname" => $info["hname"], "category" => $info["category"]);
  }
  return($mods);
}

function modules_list_sources($params) {
  $modules = loadModules();
  $ret = array();
  foreach ($modules as $name => $info) {
    if (isset($info["sources"])) {
      $ret["data"][$info["mname"]] = $info["sources"];
    }
  }
  return($ret);
}

function modules_list_embeds($params) {
  $modules = loadModules();
  $ret = array();
  foreach ($modules as $name => $info) {
    if (function_exists($name."_embed_info")) {
      $ret["data"][$info["mname"]] = call_user_func($name."_embed_info");
    }
  }
  return($ret);
}
