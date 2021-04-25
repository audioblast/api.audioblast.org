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
      )
    )
  );
  return($info);
}

function seqss_deployment_latest($f) {
  $output = array();

  if (!isset($f["deployment"])) {
    $output["notes"][] = "No deployment ID set in query";
    return($output);
  }

  global $db;
  $sql = "SELECT * FROM `audioblast`.`deployments` WHERE `deployments`.`id` = '".$f["deployment"]."';";
  $res = $db->query($sql);
  if (mysqli_num_rows($res) == 1) {
    $output["deployment"] = $res->fetch_assoc();
  } else {
    $output["notes"][] = "Non atomic return from deployment query.";
    return($output);
  }
  $res->close();

  $sql = "SELECT * FROM `audioblast`.`devices` WHERE `devices`.`id` = '".$output["deployment"]["device"]."';";
  $res = $db->query($sql);
  if (mysqli_num_rows($res) == 1) {
    $output["deployment"]["device"] = $res->fetch_assoc();
  } else {
    $output["notes"] = "Non atomic return form device query.";
    return($output);
  }
  $res-> close();

  $sql = "SELECT * FROM `audioblast`.`sensors` WHERE `device` = '".$output["deployment"]["device"]["id"]."';";
  $res = $db->query($sql);
  $output["deployment"]["device"]["sensors"] = $res->fetch_all(MYSQLI_ASSOC);
  $res->close();
  return($output);
}
