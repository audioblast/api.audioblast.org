<?php

function tdsc_info() {
  $info = array(
    "mname" => "tdsc",
    "version" => 1.0,
    "category" => "analysis",
    "hname" => "Time Domain Signal Coding",
    "desc" => "Time domain signal coding is...",
    "table" => "analysis-tdsc",
    "code" => array(
    "type" => "package",
      "language" => "R",
      "name" => "tdsc",
      "source" => "CRAN",
      "function" => "tdsc()"
    ),
    "params" => array(
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
      "startTime" => array(
        "desc" => "start time(s) to return",
        "type" => "integer",
        "default" => 0,
        "column" => "startTime",
        "op" => "range"
      ),
      "value" => array(
        "desc" => "Output of analysis",
        "type" => "integer",
        "default" => 0,
        "column" => "tdsc",
        "op" => "range"
      ),
      "output" => array(
        "desc" => "At present just an array",
        "type" => "string",
        "allowed" => array(
          "JSON"
        ),
        "default" => "JSON"
      )
    ),
  );
  return($info);
}

function tdsc_search_info() {
  $info = array(
    "5x5" => array(
      "callback" => "tdsc_search_5x5",
      "params" => array(
        "source" => array(
          "desc" => "Source",
          "type" => "string",
          "column" => "source"
        ),
        "id" => array(
          "desc" => "ID within source",
          "type" => "string",
          "column" => "id"
        ),
        "startTime" => array(
          "desc" => "start time",
          "type" => "integer",
          "column" => "startTime"
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
  );
  return($info);
}

function tdsc_search_5x5($f) {
  $sql = "SELECT * FROM `analysis-tdsc5x5` WHERE source='".$f["source"]."' AND id=".$f["id"]." AND startTime=".$f["startTime"].";";
  global $db;
  $res = $db->query($sql);
  $ret = $res->fetch_assoc();
  return($ret);
}
