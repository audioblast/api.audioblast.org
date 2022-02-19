<?php

/*
List columns that do not need to go through generateParams().
*/
function listOutputColumns() {
  return(array(
    "output",
    "cache"
  ));
}

function generateParams($params, $inputs) {
    $ret = array();
    foreach($params["params"] as $name => $data) {
     if (in_array($name, listOutputColumns())) {continue;}
     if (isset($inputs[$name])) {
        if ($inputs[$name] == "") {continue;}
        switch ($params["params"][$name]["op"]) {
          case "range":
            if (!isset($params["params"][$name]["column"]) {continue;}
            $ret = filterMerge($ret, filterABrange($params["params"][$name]["column"], $inputs[$name], $params["params"][$name]["type"]));
            break;
          default:
            $ret[] = array(
              "column" => isset($params["params"][$name]["column"]) ? $params["params"][$name]["column"] : "",
              "op" => isset9$params["params"][$name]["op"]) ? $params["params"][$name]["op"] : "",
              "value" => $inputs[$name],
              "type" => $params["params"][$name]["type"]
            );
        }
      }
    }
    return($ret);
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
