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
  //Check for fragments already identified
  $parts = array();
  foreach($q_parts as $part) {
    print $part."\n";
    if (strpos($part, ":")===false) {
      $parts[] = $part;
    }
  }

  //Match taxa
  $best_taxon_match = array(
    "start"  => NULL,
    "length" => NULL
  );
  for ($i=0; $i<count($parts); $i++) {
    for($l=0; $l<(count($parts)-$i); $l++) {
        print $i."-".$l."\n";
    }
  }

  exit;
  return($ret);
}
