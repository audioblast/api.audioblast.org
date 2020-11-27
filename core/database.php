
<?php

if (file_exists("settings/db.php")) {
  include("settings/db.php");
} else {
  print("settings/db.php does not exist!");
  exit;
}

function generateParams($params, $inputs) {
  $ret = array();
  foreach($params["params"] as $name => $data) {
   if ($name == "output") {continue;}
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

function SELECTclause($module, $field=NULL, $mode="table") {
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
      if ($pname != "output") {
        if ($i > 0) {$ret .= ", ";}
        $ret .= "`".$pinfo["column"]."` as `".$pname."`";
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

function filterMerge($f1, $f2) {
  if (is_array($f1)) {
    if (is_array($f2)) {
      return(array_merge($f1, $f2));
    } else {
      return($f1);
    }
  }
}

function filterABrange($column, $value, $type) {
  $rangesplit = strpos($value, ":");
  if ($rangesplit != FALSE) {
    return(array(
      array(
        "column" => $column,
        "op" => ">",
        "value" => substr($value, 0, $rangesplit),
        "type" => $type
      ),
      array(
        "column" => $column,
        "op" => "<",
        "value" => substr($value, $rangesplit+1),
        "type" => $type
      )
    ));
  }
  $firstchar = substr($value, 0, 1);
  switch($firstchar) {
    case ">":
      return(array(array(
        "column" => $column,
        "op" => ">",
        "value" => substr($value, 1),
        "type" => $type
      )));
      break;
    case "<":
      return(array(array(
        "column" => $column,
        "op" => "<",
        "value" => substr($value, 1),
        "type" => $type
      )));
      break;
    default:
      return(array(array(
        "column" => $column,
        "op" => "=",
        "value" => $value,
        "type" => $type
      )));
  }
}
