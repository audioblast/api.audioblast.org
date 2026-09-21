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
The filters of a module that take several values at once (see paramTakesMany()).
*/
function listMultiParams($module) {
  $ret = array();
  foreach (($module["params"] ?? array()) as $name => $info) {
    if (paramTakesMany($info)) {$ret[] = $name;}
  }
  return($ret);
}

/*
The most values one filter may be given. Matching a hundred ids at once is one
condition on one indexed column returning at most a page of rows, so it costs
the database about what matching one id costs; a list long enough to be a scan
of the table in disguise is refused rather than answered slowly.
*/
define("MAX_FILTER_VALUES", 100);

/*
The values a filter was given. They are written together, `id=a,b,c`, or as a
repeated list, `id[]=a&id[]=b`, which is the way to ask for a value with a comma
in it. Spaces around a value are not part of it, so a caller that joins its
values with ", " asks for the values it named.
*/
function filterValues($value) {
  if (is_array($value)) {return(array_values($value));}
  return(array_map("trim", explode(",", $value)));
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
Say what is wrong with the values a parameter was given.

A parameter that takes one value and is given several kept the last of them, so
a request naming three records was answered about one of them, with nothing in
the reply to say the other two had been dropped.
*/
function checkValues($module, $name, $value) {
  if (!paramTakesMany($module["params"][$name])) {
    if (!is_array($value)) {return(NULL);}
    $msg = "Parameter `".$name."` takes one value.";
    $many = listMultiParams($module);
    if (count($many) > 0) {
      $msg .= " These take several: ".implode(", ", $many).".";
    }
    return($msg);
  }
  //An empty value asks for no filter on the column, as it always has
  if ($value === "") {return(NULL);}
  $values = filterValues($value);
  if (count($values) > MAX_FILTER_VALUES) {
    return("Parameter `".$name."` was given ".count($values)." values, and takes at most "
      .MAX_FILTER_VALUES.".");
  }
  foreach ($values as $one) {
    if ($one !== "") {continue;}
    //`id=12,` and `id=,` name a value that is nothing, which would otherwise
    //be dropped and leave the column matching whatever remained, or nothing
    return("Parameter `".$name."` was given an empty value among its values."
      ." Several values are written `".$name."=a,b`.");
  }
  return(NULL);
}

/*
Say what is wrong with the query string itself, rather than with any one of the
parameters in it.

A parameter given twice (`?id=12&id=15`) keeps the last of them and drops the
rest, so a request naming two records was answered about one of them and read
as an answer about both. Several values are given to one parameter instead (see
filterValues()), and a parameter given twice is refused.

The bracketed form (`?id[]=12&id[]=15`) is a list, and repeats the name on
purpose, so it is left to checkValues() along with every other list.
*/
function checkQueryString($module, $query) {
  $given = array();
  foreach (explode("&", (string)$query) as $pair) {
    if ($pair === "") {continue;}
    $name = urldecode(explode("=", $pair, 2)[0]);
    $bracket = strpos($name, "[");
    $base = ($bracket === FALSE) ? $name : substr($name, 0, $bracket);
    if (!isset($given[$base])) {$given[$base] = array("all" => 0, "plain" => 0);}
    $given[$base]["all"]++;
    if ($bracket === FALSE) {$given[$base]["plain"]++;}
  }
  foreach ($given as $name => $counts) {
    if ($counts["all"] < 2 || $counts["plain"] === 0) {continue;}
    $msg = "Parameter `".$name."` was given more than once, and all but the last would be dropped.";
    if (paramTakesMany($module["params"][$name] ?? array())) {
      $msg .= " Give it all of its values at once, as `".$name."=a,b`.";
    } else {
      $msg .= " It takes one value.";
    }
    return($msg);
  }
  return(NULL);
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
    $problem = checkValues($module, $name, $inputs[$name]);
    if ($problem !== NULL) {return($problem);}
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
      if (is_array($inputs[$name])) {
        if (!$inputs[$name]) {continue;}
      } else if ($inputs[$name] == "") {continue;}
      //Columns that can't be filtered on have no operator to build a condition with
      if (($data["op"] ?? "") == "none") {continue;}
      //A filter given several values (see paramTakesMany(), and the splitting
      //in moduleAPI()) matches a row holding any one of them
      if (is_array($inputs[$name])) {
        $ret[] = array(
          "column" => $data["column"],
          "op" => "in",
          "value" => array_values($inputs[$name]),
          "type" => $data["type"],
          "fulltext" => FALSE
        );
        continue;
      }
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
