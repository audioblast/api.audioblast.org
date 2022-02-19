<?php

function data_info() {
  $info = array(
    "mname" => "data",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Generic access to data information",
    "desc" => "Provide information about data contained in audioBlast",
    "endpoints" => array(
      "fetch_data_counts" => array(
        "callback" => "data_counts",
        "desc" => "Returns a count of data items.",
        "returns" => "data",
        "params" => array(
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          ),
          "source" => array(
            "desc" => "Filter by source",
            "type" => "string",
            "default" => "",
            "column" => "source",
            "op" => "=",
          ),
          "id" => array(
            "desc" => "filter by id within source",
            "type" => "string",
            "default" => "",
            "column" => "id",
            "op" => "="
          ),
          "cache" => array(
            "desc" => "This query can be slow. Using the cache is highly reccommended.",
            "type" => "boolean",
            "default" => 1,
            "op" => "="
          ),
        )
      ),
      "list_data" => array(
        "callback" => "data_list",
        "desc" => "Returns a list of data types.",
        "returns" => "data",
        "params" => array(
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          )
        )
      ),
      "list_hours" => array(
        "callback" => "data_hours",
        "desc" => "Returns total number of hours of audio in audioBlast.",
        "returns" => "data",
        "params" => array(
          "cache" => array(
            "desc" => "This query can be slow. Using the cache is highly reccommended.",
            "type" => "boolean",
            "default" => 1,
            "op" => "="
          ),
          "output" => array(
            "desc" => "At present just an array",
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

function data_counts($params) {
  if($params["cache"]==true) {
    $ret = speedbird_get("datacount");
    if ($ret != FALSE) {
      return($ret);
    }
  }
  $modules = loadModules();
  $sql = "SELECT ";
  $i = 0;
  $wc = WHEREclause(generateParams($modules["data"]["endpoints"]["fetch_data_counts"], $params));
  foreach ($modules as $name => $info) {
    if ($info["category"] != "data") {continue;}
    if ($i > 0) { $sql .= ", ";}
    $sql .= "(SELECT COUNT(*) FROM `audioblast`.`".$info["table"]."` ".$wc.") AS `".$info["table"]."`";
    $i++;
  }
  $sql .= " FROM DUAL;";

  global $db;
  $res = $db->query($sql);
  $ret = array();
  while ($row = $res->fetch_assoc()) {
    $ret["data"]["counts"] = $row;
  }
  $ret["data"]["total"] = array_sum($ret["data"]["counts"]);
  speedbird_put("datacount", serialize($ret));
  return($ret);
}

function data_list($params) {
  $modules = loadModules();
  $ret = array();
  foreach ($modules as $name => $info) {
    if ($info["category"] != "data") {continue;}
    $ret[] = $name;
  }
  return($ret);
}

function data_hours($params) {
  if($params["cache"]==true) {
    $ret = speedbird_get("datahours");
    if ($ret != FALSE) {
      return($ret);
    }
  }
  $sql= "SELECT SUM(`Duration`)/3600 as `hours` FROM `recordings`;";
  global $db;
  $ret = array();
  $res = $db->query($sql);
  $ret["data"] = $res->fetch_assoc();
  speedbird_put("datahours", serialize($ret));
  return($ret);
}
