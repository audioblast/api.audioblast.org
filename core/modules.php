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

function parseType($type) {
  if ($type == "range") { return("string");} else {return($type);}
}

function moduleAPI($db) {
  $start_time = microtime(true);
  $execute_query = TRUE;
  $parts = explode("/", $_SERVER['REQUEST_URI']);
  if ($parts[1] == "suggests") {
    $module = array();
    $module["params"] = array(
      "source" => "string",
      "id" => "string",
      "output" => array("default" => "JSON")
    );
  } else if ($parts[1] == "embed") {
    $module = array();
    loadModule($parts[2]);
    if (function_exists($parts[2]."_embed_info")) {
      $endpoints = call_user_func($parts[2]."_embed_info");
      if (isset($endpoints[$parts[3]])) {
        $module = $endpoints[$parts[3]];
      }
    }
  } else if ($parts[1] == "search") {
    $module = array();
    loadModule($parts[2]);
    if (function_exists($parts[2]."_search_info")) {
      $endpoints = call_user_func($parts[2]."_search_info");
      if (isset($endpoints[$parts[3]])) {
        $module = $endpoints[$parts[3]];
      }
    }
  } else {
    $module = loadModule($parts[2]);
  }
  $params = array();
  $notes = array();
  $special = array("autocomplete", "columns", "js", "histogram", "files");

  $notes["input_params"] = $_GET;

  if ($parts[1] != "embed" && $parts[1]!="search" && isset($parts[3]) && !in_array($parts[3], $special)  && !in_array(substr($parts[3],0, 1), array("", "?"))) {
    $ep = $parts[3];
    $execute_query = FALSE;
    $module["params"] = $module["endpoints"][$ep]["params"];
  }

  //Validate parameters
  foreach ($module["params"] as $pname => $pinfo) {
    if (isset($_GET[$pname])) {
      $params[$pname] = mysqli_real_escape_string($db, $_GET[$pname]);
    } else {
      if (isset($pinfo["default"])) {
        $params[$pname] = $pinfo["default"];
      }
    }
  }


  if (isset($_GET["filter"])) {
    foreach ($_GET["filter"] as $filter) {
      if ($filter["type"] == "function") {
        //Dealing with a range
        if ($filter["value"]["start"] != "" && $filter["value"]["end"] != "") {$range = $filter["value"]["start"].":".$filter["value"]["end"];}
        if ($filter["value"]["start"] == "" && $filter["value"]["end"] != "") {$range = "<".$filter["value"]["end"];}
        if ($filter["value"]["start"] != "" && $filter["value"]["end"] == "") {$range = ">".$filter["value"]["start"];}
        $params[mysqli_real_escape_string($db, $filter["field"])] = mysqli_real_escape_string($db, $range);
      } else {
        $params[mysqli_real_escape_string($db, $filter["field"])] = mysqli_real_escape_string($db, $filter["value"]); 
      }
    }
  }

  if (isset($parts[3]) && $parts[3] == "autocomplete") {
    $field= $parts[4];
    if (!isset($module["params"][$field])) {
      $execute_query = FALSE;
      $notes[] = "Autocomplete field does not exist.";
    }
    if (!isset($module["params"][$field]["autocomplete"]) || $module["params"][$field]["autocomplete"] == FALSE) {
      $execute_query = FALSE;
      $notes[] = "Autocomplete not allowed on field. Query will not execute.";
    }
    if (isset($_GET["s"])) {
      $op = "starts";
      $value = $_GET["s"];
    } else if (isset($_GET["c"])) {
      $op = "contains";
      $value = $_GET["c"];
    } else {
      $op = "none";
      $value = "";
    }

    $select = SELECTclause($module, $field, "autocomplete");
    $where = generateParams($module, $params);
    $where[] = array(
      "column" => $module["params"][$field]["column"],
      "op" => $op,
      "value" => $value,
      "type" => "string"
    );
  } else if ($parts[1] == "embed") {
    $execute_query = FALSE;
    if (function_exists($parts[2]."_embed_info")) {
      $endpoints = call_user_func($parts[2]."_embed_info");
      if (isset($endpoints[$parts[3]])) {
        $ret  = call_user_func($endpoints[$parts[3]]["callback"], $params);
      }
    }
  } else if ($parts[1] == "search") {
    $execute_query = FALSE;
    if (function_exists($parts[2]."_search_info")) {
      $endpoints = call_user_func($parts[2]."_search_info");
      if (isset($endpoints[$parts[3]])) {
        $ret  = call_user_func($endpoints[$parts[3]]["callback"], $params);
      }
    }
  } else if (isset($parts[3]) && $parts[3] == "columns") {
    $execute_query = FALSE;
    foreach ($module["params"] as $name => $info) {
      if ($name == "output" || $name == "format") {continue;}
      $col = array(
        "title" => $name,
        "field" => $name
      );
      if (isset($info["op"]) && $info["op"] != "none") {
        switch($info["type"]) {
          case "string":
            $col["headerFilter"] = "input";
            break;
          case "range":
            $col["headerFilter"] = "range";
            break;
        }
      }
      $ret["data"][] = $col;
    }
  } else if (isset($parts[3]) && $parts[3] == "js") {
    header('Content-Type: application/javascript');
    print(file_get_contents("modules/".$parts[2]."/component.js", TRUE));
    return;
  } else if (isset($parts[3]) && $parts[3] == "histogram") {
    $select = "CALL `audioblast`.`".$module["histogram"]."`(1000)";
    $where = '';
  } else if (isset($parts[3]) && in_array(substr($parts[3],0, 1), array("", "?")) ) {
    if (!isset($params["format"])) {$params["format"]=NULL;}
    $select = SELECTclause($module, NULL, "table", $params["format"]);
    $where = generateParams($module, $params);
  } else if ($parts[1] == "suggests") {
    $execute_query = FALSE;
    include("core/suggests.php");
    $ret["data"] = suggests($params["source"], $params["id"], $db);
  } else {
    switch ($module["endpoints"][$ep]["returns"]) {
      case "html":
        $mret = call_user_func($module["endpoints"][$ep]["callback"], $params);
        print $mret["html"];
      case "data":
        $mret = call_user_func($module["endpoints"][$ep]["callback"], $params);
        if (isset($mret["data"])) {
          $ret["data"] = $mret["data"];
        }
        if (isset($mret["notes"])) {
          $notes = $mret["notes"];
        }
        break;
      case "sql":
        $sql = call_user_func($module["endpoints"][$ep]["callback"], $params);
        $query_start_time = microtime(true);
        $result = $db->query($sql);
        $notes["query_execution_time"] = microtime(true) - $query_start_time;
        if ( $result) {
          while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $ret["data"][] = $row;
          }
          $result->close();
        } else {
          $notes[] = "Query failed on database.";
        }
        break;
    }
  }

  if ($execute_query) {
    $query_start_time = microtime(true);

    //Pagination
    $default_page = 50;
    $perPage = (isset($_GET["page_size"])) ? (int)$_GET["page_size"] : $default_page;
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    $startAt = $perPage * ($page - 1);
    $sql = $select.WHEREclause($where);
    $sql .= " LIMIT ".$startAt.", ".$perPage.";";
    $notes[] = $sql;
    $result = $db->query($sql);

    if ($result) {
      while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $ret["data"][] = $row;
      }
      $result->close();
    } else {
      $ret["data"] = array();
      $notes[] = "Query failed on database.";
    }

    $sql = SELECTcount($module).WHEREclause($where).";";
    $res = mysqli_fetch_assoc(mysqli_query($db, $sql));
    $totalPages = ceil($res['total'] / $perPage);
    $ret["last_page"] = $totalPages;

    $notes["query_execution_time"] = microtime(true) - $query_start_time;
  }

  $ret["params"] = $params;
  $ret["notes"] = $notes;
  $ret["notes"]["total_execution_time"] = microtime(true) - $start_time;
  switch($params["output"]) {
    case "JSON":
      print(json_encode($ret));
      break;
    case "nakedJSON":
      if (!isset($ret["data"])) {$ret["data"] = array();}
      print(json_encode($ret["data"]));
      break;
    default:
      if (isset($ret["html"])) {
        print($ret["html"]);
        break;
      }
  }
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
