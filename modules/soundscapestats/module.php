<?php

function soundscapestats_info() {
  ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
  $info = array(
    "mname" => "soundscapestats",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Generic stats on soundscapes",
    "desc" => "Provides stats on soundscapes",
    "endpoints" => array(
      "day_counts" => array(
        "callback" => "soundscapes_day_counts",
        "desc" => "Returns a count of soundscape by recordings by day.",
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
          "deployment" => array(
            "desc" => "filter by deployment within source",
            "type" => "string",
            "default" => "",
            "column" => "deployment",
            "op" => "="
          ),
          "year" => array(
            "desc" => "filter by year within deployment",
            "type" => "string",
            "default" => "",
            "column" => "year",
            "op" => "="
          )
        )
      )
    )
  );
  return($info);
}

function soundscapes_day_counts($params) {
  $modules = loadModules();

  $sql  = "SELECT STR_TO_DATE(`Date`, '%Y-%m-%d') as `date`, COUNT(*) as `count`, SUM(`Duration`) as `duration` ";
  $sql .= "FROM `audioblast`.`recordings` ";
  $wcc = 0;
  if ($params["source"] != "") {
    $sql .= "WHERE `source` ='".$params["source"]."' ";
    $wcc++;
  }
  if ($params["deployment"] != "") {
    $sql .= ($wcc > 0) ? "AND " : "WHERE ";
    $sql .= "`deployment` ='".$params["deployment"]."' ";
    $wcc++;
  }
  if ($params["year"] != "") {
    $sql .= ($wcc > 0) ? "AND " : "WHERE ";
    $sql .= "YEAR(`Date`) = '".$params["year"]."' ";
    $wcc++;
  }
  $sql .= "GROUP BY `date`;";

  global $db;
  $res = $db->query($sql);
  $ret = array();
  while ($row = $res->fetch_assoc()) {
    $ret["data"]["days"][] = $row;
  }

  return($ret);
}
