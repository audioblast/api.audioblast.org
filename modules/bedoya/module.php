<?php

function bedoya_info() {
  $info = array(
    "mname" => "bedoya",
    "version" => 1.0,
    "category" => "analysis",
    "hname" => "Rainfall analysis using the bedoya method",
    "desc" => "Explain how this works...",
    "ab-plugin" => TRUE,
    "code" => array(
      "type" => "package",
      "language" => "R",
      "name" => "sonicscrewdriver",
      "source" => "CRAN",
      "function" => "rainfallDetection(method=\"bedoya2017\")"
    ),
    "references" => array(
      array(
        "title" => "Automatic identification of rainfall in acoustic recordings",
        "year" => 2017,
        "authors" => "Bedoya et al",
        "doi" => "10.1016/j.ecolind.2016.12.018"
      )
    ),
    "table" => "analysis-bedoya",
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
      "channel" => array(
        "desc" => "Audio channel analysed",
        "type" => "integer",
        "default" => 1,
        "column" => "channel",
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
        "column" => "value",
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
    )
  );
  return($info);
}
