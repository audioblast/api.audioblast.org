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

/*
A request as moduleAPI() meets it. The rewrite that routes everything to
index.php adds a `path` parameter of its own, and the endpoint, which tells the
autocomplete query apart from a module's own parameters, is the fourth part of
the URI. $module is what moduleAPI() has resolved by the time it checks: a
module, or one of its endpoints or embeds.
*/
function request($module, $uri, $inputs=array()) {
  $parts = explode("/", $uri);
  return(checkParams($module, array_merge(array("path" => substr($uri, 1)), $inputs), $parts[3] ?? NULL));
}

$recordings = loadModule("recordings");
$recordingstaxa = loadModule("recordingstaxa");

// The faults this guards against. Neither `family` on recordings nor `complex`
// on recordingstaxa is a filter, and both used to return the whole table.
$problem = request($recordings, "/data/recordings/", array("family" => "Gryllotalpidae", "page_size" => "1"));
check($problem !== NULL, "An undeclared filter is refused");
check(strpos($problem, "Parameter `family` is not recognised.") === 0, "The offending parameter is named first");
check(strpos($problem, "taxon") !== FALSE, "The filters the module does take are listed");
check(strpos($problem, "page_size") !== FALSE, "The control parameters are listed");
check(strpos($problem, "path") === FALSE, "The rewrite's own parameter is not advertised");

$problem = request($recordingstaxa, "/data/recordingstaxa/", array("complex" => "Gryllotalpa gryllotalpa"));
check($problem !== NULL, "recordingstaxa refuses `complex`");
check(strpos($problem, "species") !== FALSE, "The rank that was meant is among the filters listed");

// What the modules do take
check(request($recordingstaxa, "/data/recordingstaxa/",
  array("species" => "Gryllotalpa gryllotalpa", "page_size" => "1", "output" => "nakedJSON")) === NULL,
  "A declared filter is taken");
check(request($recordings, "/data/recordings/",
  array("taxon" => "Gryllotalpa vineae", "page" => "2", "format" => "ac")) === NULL,
  "Pagination and the representation selectors are taken");
check(request($recordings, "/data/recordings/") === NULL, "A request with no filters is taken");

// `s` and `c` carry the autocomplete query, and belong to that endpoint alone
check(request($recordings, "/data/recordings/autocomplete/taxon/",
  array("c" => "Gryllo", "output" => "nakedJSON")) === NULL, "Autocomplete takes its query");
check(request($recordings, "/data/recordings/", array("c" => "Gryllo")) !== NULL,
  "`c` is refused away from autocomplete");

// A column the module declares but has no operator for is no more use as a
// filter than one it has never heard of: both returned the unfiltered table
$problem = request($recordings, "/data/recordings/", array("post_date" => "2014-03-04"));
check($problem !== NULL, "A column with no operator is refused");
check(strpos($problem, "cannot be filtered on") !== FALSE, "It is refused for the right reason");

// Tabulator names the field each of its filters applies to. Those are written
// straight into the parameters, so they reach the query the same way.
check(request($recordingstaxa, "/data/recordingstaxa/", array("filter" => array(
  array("field" => "genus", "type" => "=", "value" => "Gryllotalpa")))) === NULL,
  "A Tabulator filter on a real field is taken");
$problem = request($recordingstaxa, "/data/recordingstaxa/", array("filter" => array(
  array("field" => "utm_source", "type" => "=", "value" => "twitter"))));
check($problem !== NULL, "A Tabulator filter on an unknown field is refused");
check(strpos($problem, "`utm_source`") !== FALSE, "The field is named");
check(request($recordings, "/data/recordings/", array("filter" => "genus")) !== NULL,
  "A filter that is not a list of filters is refused");
check(request($recordings, "/data/recordings/", array("filter" => array(array("value" => "x")))) !== NULL,
  "A filter naming no field is refused");

// An endpoint hands every parameter to its callback, so what they mean is the
// callback's business and not all of them are filters
$module_info = loadModule("modules")["endpoints"]["module_info"];
check(request($module_info, "/standalone/modules/module_info/", array("module" => "recordings")) === NULL,
  "An endpoint takes its own parameters");
$problem = request($module_info, "/standalone/modules/module_info/", array("modules" => "recordings"));
check($problem !== NULL, "An endpoint refuses a name it does not know");
check(strpos($problem, "This endpoint takes: module, output.") !== FALSE,
  "An endpoint lists its parameters, filters or not");

// The recordings embed reads `title` and `credit`, which filter nothing
loadModule("recordings");
$embed = call_user_func("recordings_embed_info")["recording"];
check(request($embed, "/embed/recordings/recording/", array("source" => "bio.acousti.ca",
  "id" => "10000", "title" => "false", "credit" => "true", "output" => "html5")) === NULL,
  "An embed takes the parameters its callback reads");
