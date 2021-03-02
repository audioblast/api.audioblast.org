<?php

function recordingstaxa_info() {
  $info = array(
    "mname" => "recordingstaxa",
    "version" => 1.0,
    "category" => "data",
    "table" => "recordings-taxa",
    "hname" => "Recordings-Taxa",
    "desc" => "<p>Recordings linked to taxa. This is a join between the recordings and taxa tables, primarily of use in filtering recordings by taxonomic ranks higher than species.</p><p>The autocomplete values for the taxonomic ranks are limited to those taxa matched to recordings, for more complete taxonomic autocomplete use <a href='#taxa'>Taxa</a></p>.",
    "source_notes" => "The sources used are a subset of those from Recordings.",
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
        "autocomplete" => TRUE
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
      "rank" => array(
        "desc" => "Taxonomic rank",
        "type" => "string",
        "default" => "",
        "column" => "Rank",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "species" => array(
        "desc" => "Species name",
        "type" => "string",
        "default" => "",
        "column" => "Species",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "genus" => array(
        "desc" => "Genus name",
        "type" => "string",
        "default" => "",
        "column" => "Genus",
        "op" => "=",
        "autocomplete" => TRUE
      ), 
      "tribe" => array(
        "desc" => "Tribe name",
        "type" => "string",
        "default" => "",
        "column" => "Tribe",
        "op" => "="  ,
        "autocomplete" => TRUE
      ), 
      "subfamily" => array(
        "desc" => "Subfamily name",
        "type" => "string",
        "default" => "",
        "column" => "Subfamily",
        "op" => "=",
        "autocomplete" => TRUE
      ), 
      "family" => array(
        "desc" => "Family name",
        "type" => "string",
        "default" => "",
        "column" => "Family",
        "op" => "=",
        "autocomplete" => TRUE
      ), 
      "suborder" => array(
        "desc" => "Suborder name",
        "type" => "string",
        "default" => "",
        "column" => "Suborder",
        "op" => "=",
        "autocomplete" => TRUE
      ), 
      "order" => array(
        "desc" => "Order name",
        "type" => "string",
        "default" => "",
        "column" => "Order",
        "op" => "=",
        "autocomplete" => TRUE
      ), 
      "class" => array(
        "desc" => "Class name",
        "type" => "string",
        "default" => "",
        "column" => "Class",
        "op" => "=",
        "autocomplete" => TRUE
      ), 
      "kingdom" => array(
        "desc" => "Kingdom name",
        "type" => "string",
        "default" => "",
        "column" => "Kingdom",
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
