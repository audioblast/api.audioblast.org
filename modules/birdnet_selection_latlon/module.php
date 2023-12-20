<?php

function birdnet_selection_latlon_info() {
  $info = array(
    "mname" => "birdnet_selection_latlon",
    "version" => 1.0,
    "category" => "analysis",
    "hname" => "Birdnet identifications (with lat/lon)",
    "desc" => "",
    "table" => "analysis_3sec-birdnet_latlon-selection",
    
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
        "column" => "id",
        "op" => "="
      ),
      "analysis_id" => array(
        "desc" => "filter by annotation id within source",
        "type" => "string",
        "column" => "analysis_id",
        "op" => "="
      ),
      "annotation_date" => array(
        "desc" => "filter by annotation date",
        "type" => "string",
        "column" => "annotation_date",
        "op" => "="
      ),
      "time_start" => array(
        "desc" => "start time(s) to return",
        "type" => "integer",
        "column" => "time_start",
        "op" => "range"
      ),
      "time_end" => array(
        "desc" => "end time(s) to return",
        "type" => "integer",
        "column" => "time_end",
        "op" => "range"
      ),
      "taxon" => array(
        "desc" => "Taxon to filter by",
        "type" => "string",
        "column" => "taxon",
        "op" => "="
      ),
      "confidence" => array(
        "desc" => "Output of analysis",
        "type" => "integer",
        "column" => "confidence",
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