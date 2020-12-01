<?php

function audiowaveform_info() {
  $info = array(
    "mname" => "audiowaveform",
    "version" => 1.0,
    "category" => "analysis",
    "hname" => "Waveform analysis using audiowaveform",
    "desc" => "Explain how this works...",
    "ab-plugin" => TRUE,
    "table" => "analysis-audiowaveform",
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
      "type" => array(
        "desc" => "What type of waveform?",
        "type" => "string",
        "column" => "type",
        "op" => "=",
        "allowed" => array(
          "image300_40"
        )
      ),
      "value" => array(
        "desc" => "Output of analysis",
        "type" => "integer",
        "column" => "value",
        "op" => "none"
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
