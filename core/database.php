<?php

//Load database settings.
if (file_exists("settings/db.php")) {
  include("settings/db.php");
} else {
  print("settings/db.php does not exist!");
  exit;
}

function generateParams($params, $inputs) {
  $ret = array();
  foreach($params["params"] as $name => $data) {
   if ($name == "output" || $name == "format" || $name == "cache") {continue;}
   if (isset($inputs[$name])) {
      if ($inputs[$name] == "") {continue;}
      switch ($params["params"][$name]["op"]) {
        case "range":
          $ret = filterMerge($ret, filterABrange($params["params"][$name]["column"], $inputs[$name], $params["params"][$name]["type"]));
          break;
        default:
          $ret[] = array(
            "column" => $params["params"][$name]["column"],
            "op" => $params["params"][$name]["op"],
            "value" => $inputs[$name],
            "type" => $params["params"][$name]["type"]
          );
      }
    }
  }
  return($ret);
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
