<?php

/*
A record at its URI (see rdfRecordURI()), e.g. /recording/bio.acousti.ca/12883.
It is given as RDF where the output parameter or, without one, the Accept header
asks for it (see rdfNegotiate()), and otherwise as JSON, as the module's own
endpoint gives it.
*/

//The module whose records' URIs start with the request's path, or NULL
function recordModule($requestURI) {
  $path = explode("/", parse_url($requestURI, PHP_URL_PATH));
  //The module API's paths aren't records', so its requests needn't load every module
  if (count($path) < 4 || in_array($path[1], listModuleTypes())) {return(NULL);}
  foreach (loadModules() as $module) {
    if (($module["rdf"]["path"] ?? NULL) === $path[1]) {return($module);}
  }
  return(NULL);
}

function isRecordPage() {
  return(recordModule($_SERVER["REQUEST_URI"]) !== NULL);
}

//The module, source and id of the record at a URI or path, or NULL where it is
//no record's. The id is everything after the source, so it may hold a /.
function recordAt($uri) {
  $module = recordModule($uri);
  if ($module === NULL) {return(NULL);}
  $path = explode("/", parse_url($uri, PHP_URL_PATH));
  return(array(
    "module" => $module,
    "source" => rawurldecode($path[2]),
    "id" => implode("/", array_map("rawurldecode", array_slice($path, 3)))
  ));
}

/*
The record of a module with this source and id, NULL where there is none, or
FALSE where the lookup failed. The record's own URI is answered with it, and so
is the MCP server's get_record tool (core/mcp-tools.php).
*/
function recordByID($db, $module, $source, $id) {
  //Source and id are matched exactly, which the table's key on them makes quick
  $sql  = SELECTclause($module, NULL, "table", "internal");
  $sql .= " WHERE `".$module["params"]["source"]["column"]."` = ? AND `".$module["params"][$module["rdf"]["id"] ?? "id"]["column"]."` = ? LIMIT 1;";
  $stmt = $db->prepare($sql);
  if (!$stmt) {return(FALSE);}
  $stmt->bind_param("ss", $source, $id);
  $result = $stmt->execute() ? $stmt->get_result() : FALSE;
  if (!$result) {return(FALSE);}
  $record = $result->fetch_assoc();
  return(is_array($record) ? $record : NULL);
}

function recordAPI($db) {
  $at = recordAt($_SERVER["REQUEST_URI"]);
  $module = $at["module"];
  $source = $at["source"];
  $id = $at["id"];

  $output = $_GET["output"] ?? NULL;
  if ($output !== NULL) {
    $output = outputValue($module, $_GET["output"]);
    if ($output === NULL) {
      badRequest(htmlspecialchars(outputProblem($module, $_GET["output"]), ENT_QUOTES));
    }
  } else {
    header("Vary: Accept");
    $output = rdfNegotiate($_SERVER["HTTP_ACCEPT"] ?? "") ?? "JSON";
  }

  $record = recordByID($db, $module, $source, $id);

  if ($record === FALSE) {
    http_response_code(500);
    $record = NULL;
  } else if ($record === NULL) {
    http_response_code(404);
  } else if ($record["source"] !== $source || $record[$module["rdf"]["id"] ?? "id"] !== $id) {
    //The database compares text regardless of case, but each record has one URI
    $query = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
    header("Location: ".rdfRecordURI($module, $record["source"], $record[$module["rdf"]["id"] ?? "id"]).(($query === NULL) ? "" : "?".$query), TRUE, 301);
    return;
  }

  $records = ($record === NULL) ? array() : array($record);
  if (in_array($output, rdfOutputs())) {
    //One record was asked for, at its own URI, so it carries what a module can
    //only afford to say about a record on its own (see rdfResponseNodes())
    printRecordRDF($db, $module, $records, $output, NULL, TRUE);
  } else {
    header("Content-Type: application/json");
    print(json_encode(($output == "nakedJSON") ? $records : array("data" => $records)));
  }
}
