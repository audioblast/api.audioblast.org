<?php

function details_info() {
  $info = array(
    "mname" => "details",
    "version" => 1.0,
    "category" => "data",
    "table" => "details",
    "hname" => "Details",
    "desc" => "This endpoint allows for the querying of the details of the records held within audioBLAST!: the things a record holds that have no column of their own, such as the tape a recording was made on, the temperature it was made at, or the field notes of a specimen. Each detail is a named value, with a unit where it is a measurement, belonging to the record of a data module (its type) with an id there. The details a record has of one name are numbered from 0 by delta. A source can give details of another source's records, such as a corpus giving details of the xeno-canto recordings it marked regions of: source is the source that gave a detail, and record_source the source of the record it belongs to. A record's details, whichever source gave them, are those with its record_source, type and id.",
    "source_notes" => "Details are ingested from each source's details, and replace the details that the source gave before, including those it gave of other sources' records. The details that other sources gave of a source's records are theirs, and stay. Their names are each source's own, and are not yet matched to vocabulary terms, so details are not given as RDF.",
    "params" => array(
      "source" => array(
        "desc" => "Source that gave the detail, by its exact name (letter case aside)",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "type" => array(
        "desc" => "Type of the record the detail belongs to: a data module, e.g. recordings or specimens",
        "type" => "string",
        "default" => "",
        "column" => "type",
        "op" => "=",
        "multiple" => TRUE,
        "autocomplete" => TRUE
      ),
      "id" => array(
        "desc" => "ID of the record the detail belongs to, within the record's source (record_source)",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "multiple" => TRUE
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
      "record_source" => array(
        "desc" => "Source of the record the detail belongs to: the source that gave it, unless it gave a detail of another source's record",
        "type" => "string",
        "default" => "",
        //Named for every detail, its giving source's own records included (see
        //uploadDetails() in audioBlastIngest), so that the table's key, which
        //starts with it, the type and the id, answers a record's details
        //whichever source gave them
        "column" => "record_source",
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
