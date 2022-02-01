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
      "annotation_id" => array(
        "desc" => "Annotation ID",
        "type" => "string",
        "default" => "",
        "column" => "annotation_id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "annotation_date" => array(
        "desc" => "Annotation date",
        "type" => "string",
        "default" => "",
        "column" => "annotation_date",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "annotation_info_url" => array(
        "desc" => "Annotation information link",
        "type" => "string",
        "default" => "",
        "column" => "annotation_info_url",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "recording_url" => array(
        "desc" => "Recording URL",
        "type" => "string",
        "default" => "",
        "column" => "recording_url",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "time_start" => array(
        "desc" => "Start time",
        "type" => "string",
        "default" => "",
        "column" => "time_start",
        "op" => "=",
        "autocomplete" => FALSE,
        "ac" => "ac:startTime"
      ),
      "time_end" => array(
        "desc" => "End time",
        "type" => "string",
        "default" => "",
        "column" => "time_end",
        "op" => "=",
        "autocomplete" => FALSE,
        "ac" => "ac:endTime"
      ),
      "taxon" => array(
        "desc" => "Taxon",
        "type" => "string",
        "default" => "",
        "column" => "taxon",
        "op" => "contains",
        "autocomplete" => TRUE,
        "ac" => "dwc:scientificName"
      ),
      "type" => array(
        "desc" => "Annotation type",
        "type" => "string",
        "default" => "",
        "column" => "type",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "lat" => array(
        "desc" => "Latitude",
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
      "contact" => array(
        "desc" => "Contact",
        "type" => "string",
        "default" => "",
        "column" => "contact",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "format" => array(
        "desc" => "Data represenation to return.",
        "type" => "string",
        "allowed" => array(
          "internal",
          "ac"
        ),
       "default" => "internal"
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
