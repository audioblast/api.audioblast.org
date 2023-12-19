<?php

function deployments_info() {
  $info = array(
    "mname" => "deployments",
    "version" => 1.0,
    "category" => "data",
    "table" => "deployments",
    "hname" => "Deployments",
    "desc" => "This endpoint allows for listing equipment deployments.",
    "params" => array(
      "source" => array(
        "desc" => "Source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "contains",
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
        "desc" => "Deployment name",
        "type" => "string",
        "default" => "",
        "column" => "name",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "lat" => array(
        "desc" => "Latiude",
        "type" => "string",
        "default" => "",
        "column" => "lat",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "lon" => array(
        "desc" => "Longitude",
        "type" => "string",
        "default" => "",
        "column" => "lon",
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
