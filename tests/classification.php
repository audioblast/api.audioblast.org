<?php
// No settings or real database are loaded. Run from the repository root.
require 'core/modules.php';
require 'core/input.php';
require 'core/rdf.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}

$taxa = loadModule("taxa");

//A taxa row as the module serves it, with only the columns the walk reads
function taxon($id, $parent, $name, $rank) {
  return(array("source" => "fixture", "id" => $id, "parent_id" => $parent,
    "taxon" => $name, "rank" => $rank));
}

/*
A source's tree, looked up as the database looks it up: by the source holding a
taxon and its id there, regardless of case.
*/
function tree($rows) {
  return(function($source, $id) use ($rows) {
    foreach ($rows as $row) {
      if (strtolower($row["source"]) !== strtolower($source)) {continue;}
      if (strtolower($row["id"]) !== strtolower($id)) {continue;}
      return($row);
    }
    return(NULL);
  });
}

//The tree above Gryllotalpa vineae, which is fourteen requests to walk a taxon
//at a time. Gryllidea and Gryllotalpoidea are the reason the rank columns are
//not an answer: neither an infraorder nor a superfamily has a column to be in.
$names = array(
  array("Eukaryota", "domain"), array("Animalia", "kingdom"),
  array("Arthropoda", "phylum"), array("Hexapoda", "subphylum"),
  array("Insecta", "class"), array("Orthoptera", "order"),
  array("Ensifera", "suborder"), array("Gryllidea", "infraorder"),
  array("Gryllotalpoidea", "superfamily"), array("Gryllotalpidae", "family"),
  array("Gryllotalpinae", "subfamily"), array("Gryllotalpini", "tribe"),
  array("Gryllotalpa", "genus"), array("Gryllotalpa vineae", "species")
);
$rows = array();
foreach ($names as $i => $name) {
  $rows[] = taxon((string)($i + 1), ($i === 0) ? "" : (string)$i, $name[0], $name[1]);
}
$vineae = $rows[13];

// The whole chain, in one request
$chain = taxa_classification_chain($vineae, tree($rows));
check($chain["problem"] === NULL, "A chain that reaches the root has nothing to report");
check(count($chain["taxa"]) === 14, "Every taxon between the root and the taxon is given");
check(array_column($chain["taxa"], "taxon") === array_column($names, 0),
  "The root comes first and the taxon asked for last, in the order the source holds them");
check(in_array("Gryllidea", array_column($chain["taxa"], "taxon")),
  "A rank with no column of its own is in the chain");
check($chain["taxa"][13]["rank"] === "species", "The taxa are rows as the module serves them");

// A taxon at the root of its source's tree
$chain = taxa_classification_chain($rows[0], tree($rows));
check(count($chain["taxa"]) === 1 && $chain["problem"] === NULL,
  "The classification of a root is the root");

// Nothing to walk from
$chain = taxa_classification_chain(NULL, tree($rows));
check($chain["taxa"] === array(), "A taxon its source does not hold has no classification");
check(strpos($chain["problem"], "No taxon of that source has that id") === 0, "and is told so");

// A lookup that failed is not a classification that is empty or short
check(taxa_classification_chain(FALSE, tree($rows)) === FALSE, "A first lookup that failed");
check(taxa_classification_chain($vineae, function($source, $id) {return(FALSE);}) === FALSE,
  "A lookup part way up that failed");

/*
A tree that does not reach the root is still served, with what it does reach
and a note saying where it stops: what a source holds is what audioBLAST!
serves. It is not served as though it were whole.
*/
$broken = $rows;
unset($broken[5]);  //Orthoptera, which Ensifera is inside
$chain = taxa_classification_chain($vineae, tree($broken));
check(count($chain["taxa"]) === 8, "A chain stops where its source stops holding it");
check($chain["taxa"][0]["taxon"] === "Ensifera", "with what it did reach, the highest first");
check($chain["problem"] !== NULL, "and says that it is not the whole classification");
check(strpos($chain["problem"], "does not hold") !== FALSE, "naming what is missing");
check(strpos($chain["problem"], "not the whole classification") !== FALSE, "and what that means");

// A taxon inside itself would be walked for ever
$loop = $rows;
$loop[7]["parent_id"] = "14";  //Gryllidea, inside the species that is inside it
$chain = taxa_classification_chain($loop[13], tree($loop));
check(count($chain["taxa"]) === 7, "A taxon already in the chain ends it");
check(strpos($chain["problem"], "inside itself") !== FALSE, "saying what the source says");

