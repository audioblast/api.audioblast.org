<?php

/*
Lists stadard special special endpoints
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
    if (isset($parts[1])) {
      if (!in_array($parts[1], listModuleTypes())) {
        print("Module type `".$parts[1]."` is not recognised.");
        exit;
      }
    } else {
      print("No module type provided.");
      exit;
    }

    //Check module is set and exists
    if (isset($parts[2])) {
      if (in_array($parts[2], listModules())) {
        $module = loadModule($parts[2]);
      } else {
        print("Module `".$parts[2]."` is not recognised.");
        exit;
      }
    } else {
      print("No module provided.");
      exit;
    }

    $params = array();
    $notes = array();
    $special = listSpecialEndpoints();

    $notes["input_params"] = $_GET;

    //Check for endpoints where this module will not execute SQL
    if (isset($parts[3]) && !in_array($parts[3], $special)  && !in_array(substr($parts[3],0, 1), array("", "?"))) {
      $endpoint = $parts[3];
      $execute_query = FALSE;
      if ($endpoint == "embed") {
        if (function_exists($parts[2]."_embed_info")) {
          $embeds = call_user_func($parts[2]."_embed_info"); 
         if (isset($parts[4])) {
            if (isset($embeds[$parts[4]])) {
              $module["endpoints"][$parts[4]] = $embeds[$parts[4]];
              $module["params"] = $embeds[$parts[4]]["params"];
            } else {
              print("Module `".$parts[3]."` has no embed `".$parts[4]."`.");
              exit;
            }
          } else {
            print("No embed type selected.");
            exit;
          }
        } else {
          print("Module `".$parts[2]."` does not have embed function.");
          exit;
        }
      } else {
        $module["params"] = $module["endpoints"][$endpoint]["params"];
      }
    }

    //Sanitise parameters and apply defaults
    foreach ($module["params"] as $pname => $pinfo) {
      if (isset($_GET[$pname])) {
        $params[$pname] = mysqli_real_escape_string($db, $_GET[$pname]);
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
    } else if (isset($parts[3]) && $parts[3] == "js") {
      header('Content-Type: application/javascript');
      print(file_get_contents("modules/".$parts[2]."/component.js", TRUE));
      return;
    } else if (isset($parts[3]) && $parts[3] == "histogram") {
      $select = "CALL `audioblast`.`".$module["histogram"]."`(1000)";
      $where = '';
    } else if (in_array(substr($parts[3],0, 1), array("", "?")) ) {
      $select = SELECTclause($module);
      $where = generateParams($module, $params);
    } else {
      if ($endpoint=="embed") { $endpoint = $parts[4];}
      switch ($module["endpoints"][$endpoint]["returns"]) {
        case "html":
          $mret = call_user_func($module["endpoints"][$endpoint]["callback"], $params);
          print $mret["html"];
        case "data":
          $mret = call_user_func($module["endpoints"][$endpoint]["callback"], $params);
          if (isset($mret["data"])) {
            $ret["data"] = $mret["data"];
          }
          if (isset($mret["notes"])) {
            $notes = $mret["notes"];
          }
          break;
        case "sql":
          $sql = call_user_func($module["endpoints"][$endpoint]["callback"], $params);
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
