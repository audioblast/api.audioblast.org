<?php

function specimens_info() {
  $info = array(
    "mname" => "specimens",
    "version" => 1.0,
    "category" => "data",
    "table" => "specimens",
    "hname" => "Specimens",
    "desc" => "This endpoint allows for the querying of the specimens and observations that the recordings held within audioBLAST! are of. Their columns are named after the Darwin Core terms they hold. With output=JSON-LD or output=Turtle (or, without output, an Accept header asking for application/ld+json or text/turtle), specimens are given as RDF Darwin Core occurrences. Each specimen is identified by https://api.audioblast.org/specimen/{source}/{id}, which gives the specimen in the same way.",
    "source_notes" => "Specimens are ingested from each source's specimens, and are identified by their id within it. The recording a specimen was recorded in, and the taxon it is identified as, are links (see the links endpoint).",
    //Specimens as RDF (see core/rdf.php), identified by https://api.audioblast.org/specimen/{source}/{id}
    "rdf" => array(
      "links" => TRUE,
      "path" => "specimen",
      "node" => "specimens_rdf_node"
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
        "desc" => "ID of the specimen within its source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "multiple" => TRUE
      ),
      "scientificName" => array(
        "desc" => "Name of the taxon the specimen is identified as (dwc:scientificName)",
        "type" => "string",
        "default" => "",
        "column" => "scientificName",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "basisOfRecord" => array(
        "desc" => "What the record is of (dwc:basisOfRecord), e.g. PreservedSpecimen, LivingSpecimen or HumanObservation",
        "type" => "string",
        "default" => "",
        "column" => "basisOfRecord",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "institutionCode" => array(
        "desc" => "Code of the institution holding the specimen (dwc:institutionCode), e.g. NHMUK",
        "type" => "string",
        "default" => "",
        "column" => "institutionCode",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "collectionCode" => array(
        "desc" => "Code of the collection the specimen is in (dwc:collectionCode)",
        "type" => "string",
        "default" => "",
        "column" => "collectionCode",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "catalogNumber" => array(
        "desc" => "Number of the specimen in its collection (dwc:catalogNumber)",
        "type" => "string",
        "default" => "",
        "column" => "catalogNumber",
        "op" => "contains"
      ),
      "otherCatalogNumbers" => array(
        "desc" => "Other numbers the specimen is known by (dwc:otherCatalogNumbers)",
        "type" => "string",
        "default" => "",
        "column" => "otherCatalogNumbers",
        "op" => "contains"
      ),
      "typeStatus" => array(
        "desc" => "Nomenclatural type status of the specimen (dwc:typeStatus), e.g. Holotype",
        "type" => "string",
        "default" => "",
        "column" => "typeStatus",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "sex" => array(
        "desc" => "Sex of the individual (dwc:sex), as its source gives it",
        "type" => "string",
        "default" => "",
        "column" => "sex",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "lifeStage" => array(
        "desc" => "Life stage of the individual (dwc:lifeStage), as its source gives it, e.g. Adult or Nymph",
        "type" => "string",
        "default" => "",
        "column" => "lifeStage",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "individualCount" => array(
        "desc" => "Number of individuals the record is of (dwc:individualCount)",
        "type" => "range",
        "default" => "",
        "column" => "individualCount",
        "op" => "range"
      ),
      "recordedBy" => array(
        "desc" => "Who collected or observed the specimen (dwc:recordedBy)",
        "type" => "string",
        "default" => "",
        "column" => "recordedBy",
        "op" => "contains"
      ),
      "eventDate" => array(
        "desc" => "Date the specimen was collected or observed (dwc:eventDate: YYYY-MM-DD, or YYYY-MM or YYYY when only the month or year is known)",
        "type" => "string",
        "default" => "",
        "column" => "eventDate",
        "op" => "none"
      ),
      "identifiedBy" => array(
        "desc" => "Who identified the specimen (dwc:identifiedBy)",
        "type" => "string",
        "default" => "",
        "column" => "identifiedBy",
        "op" => "contains"
      ),
      "dateIdentified" => array(
        "desc" => "Date the specimen was identified (dwc:dateIdentified)",
        "type" => "string",
        "default" => "",
        "column" => "dateIdentified",
        "op" => "none"
      ),
      "identificationQualifier" => array(
        "desc" => "Doubt about the identification (dwc:identificationQualifier), e.g. cf. or aff.",
        "type" => "string",
        "default" => "",
        "column" => "identificationQualifier",
        "op" => "none"
      ),
      "associatedSequences" => array(
        "desc" => "Genetic sequences of the individual (dwc:associatedSequences)",
        "type" => "string",
        "default" => "",
        "column" => "associatedSequences",
        "op" => "none"
      ),
      "locality" => array(
        "desc" => "Where the specimen was collected or observed (dwc:locality)",
        "type" => "string",
        "default" => "",
        "column" => "locality",
        "op" => "contains"
      ),
      "countryCode" => array(
        "desc" => "ISO 3166-1 alpha-2 code of the country it was collected or observed in (dwc:countryCode), e.g. GB",
        "type" => "string",
        "default" => "",
        "column" => "countryCode",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "decimalLatitude" => array(
        "desc" => "Latitude it was collected or observed at (dwc:decimalLatitude)",
        "type" => "range",
        "default" => "",
        "column" => "decimalLatitude",
        "op" => "range"
      ),
      "decimalLongitude" => array(
        "desc" => "Longitude it was collected or observed at (dwc:decimalLongitude)",
        "type" => "range",
        "default" => "",
        "column" => "decimalLongitude",
        "op" => "range"
      ),
      "occurrenceRemarks" => array(
        "desc" => "Remarks on the record (dwc:occurrenceRemarks), e.g. what the individual was found on",
        "type" => "string",
        "default" => "",
        "column" => "occurrenceRemarks",
        "op" => "contains"
      ),
      "info_url" => array(
        "desc" => "URL of the specimen's page at its source",
        "type" => "string",
        "default" => "",
        "column" => "info_url",
        "op" => "none"
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

//A specimen as an RDF node (see core/rdf.php) at its URI, as a Darwin Core
//occurrence. Its URI is its occurrenceID, as that is the identifier audioBLAST!
//can be asked for it by; its page at its source is where more is about it.
function specimens_rdf_node($specimen, $uri) {
  $node = array("@id" => $uri,
    "@type" => "http://rs.tdwg.org/dwc/terms/Occurrence",
    "dwc:occurrenceID" => $uri);
  foreach (array(
    "scientificName", "basisOfRecord", "institutionCode", "collectionCode",
    "catalogNumber", "otherCatalogNumbers", "typeStatus", "sex", "lifeStage",
    "recordedBy", "identifiedBy", "identificationQualifier",
    "associatedSequences", "locality", "countryCode", "occurrenceRemarks"
  ) as $field) {
    rdfAdd($node, "dwc:".$field, $specimen[$field] ?? NULL);
  }
  rdfAdd($node, "dwc:individualCount", $specimen["individualCount"] ?? NULL);
  //Dates are given as they are held, so that a specimen collected in a month or
  //a year keeps the Darwin Core date it has
  foreach (array("eventDate", "dateIdentified") as $field) {
    $date = $specimen[$field] ?? NULL;
    rdfAdd($node, "dwc:".$field, rdfDate($date) ?? $date);
  }
  rdfAdd($node, "dwc:decimalLatitude", rdfDecimal($specimen["decimalLatitude"] ?? NULL));
  rdfAdd($node, "dwc:decimalLongitude", rdfDecimal($specimen["decimalLongitude"] ?? NULL));
  if (($specimen["decimalLatitude"] ?? "") !== "" && ($specimen["decimalLongitude"] ?? "") !== "") {
    rdfAdd($node, "dwc:geodeticDatum", "EPSG:4326");
  }
  $node["rdfs:seeAlso"] = array();
  $url = rdfURL($specimen["info_url"] ?? NULL);
  if ($url !== NULL) {$node["rdfs:seeAlso"][] = $url;}
  //Discover the recordings of the specimen, and what else is linked to it
  foreach (array("subject", "object") as $side) {
    $node["rdfs:seeAlso"][] = rdfIRI("https://api.audioblast.org/data/links/?".http_build_query(array(
      $side."_type" => "specimens", $side."_source" => $specimen["source"],
      $side."_id" => $specimen["id"])));
  }
  return($node);
}
