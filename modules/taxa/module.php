<?php

function taxa_info() {
  $info = array(
    "mname" => "taxa",
    "version" => 1.0,
    "category" => "data",
    "table" => "taxa",
    "hname" => "Taxa",
    "desc" => "This endpoint allows for the querying of the taxonomic hierarchy held within audioBLAST! A taxon is held once for every source that knows it, each with the classification its own source gives it, so a name can be held by several rows and those rows can disagree. RDF responses include incoming and outgoing links to recordings, traits and references, with relationship provenance, the vernacular names a taxon is known by as dwc:vernacularName, each in the language it is in, and the rows that are the same taxon as skos:exactMatch, which is how rows of different sources are known to be one taxon without any source's classification being overruled. The taxa a taxon is inside, all the way to the root of its source's tree, are given by the classification endpoint below in one request, and a taxon asked for at its own address (https://api.audioblast.org/taxon/{source}/{id}) carries them in its RDF, each described as it is on its own page and each skos:broader of the taxon below it, with dwc:higherClassification where the walk reached the root. A page of taxa gives each taxon's parent_id and no more, as walking fifty trees is not a page.",
    "see_also" => array(
      "<a href='#recordingstaxa'>Recordings-Taxa</a> provides autocompletes on taxon ranks with recordings.</a>",
      "<a href='#vernacularnames'>Vernacular names</a> gives the names these taxa are known by in a language."
    ),
    "rdf" => array("links" => TRUE, "path" => "taxon", "node" => "taxa_rdf_node",
      "embed" => "taxa_rdf_embed", "record" => "taxa_rdf_record"),
    "endpoints" => array(
      "classification" => array(
        "callback" => "taxa_classification",
        "desc" => "The taxa a taxon is inside, from the root of its source's taxonomy down to the taxon itself, in one request. A taxon's row gives only the taxon directly above it, in parent_id, so a client walking to the root asks for one taxon at a time and cannot know which to ask for next until the last has come back; the rank columns do not answer it either, as a source's tree can put a superfamily or an infraorder between the ranks they hold and there is no column for those. Ask by the source holding the taxon and its id there, as <strong>/data/taxa/classification/?source=bio.acousti.ca&amp;id=1</strong>. The taxa come back as this module gives them, the root first and the taxon asked for last. A source that does not hold the taxon gives a 404, and a chain that cannot reach the root comes back with the taxa it does reach and a note saying where it stopped.",
        "returns" => "data",
        "params" => array(
          "source" => array(
            "desc" => "Source holding the taxon",
            "type" => "string"
          ),
          "id" => array(
            "desc" => "Taxon ID within its source",
            "type" => "string"
          ),
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON",
              "nakedJSON"
            ),
            "default" => "JSON"
          )
        )
      )
    ),
    "params" => array(
      "source" => array("desc" => "Source of the taxon", "type" => "string",
        "default" => "", "column" => "source", "op" => "=", "multiple" => TRUE),
      "id" => array("desc" => "Taxon ID within its source", "type" => "string",
        "default" => "", "column" => "id", "op" => "=", "multiple" => TRUE),
      //Each source gives its taxonomy as a tree, and audioBLAST! has held that
      //tree all along without serving it: the ranks above a taxon were only to
      //be had from the columns below, which have one for a family but none for
      //a superfamily, so a taxon of a rank with no column could not be placed
      //at all. Asking for a taxon's parent_id gives the taxa directly below
      //it, and a taxon's own parent_id is in its row, so a client can walk a
      //source's taxonomy down and back up. It is the source's own tree, with
      //whatever the source has put where. The whole way up it is a request of
      //its own, as walking it a taxon at a time cannot be (see
      //taxa_classification()).
      "parent_id" => array("desc" => "ID of the taxon this one is directly inside, within the same source",
        "type" => "string", "default" => "", "column" => "parent_id", "op" => "=",
        "multiple" => TRUE),
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

/*
The taxa a taxon is inside, from the root of its source's taxonomy down to the
taxon itself.

A taxa row holds only the taxon directly above it, so a client that wants the
whole chain asks for one taxon at a time and cannot know which to ask for next
until the last has come back: fourteen requests for a mole cricket, each
waiting on the one before it. The rank columns are not the chain either. They
hold the ranks a Linnaean classification names, and a source's tree can put
taxa of other ranks between them: Gryllotalpa vineae is inside Gryllotalpini,
inside Gryllotalpinae, inside Gryllotalpidae, inside the superfamily
Gryllotalpoidea and the infraorder Gryllidea, and neither of the last two has a
column to be held in.

The walk is still a walk. It is made here, against the database, where each
step is a lookup on the key a taxon is held by, rather than across the network.
*/
function taxa_classification($params) {
  global $db;
  if (($params["source"] ?? "") === "" || ($params["id"] ?? "") === "") {
    badRequest("A classification is asked for by the source holding the taxon and its id"
      ." there, as /data/taxa/classification/?source=bio.acousti.ca&id=1.");
  }
  $module = loadModule("taxa");
  $chain = taxa_classification_chain(
    taxa_classification_named($db, $module, $params["source"], $params["id"]),
    function($source, $id) use ($db, $module) {
      return(taxa_classification_parent($db, $module, $source, $id));
    }
  );
  if ($chain === FALSE) {
    http_response_code(500);
    return(array("data" => array(), "notes" => array("classification" => "Query failed on database.")));
  }
  $ret = array("data" => $chain["taxa"]);
  if ($chain["problem"] !== NULL) {
    //Nothing at all means the source does not hold the taxon that was asked
    //for; a chain that stops short of the root is what the source does hold
    if (!$chain["taxa"]) {http_response_code(404);}
    $ret["notes"] = array("classification" => $chain["problem"]);
  }
  return($ret);
}

//A chain longer than any taxonomy is. A source that held a taxon inside itself
//would be walked for ever, and one holding a chain this long is wrong in some
//other way; either way the walk stops rather than asking on and on.
define("TAXA_CLASSIFICATION_MAX", 100);

/*
Walk from a taxon to the root of its source's tree. $taxon is the row of the
taxon asked for, and $parent is called with a source and the id of a taxon
within it and gives that row, NULL where the source holds no such taxon, or
FALSE where the lookup failed.

Gives the taxa with the root first and the taxon asked for last, and what stopped
the walk short of the root, or NULL where it reached it; FALSE where a lookup
failed. A chain that stops short is still served, with what it did reach: what
a source holds is what audioBLAST! serves, and a classification that stops at
an order is worth more than none. It is not served as though it were whole.
*/
function taxa_classification_chain($taxon, $parent) {
  if ($taxon === FALSE) {return(FALSE);}
  if ($taxon === NULL) {
    return(array("taxa" => array(), "problem" => "No taxon of that source has that id."));
  }
  $chain = array($taxon);
  //A source's name never has a / in it (see rdfRecordURI()), and the database
  //compares text regardless of case, as this does
  $seen = array(strtolower($taxon["source"]."/".$taxon["id"]) => TRUE);
  while (($taxon["parent_id"] ?? "") !== "") {
    if (count($chain) >= TAXA_CLASSIFICATION_MAX) {
      return(taxa_classification_stopped($chain, "the chain is longer than "
        .TAXA_CLASSIFICATION_MAX." taxa"));
    }
    $up = call_user_func($parent, $taxon["source"], $taxon["parent_id"]);
    if ($up === FALSE) {return(FALSE);}
    if ($up === NULL) {
      return(taxa_classification_stopped($chain, "`".$taxon["taxon"]."` is inside `"
        .$taxon["parent_id"]."`, which this source does not hold"));
    }
    $key = strtolower($up["source"]."/".$up["id"]);
    if (isset($seen[$key])) {
      return(taxa_classification_stopped($chain, "`".$up["taxon"]."` is inside itself"));
    }
    $seen[$key] = TRUE;
    $chain[] = $up;
    $taxon = $up;
  }
  return(array("taxa" => array_reverse($chain), "problem" => NULL));
}

//A chain that stopped before it reached the root, with the taxa it did reach
function taxa_classification_stopped($chain, $why) {
  return(array("taxa" => array_reverse($chain),
    "problem" => "This is not the whole classification: ".$why
      .", so the walk stopped there and the taxa above it are not given."));
}

//The taxon a request named. Its source and id reach a module escaped for a
//query (see moduleAPI()), as the values of a WHERE clause are, so they are put
//into one rather than bound.
function taxa_classification_named($db, $module, $source, $id) {
  $sql = SELECTclause($module, NULL, "table", "internal")
    ." WHERE `".$module["params"]["source"]["column"]."` = '".$source."'"
    ." AND `".$module["params"]["id"]["column"]."` = '".$id."' LIMIT 1;";
  $result = $db->query($sql);
  if ($result === FALSE) {return(FALSE);}
  $row = $result->fetch_assoc();
  $result->close();
  return($row);
}

//A taxon the walk has read the id of. Those ids come from the database rather
//than from the request, so they have not been escaped and are bound.
function taxa_classification_parent($db, $module, $source, $id) {
  $sql = SELECTclause($module, NULL, "table", "internal")
    ." WHERE `".$module["params"]["source"]["column"]."` = ?"
    ." AND `".$module["params"]["id"]["column"]."` = ? LIMIT 1;";
  $stmt = $db->prepare($sql);
  if (!$stmt) {return(FALSE);}
  if (!$stmt->bind_param("ss", $source, $id) || !$stmt->execute()) {
    $stmt->close();
    return(FALSE);
  }
  $result = $stmt->get_result();
  if (!$result) {$stmt->close(); return(FALSE);}
  $row = $result->fetch_assoc();
  $result->close();
  $stmt->close();
  return($row);
}

/*
The taxa a taxon is inside, on the taxon's own page.

A breadcrumb is the query every page of a taxon browser makes, and following
parent_id in RDF costs a request for each step up, as it does in JSON. Here it
costs none: a taxon asked for at its own URI carries the taxa above it, each
described as it is on its own page, so what a client needs to draw
Orthoptera > Ensifera > ... > Gryllotalpa vineae arrives with the taxon.

This is the one place it is affordable. A page of fifty taxa would walk fifty
trees, so the module endpoint gives a taxon's parent and no more; whoever wants
the chain as JSON has /data/taxa/classification/ (see taxa_classification()).
*/
function taxa_rdf_record($db, $module, $taxa) {
  $nodes = array();
  foreach ($taxa as $taxon) {
    $chain = taxa_classification_chain($taxon, function($source, $id) use ($db, $module) {
      return(taxa_classification_parent($db, $module, $source, $id));
    });
    if ($chain === FALSE) {return(FALSE);}
    foreach (taxa_rdf_ancestors($module, $chain) as $node) {$nodes[] = $node;}
  }
  return($nodes);
}

/*
The nodes of a walk up a source's tree (see taxa_classification_chain()): the
taxa above the one asked for, and what each of them is inside.

A taxa row is a source's own taxon concept, which is how the module already
speaks of it in saying that two rows are the same taxon, so the taxon a row is
directly inside is skos:broader of it: more general in that source's tree, and
said of that row rather than of the name. Nothing is claimed to be transitive,
as a source is free to put whatever it likes between two ranks.

dwc:higherClassification says the same thing again the way Darwin Core says it,
as the names of the taxa above this one with the highest first. It is only
given where the walk reached the root, since a chain that stopped short would
read as the whole classification and there is nothing in RDF to note that it is
not.
*/
function taxa_rdf_ancestors($module, $chain) {
  $rows = $chain["taxa"];
  //A taxon at the root of its source's tree is inside nothing
  if (count($rows) < 2) {return(array());}
  $uris = array();
  foreach ($rows as $row) {$uris[] = rdfRecordURI($module, $row["source"], $row["id"]);}

  $nodes = array();
  $names = array();
  $last = count($rows) - 1;
  foreach ($rows as $i => $row) {
    //The taxon asked for is described already, by the page it was asked for on
    $node = ($i === $last) ? array("@id" => $uris[$i]) : taxa_rdf_node($row, $uris[$i]);
    if ($i > 0) {$node["skos:broader"] = rdfIRI($uris[$i - 1]);}
    if ($i < $last && ($row["taxon"] ?? "") !== "") {$names[] = $row["taxon"];}
    //Every name above it is in hand by the time the taxon itself is reached
    if ($i === $last && $chain["problem"] === NULL && $names) {
      $node["dwc:higherClassification"] = implode("|", $names);
    }
    $nodes[] = $node;
  }
  return($nodes);
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

// The taxon that a link says a taxa row is, as the type of record it is, the
// source holding it and its id there, or NULL where the link says something
// else. A taxa row is a source's own taxon concept, and audioBLAST! holds one
// for every source that knows the taxon; a link to the Catalogue of Life says
// which taxon that is.
//
// What a row is matched to is a record like any other, so it is named the way
// every record is. The Catalogue of Life is held as a source of its own, and a
// row is matched to its row there, which a client resolves without leaving
// audioBLAST!; a row matched to a taxonomy audioBLAST! does not hold is matched
// to an iri instead, and reads the same way.
function taxa_rdf_matched($link) {
  if (($link["subject_type"] ?? "") !== "taxa") {return(NULL);}
  if (($link["predicate"] ?? "") !== TAXA_EXACT_MATCH) {return(NULL);}
  $type = $link["object_type"] ?? "";
  $id = $link["object_id"] ?? "";
  if ($type === "" || $id === "") {return(NULL);}
  return(array($type, $link["object_source"] ?? "", $id));
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
    $values = array(TAXA_EXACT_MATCH);
    $places = array();
    foreach ($batch as $match) {
      $places[] = "(?, ?, ?)";
      foreach ($match as $part) {$values[] = $part;}
    }
    $sql = SELECTclause($links_module, NULL, "table", "internal");
    $sql .= " WHERE `subject_type` = 'taxa' AND `predicate` = ?";
    $sql .= " AND (`object_type`, `object_source`, `object_id`) IN (".implode(", ", $places).");";
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
