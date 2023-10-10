<?php
// Acoustic Complexity Index module for audioBlast API
//
// This module provides access to the Acoustic Complexity Index (ACI) analyses
// in audioBlast. The ACI is a measure of the acoustic complexity of a soundscape,
// and is in this instance calculated using the seewave package in R, with an analysis
// window of 60 seconds.

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