check(request($embed, "/embed/recordings/recording/", array("titel" => "false")) !== NULL,
  "An embed refuses a misspelt parameter");

// A module that declares no parameters has nothing to check a request against
check(request(loadModule("bioacoustica"), "/source/bioacoustica/", array("anything" => "1")) === NULL,
  "A module with no parameters is left alone");

// An analysis index takes the parameters analysis_index_module() gives it all
$aci = loadModule("aci");
check(request($aci, "/analysis/aci/", array("source" => "bio.acousti.ca", "id" => "10000",
  "startTime" => "0:60", "value" => ">1", "channel" => "1", "output" => "JSON")) === NULL,
  "An analysis index takes the parameters it shares with every other");
check(request($aci, "/analysis/aci/", array("stopTime" => "60")) !== NULL,
  "An analysis index refuses a parameter it does not have");

/*
audioblast.org checks a rank against its own list before filtering on it,
precisely because an unknown rank returned everything. That list is these nine,
and every module it filters by rank has to take all of them, or searches on the
site start being refused.
*/
$ranks = array("kingdom", "class", "order", "suborder", "family", "subfamily",
  "tribe", "genus", "species");
foreach (array("recordingstaxa", "traitstaxa", "taxa") as $name) {
  $filters = listFilterParams(loadModule($name));
  foreach ($ranks as $rank) {
    check(in_array($rank, $filters), $name." does not take the rank ".$rank);
  }
}

/*
The requests audioblast.org makes of the API, which is its largest caller: the
tables of ab-tabulator.js, the search plugins, and the homepage counts. None of
them may now be refused.
*/
$site = array(
  array($recordings, "/data/recordings/", array("page" => "1", "page_size" => "50")),
  array($recordings, "/data/recordings/columns/", array()),
  array($recordings, "/data/recordings/autocomplete/taxon/", array("c" => "Gryllo", "output" => "nakedJSON")),
  array($recordingstaxa, "/data/recordingstaxa/", array("page" => "1", "page_size" => "50",
    "filter" => array(array("field" => "genus", "type" => "=", "value" => "Gryllotalpa")))),
  array($recordingstaxa, "/data/recordingstaxa/", array("family" => "Gryllotalpidae",
    "page_size" => "1", "output" => "nakedJSON")),
  array(loadModule("annomate"), "/data/annomate/", array("taxon" => "Gryllotalpa vineae",
    "page_size" => "1", "output" => "nakedJSON")),
  array(loadModule("traits"), "/data/traits/", array("trait" => "calling song",
    "page_size" => "1", "output" => "nakedJSON")),
  array(loadModule("traits")["endpoints"]["list_text_values"], "/data/traits/list_text_values/", array()),
  array(loadModule("traitstaxa"), "/data/traitstaxa/", array("trait" => "calling song",
    "value" => "present", "genus" => "Gryllotalpa", "page_size" => "1", "output" => "nakedJSON")),
  array(loadModule("taxa"), "/data/taxa/", array("taxon" => "Gryllotalpa vineae", "output" => "nakedJSON")),
  array(loadModule("taxa"), "/data/taxa/", array("id" => "1", "source" => "bio.acousti.ca",
    "output" => "nakedJSON")),
  array(loadModule("vernacularnames"), "/data/vernacularnames/",
    array("vernacularName" => "mole cricket", "output" => "nakedJSON")),
  array(loadModule("links"), "/data/links/", array("subject_type" => "vernacularnames",
    "subject_source" => "bio.acousti.ca", "subject_id" => "1", "object_type" => "taxa",
    "predicate" => "http://purl.obolibrary.org/obo/IAO_0000219", "output" => "nakedJSON")),
  array(loadModule("data")["endpoints"]["fetch_data_counts"], "/standalone/data/fetch_data_counts/",
    array("output" => "nakedJSON")),
  array(loadModule("data")["endpoints"]["list_hours"], "/standalone/data/list_hours/",
    array("output" => "nakedJSON")),
  array(loadModule("analysis")["endpoints"]["fetch_analysis_counts"],
    "/standalone/analysis/fetch_analysis_counts/", array("output" => "nakedJSON")),
  array($module_info, "/standalone/modules/module_info/", array("module" => "recordings")),
  array(loadModule("phymoji")["endpoints"]["get_taxon"], "/standalone/phymoji/get_taxon/",
    array("emoji" => "cricket")),
  array(loadModule("pythia")["endpoints"]["process"], "/standalone/pythia/process/",
    array("query" => "Gryllotalpa"))
);
foreach ($site as $asked) {
  $problem = request($asked[0], $asked[1], $asked[2]);
  check($problem === NULL, "audioblast.org asks ".$asked[1]." and is refused: ".$problem);
}

print("unknown-params: all checks passed\n");
