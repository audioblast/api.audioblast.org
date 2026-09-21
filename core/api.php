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
Some param types (e.g. range) need to be transformed before processing.
*/
function parseType($type) {
  if ($type == "range") { return("string");} else {return($type);}
}

/*
Refuse a request that asked for something the API does not have, saying what it
was. The status says as much to anything reading the reply rather than the
message, which a caller with a mistyped module or filter needs: the reply that
followed a bare message used to be an HTTP 200, and read as an answer. Nothing
usable can be built once the module or endpoint is unknown, so this does not
return.
*/
function badRequest($message) {
  http_response_code(400);
  print($message);
  exit;
}

/*
This is the main function for returning API data
*/
function moduleAPI($db) {
  $start_time = microtime(true);  //Track execution time for this request
  $execute_query = TRUE;          //Flag. By defaut this function will execute SQL.
                                  // - some functions will execute their own.
  $parts = explode("/", $_SERVER['REQUEST_URI']);

  //Check module type is set and exists
  if (isset($parts[1]) && $parts[1] != "embed") {
    if (!in_array($parts[1], listModuleTypes())) {
      badRequest("Module type `".htmlspecialchars($parts[1], ENT_QUOTES)."` is not recognised.");
    }
  }

  //Check module is set and exists. A trailing slash and nothing after it names
  //no module rather than one called "", which read as an empty pair of quotes.
  if (($parts[2] ?? "") !== "" && $parts[1] != "embed") {
    if (in_array($parts[2], listModules())) {
      $module = loadModule($parts[2]);
    } else if ($parts[2]=="embed"){
      $module = loadModule($parts[3]);
    } else {
      badRequest("Module `".htmlspecialchars($parts[2], ENT_QUOTES)."` is not recognised.");
    }
  } else {
    if ($parts[1] != "embed") {
      badRequest("No module provided.");
    }
  }

  //Process embeds
  if (isset($parts[1]) && $parts[1] =="embed") {
    loadModule($parts[2]);
    if (function_exists($parts[2]."_embed_info")) {
      $module = call_user_func($parts[2]."_embed_info");
      if (array_key_exists($parts[3] ?? "", $module)) {
        $module = $module[$parts[3]];
      } else {
        badRequest("Module does not have requested embed.");
      }
    } else {
      badRequest("No embed info for module.");
    }
  }

  //Process endpoints
  if (isset($parts[1]) && $parts[1] == "standalone") {
    if (isset($parts[3])) {
      if (array_key_exists($parts[3], $module["endpoints"])) {
        $module = $module["endpoints"][$parts[3]];
      } else {
        badRequest("Module does not have requested endpoint.");
      }
    }
  } else if (isset($module["endpoints"]) && array_key_exists($parts[3] ?? "", $module["endpoints"])) {
    //Endpoints in a module that is not standalone
    $module = $module["endpoints"][$parts[3]];
  }

  //Reject an input the module has no use for, rather than dropping it in
  //silence: the unfiltered whole table that then came back under an HTTP 200
  //reads as an answer to the question that was asked.
  $problem = checkParams($module ?? array(), $_GET, $parts[3] ?? NULL);
  if ($problem !== NULL) {badRequest(htmlspecialchars($problem, ENT_QUOTES));}

  //What the parameters alone cannot show: a parameter given more than once, of
  //which PHP keeps only the last. The query string is read from the request as
  //it was made, so the parameter the rewrite adds is not among it.
  $problem = checkQueryString($module ?? array(),
    parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY) ?? "");
  if ($problem !== NULL) {badRequest(htmlspecialchars($problem, ENT_QUOTES));}

  $params = array();
  $notes = array();
  $rdf = FALSE;                   //Flag. Set when records are returned as RDF (see core/rdf.php).

  $notes["input_params"] = $_GET;

  //Sanitise parameters and apply defaults
  foreach ($module["params"] as $pname => $pinfo) {
    if (isset($_GET[$pname])) {
      if (is_array($_GET[$pname])) {
        $params[$pname] = array();
        foreach ($_GET[$pname] as $key => $value) {
          $params[$pname][mysqli_real_escape_string($db, $key)] = mysqli_real_escape_string($db, $value);
        }
      } else if (paramTakesMany($pinfo) && strpos($_GET[$pname], ",") !== FALSE) {
        //A filter given several values at once (see filterValues()). The reply
        //gives them back as the values they were read as rather than as the
        //one string they were written as, so that a caller can see how its
        //request was split.
        $params[$pname] = array();
        foreach (filterValues($_GET[$pname]) as $value) {
          $params[$pname][] = mysqli_real_escape_string($db, $value);
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

  //Without an output parameter, clients of a module that gives RDF can ask for
  //it in their Accept header
  if (isset($module["rdf"]) && !isset($_GET["output"])) {
    header("Vary: Accept");
    $params["output"] = rdfNegotiate($_SERVER["HTTP_ACCEPT"] ?? "") ?? $params["output"];
  }

  //Special processing for filters via Tabulator
  if (isset($_GET["filter"])) {
    foreach ($_GET["filter"] as $filter) {
      if ($filter["type"] == "function" && is_array($filter["value"])) {
        //Dealing with a range: min and max are both inclusive
        $start = $filter["value"]["start"] ?? "";
        $end = $filter["value"]["end"] ?? "";
        $range = "";
        if ($start != "" && $end != "") {$range = $start.":".$end;}
        if ($start == "" && $end != "") {$range = "<=".$end;}
        if ($start != "" && $end == "") {$range = ">=".$start;}
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
      $value = mysqli_real_escape_string($db, $_GET["s"]);
    } else if (isset($_GET["c"])) {
      $op = "contains";
      $value = mysqli_real_escape_string($db, $_GET["c"]);
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
      "type" => "string",
      "fulltext" => !empty($module["params"][$field]["fulltext"])
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
    $format = isset($params["format"]) ? $params["format"] : "internal";
    $rdf = isset($module["rdf"]) && in_array($params["output"] ?? "", rdfOutputs());
    //RDF nodes are made from records with their columns as named in the module
    if ($rdf) {$format = "internal";}
    $select = SELECTclause($module, NULL, "table", $format);
    $where = generateParams($module, $params);
  } else if ($parts[1] == "embed") {
    //HTML response: emit it and return. Falling through to the JSON output
    //switch at the end would append a JSON body (and Content-Type) after the
    //embed markup.
    $mret = call_user_func($module["callback"], $params);
    print($mret["html"]);
    return;
  } else {
    //Standalone
    $execute_query=FALSE;
    switch ($module["returns"]) {
      case "html":
        //HTML response: emit and return. Without the return this fell through
        //into the "data" case below -- invoking the callback a second time --
        //and then on into the JSON output switch.
        $mret = call_user_func($module["callback"], $params);
        print $mret["html"];
        return;
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

    //Pagination. Floor the client-supplied values so the query stays valid:
    //page_size must be at least 1 (page_size=0 previously reached the COUNT
    //branch below and raised a divide-by-zero, HTTP 500; negatives produced a
    //malformed LIMIT), and page at least 1 so the OFFSET can never go negative.
    //No upper bound is imposed on page_size.
    $default_page = 50;
    $perPage = (isset($_GET["page_size"])) ? (int)$_GET["page_size"] : $default_page;
    $perPage = max(1, $perPage);
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    $page = max(1, $page);
    $startAt = $perPage * ($page - 1);
    $sql = $select.WHEREclause($where);
    $sql .= " LIMIT ".$startAt.", ".$perPage.";";
    $notes[] = $sql;

    $result = $db->query($sql);

    //Always return a data array, even when no rows match: Tabulator rejects a
    //response without one ("Expecting: array Received: undefined").
    $ret["data"] = array();
    if ($result) {

      while ($row = $result->fetch_array(MYSQLI_ASSOC)) {

        $ret["data"][] = $row;
      }
      $result->close();
    } else {
      $notes[] = "Query failed on database.";
    }

    //Pagination total. This COUNT(*) is a second full scan of the filtered
    //set, so only run it when the result is actually needed and cannot be
    //derived for free from the page we already fetched.
    $rowsReturned = count($ret["data"]);
    if (($params["output"] ?? "") == "nakedJSON") {
      //last_page is not part of nakedJSON output, so the count is never used.
    } else if ($page == 1 && $rowsReturned < $perPage) {
      //Whole result set fits on the first page; derive last_page directly.
      $ret["last_page"] = ($rowsReturned == 0) ? 0 : 1;
    } else {
      $sql = SELECTcount($module).WHEREclause($where).";";
      $res = mysqli_fetch_assoc(mysqli_query($db, $sql));
      $ret["last_page"] = ceil($res['total'] / $perPage);
    }

    $notes["query_execution_time"] = microtime(true) - $query_start_time;
  }

  if ($rdf) {
    //RDF has no notes to say that the query failed, so the status says it
    if (!$result) {http_response_code(500);}
    printRecordRDF($db, $module, $ret["data"], $params["output"],
             ($page < ($ret["last_page"] ?? 0)) ? $page + 1 : NULL);
    return;
  }

  $ret["params"] = $params;
  $ret["notes"] = $notes;
  $ret["notes"]["total_execution_time"] = microtime(true) - $start_time;
  //Everything reaching this point is a data response (HTML paths returned
  //earlier), so always declare JSON. "tabulator" consumes the same
  //{data, last_page} envelope as JSON; the default stops an unrecognised
  //output from silently returning an empty body.
  header("Content-Type: application/json");
  switch($params["output"] ?? "") {
    case "JSON":
    case "tabulator":
      print(json_encode($ret));
      break;
    case "nakedJSON":
      if (!isset($ret["data"])) {$ret["data"] = array();}
      print(json_encode($ret["data"]));
      break;
    default:
      print(json_encode($ret));
  }
}
