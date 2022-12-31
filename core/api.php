<?php

/*
Lists standard special endpoints
*/
function listSpecialEndpoints() {
    return(array(
      "autocomplete",
      "columns",
      "js",
      "histogram",
      "files"
    ));
  }


/*
Some param types (e.g. range) need to be transofrmed before processing.
*/
function parseType($type) {
  if ($type == "range") { return("string");} else {return($type);}
}

/*
This is the main function for returning API data
*/
function moduleAPI($db) {
  $start_time = microtime(true);  //Track execution time for this request
  $execute_query = TRUE;          //Flag. By defaut this function will excute SQL.
                                  // - some functions will excute their own.
  $parts = explode("/", $_SERVER['REQUEST_URI']);

  //Check module type is set and exists
  if (isset($parts[1]) && $parts[1] != "embed") {
    if (!in_array($parts[1], listModuleTypes())) {
      print("Module type `".$parts[1]."` is not recognised.");
      exit;
    }
  } else {
    if (isset($parts[1]) && $parts[1] == "embed") {

    } else {
      print("No module type provided.");
      exit;
    }
  }

  //Check module is set and exists
  if (isset($parts[2]) && $parts[1] != "embed") {
    if (in_array($parts[2], listModules())) {
      $module = loadModule($parts[2]);
    } else if ($parts[2]=="embed"){
      $module = loadModule($parts[3]);
    } else {
      print("Module `".$parts[2]."` is not recognised.");
      exit;
    }
  } else {
    if ($parts[1] != "embed") {
      print("No module provided.");
      exit;
    }
  }

  //Process embeds
  if (isset($parts[1]) && $parts[1] =="embed") {
    loadModule($parts[2]);
    if (function_exists($parts[2]."_embed_info")) {
      $module = call_user_func($parts[2]."_embed_info");
      if (array_key_exists($parts[3], $module)) {
        $module = $module[$parts[3]];
      } else {
        print("Module doe snot have requested embed");
      }
    } else {
      print("No embed info for module.");
    }
  }

  //Process endpoints
  if (isset($parts[1]) && $parts[1] == "standalone") {
    if (isset($parts[3])) {
      if (array_key_exists($parts[3], $module["endpoints"])) {
        $module = $module["endpoints"][$parts[3]];
      } else {
        print("Module does not have requested endpoint.");
      }
    }
  } else if (in_array(parts[3], $module["endpoints"])) {
    //Endpoints in a module that is not standalone
    $module = $module["endpoints"][$parts[3]];
  }

  $params = array();
  $notes = array();

  $notes["input_params"] = $_GET;

  //Sanitise parameters and apply defaults
  foreach ($module["params"] as $pname => $pinfo) {
    if (isset($_GET[$pname])) {
      if (is_array($_GET[$pname])) {
        $params[$pname] = array();
        foreach ($_GET[$pname] as $key => $value) {
          $params[$pname][mysqli_real_escape_string($db, $key)] = mysqli_real_escape_string($db, $value);
        }
      } else {
        $params[$pname] = mysqli_real_escape_string($db, $_GET[$pname]);
      }
    } else {
      if (isset($pinfo["default"])) {
        $params[$pname] = $pinfo["default"];
      }
    }
  }

  //Special processing for filters via Tabulator
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
  } else if (isset($parts[3]) && $parts[3] == "columns") {
    $execute_query = FALSE;
    foreach ($module["params"] as $name => $info) {
      if ($name == "output") {continue;}
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
  } else if (isset($parts[3]) && $parts[3] == "histogram") {
    $select = "CALL `audioblast`.`".$module["histogram"]."`(1000)";
    $where = '';
  } else if (in_array(substr($parts[3],0, 1), array("", "?")) ) {
    $select = SELECTclause($module);
    $where = generateParams($module, $params);
  } else if ($parts[1] == "embed") {
    $execute_query = FALSE;
    $mret = call_user_func($module["callback"], $params);
    print($mret["html"]);
  } else {
    //Standalone
    $execute_query=FALSE;
    switch ($module["returns"]) {
      case "html":
        $mret = call_user_func($module["callback"], $params);
        print $mret["html"];
      case "data":
        $mret = call_user_func($module["callback"], $params);
        if (isset($mret["data"])) {
          $ret["data"] = $mret["data"];
        }
        if (isset($mret["notes"])) {
          $notes = $mret["notes"];
        }
        break;
      case "sql":
        $sql = call_user_func($module["callback"], $params);
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
  }
}
