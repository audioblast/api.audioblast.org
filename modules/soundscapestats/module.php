<?php

function soundscapestats_info() {
  $info = array(
    "mname" => "soundscapestats",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Generic stats on soundscapes",
    "desc" => "Provides stats on soundscapes",
    "endpoints" => array(
      "fetch_soundscapes_day_counts" => array(
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
  $wc = WHEREclause(generateParams($modules["soundscapestats"]["endpoints"]["soundscapes_day_counts"], $params));

  $sql  = "SELECT STR_TO_DATE(`Date`, '%Y-%m-%d') as `date`, COUNT(*) as `count`, SUM((`Duration`) as `duration` ";
  $sql .= " FROM `audioblast`.`recordings` WHERE `deployment ='".$params["deployment"]."' ";
  $sql .= " AND YEAR(`Date`) = '".$params["year"]."' ";
  $sql .= " GROUP BY `date` ";

  global $db;
  $res = $db->query($sql);
  $ret = array();
  while ($row = $res->fetch_assoc()) {
    $ret["data"]["days"] = $row;
  }
  return($ret);
}
