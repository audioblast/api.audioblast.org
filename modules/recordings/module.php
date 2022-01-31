<?php

function recordings_info() {
  $info = array(
    "mname" => "recordings",
    "version" => 1.0,
    "category" => "data",
    "table" => "recordings",
    "hname" => "Recordings",
    "desc" => "This endpoint allows for the querying of recording metadata held within audioBLAST!",
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
        "desc" => "Recording name",
        "type" => "string",
        "default" => "",
        "column" => "Title",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "taxon" => array(
        "desc" => "Taxon",
        "type" => "string",
        "default" => "",
        "column" => "taxon",
        "op" => "=",
        "autocomplete" => TRUE,
        "suggest" => TRUE
      ),
      "filename" => array(
        "desc" => "File name",
        "type" => "string",
        "default" => "",
        "column" => "file",
        "op" => "contains"
      ),
      "author" => array(
        "desc" => "Author",
        "type" => "string",
        "default" => "",
        "column" => "author",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "post_date" => array(
        "desc" => "Date the content was uploaded",
        "type" => "string",
        "column" => "post_date",
        "op" => "none"
      ),
      "human_size" => array(
        "desc" => "Human readble size of file",
        "type" => "string",
        "column" => "size",
        "op" => "none"
      ),
      "bytes" => array(
        "desc" => "File size",
        "type" => "range",
        "default" => "",
        "column" => "size_raw",
        "op" => "range"
      ),
      "mime" => array(
        "desc" => "MIME type of content",
        "type" => "string",
        "column" => "type",
        "default" => "",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "recording_type" => array(
        "desc" => "Used to identify soundscape recordings",
        "type" => "string",
        "column" => "NonSpecimen",
        "default" => "",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "date" => array(
        "desc" => "Date",
        "type" => "string",
        "column" => "Date",
        "default" => "",
        "op" => "none"
      ),
      "time" => array(
        "desc" => "Time",
        "type" => "string",
        "column" => "Time",
        "default" => "",
        "op" => "none"
      ),
      "duration" => array(
        "desc" => "Duration",
        "type" => "range",
        "default" => "",
        "column" => "Duration",
        "op" => "range"
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
