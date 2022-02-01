<?php

function annomate_info() {
  $info = array(
    "mname" => "annomate",
    "version" => 1.0,
    "category" => "data",
    "table" => "annomate",
    "hname" => "ann-o-mate",
    "desc" => "This endpoint allows for the querying of ann-o-mate.",
    "params" => array(
      "source" => array(
        "desc" => "Recording source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "source_id" => array(
        "desc" => "Recording ID",
        "type" => "string",
        "default" => "",
        "column" => "source_id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "annotator" => array(
        "desc" => "Annotator",
        "type" => "string",
        "default" => "",
        "column" => "annotator",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "output" => array(
        "desc" => "The format of the returned data",
        "type" => "string",
        "allowed" => array(
          "JSON",
          "nakedJSON",
          "tabulator"
        ),
        "default" => "JSON"
      )
    ),
  );
  return($info);
}
