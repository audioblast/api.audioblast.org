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
    if (preg_match('/^(:\'(\p{L}| |_)+\')+:$/', $part)==0) {
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
    $name_string = $parts[$i];
    $sql = "SELECT `taxon` FROM `taxa` WHERE `taxon` = '".$name_string."';";
    $res = $db->query($sql);
    if ($res->num_rows == 0) {continue;}

    //If this is last term...
    if (($i+1)==$n_parts) {
      $taxon_match[] = array(
        "start" => $i,
        "length" => 1,
        "match" => $name_string
      );
    }
    for($l=$i+1; $l<$n_parts; $l++) {
      $last_name_string = $name_string;
      $name_string .= ' '.$parts[$l];
      $sql = "SELECT `taxon` FROM `taxa` WHERE `taxon` = '".$name_string."';";
      $res = $db->query($sql);

      if($res->num_rows == 0) {
        $length = $l - $i;
        $taxon_match[] = array(
          "start" => $i,
          "length" => $length,
          "match" => $last_name_string
        );
        break;
      }

      //If this is last term...
      if (($l + 1) == $n_parts) {
        $length = $l - $i + 1;
        $taxon_match[] = array(
          "start" => $i,
          "length" => $length,
          "match" => $name_string
        );
        break;
      }
    }
  }

  return($taxon_match);
}