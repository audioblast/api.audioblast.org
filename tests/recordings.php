<?php
// Recordings, and what audioBLAST! made from their files (modules/recordings).
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

//As in many-values.php
function request($module, $uri, $inputs=array()) {
  $parts = explode("/", $uri);
  return(checkParams($module, array_merge(array("path" => substr($uri, 1)), $inputs), $parts[3] ?? NULL));
}

$recordings = loadModule("recordings");

/*
The waveform peaks and spectrogram tiles audioBLAST! made from a recording's
file are held with what it measured of the file, and served with the
recording. Every field is selected as a column of v-recordings, so each has to
be a column of the view before it is served.
*/
$select = SELECTclause($recordings, NULL, "table", "internal");
check(strpos($select, "`peaks_url` as `peaks_url`") !== FALSE, "A recording's waveform peaks are served");
check(strpos($select, "`spectrogram_url` as `spectrogram_url`") !== FALSE, "and so are its spectrogram tiles");
check(strpos($select, " FROM `audioblast`.`v-recordings`") !== FALSE, "from the view that joins on what was measured");

//They are addresses to fetch, not values to look for
foreach (array("peaks_url", "spectrogram_url") as $field) {
  $problem = request($recordings, "/data/recordings/", array($field => "https://example.org/rec1"));
  check(strpos((string)$problem, "cannot be filtered on") !== FALSE, $field." is not a filter");
}

/*
The peaks were also served by a module of their own, /analysis/audiowaveform/,
from a table of their own. They are now the recording's alone. moduleAPI()
refuses, with a 400, a module that listModules() does not list.
*/
check(!in_array("audiowaveform", listModules()), "There is no audiowaveform module to ask");
check(loadModule("audiowaveform") === NULL, "nor one to load");

print("recordings: all checks passed\n");