// And so would a chain longer than any taxonomy is
$long = array();
for ($i = 1; $i <= TAXA_CLASSIFICATION_MAX + 50; $i++) {
  $long[] = taxon((string)$i, ($i === 1) ? "" : (string)($i - 1), "Taxon ".$i, "clade");
}
$chain = taxa_classification_chain($long[count($long) - 1], tree($long));
check(count($chain["taxa"]) === TAXA_CLASSIFICATION_MAX, "A chain is walked only so far");
check(strpos($chain["problem"], "longer than ".TAXA_CLASSIFICATION_MAX) !== FALSE, "and says so");

// What the endpoint takes
check(isset($taxa["endpoints"]["classification"]), "The taxa module has the endpoint");
$classification = $taxa["endpoints"]["classification"];
$asked = array_merge(array("path" => "data/taxa/classification/"),
  array("source" => "bio.acousti.ca", "id" => "1", "output" => "nakedJSON"));
check(checkParams($classification, $asked, "classification") === NULL,
  "which takes the taxon to walk from, and how to give the taxa back");
check(checkParams($classification, array("taxon" => "Gryllotalpa vineae"), "classification") !== NULL,
  "and refuses what it does not take");

/*
The same chain on the taxon's own page, where a breadcrumb reading RDF finds it
without a request for each step up.
*/
$uri = function($id) use ($taxa) {return(rdfRecordURI($taxa, "fixture", $id));};
$whole = taxa_classification_chain($vineae, tree($rows));
$nodes = array_column(taxa_rdf_ancestors($taxa, $whole), NULL, "@id");
check(count($nodes) === 14, "Every taxon of the chain is on the page");
check($nodes[$uri("6")]["dwc:scientificName"] === "Orthoptera",
  "The taxa above are described as they are on their own pages");
check($nodes[$uri("6")]["dwc:taxonRank"] === "order", "with the rank their source gives them");
check($nodes[$uri("6")]["@type"] === "http://rs.tdwg.org/dwc/terms/Taxon", "as taxa");

// A row is a source's own taxon concept, so the taxon it is directly inside is
// broader in that source's tree. Nothing is said to be transitive: a source may
// put whatever it likes between two ranks.
check($nodes[$uri("14")]["skos:broader"] === rdfIRI($uri("13")),
  "A taxon is inside the taxon above it");
check($nodes[$uri("7")]["skos:broader"] === rdfIRI($uri("6")),
  "and so is each taxon of the chain");
check(!isset($nodes[$uri("1")]["skos:broader"]), "The root is inside nothing");
check(count($nodes[$uri("14")]) === 3,
  "The taxon asked for is described by its own page, not again here");

// Darwin Core's own summary of the same walk
check($nodes[$uri("14")]["dwc:higherClassification"] ===
  "Eukaryota|Animalia|Arthropoda|Hexapoda|Insecta|Orthoptera|Ensifera|Gryllidea|"
  ."Gryllotalpoidea|Gryllotalpidae|Gryllotalpinae|Gryllotalpini|Gryllotalpa",
  "The names above the taxon, highest first, ending at the one it is inside");

// A walk that stopped short says what it reached and no more: there is no note
// in RDF to say that a classification is not the whole one.
$short = taxa_classification_chain($vineae, tree($broken));
$stopped = array_column(taxa_rdf_ancestors($taxa, $short), NULL, "@id");
check($stopped[$uri("8")]["skos:broader"] === rdfIRI($uri("7")),
  "What the walk did reach is still said");
check(!isset($stopped[$uri("14")]["dwc:higherClassification"]),
  "but a chain that stopped short is not given as the whole classification");

// A taxon at the root of its tree has nothing to add
check(taxa_rdf_ancestors($taxa, taxa_classification_chain($rows[0], tree($rows))) === array(),
  "A root carries no ancestors");
check(taxa_rdf_ancestors($taxa, taxa_classification_chain(NULL, tree($rows))) === array(),
  "Nor does a taxon that is not there");

// Both serialisations say it, as they say everything else
$merged = rdfMergeNodes(array_merge(rdfNodes($taxa, array($vineae)),
  taxa_rdf_ancestors($taxa, $whole)));
$turtle = rdfTurtle($merged);
check(strpos($turtle, "skos:broader <".$uri("13").">") !== FALSE, "Turtle gives the chain");
check(strpos($turtle, "dwc:higherClassification") !== FALSE, "and Darwin Core's summary of it");
$graph = json_decode(rdfJSONLD($merged), TRUE)["@graph"];
check(count($graph) === 14, "JSON-LD describes each taxon once");
$asked = array_column($graph, NULL, "@id")[$uri("14")];
check($asked["dwc:scientificName"] === "Gryllotalpa vineae" && isset($asked["skos:broader"]),
  "with what the page says of the taxon and what the walk adds on one node");

print("classification: all checks passed\n");
