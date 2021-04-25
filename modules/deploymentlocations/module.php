<?php

function deploymentlocations_info() {
  $info = array(
    "mname" => "deploymentlocations",
    "version" => 1.0,
    "category" => "data",
    "table" => "deployment_locations",
    "hname" => "Deployment locations",
    "desc" => "This endpoint allows for listing the location of  deployments.",
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
      "deployment" => array(
        "desc" => "Deployment ID",
        "type" => "string",
        "default" => "",
        "column" => "deployment",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "timestamp" => array(
        "desc" => "UNIX timestamp",
        "type" => "range",
        "default" => "",
        "column" => "timestamp",
        "op" => "range",
        "autocomplete" => FALSE
      ),
      "latitude" => array(
        "desc" => "Decimal latitude",
        "type" => "range",
        "column" => "latitude",
        "op" => "range",
        "autocomplete" => FALSE,
      ),
      "longitude" => array(
        "desc" => "Decimal longitude",
        "type" => "range",
        "column" => "longitude",
        "op" => "range"
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
