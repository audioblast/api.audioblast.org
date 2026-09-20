<?php

function images_info() {
  $info = array(
    "mname" => "images",
    "version" => 1.0,
    "category" => "data",
    "table" => "images",
    "hname" => "Images",
    "desc" => "<p>This endpoint allows for the querying of the images held within audioBLAST!: the scans of the sheets a recording's metadata was written on and of the paper oscillographic traces made from it, the figures taken from papers, and the pictures of specimens and of the places recordings were made.</p><p>An image is a record rather than a value on the records that show it, so that one scan covering four recordings is described once, with the licence it is under, and linked to each of the four. What an image shows is in <a href='#links'>Links</a>. An image whose source gives no licence has none here, which says nothing about what may be done with it.</p><p>With output=JSON-LD or output=Turtle (or, without output, an Accept header asking for application/ld+json or text/turtle), images are given as RDF in Audiovisual Core terms. Each image is identified by https://api.audioblast.org/image/{source}/{id}, which gives the image in the same way.</p>",
    "source_notes" => "Images are ingested from each source's images, and are identified by their id within it. What an image shows is links (see the links endpoint).",
    //Images as RDF (see core/rdf.php), identified by https://api.audioblast.org/image/{source}/{id}
    "rdf" => array(
      "links" => TRUE,
      "path" => "image",
      "node" => "images_rdf_node",
      "related" => "images_rdf_related"
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
        "desc" => "ID of the image within its source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "ac" => "ac:providerManagedID"
      ),
      "title" => array(
        "desc" => "What the source calls the image, which for a figure taken from a paper is its legend",
        "type" => "string",
        "default" => "",
        "column" => "title",
        "op" => "contains",
        "fulltext" => TRUE,
        "autocomplete" => TRUE,
        "ac" => "dcterms:title"
      ),
      "url" => array(
        "desc" => "URL of the image itself",
        "type" => "string",
        "default" => "",
        "column" => "file",
        "op" => "contains",
        "ac" => "ac:accessURI"
      ),
      "kind" => array(
        "desc" => "What kind of image it is in the source's own words, e.g. Photograph, Original trace scan",
        "type" => "string",
        "default" => "",
        "column" => "subtype",
        "op" => "=",
        "autocomplete" => TRUE,
        "ac" => "ac:subtypeLiteral"
      ),
      "creator" => array(
        "desc" => "Who made the image",
        "type" => "string",
        "default" => "",
        "column" => "creator",
        "op" => "contains",
        "autocomplete" => TRUE,
        "suggest" => array(
          "desc" => "By same creator in source",
          "same_source" => TRUE
        ),
        "ac" => "dc:creator"
      ),
      "license" => array(
        "desc" => "URL of the licence the image is under",
        "type" => "string",
        "default" => "",
        "column" => "license",
        "op" => "contains",
        "autocomplete" => TRUE,
        "ac" => "dcterms:rights"
      ),
      "post_date" => array(
        "desc" => "Date the image was uploaded (YYYY-MM-DD)",
        "type" => "string",
        "default" => "",
        "column" => "post_date",
        "op" => "none",
        "ac" => "dcterms:available"
      ),
      "mime" => array(
        "desc" => "MIME type of the image",
        "type" => "string",
        "default" => "",
        "column" => "type",
        "op" => "=",
        "autocomplete" => TRUE,
        "ac" => "dc:format"
      ),
      "bytes" => array(
        "desc" => "Size of the file in bytes",
        "type" => "range",
        "default" => "",
        "column" => "size_raw",
        "op" => "range"
      ),
      "width" => array(
        "desc" => "Width of the image in pixels",
        "type" => "range",
        "default" => "",
        "column" => "width",
        "op" => "range",
        "ac" => "exif:PixelXDimension"
      ),
      "height" => array(
        "desc" => "Height of the image in pixels",
        "type" => "range",
        "default" => "",
        "column" => "height",
        "op" => "range",
        "ac" => "exif:PixelYDimension"
      ),
      "caption" => array(
        "desc" => "The caption the source gives the image",
        "type" => "string",
        "default" => "",
        "column" => "caption",
        "op" => "contains",
        "fulltext" => TRUE,
        "ac" => "ac:caption"
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

function images_rdf_node($image, $uri) {
  static $providers = NULL;
  if ($providers === NULL) {
    $providers = array();
    foreach (loadModules("source") as $source) {
      $providers[$source["mname"]] = $source["hname"];
    }
  }

  $node = array(
    "@id" => $uri,
    "@type" => array("http://rs.tdwg.org/ac/terms/Media", "http://purl.org/dc/dcmitype/StillImage"),
    "dcterms:type" => rdfIRI("http://purl.org/dc/dcmitype/StillImage")
  );
  rdfAdd($node, "dcterms:title", $image["title"] ?? NULL);
  //What kind of image it is, as a literal: sources name their own kinds, and
  //Audiovisual Core's subtype vocabulary has no term for a scan of the paper
  //trace an oscillograph drew
  rdfAdd($node, "ac:subtypeLiteral", $image["kind"] ?? NULL);
  rdfAdd($node, "ac:caption", $image["caption"] ?? NULL);
  rdfAdd($node, "dc:creator", $image["creator"] ?? NULL);
  rdfAdd($node, "dcterms:rights", rdfURL($image["license"] ?? NULL));
  rdfAdd($node, "exif:PixelXDimension", rdfDecimal($image["width"] ?? NULL));
  rdfAdd($node, "exif:PixelYDimension", rdfDecimal($image["height"] ?? NULL));
  rdfAdd($node, "ac:providerLiteral", $providers[$image["source"]] ?? $image["source"]);
  rdfAdd($node, "ac:providerManagedID", $image["id"]);
  rdfAdd($node, "dcterms:available", rdfDate($image["post_date"] ?? NULL));
  $service = rdfServiceAccessPoint($uri, $image["url"] ?? NULL, $image["mime"] ?? NULL);
  if ($service !== NULL) {$node["ac:hasServiceAccessPoint"] = rdfIRI($service["@id"]);}
  return($node);
}

function images_rdf_related($image, $uri) {
  $service = rdfServiceAccessPoint($uri, $image["url"] ?? NULL, $image["mime"] ?? NULL);
  return($service === NULL ? array() : array($service));
}
