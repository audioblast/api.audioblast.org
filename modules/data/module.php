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
            "desc" => "This query can be slow. Using the cache is highly recommended.",
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
            "desc" => "This query can be slow. Using the cache is highly recommended.",
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
  // Only this module's endpoint definition is needed to build the cache key,
  // so avoid loading every module until we know we have a cache miss.
  $data_module = loadModule("data");
  $wc = WHEREclause(generateParams($data_module["endpoints"]["fetch_data_counts"], $params));
  $speedbird_hash = hash("sha256", "dc-v3".$wc);
  if($params["cache"]==true) {
    $ret = speedbird_get($speedbird_hash);
    if ($ret != FALSE) {
      return($ret);
    }
  }
  $modules = loadModules();
  $sql = "SELECT ";
  $i = 0;
  foreach ($modules as $name => $info) {
    if ($info["category"] != "data") {continue;}
    if ($i > 0) { $sql .= ", ";}
    // Each count is named after the endpoint it belongs to, not the table behind it: a table may be a view,
    // as recordings is, or named differently, as recordings-taxa is, and neither is what a client asks for.
    $sql .= "(SELECT COUNT(*) FROM `audioblast`.`".$info["table"]."` ".$wc.") AS `".$info["mname"]."`";
    $i++;
  }
  $links_wc = $wc.(trim($wc) === "" ? " WHERE " : " AND ");
  $links_wc .= "`subject_source` <> '' AND `object_source` <> '' AND `subject_source` <> `object_source`";
  $sql .= ($i > 0 ? ", " : "")."(SELECT COUNT(*) FROM `audioblast`.`links` ".$links_wc.") AS `cross_source_links`";
  $sql .= " FROM DUAL;";

  global $db;
  $res = $db->query($sql);
  $ret = array();
  while ($row = $res->fetch_assoc()) {
    // This is a subset of links, not another data type to add to the total.
    $ret["data"]["cross_source_links"] = $row["cross_source_links"];
    unset($row["cross_source_links"]);
    $ret["data"]["counts"] = $row;
  }
  $ret["data"]["total"] = array_sum($ret["data"]["counts"]);
  speedbird_put($speedbird_hash, serialize($ret));
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
