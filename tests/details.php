<?php
// The details a record has, whichever source gave them (modules/details). No
// settings or real database are loaded. Run from the repository root.
require 'core/modules.php';
require 'core/input.php';
require 'core/query.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}

//As in many-values.php
function request($module, $uri, $inputs=array()) {
  $parts = explode("/", $uri);
  return(checkParams($module, array_merge(array("path" => substr($uri, 1)), $inputs), $parts[3] ?? NULL));
}

$details = loadModule("details");

/*
A detail is served with the source that gave it and the source of the record it
belongs to, which differ where a source gives details of another's records: a
corpus giving details of the xeno-canto recordings it marked regions of, say.
*/
$select = SELECTclause($details, NULL, "table", "internal");
check(strpos($select, "`source` as `source`") !== FALSE, "A detail says which source gave it");
check(strpos($select, "`record_source` as `record_source`") !== FALSE,
  "and the source of the record it belongs to");

/*
A record's details are those of its source, type and id, whichever source gave
them: xeno-canto's own, and a corpus's of xeno-canto's recording
*/
check(request($details, "/data/details/",
  array("record_source" => "xeno-canto", "type" => "recordings", "id" => "280667")) === NULL,
  "A record's details are asked for by its source, type and id");
$where = WHEREclause(generateParams($details,
  array("record_source" => "xeno-canto", "type" => "recordings", "id" => "280667")));
check(strpos($where, "`record_source` = 'xeno-canto'") !== FALSE, "The record's source is matched exactly");
check(strpos($where, "`type` = 'recordings'") !== FALSE && strpos($where, "`id` = '280667'") !== FALSE,
  "beside its type and id");
check(substr_count($where, "AND") === 2, "as conditions that all have to hold");
check(strpos($where, "`source`") === FALSE, "whichever source gave them");

//What a source gave is still asked for by source, of whatever records
$where = WHEREclause(generateParams($details, array("source" => "jeantet-dufourq-2023")));
check(strpos($where, "`source` = 'jeantet-dufourq-2023'") !== FALSE,
  "The giving source is matched exactly, as every source is");
check(strpos($where, "`record_source`") === FALSE, "and says nothing of whose records they are");

check(in_array("record_source", listFilterParams($details)), "record_source is a filter");
check(!empty($details["params"]["record_source"]["autocomplete"]), "whose values can be suggested");

print("details: all checks passed\n");
