<?php

function taxa_info() {
  $info = array(
    "mname" => "taxa",
    "version" => 1.0,
    "category" => "data",
    "table" => "taxa",
    "hname" => "Taxa",
    "desc" => "This endpoint allows for the querying of the taxonomic hierarchy held within audioBLAST!",
    "see_also" => array(
      "<a href='#recordingstaxa'>Recordings-Taxa</a> provides autocompletes on taxon ranks with recordings.</a>"
    ),
    "rdf" => array("path" => "taxon", "node" => "taxa_rdf_node"),
    "params" => array(
      "source" => array("desc" => "Source of the taxon", "type" => "string",
        "default" => "", "column" => "source", "op" => "="),
      "id" => array("desc" => "Taxon ID within its source", "type" => "string",
        "default" => "", "column" => "id", "op" => "="),
      "taxon" => array(
        "desc" => "Taxonomic name",
        "type" => "string",
        "default" => "",
        "column" => "taxon",
        "op" => "=",
        "fulltext" => TRUE,
        "autocomplete" => TRUE
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
        "desc" => "At present just an array",
        "type" => "string",
        "allowed" => array(
          "JSON",
          "nakedJSON",
          "JSON-LD",
          "Turtle"
        ),
        "default" => "JSON"
      )
    )
  );
  return($info);
}

// Source-local taxon concepts; names alone do not establish cross-source identity.
function taxa_rdf_node($taxon, $uri) {
  $node = array("@id" => $uri, "@type" => "http://rs.tdwg.org/dwc/terms/Taxon",
    "dwc:taxonID" => $uri);
  rdfAdd($node, "dwc:scientificName", $taxon["taxon"] ?? NULL);
  rdfAdd($node, "dwc:taxonRank", $taxon["rank"] ?? NULL);
  foreach (array("genus", "subfamily", "family", "order", "class", "kingdom") as $rank) {
    rdfAdd($node, "dwc:".$rank, $taxon[$rank] ?? NULL);
  }
  return($node);
}
