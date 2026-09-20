<?php

function details_info() {
  $info = array(
    "mname" => "details",
    "version" => 1.0,
    "category" => "data",
    "table" => "details",
    "hname" => "Details",
    "desc" => "This endpoint allows for the querying of the details of the records held within audioBLAST!: the things a record holds that have no column of their own, such as the tape a recording was made on, the temperature it was made at, or the field notes of a specimen. Each detail is a named value, with a unit where it is a measurement, belonging to the record of a data module (its type) with an id there. The details a record has of one name are numbered from 0 by delta.",
    "source_notes" => "Details are ingested from each source's details, and replace the details that the source gave before. Their names are each source's own, and are not yet matched to vocabulary terms, so details are not given as RDF.",
    "params" => array(
      "source" => array(
        "desc" => "Source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "type" => array(
        "desc" => "Type of the record the detail belongs to: a data module, e.g. recordings or specimens",
        "type" => "string",
        "default" => "",
        "column" => "type",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "id" => array(
        "desc" => "ID of the record the detail belongs to, within its source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "="
      ),
      "name" => array(
        "desc" => "Name of the detail, e.g. tape, cd_track or temperature_start",
        "type" => "string",
        "default" => "",
        "column" => "name",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "delta" => array(
        "desc" => "Which of a record's details of this name it is, from 0",
        "type" => "string",
        "default" => "",
        "column" => "delta",
        "op" => "none"
      ),
      "value" => array(
        "desc" => "Value of the detail",
        "type" => "string",
        "default" => "",
        "column" => "value",
        "op" => "contains"
      ),
      "unit" => array(
        "desc" => "Unit the value is measured in, where it is a measurement, e.g. cm or °C",
        "type" => "string",
        "default" => "",
        "column" => "unit",
        "op" => "=",
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
    )
  );
  return($info);
}
