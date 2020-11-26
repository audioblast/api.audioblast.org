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
