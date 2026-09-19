<?php
// No settings or real database are loaded. Run from the repository root.
require 'core/rdf.php';
require 'core/modules.php';
require 'core/record.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}
$module = loadModule('references');
$ref = array('source' => 'fixture', 'id' => 'book/a #1', 'type' => 'article',
  'title' => "A \"quoted\" title\nwith Unicode: Ã©", 'year' => '1922', 'month' => '',
  'author' => 'Darwin, Charles; {Natural History Museum}', 'editor' => 'Editor, One; Editor, Two',
  'journal' => 'Test journal', 'issn' => '1234-5678', 'journal_abbreviation' => 'Test J',
  'doi' => '10.1234/test#part', 'pmid' => '12345', 'keywords' => 'song; call',
  'attachments' => 'https://example.org/a.pdf; https://example.org/b.pdf',
  'info_url' => 'https://example.org/catalogue/1', 'url' => 'https://example.org/paper');
$uri = rdfRecordURI($module, $ref['source'], $ref['id']);
check($uri === 'https://api.audioblast.org/reference/fixture/book/a%20%231', 'URI encoding');
$nodes = rdfNodes($module, array($ref));
check($nodes[0]['dwc:referenceType'] === 'journalArticle', 'Darwin Core type');
check($nodes[0]['dcterms:issued']['@type'] === 'xsd:gYear', 'Year precision');
check(count($nodes[0]['dcterms:relation']) === 2, 'Attachments');
check(count($nodes[0]['dcterms:creator']) === 2, 'Distinct authors');
check(strpos(rdfJSONLD($nodes), '{Natural History Museum}') !== FALSE, 'Corporate author retained');
check(count($nodes[0]['rdfs:seeAlso']) === 6, 'Catalogue, incoming/outgoing links, identifiers');
$sparse = array('source' => 'fixture', 'id' => 'sparse', 'type' => 'unusual', 'year' => 'in press',
  'editor' => NULL, 'journal' => NULL);
$sparseNodes = rdfNodes($module, array($sparse));
check($sparseNodes[0]['dcterms:issued'] === 'in press', 'Unusual year retained');
check(count($sparseNodes) === 1, 'No empty related descriptions');
$nodes = array_merge($nodes, $sparseNodes);
$link = array('source' => 'curator', 'id' => 'link1',
  'subject_type' => 'references', 'subject_source' => 'fixture', 'subject_id' => $ref['id'],
  'predicate' => 'http://purl.obolibrary.org/obo/IAO_0000136',
  'object_type' => 'taxa', 'object_source' => 'other-source', 'object_id' => '42',
  'qualifier' => 'https://vocab.audioblast.org/cv/referenceContent#Oscillogram', 'remarks' => 'p. 12');
// Existing module callbacks remain compatible with the expanded graph writer.
foreach (array('recordings', 'traits') as $existing) {
  $definition = loadModule($existing);
  $row = array_fill_keys(array_keys($definition['params']), NULL);
  $row['source'] = 'fixture'; $row['id'] = 'existing';
  $nodes = array_merge($nodes, rdfNodes($definition, array($row)));
}
$links = loadModule('links');
$linkNodes = rdfNodes($links, array($link));
check($linkNodes[0]['rdf:subject']['@id'] === $uri, 'Shared reference identity');
check($linkNodes[0]['rdf:object']['@id'] === 'https://api.audioblast.org/taxon/other-source/42', 'Cross-source target');
check($linkNodes[0]['dwc:relationshipAccordingTo'] === 'curator', 'Asserting source');
check($linkNodes[0]['dwc:relationshipRemarks'] === 'p. 12', 'Relationship remarks');
check($linkNodes[0]['dcterms:type']['@id'] === $link['qualifier'], 'Qualifier IRI');
check(isset($linkNodes[1][$link['predicate']]), 'Direct assertion');
$nodes = array_merge($nodes, $linkNodes);
$link['id'] = 'link2'; $link['remarks'] = 'p. 14';
$nodes = array_merge($nodes, rdfNodes($links, array($link)));
$link['object_type'] = 'iri'; $link['object_id'] = 'https://example.org/topic';
check(links_rdf_endpoint($link, 'object')['@id'] === $link['object_id'], 'External target');
$link['object_type'] = '../unsupported';
check(count(rdfNodes($links, array($link))) === 1, 'Unknown type retains metadata');
$taxa = loadModule('taxa');
$nodes = array_merge($nodes, rdfNodes($taxa, array(array('source' => 'other-source', 'id' => '42',
  'taxon' => 'Example species', 'rank' => 'species', 'genus' => 'Example'))));
check(recordModule('/reference/fixture/book/a%20%231')['mname'] === 'references', 'Reference route');
check(recordModule('/taxon/other-source/42')['mname'] === 'taxa', 'Taxon route');
check(recordModule('/link/curator/link1')['mname'] === 'links', 'Link route');
check(rdfNegotiate('application/ld+json') === 'JSON-LD', 'JSON-LD negotiation');
check(rdfNegotiate('text/turtle') === 'Turtle', 'Turtle negotiation');
check(rdfNegotiate('text/turtle;q=0, application/json') === NULL, 'JSON preference');
check(pageURI('/data/references/?source=fixture&page=1', 2) === '/data/references/?source=fixture&page=2', 'Pagination');

// Test the real record handler with a mock prepared lookup.
function SELECTclause($module, $field, $mode, $format) {return('SELECT * FROM '.$module['table']);}
class FixtureResult {
  private $row;
  function __construct($row) {$this->row = $row;}
  function fetch_assoc() {return($this->row);}
}
class FixtureStatement {
  private $row;
  function __construct($row) {$this->row = $row;}
  function bind_param($types, &$source, &$id) {
    check($types === 'ss' && $source === 'fixture' && $id === 'book/a #1', 'Bound decoded IDs');
  }
  function execute() {return(TRUE);}
  function get_result() {return(new FixtureResult($this->row));}
}
class FixtureDB {
  private $row;
  function __construct($row) {$this->row = $row;}
  function prepare($sql) {
    check(strpos($sql, 'WHERE `source` = ? AND `id` = ? LIMIT 1') !== FALSE, 'Exact prepared lookup');
    return(new FixtureStatement($this->row));
  }
}
$_SERVER['REQUEST_URI'] = '/reference/fixture/book/a%20%231';
$_SERVER['HTTP_ACCEPT'] = 'text/turtle';
$_GET = array('output' => 'JSON');
ob_start(); recordAPI(new FixtureDB($ref)); $json = json_decode(ob_get_clean(), TRUE);
check($json['data'][0] === $ref, 'Explicit JSON precedence and compatibility');
$_GET = array();
ob_start(); recordAPI(new FixtureDB($ref)); $turtle = ob_get_clean();
check(strpos($turtle, '@prefix') === 0, 'Accept negotiation in handler');
ob_start(); recordAPI(new FixtureDB(NULL)); ob_end_clean();
check(http_response_code() === 404, 'Unknown record');
http_response_code(200);
$canonical = $ref; $canonical['source'] = 'Fixture';
ob_start(); recordAPI(new FixtureDB($canonical)); $body = ob_get_clean();
check(http_response_code() === 301 && $body === '', 'Canonical case redirect');
http_response_code(200);
if (isset($argv[1])) {
  file_put_contents($argv[1].'/linked-data.jsonld', rdfJSONLD($nodes));
  file_put_contents($argv[1].'/linked-data.ttl', rdfTurtle($nodes));
}
echo "Linked-data fixture and routing checks passed.\n";
