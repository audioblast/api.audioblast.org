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
        "returns" => "sql",
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
  $ret = "SELECT ";
  $i = 0;
  $wc = WHEREclause(generateParams($modules["analysis"]["endpoints"]["fetch_analysis_counts"], $params));
  foreach ($modules as $name => $info) {
    if ($info["category"] != "analysis") {continue;}
    if ($i > 0) { $ret .= ", ";}
    $ret .= "(SELECT COUNT(*) FROM `audioblast`.`".$info["table"]."` ".$wc.") AS `".$info["table"]."`";
    $i++;
  }
  $ret .= " FROM DUAL;";
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
