<?php

function taxa_info() {
  $info = array(
    "mname" => "taxa",
    "version" => 1.0,
    "category" => "data",
    "table" => "taxa",
    "hname" => "Taxa",
    "desc" => "This endpoint allows for the querying of the taxonomic hierarchy held within audioBLAST! RDF responses include incoming and outgoing links to recordings, traits and references, with relationship provenance, and the vernacular names a taxon is known by as dwc:vernacularName, each in the language it is in.",
    "see_also" => array(
      "<a href='#recordingstaxa'>Recordings-Taxa</a> provides autocompletes on taxon ranks with recordings.</a>",
      "<a href='#vernacularnames'>Vernacular names</a> gives the names these taxa are known by in a language."
    ),
    "rdf" => array("links" => TRUE, "path" => "taxon", "node" => "taxa_rdf_node",
      "embed" => "taxa_rdf_embed"),
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
      "taxonomicStatus" => array(
        "desc" => "Whether the name is the one in use (dwc:taxonomicStatus), e.g. accepted, homotypic synonym, heterotypic synonym, misapplied, doubtful or invalid. Empty where the source says nothing about the name.",
        "type" => "string",
        "default" => "",
        "column" => "taxonomicStatus",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "nomenclaturalStatus" => array(
        "desc" => "Why a name is not the one in use, in its source's own words (dwc:nomenclaturalStatus), e.g. junior synonym or subsequent name/combination",
        "type" => "string",
        "default" => "",
        "column" => "nomenclaturalStatus",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "acceptedNameUsageID" => array(
        "desc" => "ID within the source of the name that replaced this one (dwc:acceptedNameUsageID); empty where the name is in use, or where the source does not say which replaced it",
        "type" => "string",
        "default" => "",
        "column" => "acceptedNameUsageID",
        "op" => "="
      ),
      "acceptedNameUsage" => array(
        "desc" => "The name that replaced this one (dwc:acceptedNameUsage)",
        "type" => "string",
        "default" => "",
        "column" => "acceptedNameUsage",
        "op" => "contains"
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

// The vernacular names of the taxa, read onto the taxa themselves as
// dwc:vernacularName, which Darwin Core defines on dwc:Taxon: a client reading
// a taxon has the names it is known by without following a link for each. Each
// is a literal in the language it is in, as the vernacularnames module gives
// it. The name records stay linked as well, since they hold what a name alone
// does not: where it is used, its remarks and the reference it was taken from.
//
// Only a name that denotes the taxon is a name of it. A link that merely says
// a name is about a taxon is left as a link, as it does not say that the taxon
// is called that.
function taxa_rdf_key($pair) {
  return(json_encode(array_map("strtolower", $pair)));
}

function taxa_rdf_embed($db, $module, $taxa, $links) {
  $denotes = "http://purl.obolibrary.org/obo/IAO_0000219";
  $named = array();
  $pairs = array();
  foreach ($links as $link) {
    if (($link["subject_type"] ?? "") !== "vernacularnames") {continue;}
    if (($link["object_type"] ?? "") !== "taxa") {continue;}
    if (($link["predicate"] ?? "") !== $denotes) {continue;}
    $name = array($link["subject_source"], $link["subject_id"]);
    //The database compares text regardless of case, so a record can come back
    //cased differently from the link that asked for it and still be the one
    //meant; the key each is found by ignores case, as the lookup does.
    $key = taxa_rdf_key($name);
    $pairs[$key] = $name;
    $taxon = rdfRecordURI($module, $link["object_source"], $link["object_id"]);
    if (!isset($named[$key]) || !in_array($taxon, $named[$key], TRUE)) {
      $named[$key][] = $taxon;
    }
  }
  if (!$pairs) {return(array());}

  $names = loadModule("vernacularnames");
  $records = rdfRecordsByID($db, $names, $pairs);
  if ($records === FALSE) {return(FALSE);}

  $nodes = array();
  foreach ($records as $record) {
    $name = rdfLang($record["vernacularName"] ?? NULL, $record["language"] ?? NULL);
    if ($name === NULL) {continue;}
    $key = taxa_rdf_key(array($record["source"], $record["id"]));
    foreach ($named[$key] ?? array() as $taxon) {
      $nodes[] = array("@id" => $taxon, "dwc:vernacularName" => $name);
    }
  }
  return($nodes);
}

// Source-local taxon concepts; names alone do not establish cross-source identity.
function taxa_rdf_node($taxon, $uri) {
  $node = array("@id" => $uri, "@type" => "http://rs.tdwg.org/dwc/terms/Taxon");
  rdfAdd($node, "dwc:scientificName", $taxon["taxon"] ?? NULL);
  rdfAdd($node, "dwc:taxonRank", $taxon["rank"] ?? NULL);
  foreach (array("genus", "subfamily", "family", "order", "class", "kingdom") as $rank) {
    rdfAdd($node, "dwc:".$rank, $taxon[$rank] ?? NULL);
  }
  //Whether the name is the one in use, why it is not in its source's own
  //words, and the name that replaced it. A taxon a source says nothing about
  //gets no status: not saying is not the same as saying a name is accepted.
  rdfAdd($node, "dwc:taxonomicStatus", $taxon["taxonomicStatus"] ?? NULL);
  rdfAdd($node, "dwc:nomenclaturalStatus", $taxon["nomenclaturalStatus"] ?? NULL);
  rdfAdd($node, "dwc:acceptedNameUsage", $taxon["acceptedNameUsage"] ?? NULL);
  //The name that replaced this one is identified by its URI rather than by the
  //id it has within its source, as every other record is here: see the Darwin
  //Core RDF guide, section 2.6 on ID terms.
  $accepted = $taxon["acceptedNameUsageID"] ?? "";
  if ($accepted !== "" && $accepted !== NULL && ($taxon["source"] ?? "") !== "") {
    rdfAdd($node, "dwc:acceptedNameUsageID",
      rdfIRI(rdfRecordURI(loadModule("taxa"), $taxon["source"], $accepted)));
  }
  return($node);
}
