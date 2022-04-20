<?php

function traitstaxa_info() {
  $info = array(
    "mname" => "traitsstaxa",
    "version" => 1.0,
    "category" => "data",
    "table" => "traits-taxa",
    "hname" => "Traits-Taxa",
    "desc" => "<p>Traits linked to taxa. This is a join between the recordings and taxa tables, primarily of use in filtering recordings by taxonomic ranks higher than species.</p><p>The autocomplete values for the taxonomic ranks are limited to those taxa matched to recordings, for more complete taxonomic autocomplete use <a href='#taxa'>Taxa</a></p>.",
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
      "traitID" => array(
        "desc" => "Trait ID",
        "type" => "string",
        "default" => "",
        "column" => "traitID",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "taxonID" => array(
        "desc" => "Taxon ID",
        "type" => "string",
        "default" => "",
        "column" => "taxonID",
        "op" => "=",
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
      "trait" => array(
        "desc" => "Trait name",
        "type" => "string",
        "default" => "",
        "column" => "trait",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "url" => array(
        "desc" => "Ontology URL for trait",
        "type" => "string",
        "default" => "",
        "column" => "url",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "value" => array(
        "desc" => "Value",
        "type" => "string",
        "default" => "",
        "column" => "value",
        "op" => "="
      ),

      "callType" => array(
        "desc" => "Type of call",
        "type" => "string",
        "column" => "callType",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "sex" => array(
        "desc" => "Sex",
        "type" => "string",
        "column" => "sex",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "min" => array(
        "desc" => "Minimum value",
        "type" => "range",
        "default" => "",
        "column" => "min",
        "op" => "range"
      ),
      "max" => array(
        "desc" => "Maximum value",
        "type" => "range",
        "default" => "",
        "column" => "max",
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
