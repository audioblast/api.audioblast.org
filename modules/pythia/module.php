<?php

function pythia_info() {
  $info = array(
    "mname" => "pythia",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Pythia search term processor",
    "desc" => "Oracle for providing search hints.",
    "endpoints" => array(
      "process" => array(
        "callback" => "pythia_process",
        "desc" => "Provides search hints.",
        "returns" => "data",
        "params" => array(
          "query" => array(
            "desc" => "Query string",
            "type" => "string",
            "op" => "=",
            "default" => "",
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

function pythia_process($f) {
  $q_parts = explode(" ", $f["query"]);
  print_r($q_parts);

  //Check for fragments already identified
  $parts = array();
  foreach($parts as $part) {
    if (strpos($part, ":")==FALSE) {
      $parts[] = $part;
    }
  }
  print_r($parts);
  exit;
  return($ret);
}
