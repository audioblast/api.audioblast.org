<?php

function annomate_info() {
  $info = array(
    "mname" => "annomate",
    "version" => 1.0,
    "category" => "data",
    "table" => "annomate",
    "hname" => "ann-o-mate",
    "desc" => "Query annotations, including JSON-LD and Turtle descriptions as Audiovisual Core regions of interest. Individual annotations are available at /annotation/{source}/{annotation_id}.",
    "rdf" => array("path" => "annotation", "id" => "annotation_id",
      "links" => TRUE, "node" => "annomate_rdf_node", "related" => "annomate_rdf_related"),
    "params" => array(
      "source" => array(
        "desc" => "Recording source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "source_id" => array(
        "desc" => "Recording ID",
        "type" => "string",
        "default" => "",
        "column" => "source_id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "annotator" => array(
        "desc" => "Annotator",
        "type" => "string",
        "default" => "",
        "column" => "annotator",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "annotation_id" => array(
        "desc" => "Annotation ID",
        "type" => "string",
        "default" => "",
        "column" => "annotation_id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "annotation_date" => array(
        "desc" => "Annotation date",
        "type" => "string",
        "default" => "",
        "column" => "annotation_date",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "annotation_info_url" => array(
        "desc" => "Annotation information link",
        "type" => "string",
        "default" => "",
        "column" => "annotation_info_url",
        "op" => "contains",
        "autocomplete" => FALSE
      ),
      "recording_url" => array(
        "desc" => "Recording URL",
        "type" => "string",
        "default" => "",
        "column" => "recording_url",
        "op" => "contains",
        "autocomplete" => FALSE,
        "ac" => "ac:accessURI"
      ),
      "time_start" => array(
        "desc" => "Start time",
        "type" => "string",
        "default" => "",
        "column" => "time_start",
        "op" => "=",
        "autocomplete" => FALSE,
        "ac" => "ac:startTime"
      ),
      "time_end" => array(
        "desc" => "End time",
        "type" => "string",
        "default" => "",
        "column" => "time_end",
        "op" => "=",
        "autocomplete" => FALSE,
        "ac" => "ac:endTime"
      ),
      "taxon" => array(
        "desc" => "Taxon",
        "type" => "string",
        "default" => "",
        "column" => "taxon",
        "op" => "contains",
        "autocomplete" => TRUE,
        "ac" => "dwc:scientificName"
      ),
      "type" => array(
        "desc" => "Annotation type",
        "type" => "string",
        "default" => "",
        "column" => "type",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "lat" => array(
        "desc" => "Latitude",
        "type" => "string",
        "default" => "",
        "column" => "lat",
        "op" => "contains",
        "autocomplete" => FALSE,
        "ac" => "dwc:decimalLatitude"
      ),
      "lon" => array(
        "desc" => "Longitude",
        "type" => "string",
        "default" => "",
        "column" => "lon",
        "op" => "contains",
        "autocomplete" => FALSE,
        "ac" => "dwc:decimalLongitude"
      ),
      "contact" => array(
        "desc" => "Contact",
        "type" => "string",
        "default" => "",
        "column" => "contact",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "format" => array(
        "desc" => "Data representation to return.",
        "type" => "string",
        "allowed" => array(
          "internal",
          "ac"
        ),
       "default" => "internal"
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
    ),
  );
  return($info);
}

// Annotation identity differs from the recording ID; preserve existing JSON keys.
function annomate_rdf_recording($annotation) {
  if (($annotation["source"] ?? "") === "" || ($annotation["source_id"] ?? "") === "") {return(NULL);}
  return(rdfRecordURI(loadModule("recordings"), $annotation["source"], $annotation["source_id"]));
}

function annomate_rdf_node($annotation, $uri) {
  $node = array("@id" => $uri, "@type" => "http://rs.tdwg.org/ac/terms/RegionOfInterest");
  $recording = annomate_rdf_recording($annotation);
  if ($recording !== NULL) {$node["ac:isROIOf"] = rdfIRI($recording);}
  foreach (array("time_start" => "ac:startTime", "time_end" => "ac:endTime",
    "lat" => "dwc:decimalLatitude", "lon" => "dwc:decimalLongitude") as $field => $property) {
    $value = $annotation[$field] ?? NULL;
    rdfAdd($node, $property, rdfDecimal($value) ?? $value);
  }
  foreach (array("annotator" => "dcterms:creator", "taxon" => "dwc:scientificName",
    "type" => "dc:type") as $field => $property) {
    rdfAdd($node, $property, $annotation[$field] ?? NULL);
  }
  $date = $annotation["annotation_date"] ?? NULL;
  rdfAdd($node, "dcterms:created", rdfDate($date) ?? $date);
  rdfAdd($node, "rdfs:seeAlso", rdfURL($annotation["annotation_info_url"] ?? NULL));
  return($node);
}

function annomate_rdf_related($annotation, $uri) {
  $recording = annomate_rdf_recording($annotation);
  if ($recording === NULL) {return(array());}
  $node = array("@id" => $recording, "ac:hasROI" => rdfIRI($uri));
  // Match the recording module's access metadata; the URL is not the ROI itself.
  rdfAdd($node, "ac:accessURI", rdfURL($annotation["recording_url"] ?? NULL));
  return(array($node));
}
