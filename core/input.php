<?php

/*
List columns that do not need to go through generateParams().
*/
function listOutputColumns() {
  return(array(
    "output",
    "format",
    "cache"
  ));
}

/*
Parameters the framework reads for itself, whatever module is being asked for,
and so are not a module's to declare. `path` is set by the rewrite that routes
every request to index.php, so it arrives on every request. `s` and `c` carry
the autocomplete query and mean nothing on any other endpoint.
*/
function listControlParams($endpoint=NULL) {
  $control = array(
    "path",
    "page",
    "page_size",
    "filter"
  );
  if ($endpoint === "autocomplete") {
    $control[] = "s";
    $control[] = "c";
  }
  return($control);
}

/*
The names a module can be filtered by: those it declares an operator for, which
is what generateParams() needs to build a condition. Parameters that choose how
the rows are represented rather than which rows they are (listOutputColumns())
are not filters.
*/
function listFilterParams($module) {
  $ret = array();
  foreach (($module["params"] ?? array()) as $name => $info) {
    if (in_array($name, listOutputColumns())) {continue;}
    if (in_array($info["op"] ?? "none", array("", "none"))) {continue;}
    $ret[] = $name;
  }
  return($ret);
}

/*
Say what is wrong with a parameter, and what the module would have taken, so the
name that was meant can be found without a trip to the documentation.
*/
function paramProblem($module, $name, $problem, $endpoint=NULL) {
  $msg = "Parameter `".$name."` ".$problem.".";
  if (isset($module["callback"])) {
    //An endpoint hands every parameter to its callback, filters or not
    return($msg." This endpoint takes: ".implode(", ", array_keys($module["params"])).".");
  }
  $filters = listFilterParams($module);
  if (count($filters) > 0) {
    $msg .= " This module can be filtered by: ".implode(", ", $filters).".";
  }
  //`path` is the rewrite's own, so it is accepted but not advertised
  $others = array_merge(
    array_values(array_intersect(array_keys($module["params"]), listOutputColumns())),
    array("page", "page_size")
  );
  return($msg." It also takes: ".implode(", ", $others).".");
}

/*
Check what was asked for against what the module declares.

A parameter naming nothing the module knows used to be dropped in silence: the
query then ran without it and returned the whole table under an HTTP 200, so a
mistyped or imagined filter came back as a plausible answer rather than as an
error. A wrong answer that looks right is worse than no answer, so the offending
name is reported instead, as an unrecognised module is.

A module that hands its parameters to a callback (an endpoint, an embed) is the
judge of what they mean, so only parameters that become conditions in a query
are held to being filterable. Returns the message to give the caller, or NULL
when every input is usable.
*/
function checkParams($module, $inputs, $endpoint=NULL) {
  if (!isset($module["params"])) {return(NULL);}
  $control = listControlParams($endpoint);
  //Only a module whose parameters reach generateParams() needs them filterable
  $mustFilter = !isset($module["callback"]);

  foreach (array_keys($inputs) as $name) {
    if ($name === "filter") {continue;}  //Checked below, by the field it names
    if (in_array($name, $control)) {continue;}
    if (!isset($module["params"][$name])) {
      return(paramProblem($module, $name, "is not recognised", $endpoint));
    }
    if ($mustFilter && !in_array($name, listOutputColumns())
        && in_array($module["params"][$name]["op"] ?? "none", array("", "none"))) {
      return(paramProblem($module, $name, "cannot be filtered on", $endpoint));
    }
  }

  //Tabulator names the field each of its filters applies to. Those are written
  //straight into the parameters, so the names are checked in the same way.
  if (isset($inputs["filter"])) {
    if (!is_array($inputs["filter"])) {
      return("Filter must be given as an array of filters.");
    }
    foreach ($inputs["filter"] as $filter) {
      if (!is_array($filter) || !isset($filter["field"])) {
        return("Each filter must name the field it applies to.");
      }
      if (!isset($module["params"][$filter["field"]])) {
        return(paramProblem($module, $filter["field"], "is not recognised", $endpoint));
      }
      if (!in_array($filter["field"], listFilterParams($module))) {
        return(paramProblem($module, $filter["field"], "cannot be filtered on", $endpoint));
      }
    }
  }
  return(NULL);
}

function generateParams($params, $inputs) {
    $ret = array();
    foreach($params["params"] as $name => $data) {
      if (in_array($name, listOutputColumns())) {continue;}
      if (isset($inputs[$name])) {
      if ($inputs[$name] == "") {continue;}
      //Columns that can't be filtered on have no operator to build a condition with
      if (($data["op"] ?? "") == "none") {continue;}
      switch ($params["params"][$name]["op"]) {
        case "range":
          $ret = filterMerge($ret, filterABrange($params["params"][$name]["column"], $inputs[$name], $params["params"][$name]["type"]));
          break;
        default:
          $ret[] = array(
            "column" => $params["params"][$name]["column"],
            "op" => $params["params"][$name]["op"],
            "value" => $inputs[$name],
            "type" => $params["params"][$name]["type"],
            "fulltext" => !empty($params["params"][$name]["fulltext"])
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
  //"min:max" is inclusive at both ends; either end may be left empty
  $rangesplit = strpos($value, ":");
  if ($rangesplit !== FALSE) {
    return(array(
      array(
        "column" => $column,
        "op" => ">=",
        "value" => substr($value, 0, $rangesplit),
        "type" => $type
      ),
      array(
        "column" => $column,
        "op" => "<=",
        "value" => substr($value, $rangesplit+1),
        "type" => $type
      )
    ));
  }
  //">=x" and "<=x" are inclusive; ">x" and "<x" stay exclusive
  $prefix = substr($value, 0, 2);
  if ($prefix == ">=" || $prefix == "<=") {
    return(array(array(
      "column" => $column,
      "op" => $prefix,
      "value" => substr($value, 2),
      "type" => $type
    )));
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
