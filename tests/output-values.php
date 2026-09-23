<?php
// No settings or real database are loaded. Run from the repository root.
require 'core/modules.php';
require 'core/rdf.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}

$taxa = loadModule("taxa");
$traits = loadModule("traits");

// The faults this guards against: `turtle` and `ttl` were both answered in
// JSON under an HTTP 200
check(outputValue($taxa, "turtle") === "Turtle", "An output is read regardless of case");
check(outputValue($taxa, "json-ld") === "JSON-LD", "JSON-LD is read regardless of case");
check(outputValue($taxa, "NAKEDJSON") === "nakedJSON", "The output is given back as the module spells it");
check(outputValue($taxa, "ttl") === NULL, "An output the module does not have is refused");
check(outputValue($taxa, "") === NULL, "An empty output is refused");
check(outputValue($taxa, array("Turtle")) === NULL, "A list is not an output");
$problem = outputProblem($taxa, "ttl");
check(strpos($problem, "`ttl`") !== FALSE, "The output given is named");
check(strpos($problem, "Turtle") !== FALSE, "The outputs taken are listed");

// What every module is answered with, whatever its own list said
check(outputValue($taxa, "tabulator") === "tabulator", "taxa takes tabulator, which it left out");
check(outputValue($traits, "nakedJSON") === "nakedJSON", "traits takes nakedJSON, which it left out");
check(in_array("tabulator", $taxa["params"]["output"]["allowed"]), "The documentation lists what is taken");

// RDF only where a module describes its records in RDF
$details = loadModule("details");
check(!isset($details["rdf"]), "details has no RDF, as this test assumes");
check(outputValue($details, "Turtle") === NULL, "A module without RDF refuses Turtle");
check(outputValue($details, "JSON") === "JSON", "A module without RDF takes JSON");

// An endpoint is answered by the same code, so takes the same outputs
$counts = loadModule("data")["endpoints"]["fetch_data_counts"];
check(outputValue($counts, "nakedjson") === "nakedJSON", "An endpoint takes nakedJSON");

// An embed makes its own sense of `output`, and takes only what it declares
loadModule("recordings");
$embed = call_user_func("recordings_embed_info")["recording"];
check(outputValue($embed, "HTML5", TRUE) === "html5", "An embed's output is read regardless of case");
check(outputValue($embed, "JSON", TRUE) === NULL, "An embed refuses an output it does not declare");

// `format` likewise: `AC` was answered with the internal columns
$recordings = loadModule("recordings");
check(representationValue($recordings, "format", "AC") === "ac", "A format is read regardless of case");
check(representationValue($recordings, "format", "Internal") === "internal", "internal is read regardless of case");
check(representationValue($recordings, "format", "dwc") === NULL, "A format the module does not have is refused");
$problem = representationProblem($recordings, "format", "dwc");
check(strpos($problem, "Parameter `format` does not take `dwc`.") === 0, "The format given is named");
check(strpos($problem, "internal, ac") !== FALSE, "The formats taken are listed");
check(representationValue(loadModule("images"), "format", "ac") === "ac",
  "A module given format for its Audiovisual Core terms takes it");

print("output-values: all checks passed\n");
