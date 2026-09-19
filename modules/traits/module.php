<?php

function traits_info() {
  $info = array(
    "mname" => "traits",
    "version" => 1.0,
    "category" => "data",
    "table" => "traits",
    "hname" => "Traits",
    "desc" => "This endpoint allows for the querying of the organism traits held within audioBLAST! With output=JSON-LD or output=Turtle (or, without output, an Accept header asking for application/ld+json or text/turtle), traits are given as RDF: Darwin Core MeasurementOrFacts whose types link to terms at vocab.audioblast.org. Each trait is identified by https://api.audioblast.org/trait/{source}/{id}, which gives the trait in the same way. RDF responses include incoming and outgoing relationships from the links table, including source references and taxa.",
    //Traits as RDF (see core/rdf.php), identified by https://api.audioblast.org/trait/{source}/{id}
    "rdf" => array(
      "links" => TRUE,
      "path" => "trait",
      "node" => "traits_rdf_node"
    ),
    "endpoints" => array(
      "list_text_values" => array(
        "callback" => "traits_list_text_values",
        "desc" => "Returns a list of distinct text values.",
        "returns" => "data",
        "params" => array(
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          )
        )
      )
    ),
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
        "column" => "traitID",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "taxon" => array(
        "desc" => "Taxonomic name",
        "type" => "string",
        "default" => "",
        "column" => "Taxonomic.name",
        "op" => "=",
        "autocomplete" => TRUE,
        "ac" => "dwc:scientificName"
      ),
      "trait" => array(
        "desc" => "Trait name",
        "type" => "string",
        "default" => "",
        "column" => "Trait",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "trait_ontology" => array(
        "desc" => "Link to trait in ontology",
        "type" => "string",
        "default" => "",
        "column" => "Ontology.Link",
        "op" => "="
      ),
      "value" => array(
        "desc" => "Value of trait",
        "type" => "string",
        "default" => "",
        "column" => "Value",
        "op" => "="
      ),
      "call_type" => array(
        "desc" => "Type of call the trait applies to",
        "type" => "string",
        "default" => "",
        "column" => "Call.Type",
        "op" => "="
      ),
      "sex" => array(
        "desc" => "Sex",
        "type" => "string",
        "default" => "",
        "column" => "Sex",
        "op" => "="
      ),
      "temperature" => array(
        "desc" => "Temperature at which trait was measured",
        "type" => "string",
        "default" => "",
        "column" => "Temperature",
        "op" => "range"
      ),
      "value_min" => array(
        "desc" => "Value min",
        "type" => "string",
        "default" => "",
        "column" => "min",
        "op" => "="
      ),
      "value_max" => array(
        "desc" => "Value max",
        "type" => "string",
        "default" => "",
        "column" => "max",
        "op" => "="
      ),
      "output" => array(
        "desc" => "The format of the returned data",
        "type" => "string",
        "allowed" => array(
          "JSON",
          "JSON-LD",
          "Turtle"
        ),
        "default" => "JSON"
      )
    )
  );
  return($info);
}

//A trait as an RDF node (see core/rdf.php) at its URI: a Darwin Core
//MeasurementOrFact about a taxon, whose type is linked to its term in the
//vocabulary at vocab.audioblast.org. The call type and temperature it was
//measured at are given with the vocabulary's terms for them. Values are given
//as they are held.
function traits_rdf_node($trait, $uri) {
  $node = array(
    "@id" => $uri,
    "@type" => "http://rs.tdwg.org/dwc/terms/MeasurementOrFact"
  );
  rdfAdd($node, "dcterms:identifier", $trait["id"]);
  rdfAdd($node, "dwc:scientificName", $trait["taxon"]);
  rdfAdd($node, "dwc:measurementType", $trait["trait"]);
  rdfAdd($node, "dwciri:measurementType", rdfURL($trait["trait_ontology"]));
  rdfAdd($node, "dwc:measurementValue", $trait["value"]);
  rdfAdd($node, "dwc:sex", $trait["sex"]);
  rdfAdd($node, "abv:CallType", $trait["call_type"]);
  rdfAdd($node, "abv:Temperature", $trait["temperature"]);
  return($node);
}

function traits_list_text_values() {
  $ret = array();
  $ret["data"] = array();
  global $db;
  $sql = "SELECT DISTINCT `Value` as `value` FROM `traits`;";
  $res = $db->query($sql);
  while ($row = $res->fetch_assoc()) {
    if (preg_match('/^[\p{L} \(\)]+$/u', $row["value"])) {
      $ret["data"][] = strtolower($row["value"]);
    }
  }
  return($ret);
}
