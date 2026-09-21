<?php

function taxa_info() {
  $info = array(
    "mname" => "taxa",
    "version" => 1.0,
    "category" => "data",
    "table" => "taxa",
    "hname" => "Taxa",
    "desc" => "This endpoint allows for the querying of the taxonomic hierarchy held within audioBLAST! A taxon is held once for every source that knows it, each with the classification its own source gives it, so a name can be held by several rows and those rows can disagree. RDF responses include incoming and outgoing links to recordings, traits and references, with relationship provenance, the vernacular names a taxon is known by as dwc:vernacularName, each in the language it is in, and the rows that are the same taxon as skos:exactMatch, which is how rows of different sources are known to be one taxon without any source's classification being overruled.",
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
      //Each source gives its taxonomy as a tree, and audioBLAST! has held that
      //tree all along without serving it: the ranks above a taxon were only to
      //be had from the columns below, which have one for a family but none for
      //a superfamily, so a taxon of a rank with no column could not be placed
      //at all. Asking for a taxon's parent_id gives the taxa directly below
      //it, and a taxon's own parent_id is in its row, so a client can walk a
      //source's taxonomy down and back up. It is the source's own tree, with
      //whatever the source has put where.
      "parent_id" => array("desc" => "ID of the taxon this one is directly inside, within the same source",
        "type" => "string", "default" => "", "column" => "parent_id", "op" => "="),
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

//The relationship that says a taxa row and a taxon of another taxonomy are the
//same taxon, which is how audioBLAST! knows that two of its own rows are one
define("TAXA_EXACT_MATCH", "http://www.w3.org/2004/02/skos/core#exactMatch");

// The taxon of an external taxonomy that a link says a taxa row is, as the
// source holding it and its IRI, or NULL where the link says something else. A
// taxa row is a source's own taxon concept, and audioBLAST! holds one for every
// source that knows the taxon; a link to the Catalogue of Life says which taxon
// that is. The holding source comes back with the IRI because the taxa matched
// to are looked up by it as well, as every other record is.
function taxa_rdf_matched($link) {
  if (($link["subject_type"] ?? "") !== "taxa") {return(NULL);}
  if (($link["predicate"] ?? "") !== TAXA_EXACT_MATCH) {return(NULL);}
  if (($link["object_type"] ?? "") !== "iri") {return(NULL);}
  $iri = $link["object_id"] ?? "";
  return(($iri === "") ? NULL : array($link["object_source"] ?? "", $iri));
}

// The taxa rows that are the same taxon as the ones asked for, said of the
// rows themselves rather than left for a client to work out.
//
// Two rows are the same taxon when they are matched to the same taxon of an
// external taxonomy, which is what the links from taxa to the Catalogue of
// Life record. skos:exactMatch is transitive, so a row matched to the taxon
// another row is matched to is that row; saying so here saves a client
// gathering every match of a taxonomy it may not hold, and is what makes a
// recording held under one source's taxon findable under another's.
//
// Nothing here chooses between the sources. Each row keeps the classification
// its source gives it, and a client reading two equivalent rows sees both.
function taxa_rdf_equivalents($db, $module, $links) {
  //The rows asked for that are matched, by the taxon they are matched to. The
  //key ignores case, as the lookup that finds the taxa again does.
  $asked = array();
  $taxa = array();
  foreach ($links as $link) {
    $match = taxa_rdf_matched($link);
    if ($match === NULL) {continue;}
    $key = taxa_rdf_key($match);
    $taxa[$key] = $match;
    $asked[$key][] = rdfRecordURI($module, $link["subject_source"], $link["subject_id"]);
  }
  if (!$asked) {return(array());}

  //Every row matched to those taxa, the ones asked for among them. The taxa are
  //looked up by the source that holds them and their id there, which is how
  //every other record of a page is looked up, in batches of the same size.
  $matched = array();
  $links_module = loadModule("links");
  foreach (array_chunk(array_values($taxa), 100) as $batch) {
    $values = array(TAXA_EXACT_MATCH, "iri");
    $places = array();
    foreach ($batch as $match) {
      $places[] = "(?, ?)";
      $values[] = $match[0];
      $values[] = $match[1];
    }
    $sql = SELECTclause($links_module, NULL, "table", "internal");
    $sql .= " WHERE `subject_type` = 'taxa' AND `predicate` = ? AND `object_type` = ?";
    $sql .= " AND (`object_source`, `object_id`) IN (".implode(", ", $places).");";
    $stmt = $db->prepare($sql);
    if (!$stmt) {return(FALSE);}
    if (!$stmt->bind_param(str_repeat("s", count($values)), ...$values) || !$stmt->execute()) {
      $stmt->close();
      return(FALSE);
    }
    $result = $stmt->get_result();
    if (!$result) {$stmt->close(); return(FALSE);}
    while ($link = $result->fetch_assoc()) {
      $match = taxa_rdf_matched($link);
      if ($match === NULL) {continue;}
      $key = taxa_rdf_key($match);
      if (!isset($asked[$key])) {continue;}
      $matched[$key][] = rdfRecordURI($module, $link["subject_source"], $link["subject_id"]);
    }
    $result->close();
    $stmt->close();
  }

  $nodes = array();
  foreach ($asked as $key => $rows) {
    foreach (array_unique($rows) as $taxon) {
      //A row is not an equivalent of itself, and a row matched to a taxon no
      //other row is matched to has none
      $others = array_values(array_diff(array_unique($matched[$key] ?? array()), array($taxon)));
      if (!$others) {continue;}
      $nodes[] = array("@id" => $taxon,
        "skos:exactMatch" => array_map("rdfIRI", $others));
    }
  }
  return($nodes);
}

function taxa_rdf_embed($db, $module, $taxa, $links) {
  //Which of the rows asked for are the same taxon as rows of other sources
  $nodes = taxa_rdf_equivalents($db, $module, $links);
  if ($nodes === FALSE) {return(FALSE);}

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
  if (!$pairs) {return($nodes);}

  $names = loadModule("vernacularnames");
  $records = rdfRecordsByID($db, $names, $pairs);
  if ($records === FALSE) {return(FALSE);}

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
  return($node);
}
