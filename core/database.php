<?php

//Load database settings.
if (file_exists("settings/db.php")) {
  include("settings/db.php");
} else {
  print("settings/db.php does not exist!");
  exit;
}

//Check that $db is a database connection.
if (!isset($db)) {
  print("No database connection!");
  exit;
}

function SELECTclause($module, $field=NULL, $mode="table", $format="internal") {
  $ret = "SELECT ";

  if ($mode=="autocomplete") {
    $ret .= "DISTINCT(`";
    $ret .= $module["params"][$field]["column"];
    $ret .= "`) as `";
    $ret .= $field;
    $ret .= "` ";
  }

  if ($mode=="table") {
    $i = 0;
    foreach ($module["params"] as $pname => $pinfo) {
      if ($pname != "output" && $pname != "format") {
        if ($i > 0) {$ret .= ", ";}
        if ($format=="ac" && isset($pinfo["ac"])) {
          $ret .= "`".$pinfo["column"]."` as `".$pinfo["ac"]."`";
        } else {
          $ret .= "`".$pinfo["column"]."` as `".$pname."`";
        }
        $i++;
      }
    }
  }

  $ret .= " FROM ";
  $ret .= "`audioblast`.`".$module["table"]."`";
  return($ret);
}

function SELECTcount($module) {
  $ret = "SELECT COUNT(*) as `total`";
  $ret .= " FROM ";
  $ret .= "`audioblast`.`".$module["table"]."`";
  return($ret);
}

function WHEREclause($filters) {
  if ($filters == "") {return("");}
  $i = 0;
  $wc = "";
  foreach ($filters as $filter) {
    if ($filter["column"] == "") {continue;}
    if ($filter["value"] == "") {continue;}
    if ($i > 0) { $wc .= "AND "; } else { $wc.= " WHERE ";}
    $wc .= "`".$filter["column"]."` ";
    switch ($filter["op"]) {
      case "=":
        $wc .= "= ";
        break;
      case ">":
        $wc .= "> ";
        break;
      case "<":
        $wc .= "< ";
        break;
      case "contains":
        $wc .= "LIKE ";
        break;
      case "starts":
        $wc .= "LIKE ";
        break;
    }
    switch($filter["type"]) {
      case "string":
        switch($filter["op"]) {
          case "contains":
            $wc .= "'%".$filter["value"]."%' ";
            break;

          case "starts":
            $wc .= "'".$filter["value"]."%' ";
            break;

          default:
            $wc .= "'".$filter["value"]."' ";
        }
        break;
      default:
        $wc .= $filter["value"]. " ";
    }
    $i++;
  }
  return($wc);
}
