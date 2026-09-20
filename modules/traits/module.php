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
        "desc" => "Type of call the trait applies to, as written",
        "type" => "string",
        "default" => "",
        "column" => "Call.Type",
        "op" => "="
      ),
      "call_type_ontology" => array(
        "desc" => "Link to the call type's term in the Type of Call vocabulary",
        "type" => "string",
        "default" => "",
        "column" => "Call.Type.Link",
        "op" => "="
      ),
      "call_part" => array(
        "desc" => "Part of the call the trait describes, e.g. B of a call whose parts follow the pattern A:B:C",
        "type" => "string",
        "default" => "",
        "column" => "Call.Part",
        "op" => "="
      ),
      "call_qualifier" => array(
        "desc" => "What else the call type says, e.g. Night in Calling Call (Night)",
        "type" => "string",
        "default" => "",
        "column" => "Call.Qualifier",
        "op" => "contains"
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
        "desc" => "Lower end of the range the value is written as, e.g. 4 for 4-6 or 3.5 for 4 ± 0.5; empty for a value that isn't a range",
        "type" => "range",
        "default" => "",
        "column" => "min",
        "op" => "range"
      ),
      "value_max" => array(
        "desc" => "Upper end of the range the value is written as, e.g. 6 for 4-6",
        "type" => "range",
        "default" => "",
        "column" => "max",
        "op" => "range"
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
//vocabulary at vocab.audioblast.org. The call it was measured on and the
//temperature are given with the vocabulary's terms for them: the call type as
//its term in the Type of Call vocabulary where it has been linked to one (or as
//written where not), and the part of the call. Anything else the call type says
//is a remark. Values are given as they are held.
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
  rdfAdd($node, "abv:CallType", rdfURL($trait["call_type_ontology"]) ?? $trait["call_type"]);
  rdfAdd($node, "abv:CallPart", $trait["call_part"]);
  rdfAdd($node, "abv:Temperature", $trait["temperature"]);
  if (($trait["call_qualifier"] ?? "") !== "") {
    rdfAdd($node, "dwc:measurementRemarks", "Call: ".$trait["call_qualifier"]);
  }
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
