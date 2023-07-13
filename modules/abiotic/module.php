<?php

function abiotic_info() {
  $info = array(
    "mname" => "abiotic",
    "version" => 1.0,
    "category" => "data",
    "table" => "abiotic",
    "hname" => "Abiotic data",
    "desc" => "This endpoint allows querying abiotic measurements from deployments.",
    "params" => array(
      "deployment" => array(
        "desc" => "Deployment ID",
        "type" => "string",
        "default" => "",
        "column" => "deployment",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "timestamp" => array(
        "desc" => "UNIX timestamp of measurement",
        "type" => "string",
        "default" => "",
        "column" => "timestamp",
        "op" => "=",
        "autocomplete" => FALSE
      ),
      "file_source" => array(
        "desc" => "File source",
        "type" => "string",
        "column" => "file_source",
        "op" => "contains",
        "autocomplete" => FALSE,
      ),
      "file_id" => array(
        "desc" => "File ID",
        "type" => "string",
        "column" => "file_id",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "file_relative_time" => array(
        "desc" => "Relative time in file measurement was taken",
        "type" => "range",
        "column" => "file_relative_time",
        "op" => "range",
        "autocomplete" => FALSE
      ),
      "property" => array(
        "desc" => "Measured property",
        "type" => "string",
        "column" => "property",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "value" => array(
        "desc" => "Measured value",
        "type" => "string",
        "column" => "value",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "unit" => array(
        "desc" => "Unit of measurement",
        "type" => "string",
        "column" => "unit",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "node_id" => array(
        "desc" => "Node ID",
        "type" => "string",
        "column" => "node_id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "sensor_id" => array(
        "desc" => "Sensor ID",
        "type" => "string",
        "column" => "sensor_id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "sensor" => array(
        "desc" => "Sensor",
        "type" => "string",
        "column" => "sensor",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "config" => array(
        "desc" => "Configuration",
        "type" => "string",
        "column" => "config",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "output" => array(
        "desc" => "The format of the returned data",
        "type" => "string",
        "allowed" => array(
          "JSON",
          "nakedJSON"
        ),
        "default" => "JSON"
      )
    ),
  );
  return($info);
}
