<?php
// No settings or real database are loaded. Run from the repository root.
require 'core/modules.php';
require 'core/input.php';
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

print("classification: all checks passed\n");
