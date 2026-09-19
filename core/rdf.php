<?php

/*
RDF output (output=JSON-LD or output=Turtle, or asked for by media type, see
rdfNegotiate()) for modules that describe their records in RDF. Such a module
has an "rdf" entry in its info giving "path", the start of its records' URIs
(see rdfRecordURI()), and "node", the function that turns a record and its URI
into a node. An optional "related" callback returns additional nodes describing
contributors, containers or assertions. Records are identified by their source
and id parameters. JSON-LD and Turtle are written from the same nodes, so both formats always say the same
thing.

A node is an array of properties (prefixed names, see rdfContext()) and their
values, with "@id" and "@type" (full IRIs) as in JSON-LD. A value is a string,
an IRI (rdfIRI()) or a typed literal (rdfTyped()).
*/

//The outputs that give records as RDF
function rdfOutputs() {
  return(array("JSON-LD", "Turtle"));
}

//The RDF output a client asks for in its Accept header, or NULL where it
//prefers JSON (the API's default) or doesn't say. The media range with the
//highest quality wins, and of those with the same quality the most specific.
function rdfNegotiate($accept) {
  $outputs = array(
    "text/turtle" => "Turtle",
    "application/ld+json" => "JSON-LD",
    "application/json" => NULL,
    "application/*" => NULL,
    "*/*" => NULL
  );
  $best = NULL;
  $bestQuality = 0;
  $bestSpecific = FALSE;
  foreach (explode(",", $accept) as $range) {
    $parts = array_map("trim", explode(";", $range));
    $type = strtolower($parts[0]);
    if (!array_key_exists($type, $outputs)) {continue;}
    $quality = 1;
    foreach (array_slice($parts, 1) as $parameter) {
      if (preg_match('/^q=([0-9.]+)$/i', $parameter, $matches)) {$quality = (float)$matches[1];}
    }
    //A quality of 0 says the type is not acceptable
    if ($quality <= 0) {continue;}
    $specific = (strpos($type, "*") === FALSE);
    if ($quality > $bestQuality || ($quality == $bestQuality && $specific && !$bestSpecific)) {
      $best = $outputs[$type];
      $bestQuality = $quality;
      $bestSpecific = $specific;
    }
  }
  return($best);
}

//The URI of a module's record, e.g. https://api.audioblast.org/recording/bio.acousti.ca/12883
//for a recording. Source names never have a /, so all of the rest is the id,
//and a / in an id is kept as one.
function rdfRecordURI($module, $source, $id) {
  return("https://api.audioblast.org/".$module["rdf"]["path"]."/".rawurlencode($source)."/"
    .implode("/", array_map("rawurlencode", explode("/", $id))));
}

//The prefixes of the vocabularies records are described with. abv is the
//prefix the vocabulary at vocab.audioblast.org gives itself.
function rdfContext() {
  return(array(
    "abv" => "https://vocab.audioblast.org/",
    "bibo" => "http://purl.org/ontology/bibo/",
    "foaf" => "http://xmlns.com/foaf/0.1/",
    "rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
    "ac" => "http://rs.tdwg.org/ac/terms/",
    "dc" => "http://purl.org/dc/elements/1.1/",
    "dcterms" => "http://purl.org/dc/terms/",
    "dwc" => "http://rs.tdwg.org/dwc/terms/",
    "dwciri" => "http://rs.tdwg.org/dwc/iri/",
    "xmp" => "http://ns.adobe.com/xap/1.0/",
    "xsd" => "http://www.w3.org/2001/XMLSchema#"
  ));
}

function rdfIRI($iri) {
  return(array("@id" => $iri));
}

//A literal of a datatype, such as xsd:decimal
function rdfTyped($value, $datatype) {
  return(array("@value" => (string)$value, "@type" => $datatype));
}

//Adds a value to a node, unless it is missing or empty
function rdfAdd(&$node, $property, $value) {
  if ($value === NULL || $value === "") {return;}
  $node[$property] = $value;
}

//An http(s) URL as an IRI, or NULL if the value isn't one
function rdfURL($value) {
  return(preg_match('#^https?://[^\s/?\#]+\S*$#', (string)$value) ? rdfIRI($value) : NULL);
}

//A decimal number as an xsd:decimal, or NULL if the value isn't one
function rdfDecimal($value) {
  return(preg_match('/^-?[0-9]+(\.[0-9]+)?$/', (string)$value) ? rdfTyped($value, "xsd:decimal") : NULL);
}

