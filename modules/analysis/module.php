<?php

function analysis_info() {
  $info = array(
    "mname" => "analysis",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Generic access to analysis information",
    "desc" => "Provide information about analyses performed",
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
            "desc" => "This query can be slow. Using the cache is highly recommended.",
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
            "desc" => "This query can be slow. Using the cache is highly recommended.",
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

/*
Which of the given tables the schema has. The counts below are one statement,
one subquery per analysis table, so a single dropped table made mysqli throw
and the endpoint answered HTTP 500 with an empty body rather than counting the
tables that were still there.

The names come from the modules' own definitions rather than from a caller, but
they are bound as parameters all the same.
*/
function analysis_tablesPresent($tables) {
  global $db;
  if (count($tables) == 0) {return(array());}
  $sql  = "SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` ";
  $sql .= "WHERE `TABLE_SCHEMA` = 'audioblast' ";
  $sql .= "AND `TABLE_NAME` IN (".implode(", ", array_fill(0, count($tables), "?")).");";
  $stmt = $db->prepare($sql);
  if (!$stmt) {return(array());}
  $stmt->bind_param(str_repeat("s", count($tables)), ...$tables);
  $stmt->execute();
  $res = $stmt->get_result();
  $present = array();
  while ($row = $res->fetch_assoc()) {
    $present[] = $row["TABLE_NAME"];
  }
  $stmt->close();
  //Report the counts in the order the modules are defined in, not the order the
  //schema happens to list them in.
  return(array_values(array_intersect($tables, $present)));
}

function analysis_counts($params) {
  // Only this module's endpoint definition is needed to build the cache key,
  // so avoid loading every module until we know we have a cache miss.
  $analysis_module = loadModule("analysis");
  $wc = WHEREclause(generateParams($analysis_module["endpoints"]["fetch_analysis_counts"], $params));
  $speedbird_hash = hash("sha256", "ac".$wc);
  if($params["cache"]==true) {
    $ret = speedbird_get($speedbird_hash);
    if ($ret != FALSE) {
      return($ret);
    }
  }

  $tables = array();
  foreach (loadModules() as $name => $info) {
    if ($info["category"] != "analysis") {continue;}
    $tables[] = $info["table"];
  }
  $present = analysis_tablesPresent($tables);
  $missing = array_values(array_diff($tables, $present));

  $ret = array();
  $ret["data"]["counts"] = array();
  if (count($present) > 0) {
    $sql = "SELECT ";
    $i = 0;
    foreach ($present as $table) {
      if ($i > 0) { $sql .= ", ";}
      $sql .= "(SELECT COUNT(*) as `total` FROM `audioblast`.`".$table."` ".$wc.") AS `".$table."`";
      $i++;
    }
    $sql .= " FROM DUAL;";

    global $db;
    $res = $db->query($sql);
    while ($row = $res->fetch_assoc()) {
      $ret["data"]["counts"] = $row;
    }
  }
  $ret["data"]["total"] = array_sum($ret["data"]["counts"]);
  //A missing table is said rather than passed over, so that a total short of
  //the analysis modules on offer can be told from a total that is simply low.
  if (count($missing) > 0) {
    $ret["data"]["unavailable"] = $missing;
    //A count taken while a table was missing is not kept: a cache entry has no
    //expiry, so it would be served as the total long after the table came back.
    return($ret);
  }
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
  // This endpoint never iterates the full module list, so load just the one
  // module needed to build the cache key.
  $analysis_module = loadModule("analysis");
  $wc = WHEREclause(generateParams($analysis_module["endpoints"]["fetch_analysis_status"], $params));
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
