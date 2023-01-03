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
  //Check for fragments already identified - do this better using regex
  $parts = array();
  foreach($q_parts as $part) {
    if (strpos($part, ":")===false) {
      $parts[] = $part;
    }
  }

  $ret = array(
    "data" => array(
      "taxa" => _pythia_match_taxon($parts)
    )
  );
  return($ret);
}

function _pythia_match_taxon($parts) {
  global $db;
  $n_parts = count($parts);
  $taxon_match = array();
  for ($i=0; $i<$n_parts; $i++) {
    $sql = "SELECT `taxon` FROM `taxa` WHERE `taxon` = '".$parts[$i]."';";
    $res = $db->query($sql);
    if($res->num_rows == 0) {continue;}
    $name_string = $parts[$i];
    for($l=$i+1; $l<$n_parts; $l++) {
      $last_name_string = $name_string;
      $name_string .= ' '.$parts[$l];
      $sql = "SELECT `taxon` FROM `taxa` WHERE `taxon` = '".$name_string."';";
      $res = $db->query($sql);
      if($res->num_rows == 0 || ($l + 1) == $n_parts) {
        $length = $l - $i;
        $taxon_match[] = array(
          "start" => $i,
          "length" => $length,
          "match" => $last_name_string
        );
        break;
      }
    }
  }

  return($taxon_match);
}