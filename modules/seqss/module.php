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
  $dt_string = $f["date"]." ".substr($f["time"],0,2).":".substr($f["time"],2,2);

  global $db;
  $sql  = "SELECT *, ABS(TIMESTAMPDIFF(SECOND,'$dt_string',CONCAT(`date`,' ',SUBSTRING(`time`,1,2),':',SUBSTRING(`time`,3,2),':00'))) AS `diff` ";
  $sql .= "FROM `audioblast`.`recordings` WHERE `time` REGEXP '^[0-9]{4}$' AND `date` REGEXP '^[0-9]{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$' ";
  if (isset($f["source"])) {
    $sql .= "AND `source` = '".$f["source"]."' ";
  }
  $sql .= "HAVING `diff` <= ".$f["window"]." ORDER BY `diff`;";

  $query_start_time = microtime(true);
  $res = $db->query($sql);
  $ret["notes"]["query_execution_time"] = microtime(true) - $query_start_time;
  $ret["data"] = $res->fetch_all(MYSQLI_ASSOC);
  return($ret);
}

function seqss_deployment_latest($f) {
  $output = array();

  if (!isset($f["deployment"])) {
    $output["notes"][] = "No deployment ID set in query";
    return($output);
  }

  global $db;
  $query_start_time = microtime(true);
  $sql = "SELECT * FROM `audioblast`.`deployments` WHERE `deployments`.`id` = '".$f["deployment"]."';";
  $res = $db->query($sql);
  if (mysqli_num_rows($res) == 1) {
    $output["data"]["deployment"] = $res->fetch_assoc();
  } else {
    $output["notes"][] = "Non atomic return from deployment query.";
    return($output);
  }
  $res->close();

  $sql = "SELECT * FROM `audioblast`.`devices` WHERE `devices`.`id` = '".$output["data"]["deployment"]["device"]."';";
  $res = $db->query($sql);
  if (mysqli_num_rows($res) == 1) {
    $output["data"]["deployment"]["device"] = $res->fetch_assoc();
  } else {
    $output["notes"][] = "Non atomic return form device query.";
    return($output);
  }
  $res-> close();

  $sql = "SELECT * FROM `audioblast`.`sensors` WHERE `device` = '".$output["data"]["deployment"]["device"]["id"]."';";
  $res = $db->query($sql);
  $output["data"]["deployment"]["device"]["sensors"] = $res->fetch_all(MYSQLI_ASSOC);
  $res->close();
  $output["notes"]["query_execution_time"] = microtime(true) - $query_start_time;
  return($output);
}