//An ISO 8601 date (YYYY-MM-DD, or YYYY-MM or YYYY when only the month or year
//is known) as a typed literal, or NULL if the value isn't one. With a time of
//day (HH:MM or HH:MM:SS) a whole date becomes a date and time. Where the time
//was is not known, so it has no time zone.
function rdfDate($date, $time = NULL) {
  $date = (string)$date;
  $time = (string)$time;
  if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date)) {
    if (preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time)) {
      return(rdfTyped($date."T".$time.((strlen($time) == 5) ? ":00" : ""), "xsd:dateTime"));
    }
    return(rdfTyped($date, "xsd:date"));
  }
  if (preg_match('/^[0-9]{4}-[0-9]{2}$/', $date)) {
    return(rdfTyped($date, "xsd:gYearMonth"));
  }
  if (preg_match('/^[0-9]{4}$/', $date)) {
    return(rdfTyped($date, "xsd:gYear"));
  }
  return(NULL);
}

//The nodes of records
function rdfNodes($module, $records) {
  $nodes = array();
  foreach ($records as $record) {
    $uri = rdfRecordURI($module, $record["source"], $record[$module["rdf"]["id"] ?? "id"]);
    $nodes[] = call_user_func($module["rdf"]["node"], $record, $uri);
    if (isset($module["rdf"]["related"])) {
      foreach (call_user_func($module["rdf"]["related"], $record, $uri) as $related) {
        $nodes[] = $related;
      }
    }
  }
  return($nodes);
}

