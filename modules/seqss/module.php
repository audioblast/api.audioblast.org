<?php

function seqss_info() {
  $info = array(
    "mname" => "seqss",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Sequential soundscapes",
    "desc" => "Generic access point for multiple data types relating to sequential soundscape deployments.",
    "endpoints" => array(
      "deployment_latest" => array(
        "callback" => "seqss_deployment_latest",
        "desc" => "Returns latest data for a soundscape deployment",
        "returns" => "data",
        "params" => array(
          "deployment" => array(
            "desc" => "Deployment ID",
            "type" => "string",
            "op" => "=",
          ),
          "output" => array(
            "desc" => "JSON object",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          )
        )
      ),
      "recording_nearest_start_time" => array(
        "callback" => "seqss_recording_nearest_start_time",
        "desc" => "finds recording with nearest start time matching parameters",
        "returns" => "data",
        "params" => array(
          "source" => array(
            "desc" => "Filter by source",
            "type" => "string",
            "op" => "="
          ),
          "date" => array(
            "desc" => "Date to match (YYYY-MM-DD)",
            "type" => "string",
            "op" => "="
          ),
          "time" => array(
            "desc" => "Time to match (HHMM)",
            "type" => "string",
            "op" => "="
          ),
          "window" => array(
            "desc" => "Maximum allowed difference (seconds)",
            "type" => "integer",
            "default" => 300
          ),
          "output" => array(
            "desc" => "JSON object",
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

function seqss_recording_nearest_start_time($f) {
  $ret = array();
  if (!isset($f["date"]) || !isset($f["time"])) {
    $ret["notes"][] = "Must set date and time";
    return($ret);
  }
  if (!is_string($f["date"]) || !preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $f["date"], $ymd) || !checkdate((int)$ymd[2], (int)$ymd[3], (int)$ymd[1])) {
    $ret["notes"][] = "Date must be a valid date in the form YYYY-MM-DD";
    return($ret);
  }
  if (!is_string($f["time"]) || !preg_match('/^([01][0-9]|2[0-3])[0-5][0-9]$/D', $f["time"])) {
    $ret["notes"][] = "Time must be a valid time in the form HHMM";
    return($ret);
  }
  if (!is_scalar($f["window"] ?? NULL) || !preg_match('/^[0-9]+$/D', (string)$f["window"])) {
    $ret["notes"][] = "Window must be a non-negative integer";
    return($ret);
  }
  if (isset($f["source"]) && !_seqss_bindable($f["source"])) {
    $ret["notes"][] = "Invalid source";
    return($ret);
  }
  // Safe to slice now that date and time have been validated.
  $dt_string = $f["date"]." ".substr($f["time"],0,2).":".substr($f["time"],2,2);

  $sql  = "SELECT *, ABS(TIMESTAMPDIFF(SECOND,?,CONCAT(`date`,' ',SUBSTRING(`time`,1,2),':',SUBSTRING(`time`,3,2),':00'))) AS `diff` ";
  $sql .= "FROM `audioblast`.`recordings` WHERE `time` REGEXP '^[0-9]{4}$' AND `date` REGEXP '^[0-9]{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$' ";
  $types = "s";
  $values = array($dt_string);
  if (isset($f["source"])) {
    $sql .= "AND `source` = ? ";
    $types .= "s";
    $values[] = $f["source"];
  }
  $sql .= "HAVING `diff` <= ? ORDER BY `diff`;";
  $types .= "i";
  $values[] = (int)$f["window"];

  $query_start_time = microtime(true);
  $rows = _seqss_fetch_all($sql, $types, $values);
  $ret["notes"]["query_execution_time"] = microtime(true) - $query_start_time;
  if ($rows === FALSE) {
    $ret["notes"][] = "Query failed on database.";
    return($ret);
  }
  $ret["data"] = $rows;
  return($ret);
}

function seqss_deployment_latest($f) {
  $output = array();

  if (!isset($f["deployment"])) {
    $output["notes"][] = "No deployment ID set in query";
    return($output);
  }
  if (!_seqss_bindable($f["deployment"])) {
    $output["notes"][] = "Invalid deployment ID";
    return($output);
  }

  $query_start_time = microtime(true);
  $rows = _seqss_fetch_all("SELECT * FROM `audioblast`.`deployments` WHERE `deployments`.`id` = ?;", "s", array($f["deployment"]));
  if ($rows && count($rows) == 1) {
    $output["data"]["deployment"] = $rows[0];
  } else {
    $output["notes"][] = "Non atomic return from deployment query.";
    return($output);
  }

  $rows = _seqss_fetch_all("SELECT * FROM `audioblast`.`devices` WHERE `devices`.`id` = ?;", "s", array($output["data"]["deployment"]["device"]));
  if ($rows && count($rows) == 1) {
    $output["data"]["deployment"]["device"] = $rows[0];
  } else {
    $output["notes"][] = "Non atomic return form device query.";
    return($output);
  }

  $rows = _seqss_fetch_all("SELECT * FROM `audioblast`.`sensors` WHERE `device` = ?;", "s", array($output["data"]["deployment"]["device"]["id"]));
  if ($rows === FALSE) {
    $output["notes"][] = "Query failed on database.";
    return($output);
  }
  $output["data"]["deployment"]["device"]["sensors"] = $rows;
  $output["notes"]["query_execution_time"] = microtime(true) - $query_start_time;
  return($output);
}

/*
Request params reach module callbacks already escaped by
mysqli_real_escape_string (see core/api.php), so binding one as-is would escape
it a second time. Escaping only ever adds a backslash (or, under
NO_BACKSLASH_ESCAPES, doubles a single quote), so a string containing neither
was left unchanged and can be bound directly.
*/
function _seqss_bindable($value) {
  return(is_string($value) && strpbrk($value, "\\'") === FALSE);
}

/*
Run a prepared statement, binding $values according to the bind_param() type
string $types, and return all rows as associative arrays, or FALSE on failure.
*/
function _seqss_fetch_all($sql, $types, $values) {
  global $db;
  $stmt = $db->prepare($sql);
  if (!$stmt) {return(FALSE);}
  $stmt->bind_param($types, ...$values);
  $res = $stmt->execute() ? $stmt->get_result() : FALSE;
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : FALSE;
  $stmt->close();
  return($rows);
}
