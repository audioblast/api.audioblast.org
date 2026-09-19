<?php

function links_info() {
  $info = array(
    "mname" => "links",
    "version" => 1.0,
    "category" => "data",
    "table" => "links",
    "hname" => "Links",
    "desc" => "This endpoint allows for the querying of the links between records held within audioBLAST!, e.g. references and the taxa they are about, or recordings and the references they are published in. A link relates a subject to an object, each identified by its type (a data module, term for a vocabulary term, or iri), the source that holds it and its id there, so links can join the records of different sources. Links follow the Darwin Core Resource Relationship.",
    "source_notes" => "Links are ingested from each source's links, and replace the links that the source gave before.",
    "rdf" => array("path" => "link", "node" => "links_rdf_node", "related" => "links_rdf_related"),
    "params" => array(
      "source" => array(
        "desc" => "Source that gives the link (dwc:relationshipAccordingTo)",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "id" => array(
        "desc" => "ID of the link within its source (dwc:resourceRelationshipID): the SHA-1 of its subject, predicate, object, qualifier and remarks",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "="
      ),
      "subject_type" => array(
        "desc" => "Type of the subject: a data module, e.g. references, term for a vocabulary term, or iri",
        "type" => "string",
        "default" => "",
        "column" => "subject_type",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "subject_source" => array(
        "desc" => "Source that holds the subject",
        "type" => "string",
        "default" => "",
        "column" => "subject_source",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "subject_id" => array(
        "desc" => "ID of the subject within its source, or its IRI (dwc:resourceID)",
        "type" => "string",
        "default" => "",
        "column" => "subject_id",
        "op" => "="
      ),
      "predicate" => array(
        "desc" => "IRI of the relationship (dwc:relationshipOfResourceID), e.g. http://purl.obolibrary.org/obo/IAO_0000136 (is about) or http://rs.tdwg.org/dwc/terms/namePublishedInID",
        "type" => "string",
        "default" => "",
        "column" => "predicate",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "object_type" => array(
        "desc" => "Type of the object: a data module, e.g. taxa, term for a vocabulary term, or iri",
        "type" => "string",
        "default" => "",
        "column" => "object_type",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "object_source" => array(
        "desc" => "Source that holds the object",
        "type" => "string",
        "default" => "",
        "column" => "object_source",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "object_id" => array(
        "desc" => "ID of the object within its source, or its IRI (dwc:relatedResourceID)",
        "type" => "string",
        "default" => "",
        "column" => "object_id",
        "op" => "="
      ),
      "qualifier" => array(
        "desc" => "IRI of the vocabulary term that says more about the link, e.g. what a reference holds about a taxon, such as an oscillogram",
        "type" => "string",
        "default" => "",
        "column" => "qualifier",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "remarks" => array(
        "desc" => "Remarks on the link (dwc:relationshipRemarks), e.g. a page or a call type",
        "type" => "string",
        "default" => "",
        "column" => "remarks",
        "op" => "contains"
      ),
      "output" => array(
        "desc" => "The format of the returned data",
        "type" => "string",
        "allowed" => array(
          "JSON",
          "nakedJSON",
          "tabulator",
          "JSON-LD",
          "Turtle"
        ),
        "default" => "JSON"
      )
    )
  );
  return($info);
}

// Resolve endpoints using the same module registration as the record router.
// Unknown module types remain in the relationship record as source-local IDs.
function links_rdf_endpoint($link, $side) {
  $type = $link[$side."_type"] ?? "";
  $id = $link[$side."_id"] ?? "";
  if ($type === "term" || $type === "iri") {return(rdfURL($id));}
  $modules = loadModules();
  if (!isset($modules[$type]["rdf"])) {return(NULL);}
  return(rdfIRI(rdfRecordURI($modules[$type], $link[$side."_source"], $id)));
}

function links_rdf_node($link, $uri) {
  $node = array("@id" => $uri,
    "@type" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#Statement");
  rdfAdd($node, "dwc:relationshipAccordingTo", $link["source"] ?? NULL);
  rdfAdd($node, "dwc:relationshipRemarks", $link["remarks"] ?? NULL);
  rdfAdd($node, "dcterms:type", rdfURL($link["qualifier"] ?? NULL));
  rdfAdd($node, "rdf:predicate", rdfURL($link["predicate"] ?? NULL));
  foreach (array("subject", "object") as $side) {
    $endpoint = links_rdf_endpoint($link, $side);
    if ($endpoint !== NULL) {
      $node["rdf:".$side] = $endpoint;
    } else {
      // Preserve unresolved source-local identifiers without treating literals as resources.
      $node["rdfs:comment"][] = "Unresolved ".$side.": ".json_encode(array(
        $link[$side."_type"], $link[$side."_source"], $link[$side."_id"]), JSON_UNESCAPED_SLASHES);
    }
  }
  return($node);
}

// Publish the assertion too, while keeping each qualified/provenanced link separate.
function links_rdf_related($link, $uri) {
  $subject = links_rdf_endpoint($link, "subject");
  $object = links_rdf_endpoint($link, "object");
  $predicate = rdfURL($link["predicate"] ?? NULL);
  if ($subject === NULL || $object === NULL || $predicate === NULL) {return(array());}
  return(array(array("@id" => $subject["@id"], rdfCompactIRI($predicate["@id"]) => $object)));
}
