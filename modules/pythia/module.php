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
  global $db;
  $q_parts = explode(" ", $f["query"]);
  //Check for fragments already identified - do this better using regex
  $parts = array();
  foreach($q_parts as $part) {
    if (strpos($part, ":")===false) {
      $parts[] = $part;
    }
  }

  //Match taxa
  $best_taxon_match = array(
    "start"  => NULL,
    "length" => 0
  );
  for ($i=0; $i<count($parts); $i++) {
    $sql = "SELECT `taxon` FROM `taxa` WHERE `taxon` = '".$parts[$i]."';";
    $res = $db->query($sql);
    if($res->num_rows == 0) {continue;}
    $name_string = $parts[$i];
    for($l=$i+1; $l<(count($parts)); $l++) {
        $name_string .= ' '.$parts[$l];
        $sql = "SELECT `taxon` FROM `taxa` WHERE `taxon` = '".$name_string."';";
        $res = $db->query($sql);
        if($res->num_rows == 0) {
            $length = $l-1 - $i;
            if ($length > $best_taxon_match["length"]) {
                $best_taxon_match["start"] = $i;
                $best_taxon_match["length"] = $length;
            }
        }
    }
  }

for ($i = $best_taxon_match["start"]; $i < ($best_taxon_match["start"]+$best_taxon_match["length"]); $i++) {
    print($parts[$i]);
}

  exit;
  return($ret);
}
