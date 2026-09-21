<?php
// No settings or real database are loaded. Run from the repository root.
require 'core/modules.php';
require 'core/input.php';
require 'core/query.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}

/*
A request as moduleAPI() meets it, as in unknown-params.php: the rewrite adds a
`path` parameter of its own, and the endpoint is the fourth part of the URI.
*/
function request($module, $uri, $inputs=array()) {
  $parts = explode("/", $uri);
  return(checkParams($module, array_merge(array("path" => substr($uri, 1)), $inputs), $parts[3] ?? NULL));
}

$images = loadModule("images");
$links = loadModule("links");
$recordings = loadModule("recordings");
$recordingstaxa = loadModule("recordingstaxa");
$taxa = loadModule("taxa");

/*
What a module says about its filters. An unrecognised parameter is refused, so
a client cannot find out by trying: every filter has to say which it is, and a
missing answer must never be read as "no".
*/
check($images["params"]["id"]["multiple"] === TRUE, "A filter that takes several values says so");
check($images["params"]["title"]["multiple"] === FALSE, "One that does not says that");
check(!array_key_exists("multiple", $recordings["params"]["post_date"]),
  "A column that cannot be filtered on has nothing to say about it");
check(listMultiParams($images) === array("id"), "An image is named by its id, several at a time");
check(in_array("subject_id", listMultiParams($links)), "So is the subject of a link");
check(!in_array("predicate", listMultiParams($links)), "A predicate is not, for now");
check(!in_array("title", listMultiParams($images)), "Nor is free text, which can hold a comma");

// A module that asks for several values on a filter that is matched by what it
// contains, or by a range, is not given them: there is no reading of several
// values for those that is not a guess.
foreach (loadModules() as $name => $module) {
  foreach (($module["params"] ?? array()) as $pname => $pinfo) {
    if (empty($pinfo["multiple"])) {continue;}
    check(($pinfo["op"] ?? "none") === "=",
      $name."'s ".$pname." takes several values but is not matched exactly");
  }
}

// How the values of one filter are written
check(filterValues("12,15,19") === array("12", "15", "19"), "Values are written together");
check(filterValues("12, 15") === array("12", "15"), "Spaces around a value are not part of it");
check(filterValues("12") === array("12"), "One value is one value");
check(filterValues(array("a,b", "c")) === array("a,b", "c"), "A list keeps a comma in a value");

// What a module takes
check(request($images, "/data/images/", array("id" => "12,15,19")) === NULL, "Several ids are taken");
check(request($images, "/data/images/", array("id" => array("a,b", "c"))) === NULL,
  "As a list, so that a value can hold a comma");
check(request($images, "/data/images/", array("id" => "")) === NULL,
  "An empty filter is no filter, as it always was");
check(request($images, "/data/images/", array("title" => "Smith, 1802")) === NULL,
  "A comma in a filter that takes one value is part of the value");

$problem = request($images, "/data/images/", array("title" => array("a", "b")));
check($problem !== NULL, "A list is refused where the filter takes one value");
check(strpos($problem, "`title` takes one value") !== FALSE, "The offending parameter is named");
check(strpos($problem, "These take several: id.") !== FALSE, "and the ones that would have taken it");

$problem = request($images, "/data/images/", array("id" => implode(",", range(1, 101))));
check($problem !== NULL, "More values than a filter may be given is refused");
check(strpos($problem, "101 values") !== FALSE && strpos($problem, "at most 100") !== FALSE,
  "saying how many were given and how many may be");
check(request($images, "/data/images/", array("id" => implode(",", range(1, 100)))) === NULL,
  "A hundred is taken");

$problem = request($images, "/data/images/", array("id" => "12,,19"));
check($problem !== NULL, "A value that is nothing is refused rather than dropped");
check(strpos($problem, "empty value") !== FALSE, "for the right reason");
check(request($images, "/data/images/", array("id" => "12,")) !== NULL, "A trailing comma names one");

/*
The query string itself. PHP keeps only the last of a parameter given more than
once, so a request naming two records was answered about one of them, with
nothing in the reply to say the other had been dropped.
*/
$problem = checkQueryString($images, "path=data/images/&id=12&id=15");
check($problem !== NULL, "A parameter given twice is refused");
check(strpos($problem, "`id=a,b`") !== FALSE, "and is told how to ask for both");
check(strpos(checkQueryString($images, "title=a&title=b"), "takes one value") !== FALSE,
  "A filter that takes one value says so instead");
check(checkQueryString($images, "id%5B%5D=12&id%5B%5D=15") === NULL,
  "A list repeats the name on purpose");
check(checkQueryString($images, "id=12&kind=Photograph") === NULL,
  "Two parameters are not one parameter twice");
check(checkQueryString($images, "id=12&id%5B%5D=15") !== NULL,
  "The two forms of one parameter are still that parameter twice");
check(checkQueryString($recordingstaxa,
  "filter%5B0%5D%5Bfield%5D=genus&filter%5B1%5D%5Bfield%5D=species") === NULL,
  "Tabulator names a field per filter, which is not a repeat");
check(checkQueryString($images, "") === NULL, "A request with no query string");
check(checkQueryString($images, "id=12") === NULL, "A request with one of each");

// The SQL a request comes to
$where = WHEREclause(generateParams($images, array("id" => array("12", "15", "19"))));
check(strpos($where, "`id` IN ('12', '15', '19')") !== FALSE,
  "Several values are one condition on one column, which its index still answers");
check(substr_count($where, "IN (") === 1, "and only one");
check(strpos(WHEREclause(generateParams($images, array("id" => "12"))), "`id` = '12'") !== FALSE,
  "One value is matched as it always was");

$where = WHEREclause(generateParams($links,
  array("subject_type" => "taxa", "subject_id" => array("1", "2"))));
check(strpos($where, "`subject_type` = 'taxa'") !== FALSE, "A filter of one value beside one of several");
check(strpos($where, "`subject_id` IN ('1', '2')") !== FALSE, "and both are applied");
check(substr_count($where, "AND") === 1, "as conditions that both have to hold");
check(WHEREclause(generateParams($images, array("id" => array()))) === "",
  "No values is no condition, as an empty filter is");

/*
The requests audioblast.org makes are unchanged: none of them gives a filter
more than one value, and a comma in one of its searches is still part of what
it is searching for.
*/
check(request($recordingstaxa, "/data/recordingstaxa/",
  array("species" => "Gryllotalpa gryllotalpa", "page_size" => "1")) === NULL,
  "A species name is one value");
check(request($taxa, "/data/taxa/", array("id" => "1", "source" => "bio.acousti.ca")) === NULL,
  "A taxon is asked for by source and id");
check(request($taxa, "/data/taxa/", array("id" => "1,2,3", "source" => "bio.acousti.ca")) === NULL,
  "and three taxa in the same request");

print("many-values: all checks passed\n");
