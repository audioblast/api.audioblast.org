<?php
// The analyses audioBLAST! makes of recordings, as list_analysis names them
// (modules/analysis). No settings or real database are loaded. Run from the
// repository root.
require 'core/modules.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}

$analysis = loadModule("analysis");

/*
moduleAPI() gives the caller of an endpoint that returns "data" only what its
callback returns under "data". analysis_list() returned the names as a bare
list, so /standalone/analysis/list_analysis/ answered with no list at all.
*/
$endpoint = $analysis["endpoints"]["list_analysis"];
check($endpoint["callback"] === "analysis_list" && $endpoint["returns"] === "data",
  "list_analysis is answered with what analysis_list() returns under data");
$list = analysis_list(array());
check(isset($list["data"]), "The analyses are named under data");
check($list["data"] === array_values($list["data"]), "as a list, which JSON gives as an array");
check(in_array("aci", $list["data"]), "aci among them");
check($list["data"] === array_keys(loadModules("analysis")), "Every analysis module is named, and nothing else");

//The waveform peaks are the recording's alone (see recordings.php)
check(!in_array("audiowaveform", $list["data"]), "audiowaveform is no longer an analysis");

print("analysis: all checks passed\n");
