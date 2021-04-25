<?php

function sensors_info() {
  $info = array(
    "mname" => "sensors",
    "version" => 1.0,
    "category" => "data",
    "table" => "sensors",
    "hname" => "Sensors",
    "desc" => "This endpoint allows for listing sensors attached to devices.",
    "params" => array(
      "source" => array(
        "desc" => "Source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "id" => array(
        "desc" => "ID",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "device" => array(
        "desc" => "Device ID",
        "type" => "string",
        "default" => "",
        "column" => "device",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "name" => array(
        "desc" => "Sensor name",
        "type" => "string",
        "default" => "",
        "column" => "name",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "model" => array(
        "desc" => "Sensor model",
        "type" => "string",
        "column" => "model",
        "op" => "contains",
        "autocomplete" => TRUE,
      ),
      "serial" => array(
        "desc" => "Sensor serial number",
        "type" => "string",
        "column" => "serial",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "property" => array(
        "desc" => "Property measured",
        "type" => "string",
        "column" => "property",
        "op" => "=",
        "autocomplete" => TRUE,
        "allowed" => array(
          "air_temperature",
          "air_relative_humidity"
        )
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
