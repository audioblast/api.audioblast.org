<?php

function ecoint_info() {
  $info = array(
    "mname" => "ecoint",
    "version" => 1.0,
    "category" => "data",
    "table" => "ecoint",
    "hname" => "Ecological Interactions",
    "desc" => "<p>How one taxon interacts with another: the acoustically orientating predators and parasites that find their prey and hosts by listening for them, and the taxa that answer another's alarm call.</p><p>An interaction is a relationship, so it is held in the links table and this endpoint is a view of it: the interaction is the link's predicate, the taxa its subject and object, and the reference that established it is a link of its own from the interaction to that reference. Querying <a href='#links'>Links</a> by an interaction predicate gives the same relationships, with their assertions, and serves them as RDF.</p>",
    "source_notes" => "Interactions are ingested as links, from each source's links.",
    "params" => array(
      "source" => array(
        "desc" => "Source that gives the interaction",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "=",
        "multiple" => TRUE,
        "autocomplete" => TRUE
      ),
      "id" => array(
        "desc" => "ID of the link the interaction is, within its source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "multiple" => TRUE
      ),
      "taxon" => array(
        "desc" => "ID of the taxon that acts: the predator, the parasite, or the one that answers the call",
        "type" => "string",
        "default" => "",
        "column" => "taxon",
        "op" => "=",
        "multiple" => TRUE
      ),
      "interaction" => array(
        "desc" => "IRI of how it interacts, e.g. https://vocab.audioblast.org/cv/interaction#AcousticallyOrientatingPredatorOf",
        "type" => "string",
        "default" => "",
        "column" => "interaction",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "interacts_with" => array(
        "desc" => "ID of the taxon acted upon: the prey, the host, or the one whose call is answered",
        "type" => "string",
        "default" => "",
        "column" => "interacts_with",
        "op" => "=",
        "multiple" => TRUE
      ),
      "reference" => array(
        "desc" => "ID of the reference that established the interaction, within the same source",
        "type" => "string",
        "default" => "",
        "column" => "reference",
        "op" => "=",
        "multiple" => TRUE
      ),
      "remarks" => array(
        "desc" => "Remarks on the interaction",
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
          "tabulator"
        ),
        "default" => "JSON"
      )
    )
  );
  return($info);
}
