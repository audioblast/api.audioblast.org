<?php

function vernacularnames_info() {
  $info = array(
    "mname" => "vernacularnames",
    "version" => 1.0,
    "category" => "data",
    "table" => "vernacularnames",
    "hname" => "Vernacular names",
    "desc" => "This endpoint allows for the querying of the vernacular names of the taxa held within audioBLAST!: the names a taxon is known by in a language, such as Warzenbeißer for Decticus verrucivorus. Their columns are named after the Darwin Core terms they hold. With output=JSON-LD or output=Turtle (or, without output, an Accept header asking for application/ld+json or text/turtle), names are given as RDF, each as a Darwin Core vernacular name whose dwc:vernacularName carries the language it is in. Each name is identified by https://api.audioblast.org/vernacular-name/{source}/{id}, which gives the name in the same way.",
    "source_notes" => "Vernacular names are ingested from each source's vernacular names, and are identified by their id within it. The taxon a name names, and the reference it was taken from, are links (see the links endpoint): the taxon by http://purl.obolibrary.org/obo/IAO_0000219 (denotes), a subproperty of is about, as Darwin Core's vernacularName takes the name rather than the taxon; the reference by dcterms:source. A name is as its source gives it, with the article a reference wrote it with, and one whose language a source doesn't give has none: the API never infers a language from a name.",
    "see_also" => array(
      "<a href='#taxa'>Taxa</a> gives the taxa that these are the names of."
    ),
    //Vernacular names as RDF (see core/rdf.php), identified by
    //https://api.audioblast.org/vernacular-name/{source}/{id}
    "rdf" => array(
      "links" => TRUE,
      "path" => "vernacular-name",
      "node" => "vernacularnames_rdf_node"
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
        "desc" => "ID of the vernacular name within its source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "multiple" => TRUE
      ),
      "vernacularName" => array(
        "desc" => "The name the taxon is known by (dwc:vernacularName)",
        "type" => "string",
        "default" => "",
        "column" => "vernacularName",
        "op" => "contains"
      ),
      "language" => array(
        "desc" => "Language the name is in (dcterms:language), as an IETF BCP 47 language tag, e.g. en, fr or pt-BR. A name whose source did not record its language has none.",
        "type" => "string",
        "default" => "",
        "column" => "language",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "locality" => array(
        "desc" => "Where the name is used (dwc:locality), where it is used in one place, e.g. North America",
        "type" => "string",
        "default" => "",
        "column" => "locality",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "remarks" => array(
        "desc" => "Remarks on the name (dwc:taxonRemarks)",
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

//A vernacular name as an RDF node (see core/rdf.php) at its URI. The name
//itself is a literal in the language it is in, so that a client can take the
//names in a language it reads as it takes any other labelled text; the tag is
//given as dcterms:language as well, for a client that wants it on its own,
//typed as the IETF BCP 47 syntax it is, as onomatopoeia give theirs.
function vernacularnames_rdf_node($name, $uri) {
  $node = array("@id" => $uri,
    "@type" => "http://rs.gbif.org/terms/1.0/VernacularName");
  rdfAdd($node, "dwc:vernacularName",
    rdfLang($name["vernacularName"] ?? NULL, $name["language"] ?? NULL));
  $language = $name["language"] ?? "";
  if ($language !== "" && $language !== NULL) {
    rdfAdd($node, "dcterms:language", rdfTyped($language, "xsd:language"));
  }
  rdfAdd($node, "dwc:locality", $name["locality"] ?? NULL);
  rdfAdd($node, "dwc:taxonRemarks", $name["remarks"] ?? NULL);
  //Discover the taxon the name is for, and the reference it was taken from
  $node["rdfs:seeAlso"] = array();
  foreach (array("subject", "object") as $side) {
    $node["rdfs:seeAlso"][] = rdfIRI("https://api.audioblast.org/data/links/?".http_build_query(array(
      $side."_type" => "vernacularnames", $side."_source" => $name["source"],
      $side."_id" => $name["id"])));
  }
  return($node);
}
