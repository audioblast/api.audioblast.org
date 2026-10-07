<?php
// The regions of interest annotations mark (modules/annomate). No settings or
// real database are loaded. Run from the repository root.
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

$annomate = loadModule("annomate");

/*
A region is bounded in frequency, in Hz, as it is in time, in seconds, and in
format=ac the bounds are named by the Audiovisual Core terms for them
*/
$select = SELECTclause($annomate, NULL, "table", "internal");
check(strpos($select, "`freq_low` as `freq_low`") !== FALSE
  && strpos($select, "`freq_high` as `freq_high`") !== FALSE, "A region's frequency bounds are served");
$select = SELECTclause($annomate, NULL, "table", "ac");
check(strpos($select, "`time_start` as `ac:startTime`") !== FALSE, "format=ac names the times by their terms");
check(strpos($select, "`freq_low` as `ac:freqLow`") !== FALSE
  && strpos($select, "`freq_high` as `ac:freqHigh`") !== FALSE, "and the frequencies by theirs");

/*
A region bounded from 0 Hz holds that frequency, so a filter of 0 is a
condition rather than no filter
*/
check(request($annomate, "/data/annomate/", array("freq_low" => "0")) === NULL,
  "A region's lowest frequency is a filter");
$where = WHEREclause(generateParams($annomate, array("freq_low" => "0", "freq_high" => "7309.05")));
check(strpos($where, "`freq_low` = '0'") !== FALSE, "0 Hz is a bound to match");
check(strpos($where, "`freq_high` = '7309.05'") !== FALSE, "as is the highest frequency");

print("annomate: all checks passed\n");
