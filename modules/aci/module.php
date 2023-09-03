<?php

function aci_info() {
  $info = array(
    "mname" => "aci",
    "version" => 1.0,
    "category" => "analysis",
    "hname" => "ACI",
    "desc" => "Explain how this works...",
    "vocab_url" => "https://vocab.audioblast.org/cv/analysis#seewaveACI",
    "ab-plugin" => TRUE,
    "code" => array(
      "type" => "package",
      "language" => "R",
      "name" => "seewave",
      "source" => "CRAN",
      "function" => "ACI()"
    ),
    "table" => "analysis-aci",
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
        "column" => "value",
        "op" => "range"
      ),
      "channel" => array(
        "desc" => "Audio channel analysed",
        "type" => "integer",
        "default" => 1,
        "column" => "channel",
        "op" => "="
      ),
      "duration" => array(
        "desc" => "Analysis duration",
        "type" => "integer",
        "default" => 60,
        "column" => "duration",
        "op" => "=",
        "allowed" => array(
          60
        )
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
