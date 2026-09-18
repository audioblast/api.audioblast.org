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

function recordAPI($db) {
  $module = recordModule($_SERVER["REQUEST_URI"]);
  $path = explode("/", parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
  $source = rawurldecode($path[2]);
  $id = implode("/", array_map("rawurldecode", array_slice($path, 3)));

  $output = $_GET["output"] ?? NULL;
  if ($output === NULL) {
    header("Vary: Accept");
    $output = rdfNegotiate($_SERVER["HTTP_ACCEPT"] ?? "") ?? "JSON";
  }

  //Source and id are matched exactly, which the table's key on them makes quick
  $sql  = SELECTclause($module, NULL, "table", "internal");
  $sql .= " WHERE `".$module["params"]["source"]["column"]."` = ? AND `".$module["params"]["id"]["column"]."` = ? LIMIT 1;";
  $stmt = $db->prepare($sql);
  $result = FALSE;
  if ($stmt) {
    $stmt->bind_param("ss", $source, $id);
    $result = $stmt->execute() ? $stmt->get_result() : FALSE;
  }
  $record = $result ? $result->fetch_assoc() : NULL;

  if ($result === FALSE) {
    http_response_code(500);
  } else if ($record === NULL) {
    http_response_code(404);
  } else if ($record["source"] !== $source || $record["id"] !== $id) {
    //The database compares text regardless of case, but each record has one URI
    $query = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
    header("Location: ".rdfRecordURI($module, $record["source"], $record["id"]).(($query === NULL) ? "" : "?".$query), TRUE, 301);
    return;
  }

  $records = ($record === NULL) ? array() : array($record);
  if (in_array($output, rdfOutputs())) {
    printRDF(rdfNodes($module, $records), $output);
  } else {
    header("Content-Type: application/json");
    print(json_encode(($output == "nakedJSON") ? $records : array("data" => $records)));
  }
}