function rdfJSONLD($nodes) {
  return(json_encode(array("@context" => rdfContext(), "@graph" => $nodes), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function rdfTurtle($nodes) {
  $out = "";
  foreach (rdfContext() as $prefix => $namespace) {
    $out .= "@prefix ".$prefix.": ".turtleIRI($namespace)." .\n";
  }
  foreach ($nodes as $node) {
    $statements = array();
    foreach ($node as $property => $values) {
      if ($property == "@id") {continue;}
      if ($property == "@reverse") {
        foreach ($values as $predicate => $subjects) {
          $predicate = preg_match("#^https?://#", $predicate) ? turtleIRI($predicate) : $predicate;
          foreach ($subjects as $subject) {
            $out .= "\n".turtleIRI($subject["@id"])." ".$predicate." ".turtleIRI($node["@id"])." .\n";
          }
        }
        continue;
      }
      //A property has a single value or a list of them
      if (!is_array($values) || !array_key_exists(0, $values)) {
        $values = array($values);
      }
      if ($property == "@type") {
        $statements[] = "a ".implode(", ", array_map("turtleIRI", $values));
      } else {
        $predicate = preg_match("#^https?://#", $property) ? turtleIRI($property) : $property;
        $statements[] = $predicate." ".implode(", ", array_map("turtleValue", $values));
      }
    }
    if ($statements) {$out .= "\n".turtleIRI($node["@id"])." ".implode(" ;\n    ", $statements)." .\n";}
  }
  return($out);
}

//A value in Turtle: an IRI, a typed literal or a string
function turtleValue($value) {
  if (is_array($value) && isset($value["@id"])) {
    return(turtleIRI($value["@id"]));
  }
  if (is_array($value)) {
    return(turtleString($value["@value"])."^^".$value["@type"]);
  }
  return(turtleString($value));
}

//An IRI in Turtle, with the characters that can't be in one escaped
function turtleIRI($iri) {
  return("<".preg_replace_callback('/[\x00-\x20<>"{}|^`\\\\]/', function($matches) {
    return(sprintf("\\u%04X", ord($matches[0])));
  }, $iri).">");
}

function turtleString($text) {
  return("\"".strtr($text, array("\\" => "\\\\", "\"" => "\\\"", "\n" => "\\n", "\r" => "\\r"))."\"");
}

//Prints nodes in an RDF output, with a link to the next page of them if there
//is one (RFC 8288), so that they can be harvested page by page
function printRDF($nodes, $output, $nextPage = NULL) {
  if ($nextPage !== NULL) {
    header("Link: <".pageURI($_SERVER["REQUEST_URI"], $nextPage).">; rel=\"next\"");
  }
  if ($output == "Turtle") {
    header("Content-Type: text/turtle; charset=utf-8");
    print(rdfTurtle($nodes));
  } else {
    header("Content-Type: application/ld+json");
    print(rdfJSONLD($nodes));
  }
}

//The address of a page of a request's results: the request with its page
//parameter set to the page
function pageURI($requestURI, $page) {
  $uri = parse_url($requestURI);
  parse_str($uri["query"] ?? "", $query);
  $query["page"] = $page;
  return($uri["path"]."?".http_build_query($query));
}

// Add one-hop incoming and outgoing assertions for modules that opt in.
// Query a page in batches, rather than querying once per returned record.
// FALSE means a failed lookup, not a record with no relationships.
function rdfResponseNodes($db, $module, $records) {
  $nodes = rdfNodes($module, $records);
  if (empty($module["rdf"]["links"]) || !$records) {return($nodes);}
  $links = loadModule("links");
  $seen = array();
  foreach (array_chunk($records, 100) as $batch) {
    foreach (array("subject", "object") as $side) {
      $values = array($module["mname"]);
      $pairs = array();
      foreach ($batch as $record) {
        $pairs[] = "(?, ?)";
        $values[] = $record["source"];
        $values[] = $record[$module["rdf"]["id"] ?? "id"];
      }
      $sql = SELECTclause($links, NULL, "table", "internal");
      $sql .= " WHERE `".$side."_type` = ? AND (`".$side."_source`, `".$side."_id`) IN (".implode(", ", $pairs).");";
      $stmt = $db->prepare($sql);
      if (!$stmt) {return(FALSE);}
      if (!$stmt->bind_param(str_repeat("s", count($values)), ...$values) || !$stmt->execute()) {
        $stmt->close();
        return(FALSE);
      }
      $result = $stmt->get_result();
      if (!$result) {$stmt->close(); return(FALSE);}
      while ($link = $result->fetch_assoc()) {
        $key = json_encode(array($link["source"], $link["id"]));
        if (isset($seen[$key])) {continue;}
        $seen[$key] = TRUE;
        foreach (rdfNodes($links, array($link)) as $node) {$nodes[] = $node;}
      }
      $result->close();
      $stmt->close();
    }
  }
  $focus = array();
  foreach ($records as $record) {
    $focus[] = rdfRecordURI($module, $record["source"], $record[$module["rdf"]["id"] ?? "id"]);
  }
  return(rdfFrameIncoming(rdfMergeNodes($nodes), $focus));
}

// Combine descriptions of the same subject, retaining all distinct values.
// This puts outgoing relationships on the record's own JSON-LD node too.
function rdfMergeNodes($nodes) {
  $merged = array();
  foreach ($nodes as $node) {
    $id = $node["@id"];
    if (!isset($merged[$id])) {$merged[$id] = $node; continue;}
    foreach ($node as $property => $value) {
      if ($property === "@id") {continue;}
      if (!array_key_exists($property, $merged[$id])) {
        $merged[$id][$property] = $value;
        continue;
      }
      $old = $merged[$id][$property];
      $values = is_array($old) && array_key_exists(0, $old) ? $old : array($old);
      $additions = is_array($value) && array_key_exists(0, $value) ? $value : array($value);
      foreach ($additions as $addition) {
        if (!in_array($addition, $values, TRUE)) {$values[] = $addition;}
      }
      $merged[$id][$property] = count($values) === 1 ? $values[0] : $values;
    }
  }
  return(array_values($merged));
}

function printRecordRDF($db, $module, $records, $output, $nextPage = NULL) {
  $nodes = rdfResponseNodes($db, $module, $records);
  if ($nodes === FALSE) {
    http_response_code(500);
    printRDF(array(), $output);
    return;
  }
  printRDF($nodes, $output, $nextPage);
}

// Compact only safe local names so the same predicate also works in Turtle.
function rdfCompactIRI($iri) {
  foreach (rdfContext() as $prefix => $namespace) {
    if (strpos($iri, $namespace) !== 0) {continue;}
    $local = substr($iri, strlen($namespace));
    if (preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $local)) {return($prefix.":".$local);}
  }
  return($iri);
}

// Present incoming assertions on requested records without inventing inverse terms.
// Other endpoints keep only their URI, not a recursively expanded description.
function rdfFrameIncoming($nodes, $focus) {
  $byID = array_column($nodes, NULL, "@id");
  $requested = array_fill_keys($focus, TRUE);
  foreach ($nodes as $node) {
    if (!isset($node["rdf:subject"]["@id"], $node["rdf:predicate"]["@id"], $node["rdf:object"]["@id"])) {continue;}
    $subject = $node["rdf:subject"]["@id"];
    $object = $node["rdf:object"]["@id"];
    $predicate = rdfCompactIRI($node["rdf:predicate"]["@id"]);
    if (!isset($requested[$object], $byID[$object])) {continue;}
    $incoming = rdfIRI($subject);
    if (!isset($byID[$object]["@reverse"][$predicate])) {$byID[$object]["@reverse"][$predicate] = array();}
    if (!in_array($incoming, $byID[$object]["@reverse"][$predicate], TRUE)) {
      $byID[$object]["@reverse"][$predicate][] = $incoming;
    }
    // If both endpoints are requested, keep the forward property on its record too.
    if (isset($requested[$subject]) || !isset($byID[$subject][$predicate])) {continue;}
    $old = $byID[$subject][$predicate];
    $values = is_array($old) && array_key_exists(0, $old) ? $old : array($old);
    $values = array_values(array_filter($values, function($value) use ($object) {
      return($value !== rdfIRI($object));
    }));
    if (!$values) {unset($byID[$subject][$predicate]);}
    else {$byID[$subject][$predicate] = count($values) === 1 ? $values[0] : $values;}
    if (count($byID[$subject]) === 1) {unset($byID[$subject]);}
  }
  return(array_values($byID));
}
