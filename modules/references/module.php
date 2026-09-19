<?php

function references_info() {
  $info = array(
    "mname" => "references",
    "version" => 1.0,
    "category" => "data",
    "table" => "references",
    "hname" => "References",
    "desc" => "This endpoint allows for the querying of the bibliographic references held within audioBLAST!",
    "source_notes" => "References are ingested from BibTeX or CSV, and are identified by their id within each source (for BibTeX, the entry's key).",
    "rdf" => array("path" => "reference", "node" => "references_rdf_node", "related" => "references_rdf_related"),
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
        "desc" => "ID of the reference within its source (for BibTeX, its key)",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "type" => array(
        "desc" => "Type of reference, as a BibTeX entry type whatever the source's format, e.g. article, book or phdthesis",
        "type" => "string",
        "default" => "",
        "column" => "type",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "title" => array(
        "desc" => "Title",
        "type" => "string",
        "default" => "",
        "column" => "title",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "author" => array(
        "desc" => "Authors, surname first and separated by semicolons, e.g. Darwin, Charles; von Frisch, Karl",
        "type" => "string",
        "default" => "",
        "column" => "author",
        "op" => "contains"
      ),
      "editor" => array(
        "desc" => "Editors, in the same form as authors",
        "type" => "string",
        "default" => "",
        "column" => "editor",
        "op" => "contains"
      ),
      "year" => array(
        "desc" => "Year of publication",
        "type" => "range",
        "default" => "",
        "column" => "year",
        "op" => "range"
      ),
      "month" => array(
        "desc" => "Month of publication",
        "type" => "string",
        "default" => "",
        "column" => "month",
        "op" => "contains"
      ),
      "journal" => array(
        "desc" => "Journal",
        "type" => "string",
        "default" => "",
        "column" => "journal",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "booktitle" => array(
        "desc" => "Title of the book or proceedings that the reference is part of",
        "type" => "string",
        "default" => "",
        "column" => "booktitle",
        "op" => "contains"
      ),
      "series" => array(
        "desc" => "Series",
        "type" => "string",
        "default" => "",
        "column" => "series",
        "op" => "contains"
      ),
      "howpublished" => array(
        "desc" => "How an unusual reference was published",
        "type" => "string",
        "default" => "",
        "column" => "howpublished",
        "op" => "contains"
      ),
      "volume" => array(
        "desc" => "Volume",
        "type" => "string",
        "default" => "",
        "column" => "volume",
        "op" => "="
      ),
      "number" => array(
        "desc" => "Number or issue",
        "type" => "string",
        "default" => "",
        "column" => "number",
        "op" => "="
      ),
      "pages" => array(
        "desc" => "Pages",
        "type" => "string",
        "default" => "",
        "column" => "pages",
        "op" => "="
      ),
      "chapter" => array(
        "desc" => "Chapter",
        "type" => "string",
        "default" => "",
        "column" => "chapter",
        "op" => "="
      ),
      "edition" => array(
        "desc" => "Edition",
        "type" => "string",
        "default" => "",
        "column" => "edition",
        "op" => "="
      ),
      "publisher" => array(
        "desc" => "Publisher",
        "type" => "string",
        "default" => "",
        "column" => "publisher",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "organization" => array(
        "desc" => "Organisation, e.g. that sponsored a conference",
        "type" => "string",
        "default" => "",
        "column" => "organization",
        "op" => "contains"
      ),
      "institution" => array(
        "desc" => "Institution that published a report",
        "type" => "string",
        "default" => "",
        "column" => "institution",
        "op" => "contains"
      ),
      "school" => array(
        "desc" => "School (university) of a thesis",
        "type" => "string",
        "default" => "",
        "column" => "school",
        "op" => "contains"
      ),
      "address" => array(
        "desc" => "Place of publication",
        "type" => "string",
        "default" => "",
        "column" => "address",
        "op" => "contains"
      ),
      "type_of_work" => array(
        "desc" => "Type of work (BibTeX type field), e.g. the type of a thesis",
        "type" => "string",
        "default" => "",
        "column" => "type_of_work",
        "op" => "contains"
      ),
      "note" => array(
        "desc" => "Note",
        "type" => "string",
        "default" => "",
        "column" => "note",
        "op" => "contains"
      ),
      "isbn" => array(
        "desc" => "ISBN",
        "type" => "string",
        "default" => "",
        "column" => "isbn",
        "op" => "contains"
      ),
      "issn" => array(
        "desc" => "ISSN",
        "type" => "string",
        "default" => "",
        "column" => "issn",
        "op" => "contains"
      ),
      "doi" => array(
        "desc" => "DOI, without a resolver, e.g. 10.1093/database/bav054",
        "type" => "string",
        "default" => "",
        "column" => "doi",
        "op" => "="
      ),
      "url" => array(
        "desc" => "URL",
        "type" => "string",
        "default" => "",
        "column" => "url",
        "op" => "contains"
      ),
      "attachments" => array(
        "desc" => "URLs of files attached to the reference, separated by semicolons",
        "type" => "string",
        "default" => "",
        "column" => "attachments",
        "op" => "contains"
      ),
      "keywords" => array(
        "desc" => "Keywords",
        "type" => "string",
        "default" => "",
        "column" => "keywords",
        "op" => "contains"
      ),
      "abstract" => array(
        "desc" => "Abstract",
        "type" => "string",
        "default" => "",
        "column" => "abstract",
        "op" => "contains"
      ),
      "type_name" => array(
        "desc" => "Type of reference as the source names it, e.g. Journal Article, Book Chapter or Audiovisual",
        "type" => "string",
        "default" => "",
        "column" => "type_name",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "journal_abbreviation" => array(
        "desc" => "Abbreviated title of the journal, e.g. Anim Behav",
        "type" => "string",
        "default" => "",
        "column" => "journal_abbreviation",
        "op" => "contains"
      ),
      "pmid" => array(
        "desc" => "PubMed ID",
        "type" => "string",
        "default" => "",
        "column" => "pmid",
        "op" => "="
      ),
      "info_url" => array(
        "desc" => "URL of the reference's page at its source",
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

// A publication is distinct from the catalogue page describing it.
function references_rdf_node($ref, $uri) {
  $types = array(
    "article" => "journalArticle", "book" => "book", "booklet" => "book",
    "inbook" => "bookSection", "incollection" => "bookSection",
    "inproceedings" => "proceedingsPaper", "conference" => "proceedingsPaper",
    "proceedings" => "proceedings", "phdthesis" => "thesis",
    "mastersthesis" => "thesis", "techreport" => "report", "manual" => "manual"
  );
  $node = array("@id" => $uri,
    "@type" => "http://purl.org/dc/terms/BibliographicResource",
    "dwc:referenceID" => $uri);
  $type = $ref["type"] ?? "";
  rdfAdd($node, "dwc:referenceType", $types[$type] ?? $type);
  foreach (array(
    "title" => "dcterms:title", "note" => "dwc:referenceRemarks",
    "abstract" => "dcterms:abstract", "doi" => "bibo:doi",
    "pmid" => "bibo:pmid", "isbn" => "bibo:isbn",
    "volume" => "bibo:volume", "number" => "bibo:issue",
    "pages" => "bibo:pages", "chapter" => "bibo:chapter",
    "edition" => "bibo:edition", "type_name" => "dc:type"
  ) as $field => $property) {
    rdfAdd($node, $property, $ref[$field] ?? NULL);
  }
  // Keep unusual date strings as supplied; corrections belong in the ingest.
  $year = $ref["year"] ?? NULL;
  rdfAdd($node, "dcterms:issued", rdfDate($year) ?? $year);
  $node["rdfs:seeAlso"] = array();
  foreach (array("info_url", "url") as $field) {
    $url = rdfURL($ref[$field] ?? NULL);
    if ($url !== NULL) {$node["rdfs:seeAlso"][] = $url;}
  }
  if (rdfURL($ref["url"] ?? NULL) !== NULL) {
    $node["bibo:uri"] = rdfTyped($ref["url"], "xsd:anyURI");
  }
  // Discover incoming and outgoing assertions without extra database queries.
  foreach (array("subject", "object") as $side) {
    $node["rdfs:seeAlso"][] = rdfIRI("https://api.audioblast.org/data/links/?".http_build_query(array(
      $side."_type" => "references", $side."_source" => $ref["source"],
      $side."_id" => $ref["id"])));
  }
  // Literal names preserve source order and corporate-name braces.
  rdfAdd($node, "dc:creator", $ref["author"] ?? NULL);
  rdfAdd($node, "dc:publisher", $ref["publisher"] ?? NULL);
  $keywords = references_rdf_values($ref["keywords"] ?? "");
  if ($keywords) {$node["dc:subject"] = $keywords;}
  $attachments = array();
  foreach (references_rdf_values($ref["attachments"] ?? "") as $url) {
    $value = rdfURL($url);
    if ($value !== NULL) {$attachments[] = $value;}
  }
  if ($attachments) {$node["dcterms:relation"] = $attachments;}
  foreach (array("journal", "booktitle", "series") as $field) {
    if (($ref[$field] ?? "") !== "") {
      $node["dcterms:isPartOf"][] = rdfIRI($uri."#".$field);
    }
  }
  foreach (array("author" => "dcterms:creator", "editor" => "bibo:editor") as $role => $property) {
    $names = references_rdf_values($ref[$role] ?? "");
    if (!$names) {continue;}
    $node["bibo:".$role."List"] = rdfIRI($uri."#".$role."s");
    foreach ($names as $index => $name) {
      $node[$property][] = rdfIRI($uri."#".$role."-".($index + 1));
    }
  }
  $identifiers = array();
  if (!empty($ref["doi"]) && preg_match('#^10[.][0-9]+/[^\s]+$#', $ref["doi"])) {
    $identifiers[] = rdfIRI("https://doi.org/".implode("/", array_map("rawurlencode", explode("/", $ref["doi"]))));
  }
  if (!empty($ref["pmid"]) && preg_match("/^[0-9]+$/", (string)$ref["pmid"])) {
    $identifiers[] = rdfIRI("https://pubmed.ncbi.nlm.nih.gov/".$ref["pmid"]."/");
  }
  if ($identifiers) {
    $node["dcterms:identifier"] = array_map(function($value) {return($value["@id"]);}, $identifiers);
    $node["rdfs:seeAlso"] = array_merge($node["rdfs:seeAlso"], $identifiers);
  }
  if (empty($ref["journal"])) {rdfAdd($node, "bibo:issn", $ref["issn"] ?? NULL);}
  return($node);
}

// Related descriptions have local fragment identifiers, not invented global identities.
function references_rdf_related($ref, $uri) {
  $nodes = array();
  foreach (array("journal" => "Journal", "booktitle" => "Document", "series" => "Series") as $field => $type) {
    if (($ref[$field] ?? "") === "") {continue;}
    $node = array("@id" => $uri."#".$field,
      "@type" => "http://purl.org/ontology/bibo/".$type,
      "dcterms:title" => $ref[$field]);
    if ($field === "journal") {
      rdfAdd($node, "bibo:issn", $ref["issn"] ?? NULL);
      rdfAdd($node, "bibo:shortTitle", $ref["journal_abbreviation"] ?? NULL);
    }
    $nodes[] = $node;
  }
  foreach (array("author", "editor") as $role) {
    $names = references_rdf_values($ref[$role] ?? "");
    if (!$names) {continue;}
    $sequence = array("@id" => $uri."#".$role."s",
      "@type" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#Seq");
    foreach ($names as $index => $name) {
      $id = $uri."#".$role."-".($index + 1);
      $sequence["rdf:_".($index + 1)] = rdfIRI($id);
      $nodes[] = array("@id" => $id,
        "@type" => "http://xmlns.com/foaf/0.1/Agent", "foaf:name" => $name);
    }
    $nodes[] = $sequence;
  }
  return($nodes);
}

function references_rdf_values($text) {
  return(array_values(array_filter(array_map("trim", explode(";", (string)$text)),
    function($value) {return($value !== "");})));
}
