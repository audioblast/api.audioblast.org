<?php

function analysis_info() {
  $info = array(
    "mname" => "analysis",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Generic access to analysis information",
    "desc" => "Provide information about analyses perfomed",
    "endpoints" => array(
      "fetch_analysis_counts" => array(
        "callback" => "analysis_counts",
        "desc" => "Returns a count of analysis results.",
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
          )
        )
      ),
      "fetch_analysis_status" => array(
        "callback" => "analysis_status",
        "desc" => "Returns the status of a source in the analysis pipeline.",
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
          "cache" => array(
            "desc" => "This query can be slow. Using the cache is highly reccommended.",
            "type" => "boolean",
            "default" => 1,
            "op" => "="
          )
        )
      ),
      "analysis_agents" => array(
        "callback" => "analysis_agents",
        "desc" => "Returns the number of analysis agents.",
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
      "list_analysis" => array(
        "callback" => "analysis_list",
        "desc" => "Returns a list of analysis types.",
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
      )
    )
  );
  return($info);
}

function analysis_counts($params) {
  $modules = loadModules();
  $wc = WHEREclause(generateParams($modules["analysis"]["endpoints"]["fetch_analysis_counts"], $params));
  $speedbird_hash = hash("sha256", "ac".$wc);
  if($params["cache"]==true) {
    $ret = speedbird_get($speedbird_hash);
    if ($ret != FALSE) {
      return($ret);
    }
  }
  $sql = "SELECT ";
  $i = 0;
  
  foreach ($modules as $name => $info) {
    if ($info["category"] != "analysis") {continue;}
    if ($i > 0) { $sql .= ", ";}
    $sql .= "(SELECT COUNT(*) as `total` FROM `audioblast`.`".$info["table"]."` ".$wc.") AS `".$info["table"]."`";
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
  speedbird_put($speedbird_hash, serialize($ret));
  return($ret);
}

function analysis_agents($params) {
  $sql = "SELECT COUNT(DISTINCT `process`) AS `total` FROM `tasks-progress`;";
  global $db;
  $res = $db->query($sql);
  $ret = array();
  while ($row = $res->fetch_assoc()) {
    $ret["data"]["agents"] = $row;
  }
  return($ret);
}

function analysis_list($params) {
  $modules = loadModules();
  $ret = array();
  foreach ($modules as $name => $info) {
    if ($info["category"] != "analysis") {continue;}
    $ret[] = $name;
  }
  return($ret);
}

function analysis_status($params) {
  $modules = loadModules();
  $wc = WHEREclause(generateParams($modules["analysis"]["endpoints"]["fetch_analysis_status"], $params));
  $speedbird_hash = hash("sha256", "as".$wc);
  if($params["cache"]==true) {
    $ret = speedbird_get($speedbird_hash);
    if ($ret != FALSE) {
      return($ret);
    }
  }

  $sql  = "SELECT ";
  $sql .= "  `assigned`, ";
  $sql .= "  `total` - `assigned` AS `waiting`, ";
  $sql .= "  `total` ";
  $sql .= "FROM (";
  $sql .= "  SELECT";
  $sql .= "    (SELECT COUNT(*) FROM audioblast.`tasks-progress` ".$wc.") AS `assigned`,";
  $sql .= "    (SELECT COUNT(*) FROM audioblast.`tasks` ".$wc.") AS `total`";
  $sql .= "  FROM DUAL)  AS `intermediate`;";
  
  global $db;
  $res = $db->query($sql);
  $ret = array();
  while ($row = $res->fetch_assoc()) {
    $ret["data"]["counts"] = $row;
  }
  speedbird_put($speedbird_hash, serialize($ret));
  return($ret);
}
