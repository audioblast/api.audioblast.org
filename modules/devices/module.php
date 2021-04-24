<?php

function devices_info() {
  $info = array(
    "mname" => "devices",
    "version" => 1.0,
    "category" => "data",
    "table" => "devices",
    "hname" => "Devices",
    "desc" => "This endpoint allows for listing devices used in deployments.",
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
      "name" => array(
        "desc" => "Device name",
        "type" => "string",
        "default" => "",
        "column" => "name",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "model" => array(
        "desc" => "Device model",
        "type" => "string",
        "default" => "",
        "column" => "model",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "serial" => array(
        "desc" => "Device serial number",
        "type" => "string",
        "default" => "",
        "column" => "serial",
        "op" => "contains",
        "autocomplete" => TRUE,
      ),
      "hardware" => array(
        "desc" => "Hardware",
        "type" => "string",
        "default" => "",
        "column" => "hardware",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "hardware_version" => array(
        "desc" => "Hardware version number",
        "type" => "string",
        "default" => "",
        "column" => "hardware_version",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "os" => array(
        "desc" => "Operating system",
        "type" => "string",
        "column" => "os",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "os_version" => array(
        "desc" => "Operating system version",
        "type" => "string",
        "column" => "os_version",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "software" => array(
        "desc" => "Software",
        "type" => "string",
        "default" => "",
        "column" => "software",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "software_version" => array(
        "desc" => "Software version number",
        "type" => "string",
        "default" => "",
        "column" => "software_version",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "firmware" => array(
        "desc" => "Firmware",
        "type" => "string",
        "column" => "firmware",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "firmware_version" => array(
        "desc" => "Firmware version",
        "type" => "string",
        "column" => "firmware_version",
        "op" => "contains",
        "autocomplete" => FALSE
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
