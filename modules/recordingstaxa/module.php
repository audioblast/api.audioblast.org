<?php

function recordingstaxa_info() {
  $info = array(
    "mname" => "recordingstaxa",
    "version" => 1.0,
    "category" => "data",
    "table" => "recordings-taxa",
    "hname" => "Recordings-Taxa",
    "desc" => "Recordings linked to taxa. This is a join between the recordings and taxa tables, primarily of use in filtering recordings by taxonomic ranks hgher than species.",
    "params" => array(
      "source" => array(
        "desc" => "Filter by source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "id" => array(
        "desc" => "Filter by id within source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "name" => array(
        "desc" => "Filter by recording name",
        "type" => "string",
        "default" => "",
        "column" => "Title",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "taxon" => array(
        "desc" => "Filter by recording taxon",
        "type" => "string",
        "default" => "",
        "column" => "taxon",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "filename" => array(
        "desc" => "Filter by file name",
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
        "desc" => "Filter by file size",
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
        "desc" => "Filter by duration",
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
        "op" => "="
      ),
      "genus" => array(
        "desc" => "Genus name",
        "type" => "string",
        "default" => "",
        "column" => "Genus",
        "op" => "="
      ), 
      "tribe" => array(
        "desc" => "Tribe name",
        "type" => "string",
        "default" => "",
        "column" => "Tribe",
        "op" => "="  
      ), 
      "subfamily" => array(
        "desc" => "Subfamily name",
        "type" => "string",
        "default" => "",
        "column" => "Subfamily",
        "op" => "="  
      ), 
      "family" => array(
        "desc" => "Family name",
        "type" => "string",
        "default" => "",
        "column" => "Family",
        "op" => "="  
      ), 
      "suborder" => array(
        "desc" => "Suborder name",
        "type" => "string",
        "default" => "",
        "column" => "Suborder",
        "op" => "="  
      ), 
      "order" => array(
        "desc" => "Order name",
        "type" => "string",
        "default" => "",
        "column" => "Order",
        "op" => "="  
      ), 
      "class" => array(
        "desc" => "Class name",
        "type" => "string",
        "default" => "",
        "column" => "Class",
        "op" => "="  
      ), 
      "kingdom" => array(
        "desc" => "Kingdom name",
        "type" => "string",
        "default" => "",
        "column" => "Kingdom",
        "op" => "="  
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
