<?php

function descriptions_info() {
  $info = array(
    "mname" => "descriptions",
    "version" => 1.0,
    "category" => "data",
    "table" => "descriptions",
    "hname" => "Descriptions",
    "desc" => "This endpoint allows for the querying of what the sources held within audioBLAST! say about something in prose, such as how a taxon behaves, where and when it calls and how far apart calling males are. Each description has a topic saying what it is of, and the Species Profile Model info item that topic names where it names one. What a description is about, and the references it rests on, are links (see the links endpoint), not columns here, so a description that cites a paper says which paper. With output=JSON-LD or output=Turtle (or, without output, an Accept header asking for application/ld+json or text/turtle), descriptions are given as RDF texts. Each is identified by https://api.audioblast.org/description/{source}/{id}, which gives it in the same way.",
    "source_notes" => "Descriptions are ingested from each source's descriptions and replace the descriptions that the source gave before. Topics are each source's own words, and the ingest reads the Species Profile Model info item each one names.",
    //Descriptions as RDF (see core/rdf.php), identified by
    //https://api.audioblast.org/description/{source}/{id}
    "rdf" => array(
      "links" => TRUE,
      "path" => "description",
      "node" => "descriptions_rdf_node"
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
        "desc" => "ID of the description within its source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "multiple" => TRUE
      ),
      "topic" => array(
        "desc" => "What the description is of, in its source's own words, e.g. behaviour, morphology, diagnostic or general",
        "type" => "string",
        "default" => "",
        "column" => "topic",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "value" => array(
        "desc" => "The description itself, as plain text",
        "type" => "string",
        "default" => "",
        "column" => "value",
        "op" => "contains"
      ),
      "topic_link" => array(
        "desc" => "IRI of the Species Profile Model info item the topic names, e.g. http://rs.tdwg.org/ontology/voc/SPMInfoItems#Behaviour; empty where the topic names none",
        "type" => "string",
        "default" => "",
        "column" => "topic_link",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "info_url" => array(
        "desc" => "URL of the description's page at its source",
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

//A description as an RDF node (see core/rdf.php) at its URI: a Dublin Core
//text whose topic is its dc:type, as GBIF's Taxon Description extension gives
//them. What it is about and the references it rests on are links, so they
//reach the graph the way every other relationship does.
function descriptions_rdf_node($description, $uri) {
  $node = array("@id" => $uri,
    "@type" => "http://purl.org/dc/dcmitype/Text");
  rdfAdd($node, "dc:description", $description["value"] ?? NULL);
  //A topic is its source's own word, and the info item it names is what that
  //word means to anyone else
  rdfAdd($node, "dc:type", $description["topic"] ?? NULL);
  rdfAdd($node, "dcterms:type", rdfURL($description["topic_link"] ?? NULL));
  $node["rdfs:seeAlso"] = array();
  $url = rdfURL($description["info_url"] ?? NULL);
  if ($url !== NULL) {$node["rdfs:seeAlso"][] = $url;}
  //Discover what the description is about and what it cites
  foreach (array("subject", "object") as $side) {
    $node["rdfs:seeAlso"][] = rdfIRI("https://api.audioblast.org/data/links/?".http_build_query(array(
      $side."_type" => "descriptions", $side."_source" => $description["source"],
      $side."_id" => $description["id"])));
  }
  return($node);
}
