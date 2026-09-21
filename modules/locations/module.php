<?php

function locations_info() {
  $info = array(
    "mname" => "locations",
    "version" => 1.0,
    "category" => "data",
    "table" => "locations",
    "hname" => "Locations",
    "desc" => "This endpoint allows for the querying of the places that the records held within audioBLAST! were made or collected at. A place is described once, however many recordings and specimens are of it, and its columns are named after the Darwin Core terms they hold. Which records are of a place is a link (dwciri:inDescribedPlace, see the links endpoint), not a column here. With output=JSON-LD or output=Turtle (or, without output, an Accept header asking for application/ld+json or text/turtle), places are given as RDF Darwin Core locations. Each place is identified by https://api.audioblast.org/location/{source}/{id}, which gives the place in the same way.",
    "source_notes" => "Locations are ingested from each source's locations, and are identified by their id within it. A recording that is not at a described place still carries its own country, locality and coordinates.",
    //Places as RDF (see core/rdf.php), identified by https://api.audioblast.org/location/{source}/{id}
    "rdf" => array(
      "links" => TRUE,
      "path" => "location",
      "node" => "locations_rdf_node"
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
        "desc" => "ID of the place within its source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "multiple" => TRUE
      ),
      "name" => array(
        "desc" => "What its source calls the place, e.g. Chapman's Pool, Dorset",
        "type" => "string",
        "default" => "",
        "column" => "name",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "continent" => array(
        "desc" => "Continent or ocean the place is in (dwc:continent), as its source names it, e.g. Australasia",
        "type" => "string",
        "default" => "",
        "column" => "continent",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "countryCode" => array(
        "desc" => "ISO 3166-1 alpha-2 code of the country the place is in (dwc:countryCode), e.g. GB",
        "type" => "string",
        "default" => "",
        "column" => "countryCode",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "stateProvince" => array(
        "desc" => "State, province or region the place is in (dwc:stateProvince)",
        "type" => "string",
        "default" => "",
        "column" => "stateProvince",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "county" => array(
        "desc" => "County or district the place is in (dwc:county)",
        "type" => "string",
        "default" => "",
        "column" => "county",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "island" => array(
        "desc" => "Island the place is on (dwc:island)",
        "type" => "string",
        "default" => "",
        "column" => "island",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "islandGroup" => array(
        "desc" => "Island group the place is in (dwc:islandGroup)",
        "type" => "string",
        "default" => "",
        "column" => "islandGroup",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "locality" => array(
        "desc" => "How the place is described within the units above it (dwc:locality)",
        "type" => "string",
        "default" => "",
        "column" => "locality",
        "op" => "contains"
      ),
      "decimalLatitude" => array(
        "desc" => "Latitude of the place (dwc:decimalLatitude)",
        "type" => "range",
        "default" => "",
        "column" => "decimalLatitude",
        "op" => "range"
      ),
      "decimalLongitude" => array(
        "desc" => "Longitude of the place (dwc:decimalLongitude)",
        "type" => "range",
        "default" => "",
        "column" => "decimalLongitude",
        "op" => "range"
      ),
      "coordinateUncertaintyInMeters" => array(
        "desc" => "How far from the coordinates the place may be (dwc:coordinateUncertaintyInMeters)",
        "type" => "range",
        "default" => "",
        "column" => "coordinateUncertaintyInMeters",
        "op" => "range"
      ),
      "geodeticDatum" => array(
        "desc" => "Datum the coordinates are given against (dwc:geodeticDatum), e.g. WGS84",
        "type" => "string",
        "default" => "",
        "column" => "geodeticDatum",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "georeferenceRemarks" => array(
        "desc" => "Remarks on how the place was georeferenced (dwc:georeferenceRemarks)",
        "type" => "string",
        "default" => "",
        "column" => "georeferenceRemarks",
        "op" => "contains"
      ),
      "minimumElevationInMeters" => array(
        "desc" => "Lowest elevation of the place in metres (dwc:minimumElevationInMeters), below 0 below sea level",
        "type" => "range",
        "default" => "",
        "column" => "minimumElevationInMeters",
        "op" => "range"
      ),
      "maximumElevationInMeters" => array(
        "desc" => "Highest elevation of the place in metres (dwc:maximumElevationInMeters)",
        "type" => "range",
        "default" => "",
        "column" => "maximumElevationInMeters",
        "op" => "range"
      ),
      "info_url" => array(
        "desc" => "URL of the place's page at its source",
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

//A place as an RDF node (see core/rdf.php) at its URI, as a Darwin Core
//location. Its URI is its locationID, as that is the identifier audioBLAST!
//can be asked for it by; its page at its source is where more is about it.
function locations_rdf_node($place, $uri) {
  $node = array("@id" => $uri,
    "@type" => "http://rs.tdwg.org/dwc/terms/Location",
    "dwc:locationID" => $uri);
  //What a source calls a continent or an island is its own: the names are not
  //mapped to a gazetteer here
  foreach (array(
    "name" => "rdfs:label", "continent" => "dwc:continent",
    "countryCode" => "dwc:countryCode", "stateProvince" => "dwc:stateProvince",
    "county" => "dwc:county", "island" => "dwc:island",
    "islandGroup" => "dwc:islandGroup", "locality" => "dwc:locality",
    "geodeticDatum" => "dwc:geodeticDatum", "georeferenceRemarks" => "dwc:georeferenceRemarks"
  ) as $field => $property) {
    rdfAdd($node, $property, $place[$field] ?? NULL);
  }
  foreach (array("decimalLatitude", "decimalLongitude", "coordinateUncertaintyInMeters",
                 "minimumElevationInMeters", "maximumElevationInMeters") as $field) {
    rdfAdd($node, "dwc:".$field, rdfDecimal($place[$field] ?? NULL));
  }
  //Decimal latitude and longitude mean WGS84 unless a source says otherwise
  if (($place["decimalLatitude"] ?? "") !== "" && ($place["decimalLongitude"] ?? "") !== "" &&
      ($place["geodeticDatum"] ?? "") === "") {
    rdfAdd($node, "dwc:geodeticDatum", "EPSG:4326");
  }
  $node["rdfs:seeAlso"] = array();
  $url = rdfURL($place["info_url"] ?? NULL);
  if ($url !== NULL) {$node["rdfs:seeAlso"][] = $url;}
  //Discover the recordings and specimens that are of the place
  foreach (array("subject", "object") as $side) {
    $node["rdfs:seeAlso"][] = rdfIRI("https://api.audioblast.org/data/links/?".http_build_query(array(
      $side."_type" => "locations", $side."_source" => $place["source"],
      $side."_id" => $place["id"])));
  }
  return($node);
}
