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
        "desc" => "Deployment name",
        "type" => "string",
        "default" => "",
        "column" => "name",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "device" => array(
        "desc" => "Device ID",
        "type" => "string",
        "default" => "",
        "column" => "device",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "type" => array(
        "desc" => "Type of deployment",
        "type" => "string",
        "default" => "",
        "column" => "type",
        "op" => "=",
        "autocomplete" => FALSE,
        "allowed" => array(
          "static",
          "dynamic"
        )
      ),
      "start" => array(
        "desc" => "Start of deployment",
        "type" => "range",
        "default" => "",
        "column" => "start",
        "op" => "range"
      ),
      "end" => array(
        "desc" => "End of deployment",
        "type" => "range",
        "default" => "",
        "column" => "end",
        "op" => "range",
        "autocomplete" => FALSE
      ),
      "continues_from" => array(
        "desc" => "Continues form previous deployment",
        "type" => "string",
        "column" => "continues_from",
        "op" => "="
      ),
      "group" => array(
        "desc" => "Deployment group",
        "type" => "string",
        "column" => "group",
        "op" => "=",
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
