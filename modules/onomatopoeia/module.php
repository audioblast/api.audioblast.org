<?php

function onomatopoeia_info() {
  $info = array(
    "mname" => "onomatopoeia",
    "version" => 1.0,
    "category" => "data",
    "table" => "onomatopoeia",
    "hname" => "Onomatopoeia",
    "desc" => "This endpoint allows for the querying of the words that the sources held within audioBLAST! render an animal's sound with, such as bow-wow for a dog barking, kikeriki for a cockerel crowing, or a mnemonic a birdwatcher remembers a song by. Each has a kind saying what sort of rendering it is, and the class that kind names where it names one. The taxon whose sound is rendered, and the reference the rendering was taken from, are links (see the links endpoint), not columns here. With output=JSON-LD or output=Turtle (or, without output, an Accept header asking for application/ld+json or text/turtle), renderings are given as RDF texts, each one that is a word also a lexical entry. Each is identified by https://api.audioblast.org/onomatopoeia/{source}/{id}, which gives it in the same way.",
    "source_notes" => "Onomatopoeia are ingested from each source's renderings, and are identified by their id within it. A rendering is about the taxon whose sound it renders (http://purl.obolibrary.org/obo/IAO_0000136), and never denotes it: denoting is what a vernacular name does, and what reads a name onto a taxon as one of the names it is known by, so bark is not published as a name for Canis lupus familiaris. The reference a rendering was taken from is linked by dcterms:source. A rendering whose language a source doesn't give has none: the API never infers a language from a word, and musical notation is in no language at all.",
    "see_also" => array(
      "<a href='#taxa'>Taxa</a> gives the taxa whose sounds these render.",
      "<a href='#vernacularnames'>Vernacular names</a> gives the names those taxa are known by, which these are not."
    ),
    //Onomatopoeia as RDF (see core/rdf.php), identified by
    //https://api.audioblast.org/onomatopoeia/{source}/{id}
    "rdf" => array(
      "links" => TRUE,
      "path" => "onomatopoeia",
      "node" => "onomatopoeia_rdf_node"
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
        "desc" => "ID of the rendering within its source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "multiple" => TRUE
      ),
      "word" => array(
        "desc" => "The word the sound is rendered with, e.g. bow-wow",
        "type" => "string",
        "default" => "",
        "column" => "word",
        "op" => "contains"
      ),
      "kind" => array(
        "desc" => "What sort of rendering it is, in its source's own words, e.g. imitation, onomatopoeia verb, mnemonic or musical notation",
        "type" => "string",
        "default" => "",
        "column" => "kind",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "kind_link" => array(
        "desc" => "IRI of the class the kind names, e.g. http://purl.org/olia/olia.owl#OnomatopoeticWord; empty where the kind names none, as no vocabulary names a mnemonic or musical notation",
        "type" => "string",
        "default" => "",
        "column" => "kind_link",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "language" => array(
        "desc" => "Language the rendering is in (dcterms:language), as an IETF BCP 47 language tag, e.g. en, ja or en-GB. A rendering whose source did not record its language has none.",
        "type" => "string",
        "default" => "",
        "column" => "language",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "sex" => array(
        "desc" => "The sex the rendering is of (dwc:sex), where it is of one, e.g. male for a cockerel's crow",
        "type" => "string",
        "default" => "",
        "column" => "sex",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "lifeStage" => array(
        "desc" => "The life stage the rendering is of (dwc:lifeStage), where it is of one, e.g. juvenile for a puppy's yelp",
        "type" => "string",
        "default" => "",
        "column" => "lifeStage",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "locality" => array(
        "desc" => "Where, or by whom, the rendering is used (dwc:locality), e.g. Lokele tribe of the Congo",
        "type" => "string",
        "default" => "",
        "column" => "locality",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "remarks" => array(
        "desc" => "Remarks on the rendering (dwc:taxonRemarks), e.g. Sound of wings",
        "type" => "string",
        "default" => "",
        "column" => "remarks",
        "op" => "contains"
      ),
      "info_url" => array(
        "desc" => "URL of the rendering's page at its source",
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

//A rendering as an RDF node (see core/rdf.php) at its URI. The word itself is
//an rdfs:label in the language it is in, as no standard has a property for the
//word a sound is rendered with: Darwin Core's vernacularName takes a name for
//the taxon, which this is not, and ontolex:writtenRep takes a form rather than
//an entry and must be in a language, which a rendering need not be.
//
//Every rendering is a text. One whose kind names a class is also a lexical
//entry, since the classes a kind can name are word classes; a rendering whose
//kind names none is only ever said to be a text, so a line of musical notation
//is not published as a word of some language.
function onomatopoeia_rdf_node($rendering, $uri) {
  $node = array("@id" => $uri,
    "@type" => array("http://purl.org/dc/dcmitype/Text"));
  $kind = rdfURL($rendering["kind_link"] ?? NULL);
  if ($kind !== NULL) {
    $node["@type"][] = "http://www.w3.org/ns/lemon/ontolex#LexicalEntry";
  }
  rdfAdd($node, "rdfs:label",
    rdfLang($rendering["word"] ?? NULL, $rendering["language"] ?? NULL));
  //A kind is its source's own word, and the class it names is what that word
  //means to anyone else
  rdfAdd($node, "dc:type", $rendering["kind"] ?? NULL);
  rdfAdd($node, "dcterms:type", $kind);
  //The tag is given on its own as well, for a client that wants it apart from
  //the word, and typed as the BCP 47 syntax it is
  $language = $rendering["language"] ?? "";
  if ($language !== "" && $language !== NULL) {
    rdfAdd($node, "dcterms:language", rdfTyped($language, "xsd:language"));
  }
  rdfAdd($node, "dwc:sex", $rendering["sex"] ?? NULL);
  rdfAdd($node, "dwc:lifeStage", $rendering["lifeStage"] ?? NULL);
  rdfAdd($node, "dwc:locality", $rendering["locality"] ?? NULL);
  rdfAdd($node, "dwc:taxonRemarks", $rendering["remarks"] ?? NULL);
  $node["rdfs:seeAlso"] = array();
  $url = rdfURL($rendering["info_url"] ?? NULL);
  if ($url !== NULL) {$node["rdfs:seeAlso"][] = $url;}
  //Discover the taxon the rendering is of, and the reference it came from
  foreach (array("subject", "object") as $side) {
    $node["rdfs:seeAlso"][] = rdfIRI("https://api.audioblast.org/data/links/?".http_build_query(array(
      $side."_type" => "onomatopoeia", $side."_source" => $rendering["source"],
      $side."_id" => $rendering["id"])));
  }
  return($node);
}
