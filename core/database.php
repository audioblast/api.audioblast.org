<?php

//Load database settings.
if (file_exists("settings/db.php")) {
  //The database check reports a failed connection itself, where PHP 8.1 and
  //later would otherwise stop with an error. PHP's own warnings are kept out of
  //its reply, as they can include the database user and host.
  if ($_SERVER['REQUEST_URI'] == "/dbping") {
    mysqli_report(MYSQLI_REPORT_OFF);
    ini_set("display_errors", "0");
  }
  include("settings/db.php");
} else {
  print("settings/db.php does not exist!");
  exit;
}

//Check the database is up, for status monitoring: it must take the connection
//and answer a query. The reason for a failure isn't shown, as it can include
//the database user and host.
if ($_SERVER['REQUEST_URI'] == "/dbping") {
  header("Cache-Control: no-store");
  if ($db->connect_error || !$db->query("SELECT 1;")) {
    http_response_code(503);
    print("Database connection failed: the database is not answering");
  } else {
    print("pong");
  }
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

    // Rows with a value in the column (neither NULL nor empty), e.g. those
    // with an identifier for RDF output
    if ($filter["op"] == "notempty") {
      $wc .= "`".$filter["column"]."` <> '' ";
      $i++;
      continue;
    }

    // Opt-in index-backed full-text search for the `contains` op. Activated by
    // setting "fulltext" => TRUE on the param; requires a FULLTEXT index on the
    // column. Without the flag, `contains` keeps the LIKE '%value%' behaviour
    // below. Boolean mode with a trailing wildcard gives prefix matching; the
    // value is already SQL-escaped, and we strip the boolean operators so user
    // punctuation can't reshape the search.
    if ($filter["op"] == "contains" && !empty($filter["fulltext"])) {
      $term = str_replace(array('+','-','*','"','(',')','~','<','>','@'), ' ', $filter["value"]);
      $wc .= "MATCH(`".$filter["column"]."`) AGAINST ('".$term."*' IN BOOLEAN MODE) ";
      $i++;
      continue;
    }

    // Range columns can be stored as text, which MySQL compares
    // alphabetically ('127' falls between '10' and '20'), so compare them
    // as numbers instead.
    if ($filter["type"] == "range") {
      $wc .= "CAST(`".$filter["column"]."` AS DECIMAL(65,10)) ";
    } else {
      $wc .= "`".$filter["column"]."` ";
    }
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
      case ">=":
        $wc .= ">= ";
        break;
      case "<=":
        $wc .= "<= ";
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
      case "range":
        // Keep the already-escaped value quoted, and cast it to match the
        // column cast above.
        $wc .= "CAST('".$filter["value"]."' AS DECIMAL(65,10)) ";
        break;
      default:
        // Non-string types (integer/boolean) are numeric. Quote the
        // already-escaped value so it cannot break out of the literal;
        // MySQL coerces the quoted value when comparing numeric columns.
        $wc .= "'".$filter["value"]."' ";
    }
    $i++;
  }
  return($wc);
}
